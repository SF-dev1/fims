<?php
include_once(ROOT_PATH . '/includes/connectors/shopify/shopify-dashboard.php');

use Razorpay\Api\Api;

class shopify
{

	private $sp_account = "";
	private $sandbox = false;

	function __construct($sp_account, $sandbox = false)
	{
		$this->sp_account = $sp_account;
		$this->sandbox = $sandbox;
	}

	public function insert_order($data)
	{
		global $db, $log;

		$data->shipping_address->email = $data->email;
		$data->shipping_address->phone = substr(str_replace(array('+', ' '), '', $data->shipping_address->phone), -10);
		$data->billing_address->email = $data->email;
		$data->billing_address->phone = substr(str_replace(array('+', ' '), '', $data->billing_address->phone), -10);

		$order_details = array(
			"orderId" 				=> $data->id,
			"checkoutId"			=> $data->checkout_id,
			"status" 				=> "new",
			"accountId" 			=> $this->sp_account->account_id,
			"spStatus"	 			=> "new",
			"hold" 					=> false,
			"isFlagged" 			=> false,
			"orderType"				=> 'EasyShip',
			"dispatchServiceTier"	=> $data->shipping_lines[0]->title,
			"discountCodes"			=> json_encode($data->discount_applications),
			"orderDate" 			=> date('Y-m-d H:i:s', strtotime($data->created_at)),
			"dispatchAfterDate" 	=> date('Y-m-d H:i:s', strtotime($data->created_at) + 300), // ORDER DATE + 5 MINS
			"dispatchByDate" 		=> $this->get_next_working_day($data->created_at),
			"updatedAt" 			=> date('Y-m-d H:i:s', strtotime($data->updated_at)),
			"sla" 					=> "1",
			"deliveryAddress" 		=> json_encode($data->shipping_address),
			"billingAddress" 		=> json_encode($data->billing_address),
			"customerId"			=> $data->customer->id,
			"addressId"				=> $data->customer->default_address->id,
			// "deliveryDetails" 		=> $data->,
			// "forwardTrackingId"		=> $data->,
			"orderNumber"			=> $data->order_number,
			"orderNumberFormated"	=> $data->name,
			// "invoiceNumber" 		=> $data->name,
			// "invoiceDate" 			=> $data->,
			"invoiceAmount" 		=> $data->current_total_price,
			"tags"					=> $data->tags,
			"insertDate" 			=> $db->now(),
		);

		// if ($order_details['paymentType'] == "prepaid"){
		$sp = new shopify_dashboard($this->sp_account);
		$transactions = $sp->get_order_transactions($data->id);
		foreach ($transactions as $transaction) {
			if (strpos(strtolower($transaction['gateway']), 'razorpay') !== FALSE) {
				if (isset($transaction['message']) && !is_null($transaction['message'])) {
					$paid_amount = (float)str_replace('Paid INR ', '', $transaction['message']);
				} else if ($transaction['status'] == "success" && isset($transaction['receipt']['payment_id'])) {
					$payment_id = $transaction['receipt']['payment_id']; // TODO: Fetch payment details of the order
					$paid_amount = (float)$transaction['amount'];
				} else {
					$paid_amount = (float)$transaction['amount'];
				}

				$pg_order_id = explode('|', $transaction['authorization'])[0];
				// var_dump($pg_order_id);
				// var_dump($pg_order_id);
				// // var_dump($transaction);
				// var_dump($payment_id);
				// if ($pg_order_id){
				// 	echo 'pg_order_id';
				// } elseif ($payment_id) {
				// 	echo 'payment_id';
				// }
				// exit;

				if ($pg_order_id) {
					$order_details['paymentGatewayOrder'] = $pg_order_id;
					$db->where('pg_provider_id', $this->sp_account->account_active_pg_id);
					$pg_creds = $db->getOne(TBL_SP_PAYMENTS_GATEWAY . ' pg', array('pg_provider_id', 'pg_provider_key', 'pg_provider_secret'));
					$payment_api = new Api($pg_creds['pg_provider_key'], $pg_creds['pg_provider_secret']);
					$pg_order_details = $payment_api->order->fetch($pg_order_id);
					// var_dump($pg_order_details);
					// exit;
					$pg_offers = $pg_order_details->offers;
					$paymentGatewayDiscount = 0;
					$pg_payments_details = $payment_api->order->fetch($pg_order_id)->payments();
					$order_details['paymentGatewayMethod'] = $pg_payments_details->method;

					$active_discount_offers = json_decode(get_option('razorpay_offers'), true);
					foreach ($pg_offers as $pg_offer) {
						if (array_key_exists($pg_offer, $active_discount_offers)) {
							if ((time() > strtotime($active_discount_offers[$pg_offer]['startDate'])) &&  (time() < strtotime($active_discount_offers[$pg_offer]['endDate']))) {
								if ($active_discount_offers[$pg_offer]['offerConversion'] == "percentage") {
									$paymentGatewayDiscount = round($data->current_total_price * $active_discount_offers[$pg_offer]['offerValue']);
								} else {
									$paymentGatewayDiscount = $active_discount_offers[$pg_offer]['offerValue'];
								}

								if ($paymentGatewayDiscount == $pg_order_details['amount_due']) {
									$paymentGatewayDiscount = (float)number_format(($paymentGatewayDiscount / 100), 2, '.', '');
									$paid_amount -= $paymentGatewayDiscount;
									$data->financial_status = "paid";
									continue;
								}
							}
						}
					}

					$total_amount = (float)$transaction['amount'];
					$item_details["paymentGateway"] = $transaction['gateway'];
					$item_details['paymentGatewayAmount'] = $paid_amount;
					$item_details['paymentGatewayDiscount'] = $paymentGatewayDiscount;
				} elseif ($payment_id) {
					$db->where('pg_provider_id', $this->sp_account->account_active_pg_id);
					$pg_creds = $db->getOne(TBL_SP_PAYMENTS_GATEWAY . ' pg', array('pg_provider_id', 'pg_provider_key', 'pg_provider_secret'));
					$payment_api = new Api($pg_creds['pg_provider_key'], $pg_creds['pg_provider_secret']);

					// var_dump($payment_id);
					// $pg_payments_details = $payment_api->order->fetch($payment_id)->payments();
					// var_dump($pg_payments_details);
					// exit;
				}
				// exit;
			}

			if (strpos(strtolower($transaction['gateway']), 'gift') !== FALSE) {
				$order_details['giftCardAmount'] = $transaction['amount'];
			}

			if (strpos(strtolower($transaction['gateway']), 'cash') !== FALSE) {
				$order_details["paymentGateway"] = $data->gateway;
				$order_details['codAmount'] = $transaction['amount'];
			}
		}
		// $order_details["paymentGateway"] = $data->gateway;
		$order_details["paymentType"] = (((strpos($transaction['gateway'], 'cash') !== FALSE) || ($data->financial_status == "pending" || $data->financial_status == "partially_paid")) ? 'cod' : 'prepaid');
		// }

		$shippingCharge = $data->shipping_lines[0]->price;
		$multiInsert = array();
		$multiProducts = count($data->line_items) > 1 ? true : false;
		foreach ($data->line_items as $line_item) {
			$spDiscount = 0;
			if (!empty($line_item->discount_allocations)) {
				foreach ($line_item->discount_allocations as $discount) {
					$spDiscount += $discount->amount;
				}
			}

			$spDiscount = $spDiscount / $line_item->fulfillable_quantity;
			$shippingCharge = $shippingCharge / $line_item->fulfillable_quantity;

			$taxRates = array('cgst' => 0, 'sgst' => 0, 'igst' => 0);
			if ($line_item->tax_lines) {
				foreach ($line_item->tax_lines as $tax_line) {
					$taxRates[strtolower($tax_line->title)] = $tax_line->rate * 100;
				}
			} else { // PATCH TILL THE GST RATES ARE NOT SENT BY MAGIC CHECKOUT
				if ($data->shipping_address->province == "Gujarat") {
					if ($this->sp_account->account_id == "57162236113") {
						$taxRates['cgst'] = 9;
						$taxRates['sgst'] = 9;
					}

					if ($this->sp_account->account_id == "62824448179") {
						$taxRates['cgst'] = 1.5;
						$taxRates['sgst'] = 1.5;
					}
				} else {
					if ($this->sp_account->account_id == "57162236113") {
						$taxRates['igst'] = 18;
					}

					if ($this->sp_account->account_id == "62824448179") {
						$taxRates['igst'] = 3;
					}
				}
			}

			$item_details = array(
				"orderItemId" 			=> $line_item->id,
				"orderedQuantity" 		=> $line_item->quantity,
				"quantity" 				=> $line_item->fulfillable_quantity,
				"title" 				=> $line_item->name,
				"productId" 			=> $line_item->product_id,
				"variantId"				=> $line_item->variant_id,
				"sku" 					=> $line_item->sku,
				"hsn" 					=> '9101', // GET DYNAMIC HSN AS PER PRODUCT
				"multiProducts"			=> $multiProducts,
				"customerPrice" 		=> $line_item->price - $spDiscount,
				"spDiscount" 			=> $spDiscount,
				"sellingPrice" 			=> $line_item->price,
				"shippingCharge" 		=> $shippingCharge,
				"totalPrice" 			=> $line_item->price - $spDiscount + $shippingCharge,
				"pickupDetails" 		=> $this->sp_account->account_return_address, //!is_null($line_item->origin_location) ? json_encode($line_item->origin_location) : (!is_null($data->line_items[0]->origin_location) ? json_encode($data->line_items[0]->origin_location) : NULL),
				"cgstRate" 				=> $taxRates['cgst'],
				"sgstRate" 				=> $taxRates['sgst'],
				"igstRate" 				=> $taxRates['igst'],
				"costPrice" 			=> $this->get_cost_price_by_sku($line_item->sku),
			);
			$multiInsert[] = array_merge($order_details, $item_details);
		}

		// var_dump($multiInsert);
		// return;

		if (!empty($multiInsert)) {
			if ($db->insertMulti(TBL_SP_ORDERS, $multiInsert)) {
				$log->write('Successfully inserted ' . count($multiInsert) . ' orders', 'notification-shopify-' . str_replace(' ', '-', strtolower($this->sp_account->account_name)));
				echo 'Successfully inserted ' . count($multiInsert) . ' orders. ';
			} else {
				echo 'Error adding orders ' . $db->getLastError();
			}
		} else {
			echo 'No orders found';
		}
	}

