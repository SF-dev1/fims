<?php
include_once(dirname(dirname(__FILE__)) . "/config.php");

class IThinkClient
{
	private $accessToken;
	private $secretKey;
	private $lpProviderId;
	private $trackingURL;
	const API_URL = 'https://pre-alpha.ithinklogistics.com/api_v3/';

	public function __construct($email, $password)
	{
		global $db;
		$db->where('lp_provider_email', $email);
		$db->where('lp_provider_password', $password);
		$data = $db->getOne(TBL_SP_LOGISTIC, 'lp_provider_token, lp_provider_signing_secret, lp_provider_id, lp_provider_tracking_url');
		$this->accessToken = $data["lp_provider_token"];
		$this->secretKey = $data["lp_provider_signing_secret"];
		$this->lpProviderId = $data["lp_provider_id"];
		$this->trackingURL = $data["lp_provider_tracking_url"];
	}

	private function sendRequest($url, $type, $data)
	{
		// add token to data
		$data["data"]['access_token'] = $this->accessToken;
		$data["data"]['secret_key'] = $this->secretKey;

		// send request to iThink
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => self::API_URL . $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => strtoupper($type),
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	public function createOrder($orders)
	{
		global $log;
		// get first order
		$first_order = $orders[0];
		// get delivery address
		$delivery_address = json_decode($first_order->deliveryAddress);

		// set data
		$data = array(
			"data" => array(
				"shipments" => array(
					"waybill" => "",
					"order" => $first_order->orderNumberFormated,
					"sub_order" => "A",
					"order_date" => date('d-m-Y', strtotime($first_order->orderDate)), //dd-mm-yyyy 
					"total_amount" => 0,
					"name" => $delivery_address->first_name . ' ' . $delivery_address->last_name,
					"company_name" => $this->account->company_name,
					"add" => $delivery_address->address1,
					"add2" => $delivery_address->address2,
					"add3" => "",
					"pin" => str_replace(' ', '', $delivery_address->zip),
					"city" => $delivery_address->city,
					"state" => $delivery_address->province,
					"country" => $delivery_address->country,
					"phone" => str_replace(array(" ", "+91"), "", $delivery_address->phone),
					"alt_phone" => str_replace(array(" ", "+91"), "", $delivery_address->phone),
					"email" => $delivery_address->email,
					"is_billing_same_as_shipping" => "yes",
					"products" => array(),
					"shipment_length" => "10",
					"shipment_width" => "10",
					"shipment_height" => "10",
					"weight" => "0.200",
					"shipping_charges" => "0",
					"giftwrap_charges" => "0",
					"transaction_charges" => "0",
					"total_discount" => "0",
					"first_attemp_discount" => "0",
					"cod_charges" => "0",
					"advance_amount" => "0",
					"cod_amount" => "",
					"payment_mode" => strtoupper($first_order->paymentType),
					"gst_number" => "",
					"return_address_id" => ""
				),
				"pickup_address_id" => "",
				"s_type" => "",
				"order_type" => ""
			)
		);

		// add products
		foreach ($orders as $order) {
			if ($order->status == "cancelled" || $order->spStatus == "cancelled")
				continue;
			$data['data']['shipments']['products'][] = array(
				"product_name" => $order->title,
				"product_sku" => $order->sku,
				"product_quantity" => $order->quantity,
				"product_price" => $order->sellingPrice,
				"product_tax_rate" => $order->hsn_rate,
				"product_hsn_code" => $order->hsn_code,
				"product_discount" => "0"
			);
			$data['data']['shipments']['total_amount'] += ($order->sellingPrice * $order->quantity);
			$data['data']['shipments']['total_discount'] += ($order->spDiscount * $order->quantity) + $order->giftCardAmount;
		}

		//get shipping rate
		$data = array(
			"data" => array(
				"from_pincode" => "394105",
				"to_pincode" => str_replace(' ', '', $delivery_address->zip),
				"shipping_length_cms" => "10",
				"shipping_width_cms" => "10",
				"shipping_height_cms" => "10",
				"shipping_weight_kg" => "0.200",
				"order_type" => "forward",
				"payment_method" => strtolower($first_order->paymentType),
				"product_mrp" => $data['data']['shipments']['total_amount']
			)
		);
		$response = $this->sendRequest('rate/check.json', 'post', $data);
		$response = json_decode($response, true);
		if ($response["status_code"] == 200) {
			$logisticName = "";
			$rate = 100000;
			for ($i = 0; $i < count($response["data"]); $i++) {
				if ($response["data"][$i]["rate"] < $rate && $response["data"][$i]["rev_pickup"] == "Y" && (((strtolower($first_order->paymentType) == "cod") ? ($response["data"][$i]["cod"] == "Y") : true))) {
					$rate = $response["data"][$i]["rate"];
					$logisticName = $response["data"][$i]["logistic_name"];
				}
			}
		}
		// set logistic
		$data['data']['logistics'] = $logisticName;
		// set zone
		$zone = $response["zone"];
		// get expected delivery date
		$pattern = '/\d+\s+to\s+(\d+\s+Days)/';
		if (preg_match($pattern, $response["expected_delivery_date"], $matches))
			$dtStr = $matches[1];
		$expDate = date('Y-m-d H:i:s', strtotime($dtStr));

		// send request
		$response = $this->sendRequest('order/add.json', 'post', $data);
		// log response
		$log->write((":: " . date("d M, Y H:m:i") . "\n:: iThink create order.\n:: Response:" . $response), 'shopify-order-create');
		// create return
		$response = json_decode($response, true);
		$return  = array(
			"type" => $response["status"],
			"awb_numbers" => $response["data"][1]["waybill"],
			"locationId" => $first_order->locationId,
			"lpOrderId" => $response["data"][1]["refnum"],
			"order_id" => $first_order->orderNumberFormated,
			"zone" => $zone,
			"rate" => $rate,
			"courier_name" => $logisticName,
			"expectedDate" => $expDate
		);
		return $return;
	}

	public function generateLabel($order)
	{
		// set data
		$data = array(
			"data" => array(
				"awb_numbers" => $order["awb_numbers"],
				"page_size" => "A6",
				"display_cod_prepaid" => "",
				"display_shipper_mobile" => "",
				"display_shipper_address" => "",
			)
		);
		$response = $this->sendRequest('shipping/label.json', 'post', $data);

		global $db, $log, $current_user;

		$assigned_tracking = $this->assignTrackingId($order); // ASSIGN TRACKING ID
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
					'lpProvider' => $this->lpProviderId,
					'trackingId' => $assigned_tracking['data']['awb_code'],
					'logistic' => $assigned_tracking['data']['courier_name'],
					'shipmentStatus' => 'awb_assigned',
					'shippingZone' => $order['zone'],
					'shippingSlab' => null,
					'shippingFee' => $order["courier"]["shipping_charges"],
					'codFees' => $assigned_tracking['courier']['cod_charges'], // pending
					'rtoShippingFee' => $order["courier"]["rto_charges"], // pending
					'createdBy' => $current_user['userID'],
					'createdDate' => $db->now(),
					'lpShipmentDetails' => json_encode($assigned_tracking['data']),
					'expectedDate' => isset($order["expectedDate"]) ? ($order["expectedDate"]) : date('Y-m-d H:i:s', strtotime('+7 weekdays'))
				);
				$log->write(json_encode($data), 'shopify-tracking-test');
				$db->insert(TBL_FULFILLMENT, $data);
			}
			// update order status
			$db->where('order_id', $order['order_id']);
			$db->where('status', 'cancelled', '!=');
			$db->update(TBL_SP_ORDERS, array('labelGeneratedDate' => $db->now()));
			// log response
			$log->write((":: " . date("d M, Y H:m:i") . "\n:: iThink generate label.\n:: Response:" . $response), 'shopify-label-generate');
		}

		// create return
		$return = array(
			"data" => array(
				"awb_code" => $assigned_tracking['data']['awb_code'],
				"courier_name" => $assigned_tracking['data']['courier_name'],
				"tracking_url" => $this->trackingURL . $assigned_tracking['data']['awb_code'],
			),
			"locationId" => ""
		);
		return $return;
	}

	public function assignTrackingId($awb_number)
	{
		global $log;
		// set data
		$data["data"]["awb_number_list"] = $awb_number;
		$response = json_decode($this->sendRequest('order/get_details.json', 'post', $data), true);
		// log response
		$log->write((date("d M, Y H:m:i") . "\n:: iThink assign tracking id.\n:: Response:" . json_encode($response)), 'shopify-assign-tracking');
		// create return
		$return  = array(
			"type" => $response["status"],
			"data" => array(
				"awb_code" => $response["data"][$awb_number]["awb_no"],
				"courier_name" => $response["data"][$awb_number]["logistic"],
			),
			"courier" => array(
				"cod_charges" => $response["data"][$awb_number]["billing_cod_charges"],
				"shipping_charges" => $response["data"][$awb_number]["shipping_charges"],
				"rto_charges" => $response["data"][$awb_number]["billing_rto_charges"],
			),
		);

		return $return;
	}

	public function cancelOrder($awb_numbers)
	{
		global $log;

		$data = array(
			"data" => array(
				"awb_numbers" => implode(",", $awb_numbers)
			)
		);
		$log->write("\n Cancel Orders \n:: AWB Code List : " . json_encode($data["data"]["awb_numbers"]), 'iThink-cancel-order');
		return $this->sendRequest('order/cancel.json', "post", $data);
	}

	public function generateManifest($awb_numbers)
	{
		global $db, $log;
		// set data
		$data = array(
			"data" => array(
				"awb_numbers" => implode(",", $awb_numbers)
			)
		);

		$response = json_decode($this->sendRequest("shipping/manifest.json", "post", $data), true);
		$log->write("\nManifest Generated\n:: AWB Numbers : " . json_encode($awb_numbers) . "\n:: Response : " . json_encode($response), 'shopify-manifest');
		if ($response['status'] == "success") {
			$manifest_parse = parse_url($response['file_name']);
			$manifest_id = str_replace(array('https://pre-alpha.ithinklogistics.com/uploads/shipping/', '.pdf'), array('', ''), $manifest_parse["path"]);
			foreach ($awb_numbers as $awb_number) {
				$db->where('trackingId', $awb_number);
				$db->update(TBL_FULFILLMENT, array('lpManifestId' => $manifest_id));
			}
			return array("type" => "success", "message" => "Manifest Successfully generated", "manifest_url" => $response['file_name']);
		} else {
			return array("type" => "error", "message" => $response['message'], "error" => $response);
		}
	}

	public function getRemittance($date)
	{
		// set data
		$data = array(
			"data" => array(
				"date" => $date
			)
		);

		// send request
		$response = json_decode($this->sendRequest("remittance/get_details.json", "post", $data), true);
		if ($response["status"] == "success") {
			// insert data into database
			foreach ($response["data"] as $data) {
				$insert_data = array(
					"remittanceDate" => $date,
					"remittedAmount" => $data["price"],
					"orderId" => $data["order_no"],
					"deliveredDate" => $data["delivered_date"],
				);
			}
		}
	}
}

global $accounts;
$GLOBALS['logisticPartner'] = new IThinkClient(
	$account->lp_provider_email,
	$account->lp_provider_password
);

// $test = new IThinkClient(
// 	"stylefeathersithink@gmail.com",
// 	"Ishan576!"
// );

// $test->assignTrackingId([1333110029843,1333110029844,1333110029845,1333110029846,1333110029847]);