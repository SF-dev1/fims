<?php
// https://github.com/phpclassic/php-shopify
// 
use Shiprocket\Client as ShiprocketClient;
use GuzzleHttp\Exception\RequestException as HttpException;

class ShiprocketClientConnector extends ShiprocketClient
{
	private $account;

	public function __construct(array $config, $account)
	{
		$this->account = $account;
		parent::__construct($config);
	}

	public function getToken($refresh = false)
	{
		global $db;

		$token = $this->account->lp_provider_token;
		if ($token && !$refresh)
			return $token;
		else {
			$this->setToken("");
			$token = $this->request(
				'post',
				'auth/login',
				$this->getConfiguration()
			)['token'];
			$db->where('lp_account_id', $this->account->lp_account_id);
			$db->update(TBL_SP_LOGISTIC, array('lp_provider_token' => $token));
			return $token;
		}
	}

	public function request($verb, $path, $parameters = [])
	{
		global $log;

		$client = $this->httpClient;
		$url = $this->getUrlFromPath($path);
		$verb = strtolower($verb);
		$config = $this->getConfigForVerbAndParameters($verb, $parameters);
		$log->write(json_encode($url), '/shopify-requests');

		try {
			$response = $client->$verb($url, $config);
		} catch (HttpException $e) {
			if ($response = $e->getResponse()) {
				$exception = json_decode($response->getBody()->getContents(), 1);
			}
			if ($exception['message'] == "Token has expired") {
				$this->getToken(true);
				$response = $this->request($verb, $path, $parameters);
				return json_decode($response->getBody(), 1);
			} else {
				return $exception;
			}
		}

		return json_decode($response->getBody(), 1);
	}

	public function createOrder($orders)
	{
		global $db, $log;

		$first_order = $orders[0];
		$delivery_address = json_decode($first_order->deliveryAddress);
		$post_data = array(
			"order_id" => $first_order->orderNumberFormated,
			"order_date" => date('Y-m-d', strtotime($first_order->orderDate)), // YYYY-MM-DD
			"pickup_location" => "Primary", // DEFAULT
			"channel_id" => $this->account->lp_channel_id,
			"comment" => "", // DELIVERY INSTRUCTION
			// "company_name" => "Style Feathers",
			"billing_customer_name" => $delivery_address->first_name,
			"billing_last_name" => $delivery_address->last_name,
			"billing_address" => $delivery_address->address1,
			"billing_address_2" => $delivery_address->address2,
			"billing_city" => $delivery_address->city,
			"billing_pincode" => str_replace(' ', '', $delivery_address->zip),
			"billing_state" => $delivery_address->province,
			"billing_country" => $delivery_address->country,
			"billing_email" => $delivery_address->email,
			"billing_phone" => ((int)str_replace(array(" ", "+91"), "", $delivery_address->phone)),
			"shipping_is_billing" => true,
			"payment_method" => strtoupper($first_order->paymentType), // COD/PREPAID
			"transaction_charges" => "0", // COD CHARGES
			"total_discount" => "0",
			"sub_total" => "0", // ORDER ITEMS TOTAL
			"length" => "10",
			"breadth" => "10",
			"height" => "10",
			"weight" => "0.200",
			"invoice_number" => $first_order->invoiceNumber,
			"order_type" => "NON ESSENTIALS" // NON ESSENTIALS/ESSENTIALS
		);

		foreach ($orders as $order) {
			if ($order->status == "cancelled" || $order->spStatus == "cancelled")
				continue;

			$post_data['sub_total'] += ($order->sellingPrice * $order->quantity);
			$post_data['transaction_charges'] = ($order->shippingCharge * $order->quantity);
			$post_data['total_discount'] += ($order->spDiscount * $order->quantity) + $order->giftCardAmount;
			$post_data["order_items"][] = array(
				"name" => $order->title,
				"sku" => $order->sku,
				"units" => $order->quantity,
				"selling_price" => ($order->sellingPrice * $order->quantity),
				"discount" => "0",
				"tax" => $order->hsn_rate, // DEFAULT: 18
				"hsn" => $order->hsn_code // DEFAULT: 9101
			);
		}

		$response = $this->createQuickOrder($post_data);
		$log->write(json_encode($response), 'shopify-order-create');
		if ($first_order && isset($response['order_id']) && isset($response['shipment_id']) && $response['status_code'] === (int)"1") {
			$data = array(
				'lpOrderId' => $response['order_id'],
				'lpShipmentId' => $response['shipment_id'],
				'lpProvider' => $this->account->lp_provider_id,
			);
			$post_data['order_id'] = $first_order->orderId; // REPLACE ORIGINAL ORDER ID
			// $db->where('orderId', $first_order->orderId);
			// $db->where('status', 'cancelled', '!=');
			// if ($db->update(TBL_SP_ORDERS, $data)){
			return array_merge(array('type' => 'success'), $post_data, $data, array('locationId' => $first_order->locationId, 'from_pincode' => json_decode($first_order->pickupDetails)->zip));
			// } else {
			// 	return array('type' => 'error', 'message' => 'Unable to update Order Details', 'error' => $db->getLastError());	
			// }
		} else if ($response['status_code'] == (int)"3" || $response['status_code'] == (int)"13" || $response['status_code'] == (int)"4") {
			$data = array(
				'lpOrderId' => $response['order_id'],
				'lpShipmentId' => $response['shipment_id'],
				'lpProvider' => $this->account->lp_provider_id,
				'locationId' => $first_order->locationId,
				'forwardTrackingId' => $response['awb_code'],
				'forwardLogistic' => $response['courier_name']
			);
			$post_data = array_merge($post_data, $data, array('locationId' => $first_order->locationId, 'from_pincode' => json_decode($first_order->pickupDetails)->zip));
			$post_data['order_id'] = $first_order->orderId; // REPLACE ORIGINAL ORDER ID
			// $db->where('orderId', $first_order->orderId);
			// $db->where('status', 'cancelled', '!=');
			// $db->update(TBL_SP_ORDERS, $data);
			return array('type' => 'info', 'message' => 'AWB already assigned to order', 'error' => $post_data);
		} else {
			return array('type' => 'error', 'message' => 'Unable to add order to Shipping Queue.', 'error' => $response);
		}
	}

