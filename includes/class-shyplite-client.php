<?php

use Adil\Shyplite\Shyplite;

class ShypliteClientConnector extends Shyplite
{
	private $account;

	public function __construct(array $config, $account)
	{
		$this->account = $account;
		parent::__construct($config);
	}

	public function createOrder($orders)
	{
		global $db, $log;

		$first_order = $orders[0];
		$delivery_address = json_decode($first_order->deliveryAddress);
		$post_data = array(
			"orderId" => $first_order->orderNumberFormated,
			"customerName" => $delivery_address->name,
			"customerAddress" => $delivery_address->address1 . ' ' . $delivery_address->address2,
			"customerCity" => $delivery_address->city,
			"customerPinCode" => $delivery_address->zip,
			"customerContact" => ((int)str_replace(" ", "", $delivery_address->phone)),
			"orderType" => strtoupper($first_order->paymentType), // COD/PREPAID,
			"modeType" => "Air",
			"orderDate" => date('Y-m-d', strtotime($first_order->orderDate)), // YYYY-MM-DD
			"package" => array(
				"itemLength" => 10,
				"itemWidth" => 10,
				"itemHeight" => 10,
				"itemWeight" => 0.200,
			),
			"totalValue" => 0,
			"sellerAddressId" => 49973 // MUST BE MADE DYNAMIC
		);

		foreach ($orders as $order) {
			$post_data['totalValue'] += ($order->sellingPrice + $first_order->shippingCharge);
			// $post_data['transaction_charges'] += $order->shippingCharge;
			$post_data["skuList"][] = array(
				"sku" => $order->sku,
				"itemName" => $order->title,
				"quantity" => $order->quantity,
				"price" => $order->sellingPrice,
				"itemLength" => null, //optional
				"itemWidth" => null, //optional
				"itemHeight" => null, //optional
				"itemWeight" => null //optional
			);
		}

		try {
			$responses = $this->order()->create([$post_data]);
			$log->write("data:" . json_encode($post_data) . "\n" . json_encode($responses), 'shopify-orders');
		} catch (GuzzleHttp\Exception\ClientException $e) {
			$responses = json_decode($e->getResponse()->getBody()->getContents());
			$log->write("Error data:" . json_encode($post_data) . "\n" . json_encode($responses), 'shopify-orders');
			if (strpos($responses[0]->error, 'Order already Exist') !== FALSE) {
				return array('type' => 'success', 'order_id' => $first_order->orderId, 'invoiceNumber' => $first_order->invoiceNumber, 'orderNumberFormated' => $first_order->orderNumberFormated,  'lpProvider' => $this->account->lp_provider_id, 'locationId' => $first_order->locationId);
			}
			return array('type' => 'error', 'message' => $responses[0]->error);
		}

		foreach ($responses as $response) {
			if (isset($response->error)) {
				if (strpos($response->error, 'Order already Exist') !== FALSE) {
					continue;
				} else {
					return array('type' => 'error', 'message' => 'Error adding order to LP', 'error' => $response->error);
				}
			}
			$data = array(
				'lpOrderId' => $response->id,
				'lpShipmentId' => $response->id . 'S',
				'lpProvider' => $this->account->lp_provider_id,
				'locationId' => $first_order->locationId
			);
			$db->where('invoiceNumber', $first_order->invoiceNumber);
			if ($db->update(TBL_SP_ORDERS, $data)) {
				$log->write("data:" . json_encode($data), 'shopify-orders');
				return array_merge(array('type' => 'success', 'order_id' => $first_order->orderId, 'orderNumberFormated' => $first_order->orderNumberFormated), $post_data, $data);
			} else {
				return array('type' => 'error', 'message' => 'Unable to update Order Details', 'error' => $db->getLastError());
			}
		}
	}