	public function update_order($data)
	{
		global $db, $log, $logisticPartner, $account, $current_user;

		$update = 0;
		$error = array();
		$shippingCharge = isset($data->shipping_lines[0]->price) ? $data->shipping_lines[0]->price : 0;
		$multiProducts = count($data->line_items) > 1 ? true : false;
		$data->billing_address = ($data->billing_address ? $data->billing_address : $data->shipping_address);
		$is_cancelled = false;

		if (strpos(strtolower(json_encode($data->note_attributes)), 'magic checkout') !== FALSE)
			$data->tags .= (($data->tags == "") ? 'Magic Checkout' : ', Magic Checkout');

		if (is_null($data->cancelled_at) && $data->fulfillment_status == "fulfilled" && (strpos($data->financial_status, 'partially_refunded') !== FALSE || strpos($data->financial_status, 'refunded') !== FALSE)) {
			foreach ($data->refunds as $refunds) {
				foreach ($refunds->refund_line_items as $refund_line_item) {
					$refund_details = array(
						'refundAmount' => $refund_line_item->subtotal
					);
					$db->where('variantId', $refund_line_item->line_item->variant_id);
					$db->where('orderItemId', $refund_line_item->line_item_id);
					if ($db->update(TBL_SP_ORDERS, $refund_details))
						$update++;
					else
						$error[$data->id] = $db->getLastError();
				}
			}
		} else {
			foreach ($data->line_items as $line_item) {
				$db->where('orderItemId', $line_item->id);
				if (!$db->has(TBL_SP_ORDERS)) { // NEW LINE ITEM ADDED
					$data->line_items = array($line_item);
					$this->insert_order($data);
					continue;
				}

				if ($data->fulfillment_status == "fulfilled" && strpos($data->financial_status, 'refund') === FALSE) {
					$shipment_status = $data->fulfillments[count($data->fulfillments) - 1]->shipment_status;
					$location_id = $data->fulfillments[count($data->fulfillments) - 1]->location_id;
					$item_details = array(
						'spStatus' => $shipment_status,
						'locationId' => $location_id
					);
					if ($shipment_status == "delivered") {
						// $item_details['deliveredDate'] = $db->now();
						$item_details['isFlagged'] = false;
					}
				} else {
					$spDiscount = 0;
					if (!empty($line_item->discount_allocations)) {
						foreach ($line_item->discount_allocations as $discount) {
							$spDiscount += ($discount->amount / $line_item->fulfillable_quantity);
						}
					}

					$shippingCharge = ($line_item->fulfillable_quantity < 1) ? 0 : $shippingCharge / $line_item->fulfillable_quantity;

					$taxRates = array('cgst' => 0, 'sgst' => 0, 'igst' => 0);
					foreach ($line_item->tax_lines as $tax_line) {
						$taxRates[strtolower($tax_line->title)] = $tax_line->rate * 100;
					}

					$data->shipping_address->email = $data->email;
					$data->shipping_address->phone = substr(str_replace(array('+', ' '), '', $data->shipping_address->phone), -10);
					$data->billing_address->email = $data->email;
					$data->billing_address->phone = substr(str_replace(array('+', ' '), '', $data->billing_address->phone), -10);

					$item_details = array(
						"orderItemId" 			=> $line_item->id,
						"quantity" 				=> $line_item->fulfillable_quantity,
						"sellingPrice" 			=> $line_item->price,
						"spDiscount" 			=> $spDiscount,
						"shippingCharge" 		=> $shippingCharge,
						"customerPrice" 		=> $line_item->price - $spDiscount,
						"totalPrice" 			=> $line_item->price - $spDiscount + $shippingCharge,
						"deliveryAddress" 		=> json_encode($data->shipping_address),
						"billingAddress" 		=> json_encode($data->billing_address),
						// "pickupDetails" 		=> !is_null($line_item->origin_location) ? json_encode($line_item->origin_location) : (!is_null($data->line_items[0]->origin_location) ? json_encode($data->line_items[0]->origin_location) : null),
						"cgstRate" 				=> $taxRates['cgst'],
						"sgstRate" 				=> $taxRates['sgst'],
						"igstRate" 				=> $taxRates['igst'],
						"multiProducts"			=> $multiProducts,
						"tags"					=> $data->tags,
						"discountCodes"			=> json_encode($data->discount_applications),
					);

					$is_cancelled = false;
					if ($line_item->fulfillable_quantity < 1) {
						$item_details['status'] = "cancelled";
						$item_details['spStatus'] = "cancelled";
						$item_details['cancelledDate'] = date('c');
						$is_cancelled = true;
					}

					// ONLY PERFORM IF THE ORDER IS NOT FULLFILLED TO CHECK IF THE ORDER IS CONVERTED TO PREPAID
					$sp = new shopify_dashboard($this->sp_account);
					$transactions = $sp->get_order_transactions($data->id);
					foreach ($transactions as $transaction) {
						if (strpos(strtolower($transaction['gateway']), 'razorpay') !== FALSE) {
							if (isset($transaction['message']) && !is_null($transaction['message'])) {
								$paid_amount = (float)str_replace('Paid INR ', '', $transaction['message']);
							} else if ($transaction['status'] == "success" && isset($transaction['receipt']['payment_id'])) {
								// $payment_id = $transaction['receipt']['payment_id']; // TODO: Fetch payment details of the order
								$paid_amount = (float)$transaction['amount'];
							} else {
								$paid_amount = (float)$transaction['amount'];
							}

							$pg_order_id = explode('|', $transaction['authorization'])[0];
							if ($pg_order_id) {
								$item_details['paymentGatewayOrder'] = $pg_order_id;

								$db->where('pg_provider_id', $this->sp_account->account_active_pg_id);
								$pg_creds = $db->getOne(TBL_SP_PAYMENTS_GATEWAY . ' pg', array('pg_provider_id', 'pg_provider_key', 'pg_provider_secret'));
								$payment_api = new Api($pg_creds['pg_provider_key'], $pg_creds['pg_provider_secret']);
								$pg_order_details = $payment_api->Order->fetch($pg_order_id);
								$pg_offers = $pg_order_details->offers;
								$paymentGatewayDiscount = 0;
								$pg_payments_details = $payment_api->Order->fetch($pg_order_id)->payments();
								foreach ($pg_payments_details->items as $pg_payments_details_item) {
									if ($pg_payments_details_item->status == "captured") {
										$item_details['paymentGatewayMethod'] = $pg_payments_details_item->method;
										continue;
									}
								}

								$active_discount_offers = json_decode(get_option('razorpay_offers'), true);
								foreach ($pg_offers as $pg_offer) {
									if (array_key_exists($pg_offer, $active_discount_offers)) {
										if ((time() > strtotime($active_discount_offers[$pg_offer]['startDate'])) &&  (time() < strtotime($active_discount_offers[$pg_offer]['endDate']))) {
											if ($active_discount_offers[$pg_offer]['offerConversion'] == "percentage") {
												$paymentGatewayDiscount = round($data->current_total_price * $active_discount_offers[$pg_offer]['offerValue']);
											} else {
												$paymentGatewayDiscount = $active_discount_offers[$pg_offer]['offerValue'];
											}

											if ($paymentGatewayDiscount == $pg_order_details['amount_due']) {
												$paymentGatewayDiscount = (float)number_format(($paymentGatewayDiscount / 100), 2, '.', '');
												$paid_amount -= $paymentGatewayDiscount;
												$data->financial_status = "paid";
												continue;
											}
										}
									}
								}

								$total_amount = (float)$transaction['amount'];
								$item_details["paymentGateway"] = $transaction['gateway'];
								$item_details['paymentGatewayAmount'] = $paid_amount;
								$item_details['paymentGatewayDiscount'] = $paymentGatewayDiscount;
							}
						}

						if (strpos(strtolower($transaction['gateway']), 'gokwik') !== FALSE) {
							$item_details["paymentGateway"] = $transaction['gateway'];
							$item_details['paymentGatewayAmount'] = (float)$transaction['amount'];
							$item_details['paymentGatewayOrder'] = $transaction['authorization'];
						}

						if (strpos(strtolower($transaction['gateway']), 'gift') !== FALSE) {
							$item_details["paymentGateway"] = $transaction['gateway'];
							$item_details['giftCardAmount'] = $transaction['amount'];
						}

						if (strpos(strtolower($transaction['gateway']), 'cash') !== FALSE) {
							$item_details["paymentGateway"] = $transaction['gateway'];
							$item_details['codAmount'] = $transaction['amount'];
						}
					}

					$item_details["paymentType"] = (((strpos(strtolower($item_details['paymentGateway']), 'cash') !== FALSE) || ($data->financial_status == "pending" || $data->financial_status == "partially_paid")) ? 'cod' : 'prepaid');

					if (($item_details['totalPrice'] == ($item_details['paymentGatewayAmount'] + $item_details['paymentGatewayDiscount'])) || $item_details['totalPrice'] == $item_details['giftCardAmount']) {
						$item_details['codAmount'] = 0;
						$item_details["paymentType"] = 'prepaid';
					}
				}

				if (!is_null($data->cancelled_at) || $is_cancelled) {
					$db->where('o.orderId', $data->id);
					$db->where('o.orderItemId', $line_item->id);
					$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
					$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider');

					$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS . ' o', 'o.status, f.fulfillmentId, f.trackingId, f.logistic, o.orderNumberFormated, o.accountId, f.lpProvider, f.lpOrderId, f.shipmentStatus, sl.*');
					if (isset($order->status) && $order->status == "shipped") {
						$db->where('orderId', $data->id);
						$db->where('orderItemId', $line_item->id);
						unset($item_details['status']);
						unset($item_details['cancelledDate']);
						if ($db->update(TBL_SP_ORDERS, $item_details)) {
							$return_data = '{"awb": "' . $order->trackingId . '","courier_name": "' . $order->logistic . '","current_status": "RTO INITIATED","order_id": "' . $order->orderNumberFormated . '","current_timestamp":"' . date("Y-m-d H:i:s", time()) . '","scans": [{"date": "' . date("Y-m-d H:i:s", time()) . '","activity": "Shopify cancellation"}]}';
							$return = $this->insert_return(json_decode($return_data), $order->lp_provider_name, 'shopify_cancellation');
							exit($return);
						}
					} else {
						$item_details['status'] = "cancelled";
						$item_details['cancelledDate'] = date('c');
					}

					// SHIPMENT CANCELLATION IN CASE OF UNSHIPPED
					if (isset($order->shipmentStatus) && !is_null($order->shipmentStatus)) {
						if ($order->shipmentStatus == "delivered") {
							$error[$data->id]['cancelllation'] = 'Delivered Order. Cannot be cancelled';
							$log->write("Order with ID " . $order->lpOrderId . " is delivered. Cannot be cancelled", 'shopify-order-status');
						} else {
							$account = $order; // SET GLOBAL $account VARIABLE TO INITIATED THE CONFIG
							$active_lp = strtolower($order->lp_provider_name);
							if ($active_lp != "self") {
								include_once(ROOT_PATH . '/includes/class-' . $active_lp . '-client.php');
								$label = $logisticPartner->cancelOrder(array($order->lpOrderId));
								$db->where('lpOrderId', $order->lpOrderId);
							} else {
								$label['message'] = "Order cancelled successfully.";
								$db->where('fulfillmentId', $order->fulfillmentId);
							}

							if ($label['message'] == "Order cancelled successfully." || strpos(strtolower($label['message']), "cannot cancel order when shipment status is cancel") == FALSE || strpos($label['message'], 'Your request to cancel order id ' . $order->orderNumberFormated . ' has been taken') !== FALSE) {
								$db->update(TBL_FULFILLMENT, array(
									'fulfillmentStatus' => 'cancelled',
									'shipmentStatus' => 'cancelled',
									'canceledBy' => $current_user['userID'],
									'cancellationReason' => 'order_cancelled',
									'cancelledDate' => $db->now(),
								));
								$log->write("Cancelling Order on " . $active_lp . " :: " . json_encode($order) . " :: " . json_encode($label), 'shopify-order-status');
							} else {
								$error[$data->id]['lp_cancelllation'] = $label;
								$log->write("Error Cancelling Order on " . $active_lp . " :: " . json_encode($label), 'shopify-order-status');
							}
						}
					}

					$item_details['spStatus'] = "cancelled";
				}

				$tags = array_map('trim', explode(',', $data->tags));
				if (strpos(strtolower($data->tags), 'confirm') !== FALSE)
					$item_details['isAutoApproved'] = 1;

				if (in_array('Replacement', $tags)) {
					$db->where('r_replacementOrderId', $data->id);
					$db->where('r_status', 'completed');
					if (!$db->has(TBL_SP_RETURNS)) {
						$item_details["replacementOrder"] = true;
						// $item_details["hold"] = true;
					}
				}

				if (strpos(strtolower($data->tags), 'approved') !== false)
					$item_details["hold"] = false;

				$db->where('orderId', $data->id);
				$db->where('orderItemId', $line_item->id);
				if ($db->update(TBL_SP_ORDERS, $item_details))
					$update++;
				else
					$error[$data->id] = $db->getLastError();
			}
		}