	public function getOrder($order)
	{
		return $this->request('get', 'orders/show/' . $order);
	}

	public function getShipment($shipmentId)
	{
		return $this->request('get', 'shipments/' . $shipmentId);
	}

	public function updateOrder($data)
	{
		return $this->request('post', 'orders/update/adhoc/');
	}

	public function generateLabel($order)
	{
		global $db, $log, $current_user;

		$shipment_id = $order['lpShipmentId'];
		$assigned_tracking = $this->assign_tracking_id($order); // ASSIGN TRACKING ID
		// $already_assisgned = ($assigned_tracking['error']['message'] == "AWB is already assigned") ? true : false;
		$already_assisgned = ((isset($assigned_tracking['error']) && $assigned_tracking['error']['response']['data']['awb_assign_error'] == "AWB is already assigned") || (strpos($assigned_tracking['error']['message'], 'Cannot reassign courier for this shipment') !== FALSE)) ? true : false;
		if ($already_assisgned) {
			$order_details = $this->getOrder($order['lpOrderId']);
			$assigned_tracking = array(
				'type' => 'success',
				'data' => array(
					'awb_code' => $order_details['data']['shipments']['awb'],
					'courier_name' => $order_details['data']['shipments']['courier']
				)
			);
		}
		if ($assigned_tracking['type'] == "success") {
			$db->where('trackingId', $assigned_tracking['data']['awb_code']);
			if (!$db->has(TBL_FULFILLMENT)) {
				$data = array(
					'fulfillmentStatus' => 'start',
					'fulfillmentType' => 'forward',
					'orderId' => $order['order_id'],
					'channel' => $order['channel'],
					'lpOrderId' => $order['lpOrderId'],
					'lpShipmentId' => $order['lpShipmentId'],
					'lpProvider' => $this->account->lp_provider_id,
					'trackingId' => $assigned_tracking['data']['awb_code'],
					'logistic' => (!empty($assigned_tracking['data']['transporter_name']) ? $assigned_tracking['data']['transporter_name'] : $assigned_tracking['data']['courier_name']),
					'shipmentStatus' => 'awb_assigned',
					'shippingZone' => $assigned_tracking['courier']['zone'],
					'shippingSlab' => null,
					'shippingFee' => $assigned_tracking['courier']['freight_charge'],
					'codFees' => $assigned_tracking['courier']['cod_charges'],
					'rtoShippingFee' => $assigned_tracking['courier']['rto_charges'],
					'createdBy' => $current_user['userID'],
					'createdDate' => $db->now(),
					'lpShipmentDetails' => json_encode($assigned_tracking['data']),
					'expectedDate' => isset($assigned_tracking['courier']['etd']) ? date('Y-m-d H:i:s', strtotime('+' . $assigned_tracking['courier']['etd_hours'] . ' hours')) : date('Y-m-d H:i:s', strtotime('+7 weekdays'))
				);
				$log->write(json_encode($data), 'shopify-tracking-test');
				$db->insert(TBL_FULFILLMENT, $data);
			}

			$label = $this->getLabel(array($shipment_id)); // GENERATE LABEL AND DOWNLOAD
			$log->write("\n" . json_encode($shipment_id) . " :: " . json_encode($label), 'shopify-label');
			if ($label['label_created'] === (int)"1") {
				$label_pdf = file_get_contents($label['label_url']);
				if (strncmp($label_pdf, "%PDF-", 4) === 0) {
					$path = ROOT_PATH . '/labels/single/';
					$filename = 'FullLabel-Shopify-' . $order['order_id'] . '.pdf';
					if (file_put_contents($path . $filename, $label_pdf)) {
						$db->where('orderId', $order['order_id']);
						$db->where('status', 'cancelled', '!=');
						$db->update(TBL_SP_ORDERS, array('labelGeneratedDate' => $db->now()));
						return array(
							'type' => 'success',
							'message' => 'Label Generated',
							'data' => array(
								'awb_code' => $assigned_tracking['data']['awb_code'],
								'courier_name' => (!empty($assigned_tracking['data']['transporter_name']) ? $assigned_tracking['data']['transporter_name'] : $assigned_tracking['data']['courier_name']),
								'tracking_url' => $this->account->lp_provider_tracking_url . $assigned_tracking['data']['awb_code']
							)
						);
					} else {
						return array('type' => 'error', 'message' => 'Error downloading label', 'error' => $label);
					}
				} else {
					return array('type' => 'error', 'message' => 'Invalid file type', 'error' => $label);
				}
			} else {
				return array('type' => 'error', 'message' => 'Error generating label', 'error' => $label);
			}
		} else {
			return $assigned_tracking;
		}
	}

