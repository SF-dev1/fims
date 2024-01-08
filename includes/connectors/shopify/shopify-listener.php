<?php

/**
 * 
 */
if (!class_exists('shopify_listner')) {
	class shopify_listner extends shopify
	{
		private $sp_account = "";
		private $sandbox = false;

		function __construct($sp_account, $sandbox = false)
		{
			$this->sp_account = $sp_account;
			$this->sandbox = $sandbox;

			parent::__construct($this->sp_account, $this->sandbox);
		}

		public function verify_shopify_webhook($data, $hmac_header)
		{
			$calculated_hmac = base64_encode(hash_hmac('sha256', $data, $this->sp_account->account_secret_hash, true));
			return hash_equals($hmac_header, $calculated_hmac);
		}

		public function verify_logistic_webhook($data, $hmac_header)
		{
			// $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
			// return hash_equals($hmac_header, $calculated_hmac);
		}

		public function verify_payment_webhook($data, $hmac_header)
		{
			$calculated_hmac = hash_hmac('sha256', $data, $this->sp_account->account_secret_hash);
			var_dump($calculated_hmac);
			var_dump($hmac_header);
			return hash_equals($hmac_header, $calculated_hmac);
		}

		public function process_notification($event, $data, $logistic = "")
		{
			global $db, $log;

			list($event, $subevent) = explode('/', $event);
			switch ($event) {
				case 'orders':
					// switch ($subevent) {
					// 	case 'create':
					// 			$this->insert_order(json_decode($data));
					// 		break;

					// 	case 'updated':
					$this->update_order(json_decode($data));
					// 		break;
					// }
					break;

				case 'logistic':
					$data = json_decode($data);
					if ($logistic == "shyplite") {
						$shopify_orderId = $data->items[0]->order->orderID;
						$shipment_status = str_replace('tracking.', '', $data->event);
						if ($shipment_status == "pickedup")
							$shipment_status = "picked_up";
						if ($shipment_status == "outfordelivery")
							$shipment_status = "out_for_delivery";
						if ($shipment_status == "returnOrReverse") {
							$shipment_status = "rto_initiated";
							$data->current_status = "RTO INITIATED";
							$data->order_id = $shopify_orderId;
							$data->lpOrderId = $data->items[0]->order->id;
						}
					} else {
						$awb = $data->awb;
						$shopify_orderId = $data->order_id;
						$lp_orderid = $data->sr_order_id;
						$shipment_status = strtolower(str_replace(' ', '_', $data->shipment_status));
						if ($data->is_return)
							$shipment_status = 'return_' . $shipment_status;
						// if ($data->is_return && strpos('return_', $shipment_status) !== TRUE )
						// 	$shipment_status = 'return_'.$shipment_status;
					}

					$is_rma = (substr($data->order_id, 0, 4) === "RMA-" ? true : false);

					// ADD $data SYNC LAYER FOR EACH LOGISTIC
					switch ($shipment_status) {
						case 'picked_up':
						case 'shipped':
							$update_data = array('shipmentStatus' => $shipment_status);
							if ($shipment_status == "picked_up") {
								$update_data['pickedUpDate'] = $db->now();
								if ($logistic == "shyplite") {
									$update_data['forwardShippingFee'] = $data->items[0]->shipment->estimatedamount;
								}
							}
							// $db->where('status', 'cancelled', '!=');
							// $db->where('orderNumberFormated', $shopify_orderId);
							$db->where('trackingId', $awb);
							if ($db->update(TBL_FULFILLMENT, $update_data))
								$log->write("Successfully updated Shipment Status to " . $shipment_status . " for order " . $shopify_orderId, 'shopify-shipment-status');
							else
								$log->write("Unable to update Shipment Status to " . $shipment_status . " for order " . $shopify_orderId, 'shopify-shipment-status');

							break;

						case 'in_transit':
						case 'out_for_delivery':
						case 'delivered':
						case 'undelivered':
							$update_details = array('shipmentStatus' => $shipment_status);

							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
							$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);

							// $db->where('orderNumberFormated', $shopify_orderId);
							$order_details = $db->getOne(TBL_SP_ORDERS . ' o', 'o.orderId, o.orderItemId, pickedUpDate');
							if (is_null($order_details)) {
								$log->write("ERROR: Order Status not found for order  " . $shopify_orderId . "\nCurrent Status: " . $shipment_status, 'shopify-order-status');
								header("HTTP/1.1 400 Bad Request");
								return;
							} else if (is_null($order_details['pickedUpDate']) && $logistic == "shiprocket") {
								$scans = $data->scans;
								foreach ($scans as $scan) {
									if ($scan->{'sr-status-label'} == 'PICKED UP' || $scan->{'sr-status-label'} == 'SHIPPED') {
										$update_details['pickedUpDate'] = $scan->date;
										continue;
									}
								}
							}

							if ($shipment_status == "delivered")
								$update_details['deliveredDate'] = $db->now();

							$db->where('trackingId', $awb);
							if ($db->update(TBL_FULFILLMENT, $update_details))
								$log->write("Successfully updated Shipment Status to " . $shipment_status . " for order " . $shopify_orderId, 'shopify-shipment-status');
							else
								$log->write("Unable to update Shipment Status to " . $shipment_status . " for order " . $shopify_orderId, 'shopify-shipment-status');

							$sp = new shopify_dashboard($this->sp_account);
							$current_status = $sp->get_current_order_status($order_details['orderId']);
							if ($current_status == $shipment_status) {
								$log->write("\nAWB: " . $awb . "\nOrder ID: " . $shopify_orderId . "\nShipment Status: " . $shipment_status . "\nCurrent Status: " . $current_status . "\nUpdate Status: No Update", 'shopify-order-status');
								return;
							}

							if ($shipment_status == "undelivered") {
								// $scan = $data->scans[0];
								// if (strpos(strtolower($data->courier_name), 'delivery') !== FALSE)
								// 	$scan = $data->scans[count($data->scans)-1];

								$shipment_status = 'attempted_delivery';
								// $comment = $scan->activity;

								// $comment_data = array(
								// 	'comment' => $db->escape($comment),
								// 	'orderId' => $order_details['orderId'],
								// 	'commentFor' => 'undelivered_shipment',
								// 	'userId' => 0,
								// );
								// $db->insert(TBL_SP_COMMENTS, $comment_data);
							}

							if ($shipment_status == "delivered") {
								// 	CANCEL RETURN IF GENERATED
								$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
								$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
								$db->where('(f.shipmentStatus != ? OR f.shipmentStatus != ? OR f.shipmentStatus != ?)', array('return_received', 'return_claimed', 'return_completed'));
								$db->where('r.r_subReason', 'undelivered');
								$db->where('r.r_source', 'courier_return');
								$db->where('r.orderItemId', $order_details['orderItemId']); // USE IN AS THERE CAN BE MULTIPLE RETURNS FOR SAME ORDER
								if ($returnId = $db->getValue(TBL_SP_RETURNS . ' r', 'returnId')) {
									$update_details = array(
										'r_status' => 'cancelled',
										'r_reason' => 'shipment_delivered',
										'r_subReason' => 'shipment_delivered',
										// 'r_shipmentStatus' => 'cancelled',
										'r_cancellationDate' => $db->now()
									);
									$db->where('returnId', $returnId);
									$db->update(TBL_SP_RETURNS, $update_details);
								}

								// generate_incoice($order_details['orderId']);
								// // $update_details['deliveredDate'] = isset($data->current_timestamp) ? date('Y-m-d H:i:s', strtotime(preg_replace('/ /', '-', $data->current_timestamp, 2))) : date('Y-m-d H:i:s', time());
							}

							$update = $sp->update_fulfilment_status_sp($order_details['orderId'], $shipment_status);
							$log->write("\nAWB: " . $awb . "\nOrder ID: " . $shopify_orderId . "\nShipment Status: " . $shipment_status . "\nCurrent Status: " . $current_status . "\nUpdate Status: " . json_encode($update), 'shopify-order-status');
							break;

						case 'rto_initiated':
							$db->where('fulfillmentType', 'forward');
							$db->where('trackingId', $awb);
							$order_id = $db->getValue(TBL_FULFILLMENT, 'orderId');
							$sp = new shopify_dashboard($this->sp_account);
							$sp->update_fulfilment_status_sp($order_id, 'failure');
							$this->insert_return($data, $logistic);
							// $this->update_return_status($lp_orderid, $shipment_status);
							break;

						case 'rto_in_transit':
						case 'return_in_transit':
						case 'return_out_for_pickup':
						case 'return_pickup_exception':
						case 'rto_ofd':
						case 'return_out_for_delivery':
							if ($is_rma) {
								$status = str_replace(array('rto_', 'return_'), '', $shipment_status);
								// $db->join(TBL_SP_ORDERS.' o', 'o.orderItemId=r.orderItemId');
								$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=r.rmaNumber');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'rma_return');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
								$return_details = $db->get(TBL_RMA . ' r', NULL, 'r.rmaId, r.status, f.fulfillmentId');
								$rmaId = NULL;
								foreach ($return_details as $return_detail) {
									$rmaId[] = $return_detail['rmaId'];
									$shipment_status = $return_detail['status'];
									$fulfillment_id = $return_detail['fulfillmentId'];
								}
								// $shipment_status = $return_details['r_status'];
								if ($shipment_status) {
									$msg = 'Return Shipment Status ' . $shipment_status . ' and update status ' . $status . ' hierarchy mismatch for ' . $awb;
									if (($status == $shipment_status) ||
										($status == "out_for_pickup" && ($shipment_status == "start" || $shipment_status == "pending_pickup" || $shipment_status == "awb_assigned" || $shipment_status == "pickup_exception")) ||
										($status == "pickup_exception" && $shipment_status == "out_for_pickup") ||
										($status == "picked_up" && ($shipment_status == "out_for_pickup" || $shipment_status == "pickup_exception")) ||
										($status == "in_transit" && ($shipment_status == "start" || $shipment_status == "pending_pickup" || $shipment_status == "awb_assigned" || $shipment_status == "picked_up")) ||
										($status == "out_for_delivery" && ($shipment_status == "start" || $shipment_status == "in_transit")) ||
										($status == "delivered" && ($shipment_status == "start" || $shipment_status == "in_transit" || $shipment_status == "out_for_delivery"))
									) {

										$details = array('shipmentStatus' => $status);
										if (!is_null($rmaId)) {
											$db->where('rmaId', $rmaId, 'IN');
											if ($db->update(TBL_RMA, array('status' => $status))) {
												$db->where('fulfillmentId', $fulfillment_id);
												if ($db->update(TBL_FULFILLMENT, $details))
													$msg = 'Successfully updated return and shipment status ' . $awb;
												else
													$msg = 'Error updating shipment status ' . $db->getLastError();
											} else {
												$msg = 'Error updating return ' . $db->getLastError();
											}
										} else {
											$msg = 'Return not found for ' . $awb;
										}
									}
								} else {
									$msg = 'Return Shipments not found for ' . $awb;
								}
								$log->write($msg, 'return-sylvi');
								echo $msg;
							} else {
								$this->update_return_status($awb, $shipment_status);
							}
							break;

						case 'return_picked_up':
							if ($is_rma) {
								$status = str_replace(array('rto_', 'return_'), '', $shipment_status);
								// $db->join(TBL_SP_ORDERS.' o', 'o.orderItemId=r.orderItemId');
								$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=r.rmaNumber');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'rma_return');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
								$return_details = $db->get(TBL_RMA . ' r', NULL, 'r.rmaId, r.status, f.fulfillmentId');
								$rmaId = NULL;
								foreach ($return_details as $return_detail) {
									$rmaId[] = $return_detail['rmaId'];
									$shipment_status = $return_detail['status'];
									$fulfillment_id = $return_detail['fulfillmentId'];
								}
								if ($shipment_status) {
									$msg = 'Return Shipment Status ' . $shipment_status . ' and update status ' . $status . ' hierarchy mismatch for ' . $awb;
									if ($status == "picked_up" && ($shipment_status == "out_for_pickup" || $shipment_status == "pickup_exception")) {
										$details = array('shipmentStatus' => $status, 'pickedUpDate' => $data->scans[0]->date);
										if (!is_null($rmaId)) {
											$db->where('rmaId', $rmaId, 'IN');
											if ($db->update(TBL_RMA, array('status' => $status))) {
												$db->where('fulfillmentId', $fulfillment_id);
												if ($db->update(TBL_FULFILLMENT, $details))
													$msg = 'Successfully updated return and shipment status ' . $awb;
												else
													$msg = 'Error updating shipment status ' . $db->getLastError();
											} else {
												$msg = 'Error updating return ' . $db->getLastError();
											}
										} else {
											$msg = 'Return not found for ' . $awb;
										}
									}
								} else {
									$msg = 'Return Shipments not found for ' . $awb;
								}
								$log->write($msg, 'rma-shipment-sylvi');
								echo $msg;
							} else {
								$additional_details['pickedUpDate'] = $data->scans[0]->date;
								$this->update_return_status($awb, 'picked_up', $additional_details);
							}
							break;

						case 'rto_delivered':
						case 'return_delivered':
							$additional_details['fulfillmentStatus'] = 'completed';
							$additional_details['deliveredDate'] = $data->scans[0]->date;
							if ($is_rma) {
								$status = str_replace(array('rto_', 'return_'), '', $shipment_status);
								// $db->join(TBL_SP_ORDERS.' o', 'o.orderItemId=r.orderItemId');
								$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=r.rmaNumber');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'rma_return');
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);
								$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
								$return_details = $db->get(TBL_RMA . ' r', NULL, 'r.rmaId, r.status, f.fulfillmentId');
								$rmaId = NULL;
								foreach ($return_details as $return_detail) {
									$rmaId[] = $return_detail['rmaId'];
									$shipment_status = $return_detail['status'];
									$fulfillment_id = $return_detail['fulfillmentId'];
								}
								// $shipment_status = $return_details['r_status'];
								if ($shipment_status) {
									$msg = 'Return Shipment Status ' . $shipment_status . ' and update status ' . $status . ' hierarchy mismatch for ' . $awb;
									if (($status == $shipment_status) ||
										($status == "delivered" && ($shipment_status == "start" || $shipment_status == "in_transit" || $shipment_status == "out_for_delivery"))
									) {
										$additional_details['shipmentStatus'] = $status;
										if (!is_null($rmaId)) {
											$db->where('rmaId', $rmaId, 'IN');
											if ($db->update(TBL_RMA, array('status' => $status))) {
												$db->where('fulfillmentId', $fulfillment_id);
												if ($db->update(TBL_FULFILLMENT, $additional_details))
													$msg = 'Successfully updated return and shipment status ' . $awb;
												else
													$msg = 'Error updating shipment status ' . $db->getLastError();
											} else {
												$msg = 'Error updating return ' . $db->getLastError();
											}
										} else {
											$msg = 'Return not found for ' . $awb;
										}
									}
								} else {
									$msg = 'Return Shipments not found for ' . $awb;
								}
								$log->write($msg, 'return-sylvi');
								echo $msg;
							} else {
								$this->update_return_status($awb, $shipment_status, $additional_details);
							}
							break;

						case 'lost':
							if (!$data->is_return) {
								$db->where('orderNumberFormated', $shopify_orderId);
								$order_data = $db->getOne(TBL_SP_ORDERS, array('orderId', 'orderItemId'));
								$data->orderId = $order_data['orderId'];
								$data->orderItemId = $order_data['orderItemId'];
								$sp = new shopify_dashboard($this->sp_account);
								$sp->update_fulfilment_status_sp($order_data['orderId'], 'failure');
								$this->insert_return($data, $logistic);
							}

							$additional_details['fulfillmentStatus'] = 'completed';
							$additional_details['deliveredDate'] = $data->scans[0]->date;
							$additional_details['shipmentStatus'] = $shipment_status;
							$this->update_return_status($awb, 'delivered', $additional_details);
							$log->write("Shipment Status: " . $shipment_status, 'shopify-order-status');
							break;

							// case 'return_out_for_pickup':
							// case 'return_picked_up': // CHECK FOR PICKUP STATUS FOR 19041313164374 - Apr/16 with order_id
							// 	$details['r_status'] = str_replace('return_', '', $shipment_status);
							// 	$this->update_return_status($lp_orderid, 'start', $details);
							// 	break;
					}
					break;

				case 'products':
					$product = json_decode($data);
					foreach ($product->variants as $variant) {
						$update_data = array(
							'variantId' => $variant->id,
							'productId' => $variant->product_id,
							'accountId' => $this->sp_account->account_id,
							'title' => $product->title,
							'handle' => $product->handle,
							'imageLink' => $product->image->src,
							'price' => $variant->price,
							'sku' => $variant->sku,
							'status' => $product->status,
							'createdDate' => date('Y-m-d H:i:s', strtotime($product->created_at))
						);
						$db->where('variantId', $variant->id);
						if ($db->has(TBL_SP_PRODUCTS)) {
							$db->where('variantId', $variant->id);
							$db->update(TBL_SP_PRODUCTS, $update_data);
						} else {
							$db->insert(TBL_SP_PRODUCTS, $update_data);
						}
					}
					break;
			}
		}
	}
}