		if (empty($error))
			echo 'Successfully updated ' . $update . ' orders';
		else
			echo 'Error updating orders. Error: ' . json_encode($error);
	}

	public function insert_return($data, $logistic = "", $creator = "push_notify")
	{
		global $db, $log, $current_user;

		$order_number = $data->order_id;
		$tracking_id = $data->awb;
		$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId');
		if ($data->current_status == "RTO INITIATED") {
			$db->where('orderNumberFormated', $order_number);
		} else {
			$db->where('o.orderId', $data->order_id);
			$db->where('o.orderItemId', $data->orderItemId);
		}
		$db->where('status', 'cancelled', '!=');
		$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
		$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, 'o.*, f.*, sa.account_active_logistic_id');
		if ($data->current_status == "RTO INITIATED") {
			$scans = $data->scans[0]->activity;
			$regex = "/number ([a-zA-Z0-9_]*) -/";
			preg_match_all($regex, $scans, $tracking_details);

			$return_date = isset($data->current_timestamp) ? date('Y-m-d H:i:s', strtotime(preg_replace('/ /', '-', $data->current_timestamp, 2))) : date('Y-m-d H:i:s', time());
			if ($return_date == "1970-01-01 05:30:00")
				$return_date = $data->current_timestamp;
			if (strpos($scans, 'Redirection under Same Airwaybill') !== FALSE || is_null($tracking_details[1][0]))
				$tracking_id = $orders[0]->trackingId;
			else
				$tracking_id = $tracking_details[1][0];
		}

		$multiInsert = array();
		$exists = array();
		foreach ($orders as $order) {
			if ($data->current_status == "RTO INITIATED") {
				$lp_orderId = (($logistic == "ShipRocket" && isset($data->sr_order_id)) ? $data->sr_order_id : $order->lpOrderId);
				$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
				$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
				$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'return');
				$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $tracking_id);
				$db->where('r.orderItemId', $order->orderItemId);
				if ($db->has(TBL_SP_RETURNS . ' r')) {
					$exists[$tracking_id] = 'Exists';
					continue;
				}
			}

			$insertData = array(
				'orderItemId' => $order->orderItemId,
				'r_source' => ($data->current_status == "RTO INITIATED" ? 'courier_return' : 'customer_return'),
				'r_status' => 'start',
				'r_quantity' => $order->quantity,
				'r_productId' => $order->productId,
				'r_sku' => $order->sku,
				'r_returnType' => ($data->current_status == "RTO INITIATED" ? 'refund' : $data->returnType),
				'r_reason' => ($data->current_status == "RTO INITIATED" ? 'order_cancelled' : $data->reason),
				'r_subReason' => ($data->current_status == "RTO INITIATED" ? 'undelivered' : $data->productCondition . "\n" . $data->complains),
				// 'r_status' => 'start',
				'r_uid' => $order->uid,
				'r_createdDate' => (isset($return_date) ? date('Y-m-d H:i:s', strtotime($return_date)) : $db->now()),
				// 'r_expectedDate' => (isset($return_date) ? date('Y-m-d H:i:s', strtotime($return_date.'  +15 days')) : date('Y-m-d H:i:s', strtotime('+15 days'))),
				'insertBy' => $creator,
				'insertDate' => $db->now()
			);

			if ($data->current_status == "RTO INITIATED") {
				// $insertData['r_trackingId'] = $tracking_id;
				// $insertData['r_courierName'] = $order->logistic;
				// $insertData['r_lpOrderId'] = $lp_orderId;
				// $insertData['r_lpProvider'] = $order->lpProvider;
				$db->where('trackingId', $tracking_id);
				$fulfillment = $db->objectBuilder()->getOne(TBL_FULFILLMENT);

				$db->where('fulfillmentId', $fulfillment->fulfillmentId);
				$db->update(TBL_FULFILLMENT, array('shipmentStatus' => ($data->current_status == "RTO INITIATED" ? 'undelivered' : strtolower($data->current_status))));

				$fulfilment_data = array(
					'fulfillmentStatus'  => 'start',
					'fulfillmentType'    => 'return',
					'orderId'            => $fulfillment->orderId,
					'channel'            => $fulfillment->channel,
					'lpOrderId'          => $fulfillment->lpOrderId,
					'lpShipmentId'       => $fulfillment->lpShipmentId,
					'lpManifestId'       => $fulfillment->lpManifestId,
					'lpProvider'         => $fulfillment->lpProvider,
					'lpShipmentDetails'  => $fulfillment->lpShipmentDetails,
					'trackingId'         => $fulfillment->trackingId,
					'logistic'           => $fulfillment->logistic,
					'shipmentStatus'     => 'start',
					'shippingZone'       => $fulfillment->shippingZone,
					'shippingSlab'       => $fulfillment->shippingSlab,
					'shippingFee'        => $fulfillment->shippingFee,
					'codFees'            => 0,
					'rtoShippingFee'     => $fulfillment->rtoShippingFee,
					'createdBy'          => $current_user['userID'],
					'canceledBy'         => $current_user['userID'],
					'cancellationReason' => ($data->current_status == "RTO INITIATED" ? 'undelivered' : 'shopify_cancellation'),
					'createdDate'        => date('Y-m-d H:i:s'),
					'expectedPickupDate' => date('Y-m-d H:i:s'),
					'expectedDate'       => date('Y-m-d H:i:s'),
					// 'deliveredDate'      => date('Y-m-d H:i:s'),
					// 'cancelledDate'      => date('Y-m-d H:i:s'),
				);
				if ($creator == "shopify_cancellation") {
					$insertData['r_status'] = 'delivered';
					$fulfilment_data['fulfillmentStatus'] = 'delivered';
					$fulfilment_data['shipmentStatus'] = 'delivered';
					$fulfilment_data['deliveredDate'] = date('Y-m-d H:i:s');
				}
				$db->insert(TBL_FULFILLMENT, $fulfilment_data);
			} else {
				$comment_data = array(
					'comment' => ucwords($data->returnType) . ' Order Created',
					'orderId' => $data->order_id,
					'commentFor' => 'return_initiated',
					'userId' => $current_user['userID'],
				);
				$db->insert(TBL_SP_COMMENTS, $comment_data);

				// $insertData['r_comments'] = json_encode(array('content' => 'RMA Created', 'user' => $current_user['userID'], 'timestamp' => date('c')));
				$insertData['r_uid'] = json_encode(array($data->uid));
				if ($data->pickupType == "free_pickup") {
					$insertData['r_status'] = 'pickup_creation_pending';
					$insertData['r_lpProvider'] = $order->account_active_logistic_id;
				}

				if ($data->pickupType == "self_ship" && $data->productCondition == "wrong" && $data->returnType == "refund") {
					$insertData['r_status'] = 'refund_pending';
					// $insertData['r_status'] = 'refund';
				}

				if ($data->returnType == "replace") { // CREATE A NEW REPLACEMENT ORDER ON SHOPIFY
					$order_data = array(
						"billingAddress" => json_decode($order->billingAddress),
						"deliveryAddress" => json_decode($order->deliveryAddress),
						"locationId" => $order->locationId,
						"variantId" => $data->variantId,
						"customerId" => $order->customerId
					);
					$replacementOrderId = $this->create_replacement_order_sp((object)$order_data);
					$insertData['r_replacementOrderId'] = $replacementOrderId;
				}
			}
			$multiInsert[] = $insertData;
		}

		if (!empty($multiInsert)) {
			$db->startTransaction();
			if ($db->insertMulti(TBL_SP_RETURNS, $multiInsert)) {
				$db->commit();
				$log->write('Successfully inserted ' . count($multiInsert) . ' return(s). Exists: ' . count($exists), 'return-sylvi');
				if ($data->current_status == "RTO INITIATED") {
					echo 'Successfully inserted ' . count($multiInsert) . ' return(s). Exists: ' . count($exists);
				} else {
					return array('type' => 'success', 'message' => 'Return successfully created');
				}
			} else {
				$db->rollback();
				$log->write('Error adding orders ' . $db->getLastError() . " \nData:" . json_encode($data), 'return-sylvi');
				if ($data->current_status == "RTO INITIATED") {
					echo 'Error adding orders ' . $db->getLastError();
				} else {
					return array('type' => 'error', 'message' => 'Error creatnig return', 'error' => $db->getLastError());
				}
			}
		} else {
			if ($data->current_status == "RTO INITIATED") {
				echo 'No orders inserted. Exists: ' . count($exists) . ' :: ' . json_encode($exists);
			} else {
				return array('type' => 'error', 'message' => 'Return already exists');
			}
		}
	}

	public function update_return_status($awb, $status, $additional_details = array())
	{
		global $db, $log;

		$status = str_replace(array('rto_', 'return_'), '', $status);
		$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
		$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'return');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
		$return_details = $db->get(TBL_SP_RETURNS . ' r', NULL, 'r.returnId, r.r_status, f.fulfillmentId');
		$return_ids = NULL;
		foreach ($return_details as $return_detail) {
			$return_ids[] = $return_detail['returnId'];
			$shipment_status = $return_detail['r_status'];
			$fulfillment_id = $return_detail['fulfillmentId'];
		}
		if ($shipment_status) {
			$msg = 'Return Shipment Status ' . $shipment_status . ' and update status ' . $status . ' hierarchy mismatch for ' . $awb;
			if (($status == $shipment_status) ||
				($status == "out_for_pickup" && ($shipment_status == "start" || $shipment_status == "awb_assigned" || $shipment_status == "pickup_exception")) ||
				($status == "pickup_exception" && $shipment_status == "out_for_pickup") ||
				($status == "picked_up" && ($shipment_status == "out_for_pickup" || $shipment_status == "pickup_exception")) ||
				($status == "in_transit" && ($shipment_status == "start" || $shipment_status == "awb_assigned" || $shipment_status == "picked_up")) ||
				($status == "out_for_delivery" && ($shipment_status == "start" || $shipment_status == "in_transit")) ||
				($status == "delivered" && ($shipment_status == "start" || $shipment_status == "in_transit" || $shipment_status == "out_for_delivery"))
			) {

				$details = array('shipmentStatus' => $status);
				if (!is_null($return_ids)) {
					$db->where('returnId', $return_ids, 'IN');
					if ($db->update(TBL_SP_RETURNS, array('r_status' => $status))) {
						$db->where('fulfillmentId', $fulfillment_id);
						if ($db->update(TBL_FULFILLMENT, array_merge($details, $additional_details)))
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
	}

	private function get_next_working_day($date)
	{
		$dbd = date('H:m', strtotime($date)) > '12' ? date('Y-m-d H:i:s', strtotime('tomorrow 12 PM')) : date('Y-m-d H:i:s', strtotime('today 12 PM'));
		$dbd = date('D', strtotime($dbd)) === 'Sun' ? date('Y-m-d H:i:s', strtotime($dbd . ' + 1 day')) : $dbd;
		return $dbd;
	}

	private function get_cost_price_by_sku($sku)
	{
		global $db, $stockist;

		$db->where('sku', $sku);
		$parentSkuID = $db->objectBuilder()->getOne(TBL_PRODUCTS_MASTER, 'pid');
		if ($parentSkuID) {
			$cost_price = $stockist->get_current_acp($parentSkuID->pid);
		} else {
			$cost_price = "Parent SKU not found";
		}

		return $cost_price;
	}
}