	public function assign_tracking_id($order)
	{
		global $db, $log;

		$couriers = $this->check_courier_serviceability($order['from_pincode'], $order['billing_pincode'], $order['weight'], true, '', $order['lpOrderId'], false, false);

		$i = 0;
		$courier_count = count($couriers);
		foreach ($couriers as $courier) {
			$log->write("\nCourier Details: " . json_encode($courier), 'shopify-tracking-test');
			$logistic_details = $this->assignAWBs(array($order['lpShipmentId']), $courier['courier_company_id']);
			$log->write("\nCourier Details: " . json_encode($courier) . "\n\nLogistic Details: " . json_encode($logistic_details) . "\n\nOrder Data: " . json_encode($order), 'shopify-tracking');

			if (isset($logistic_details['awb_assign_status']) && $logistic_details['awb_assign_status'] === (int)"1") {
				return array('type' => 'success', 'data' => $logistic_details['response']['data'], 'courier' => $courier['courier']);
				// } else {
				// 	return array('type' => 'error', 'message' => 'Error assigning courier partner', 'error' => $logistic_details);
			}
			$i++;

			if ($i == $courier_count) {
				return array('type' => 'error', 'message' => 'Error assigning courier partner', 'error' => $logistic_details);
			}
		}
	}

	public function check_courier_serviceability($from_pincode, $to_pincode, $weight, $is_cod, $mode = "", $order_id = null, $is_return = false, $return_recommended = false)
	{
		global $log;

		$response = $this->checkServiceability($from_pincode, $to_pincode, $weight, $is_cod, $order_id, $is_return, $mode);
		$log->write("\n" . $this->account->lp_provider_name . "\nFrom Pincode: " . $from_pincode . "\n" . "To Pincode: " . $to_pincode . "\n" . "Weight: " . $weight . "\n" . "Is Cod: " . $is_cod . "\n" . "Mode: " . $mode . "\n" . "Order Id: " . $order_id . "\n" . "Is Return: " . $is_return . "\n" . "Return Recommended: " . $return_recommended . "\nResponse:\n" . json_encode($response), 'shopify-pincode-serviceability');
		if (isset($response["status"]) && $response["status"] === (int)"200") {
			if ($return_recommended) {
				if (is_null($response['data']['recommended_courier_company_id']))
					$response['data']['recommended_courier_company_id'] = $response['data']['available_courier_companies'][0]['courier_company_id'];

				$key = array_search($response['data']['recommended_courier_company_id'], array_column($response['data']['available_courier_companies'], 'courier_company_id'));
				return array(
					'status' => 200,
					'courier_id' => $response['data']['recommended_courier_company_id'],
					'courier' => $response['data']['available_courier_companies'][$key]
				);
			}
			return $response['data']['available_courier_companies'];
		} else {
			return array('type' => 'error', 'message' => 'Error fetching courier details', 'error' => $response);
		}
	}