	public function generateLabel($order)
	{
		global $db, $log;

		$order_id = isset($order['order_id']) ? $order['order_id'] : $order['orderId'];
		sleep(4);
		try {
			$response = $this->shipment()->getSlip($order['orderNumberFormated']);
			$log->write("\n" . json_encode($order) . " :: " . json_encode($response), 'shopify-label');
		} catch (GuzzleHttp\Exception\ClientException $e) {
			$response = json_decode($e->getResponse()->getBody()->getContents());
			$log->write("Error Data:" . json_encode($order) . " :: " . json_encode($response), 'shopify-label');
			return array('type' => 'error', 'message' => $response);
		}
		$deliveryDetails = array("trackingId" => $response->awbNo, "vendorName" => $response->carrierName);
		$label = file_get_contents($response->path);
		if ($label) {
			if (strncmp($label, "%PDF-", 4) === 0) {
				$path = ROOT_PATH . '/labels/single/';
				$filename = 'FullLabel-Shopify-' . $order_id . '.pdf';
				if (file_put_contents($path . $filename, $label)) {
					$data = array(
						'forwardTrackingId' => $response->awbNo,
						'forwardLogistic' => $response->carrierName,
						'deliveryDetails' => json_encode($deliveryDetails),
						'lpManifestId' => $response->manifestID,
						'lpProvider' => $this->account->lp_provider_id,
						'labelGeneratedDate' => $db->now()
					);
					$db->where('status', 'cancelled', '!=');
					$db->where('orderNumberFormated', $order['orderNumberFormated']);
					$db->update(TBL_SP_ORDERS, $data);
					return array(
						'type' => 'success',
						'message' => 'Label Generated',
						'data' => array(
							'awb_code' => $response->awbNo,
							'courier_name' => $response->carrierName,
							'tracking_url' => "https://tracklite.in/c/sylviin/" . $response->awbNo
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
	}

	public function check_courier_serviceability($from_pincode, $to_pincode, $weight = "", $is_cod = "", $mode = "air", $is_return = false, $return_recommended = false)
	{
		global $log;
		try {
			$responses = $this->Service()->availability($from_pincode, $to_pincode);
			$log->write("Error Data: \nFrom: " . $from_pincode . "\nTo: " . $to_pincode . "\n" . json_encode($responses), 'shopify-pincode-serviceability');
		} catch (GuzzleHttp\Exception\ClientException $e) {
			$responses = json_decode($e->getResponse()->getBody()->getContents());
			$log->write("Error Data: \nFrom: " . $from_pincode . "\nTo: " . $to_pincode . "\n" . json_encode($responses), 'shopify-pincode-serviceability');
			if (strpos($responses[0]->error, 'Order already Exist') !== FALSE) {
				return array('type' => 'success', 'order_id' => $first_order->orderId, 'invoiceNumber' => $first_order->invoiceNumber,  'lpProvider' => $this->account->lp_provider_id, 'locationId' => $first_order->locationId);
			}
			return array('type' => 'error', 'message' => $responses[0]->error);
		}
	}

	public function generateManifest($account_shipments)
	{
		global $db;

		$manifestIDs = array();
		foreach ($account_shipments as $account => $shipmentIds) {
			$db->where('lpShipmentId', $shipmentIds, 'IN');
			$manifests = $db->get(TBL_SP_ORDERS, NULL, 'DISTINCT(lpManifestId) as manifestId');
		}
		$manifest = implode('-', array_column($manifests, 'manifestId'));
		$response = $this->shipment()->getManifest($manifest);
		if (isset($response->path))
			return array("type" => "success", "msg" => "Manifest Successfully generated", "manifest_url" => $response->path);
		else
			return array("type" => "error", "msg" => "Error generating manifest", "error" => $response);
	}
}

global $account;
$GLOBALS['logisticPartner'] = new ShypliteClientConnector([
	'app_id' => $account->lp_channel_id, // Your app's ID
	'seller_id' => $account->lp_account_id, // Your seller ID
	'key' => $account->lp_provider_token,
	'secret' => $account->lp_provider_hash,
], $account);
