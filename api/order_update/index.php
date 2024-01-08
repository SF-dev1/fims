<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once(dirname(dirname(__FILE__)) . '/header-option.php');
include_once(ROOT_PATH . '/includes/connectors/shopify/shopify.php');
include_once(ROOT_PATH . '/includes/connectors/shopify/shopify-dashboard.php');
include_once(ROOT_PATH . '/shopify/functions.php');
ignore_user_abort(true); // optional
global $log, $db, $accounts, $account, $logisticPartner;

$update_type = $_GET['update_type'];
$data = json_decode(file_get_contents("php://input"), 1);
if (isset($data['orderNumber'])) {
	$orderNumber = $data['orderNumber'];
	$db->where('orderId', $orderNumber);
}

if (isset($data['orderNumberFormated'])) {
	$orderNumberFormated = $data['orderNumberFormated'];
	$db->where('orderNumberFormated', $orderNumberFormated);
}
$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS);
$accountId = $order->accountId;
if ($accountId) {
	foreach ($accounts['shopify'] as $sp_account) {
		if ($sp_account->account_id == $accountId) {
			$account = $sp_account;
		}
	}
}

if ($account) {
	if ($update_type == "address") {
		$sp = new shopify_dashboard($account);
		$order_details = $sp->shopify->Order($order->orderId)->get();
		if (strpos($order_details['tags'], 'update_address') !== FALSE) {
			$tags = explode(', ', $order_details['tags']);
			if (($key = array_search('update_address', $tags)) !== false) {
				unset($tags[$key]);
			}
		}
		$updateInfo = array(
			"shipping_address" => array(
				"address1" => $data['address1'],
				"address2" => $data['landmark'] . ' ' . $data['address2'],
				"city" => $data['city'],
				"province" => $data['state'],
				"zip" => $data['zip']
			),
			'tags' => $tags
		);
		$update = $sp->update_order_sp($order->orderId, $updateInfo);
		$result = array_diff(array_intersect($update['shipping_address'], $updateInfo['shipping_address']), $updateInfo['shipping_address']);
		if (empty($result)) {
			$db->where('orderId', $order->orderId);
			$delivery_address = json_decode($db->getValue(TBL_SP_ORDERS, 'deliveryAddress'));
			$delivery_address->address1 = $data['address1'];
			$delivery_address->address2 = $data['landmark'] . ' ' . $data['address2'];
			$delivery_address->city = $data['city'];
			$delivery_address->province = $data['province'];
			$delivery_address->zip = substr($data['zip'], 0, 6);
			$db->where('orderId', $order->orderId);
			$db->update(TBL_SP_ORDERS, array('deliveryAddress' => json_encode($delivery_address)));
			$log->write('SUCCESS ' . json_encode($data), '/api/order-update');
			$comment_data = array(
				'comment' => 'Customer updated address via WhatsApp',
				'orderId' => $order->orderId,
				'commentFor' => 'address_updated',
				'userId' => 0,
			);
			if ($db->insert(TBL_SP_COMMENTS, $comment_data))
				echo json_encode(array('status' => 'success', 'message' => 'update'));
			else
				echo $db->getLastError() . ' Query:' . $db->getLastQuery();
		} else {
			$log->write('FAILURE - Same Address ' . json_encode($data), '/api/order-update');
			echo json_encode(array('status' => 'success', 'message' => 'address same'));
		}
	}

	if ($update_type == "return_tracking") {
		$shipment_data = json_decode($data['tracking_data'], 1)['response']['data'];
		if ($data['update_for'] == "return") {
			$update = array(
				'r_courierName' => $shipment_data['courier_name'],
				'r_trackingId' => $shipment_data['awb_code'],
				'r_lpOrderId' => $shipment_data['order_id'],
				'r_lpShipmentId' => $shipment_data['shipment_id'],
				'r_lpShipmentDetails' => json_encode($shipment_data),
				'r_reverseShippingFee' => $shipment_data['freight_charges'],
			);

			$db->where('r_source', 'customer_return');
			$db->where('r_status', 'pickup_creation_pending');
			$db->where('r_shipmentStatus', 'start');
			$db->where('orderItemId', $order->orderItemId);
			if ($db->update(TBL_SP_RETURNS, $update)) {
				$log->write("TRACKING UPDATE SUCCESS: \nOID: " . $order->orderItemId . "\nData: " . json_encode($data) . "\nUpdate: " . json_encode($update), "/api/order-update");
				echo json_encode(array('status' => 'success', 'message' => 'Return tracking successfully updated for ' . $data['orderNumberFormated']));
			} else {
				$log->write("TRACKING UPDATE ERROR: \nOID: " . $order->orderItemId . "\nData: " . json_encode($data) . "\nUpdate: " . json_encode($update) . "\n Error:" . $db->getLastQuery() . "\n" . $db->getLastError(), "/api/order-update");
				echo json_encode(array('status' => 'error', 'message' => 'Error updating tracking details for ' . $data['orderNumberFormated']));
			}
		}
	}
} else {
	$log->write('FAILURE: ' . json_encode($data), '/api/order-update');
	echo json_encode(array('status' => 'error', 'message' => 'required parameters not set'));
}
exit;