	public function generateManifest($shipmentIds)
	{
		global $db, $log;

		// foreach ($account_shipments as $account => $shipmentIds) {
		$response = $this->generateManifests($shipmentIds);
		$log->write("\n" . json_encode($shipmentIds) . "\n" . json_encode($response), 'shopify-manifest');
		if (isset($response['already_manifested_shipment_ids'])) {
			$db->where('lpShipmentId', $response['already_manifested_shipment_ids'], 'IN');
			$order_ids = $db->getValue(TBL_FULFILLMENT, 'lpOrderId', NULL);
			$response = $this->printManifests($order_ids);
			$response['status'] = 1;
		}
		if ($response['status'] === 1) {
			$manifest_parse = parse_url($response['manifest_url']);
			$manifest_id = str_replace(array('/kr-shipmultichannel/', '/manifest/bulk/', '.pdf'), array('', '_', ''), $manifest_parse["path"]);
			foreach ($shipmentIds as $shipmentId) {
				$db->where('lpShipmentId', $shipmentId);
				$db->update(TBL_FULFILLMENT, array('lpManifestId' => $manifest_id));
			}
			return array("type" => "success", "message" => "Manifest Successfully generated", "manifest_url" => $response['manifest_url']);
		} else {
			if (isset($response['already_manifested_shipment_ids']) && count($response['already_manifested_shipment_ids']) === count($shipmentIds))
				return array("type" => "info", "message" => "Manifest already generated for requested orders.");
			else
				return array("type" => "error", "message" => $response['message'], "error" => $response);
		}
		// }
	}

	public function cancelOrder($order_ids, $cancel_reason = "")
	{
		global $log;

		$log->write("\Order Id: " . $order_ids . "\nReason: " . $cancel_reason, 'shiprocket-cancel-order');
		return $this->request('post', 'orders/cancel/', array('ids' => $order_ids));
	}

	public function cancelShipment($trackingIds, $cancel_reason = "")
	{
		global $log;

		$log->write("\Tracking Id: " . $trackingIds . "\nReason: " . $cancel_reason, 'shiprocket-cancel-shipment');
		return $this->request('post', 'orders/cancel/shipment/awbs', array('awbs' => $trackingIds));
	}

	public function checkServiceability($pickup_postcode, $delivery_postcode, $weight = 0, $is_cod = 0, $order_id = null, $is_return = 0, $mode = "")
	{
		return $this->request(
			'get',
			'courier/serviceability?' .
				'pickup_postcode=' . $pickup_postcode . '&' .
				'delivery_postcode=' . $delivery_postcode . '&' .
				'weight=' . $weight . '&' .
				'cod=' . $is_cod . '&' .
				'is_return=' . $is_return .
				($mode == "" ? "" : '&mode=' . $mode) .
				(is_null($order_id) ? "" : "&order_id=" . $order_id)
		);
	}

	public function createReturn($order, $requestPickup = false, $is_rma = false)
	{
		global $db, $log, $current_user;
		$data = array(
			'order_id' => $is_rma ? 'RMA-' . $order->orderNumberFormated : 'R-' . $order->orderNumberFormated,
			'order_date' => date('Y-m-d', time()),
			"channel_id" => $this->account->lp_channel_id,
			'pickup_customer_name' => isset($order->pickupAddress->name) ? $order->pickupAddress->name : $order->pickupAddress->firstName,
			'pickup_address' => isset($order->pickupAddress->address1) ? $order->pickupAddress->address1 : $order->pickupAddress->addressLine1,
			'pickup_address_2' => isset($order->pickupAddress->address2) ? $order->pickupAddress->address2 : $order->pickupAddress->addressLine2,
			"pickup_city" => $order->pickupAddress->city,
			"pickup_state" => isset($order->pickupAddress->province) ? $order->pickupAddress->province : $order->pickupAddress->state,
			"pickup_country" => isset($order->pickupAddress->country) ? $order->pickupAddress->country : 'India',
			"pickup_pincode" => isset($order->pickupAddress->zip) ? $order->pickupAddress->zip : 411015,
			"pickup_email" => $order->pickupAddress->email,
			"pickup_phone" => substr(str_replace(array('+', ' '), '', $order->pickupAddress->phone), -10),
			"pickup_isd_code" => "91",
			"shipping_customer_name" => isset($order->returnAddress->company) ? $order->returnAddress->company : '',
			"shipping_address" => isset($order->returnAddress->address1) ? $order->returnAddress->address1 : '',
			"shipping_address_2" => $order->returnAddress->address2,
			"shipping_city" => $order->returnAddress->city,
			"shipping_country" => $order->returnAddress->country,
			"shipping_pincode" => $order->returnAddress->zip,
			"shipping_state" => $order->returnAddress->province,
			"shipping_email" => $order->returnAddress->email,
			"shipping_isd_code" => "91",
			"shipping_phone" => $order->returnAddress->phone,
			"order_items" => $order->order_items,
			// "order_items" => array(
			// 	array(
			// 		"sku" => $order->sku,
			// 		"name" => $order->title,
			// 		"units" => $order->r_quantity,
			// 		"selling_price" => ($order->sellingPrice * $order->r_quantity),
			// 		"discount" => 0,
			// 		"hsn" => isset($order->hsn) ? $order->hsn : '0',
			// 		"qc_enable" => $order->qcEnable,
			// 		"qc_brand" => $order->returnAddress->brand,
			// 		// "qc_serial_no" => json_decode($return->r_uid)[0],
			// 		// "qc_product_image" => "https://s3-ap-southeast-1.amazonaws.com/kr-multichannel/1636713733zxjaV.png" // TODO: ADD FULL IMAGE COLUMN TO THE PRODUCT MASTER TABLE AND UPLOAD ALL THE HD IMAGES
			// 	)
			// ),
			"payment_method" => "PREPAID",
			"total_discount" => (isset($order->discount) ? $order->discount : 0),
			"sub_total" => $order->sub_total,
			"length" => "10",
			"breadth" => "10",
			"height" => "10",
			"weight" => "0.200",
		);

		// var_dump($data);
		// var_dump($order);
		// exit;

		$response = $this->request('post', 'orders/create/return', $data);
		// $response = json_decode('{"order_id":331315307,"shipment_id":330692874,"status":"RETURN PENDING","status_code":21,"company_name":"Style Feathers"}', 1);
		// $response = json_decode('{"message":"Order id already exists","status_code":400}', 1);

		$log_file = 'shopify-rvp';
		if ($is_rma)
			$log_file = 'rma';
		$log->write("Order Data: " . json_encode($data) . "\nResponse Data: " . json_encode($response), $log_file . '-returns');

		if ($response && ($response['status_code'] == 422 || $response['status_code'] == 400)) {
			$response = $this->findReturnOrder($data['order_id']);
			$response['our_status_code'] = 444; // ALREADY CREATED
		}

		if ($requestPickup && ($response['status_code'] == 21 || $response['our_status_code'] == 444)) {
			// var_dump($response);
			$pickup_order['from_pincode'] = $data['pickup_pincode'];
			$pickup_order['to_pincode'] = $data['shipping_pincode'];
			$pickup_order['weight'] = $data['weight'];
			$pickup_order['lpShipmentId'] = $response['shipment_id'];
			$pickup_order['lpOrderId'] = (isset($response['order_id']) ? $response['order_id'] : $response['id']);
			// REQUEST PICKUP
			$tracking_response = $this->assign_return_tracking_id($pickup_order, $is_rma);
			if ($tracking_response['type'] == "success") {
				// $response['data']['returnId'] = 'R-'.$order->orderNumberFormated;
				$tracking_response['message'] = 'Return successfully created and pickup generated';
				$shipmentDetails = $tracking_response['data']['shipmentDetails'];
				// Fulllfillment
				$fulfilment_data = array(
					'fulfillmentStatus'  => 'start',
					'fulfillmentType'    => ($is_rma ? 'rma_return' : 'return'),
					'orderId'            => $order->orderId,
					'channel'            => $order->marketplace,
					'lpOrderId'          => $pickup_order['lpOrderId'],
					'lpShipmentId'       => $pickup_order['lpShipmentId'],
					'lpManifestId'       => null,
					'lpProvider'         => $this->account->lp_provider_id,
					'lpShipmentDetails'  => $tracking_response['data']['shipmentDetails'],
					'trackingId'         => $tracking_response['data']['trackingId'],
					'logistic'           => $tracking_response['data']['logistic'],
					'shipmentStatus'     => $tracking_response['data']['shipmentStatus'],
					'shippingZone'       => $tracking_response['data']['shippingZone'],
					'shippingSlab'       => $tracking_response['data']['shippingSlab'],
					'shippingFee'        => $tracking_response['data']['shippingFee'],
					'codFees'            => 0,
					'rtoShippingFee'     => $tracking_response['data']['rtoShippingFee'],
					// 'isQcEnabled'		 => (strpos(json_decode($shipmentDetails)['courier_name'], 'QC') !== FALSE) ? 1 : 0,
					'createdBy'          => $current_user['userID'],
					'canceledBy'         => null,
					'cancellationReason' => null,
					'createdDate'        => date('Y-m-d H:i:s'),
					'expectedPickupDate' => $tracking_response['data']['expectedPickupDate'],
					'expectedDate'       => $tracking_response['data']['expectedDate'],
					'deliveredDate'      => null,
					'cancelledDate'      => null,
				);
				$db->insert(TBL_FULFILLMENT, $fulfilment_data);
			}
			return $tracking_response;
		}

		return array('type' => 'error', 'message' => 'Unable to create return order', 'data' => $response);
	}

	private function findReturnOrder($order_id)
	{
		$orders = $this->getReturnOrders();
		$r_order = false;
		foreach ($orders['data'] as $order) {
			if ($order['channel_order_id'] == $order_id)
				$r_order = $order;
		}
		return $r_order;
	}

	private function getReturnOrders()
	{
		return $this->request('get', 'orders/processing/return');
	}

	public function assign_return_tracking_id($order, $is_rma = false)
	{
		global $db, $log;

		$log_file = 'shopify-return-tracking';
		if ($is_rma)
			$log_file = 'rma-returns-tracking';

		$courier = $this->check_courier_serviceability($order['from_pincode'], $order['to_pincode'], $order['weight'], 0, '', $order['lpOrderId'], true, true);
		if ($courier['status'] == 200) {
			$logistic_details = $this->assignAWBs($order['lpShipmentId'], $courier['courier_id']);
			// $logistic_details = json_decode('{"awb_assign_status":1,"response":{"data":{"courier_company_id":125,"awb_code":"243198221230080","cod":0,"order_id":331315307,"shipment_id":330692874,"awb_code_status":1,"assigned_date_time":{"date":"2023-04-06 15:02:15.964390","timezone_type":3,"timezone":"Asia\/Kolkata"},"applied_weight":0.2,"company_id":1628757,"courier_name":"Xpressbees Reverse","child_courier_name":null,"freight_charges":68,"routing_code":"W\/S-25\/2A\/105","rto_routing_code":"","invoice_no":"","transporter_id":"27AAGCB3904P2ZC","transporter_name":"Xpressbees","shipped_by":{"shipper_company_name":"Test Test ","shipper_address_1":"addressline 1","shipper_address_2":"addressline 2","shipper_city":"SURAT","shipper_state":"GUJARAT","shipper_country":"India","shipper_postcode":"395007","shipper_first_mile_activated":0,"shipper_phone":"9099925025","lat":null,"long":null,"shipper_email":"ishan576@testing.com","rto_company_name":"Test Test ","rto_address_1":"addressline 1","rto_address_2":"addressline 2","rto_city":"SURAT","rto_state":"GUJARAT","rto_country":"India","rto_postcode":"395007","rto_phone":"9099925025","rto_email":"ishan576@testing.com"},"pickup_scheduled_date":"2023-04-07 09:00:00","pickup_status":1}},"no_pickup_popup":0,"quick_pick":0}', 1);
			// $logistic_details = json_decode('{"message":"Oops! Cannot reassign courier for this shipment for 18 hour(s).","status_code":400}', 1);
			$already_assisgned = ((isset($logistic_details['awb_assign_status']) && $logistic_details['awb_assign_status'] === 0) && $logistic_details['response']['data']['awb_assign_error'] == "AWB is already assigned") ? true : ((isset($logistic_details['status_code']) && $logistic_details['status_code'] === 400 && strpos($logistic_details['message'], 'Cannot reassign courier') !== FALSE) ? true : false);
			$log->write("\nCourier Details: " . json_encode($courier) . "\nLogistic Details: " . json_encode($logistic_details) . "\nOrder Data: " . json_encode($order), $log_file);
			if (isset($logistic_details['awb_assign_status']) && $logistic_details['awb_assign_status'] === (int)"1") {
				$shipmentDetails = $this->getShipment($order['lpShipmentId']);
				$data = array(
					'shipmentStatus' => 'awb_assigned',
					'logistic' => (!empty($logistic_details['response']['data']['transporter_name']) ? $logistic_details['response']['data']['transporter_name'] : $logistic_details['response']['data']['courier_name']),
					'trackingId' => $logistic_details['response']['data']['awb_code'],
					'shipmentDetails' => json_encode($logistic_details['response']['data']),
					'shippingFee' => $courier['courier']['freight_charge'],
					'rtoShippingFee' => $courier['courier']['rto_charges'],
					'expectedDate' => date('Y-m-d', strtotime('+16 day')) . ' 00:00:00',
					'expectedPickupDate' => date('Y-m-d H:i:s', strtotime($logistic_details['response']['data']['pickup_scheduled_date'])),
					'shippingZone' => $shipmentDetails['data']['charges']['zone'],
					'shippingSlab' => $shipmentDetails['data']['charges']['applied_weight']
				);
				// $this->requestPickup(array($order['lpShipmentId']));
				return array('type' => 'success', 'message' => 'Return successfully created and pickup generated', 'data' => $data);
			} else if ($already_assisgned) {
				$shipmentDetails = $this->getShipment($order['lpShipmentId']);
				$shipmentDetails['data']['shipment_id'] = $shipmentDetails['data']['id'];
				$data = array(
					'shipmentStatus' => 'awb_assigned',
					'logistic' => $shipmentDetails['data']['courier'],
					'trackingId' => $shipmentDetails['data']['awb'],
					'shipmentDetails' => json_encode($shipmentDetails['data']),
					'shippingFee' => $shipmentDetails['data']['charges']['freight_charges'],
					'rtoShippingFee' => $courier['courier']['rto_charges'],
					'expectedDate' => date('Y-m-d', strtotime('+16 day')) . ' 00:00:00',
					'expectedPickupDate' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00',
					'shippingZone' => $shipmentDetails['data']['charges']['zone'],
					'shippingSlab' => $shipmentDetails['data']['charges']['applied_weight']
				);
				return array('type' => 'success', 'message' => 'Return pickup generated', 'data' => $data);
			} else {
				return array('type' => 'error', 'message' => 'Error generating pickup', 'error' => $logistic_details);
			}
		} else {
			$log->write("\nCourier Details: " . json_encode($courier) . "\nOrder Data: " . json_encode($order), $log_file);
			return array('type' => 'error', 'message' => $courier['message'] . ' - ' . $courier['error']['message'], 'error' => $courier['error']['message']);
		}
	}
}

global $account;
$GLOBALS['logisticPartner'] = new ShiprocketClientConnector([
	'email' 		=> $account->lp_provider_email,
	'password' 		=> $account->lp_provider_password,
	'use_sandbox'	=> 0
], $account);
