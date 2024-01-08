<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once(dirname(dirname(__FILE__)) . '/header-option.php');

global $log;
$log->write(json_encode(apache_request_headers()) . "\n" . json_encode($_REQUEST), '/api/order-search');

$marketplace = trim($_REQUEST['marketplace']);
if (strpos(strtolower($marketplace), 'sylvi') !== FALSE)
	$marketplace = 'sylvi';

$order_id = $_REQUEST['order_id'];
$response = array(
	'type' => true,
	'order' => false,
	'orderId' => $order_id,
	'marketplace' => ucfirst($marketplace),
	'invoiceDate' => null,
	'deliveredDate' => null,
	'exDeliveryDate' => null,
	'returnable' => false,
	'warrantyRegistered' => false,
	'inWarranty' => false
);

if ($marketplace == "Amazon") {
	$mp_table = TBL_AZ_ORDERS;
	$db->where('orderId', $order_id);
	$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderId", "LEFT");
	$fields = 'o.orderId, o.shippedDate as invoiceDate, registration_id';
}
if ($marketplace == "Flipkart") {
	$mp_table = TBL_FK_ORDERS;
	$db->where('orderId', $order_id);
	$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderId", "LEFT");
	$fields = 'o.orderId, o.invoiceDate, registration_id';
}
if ($marketplace == "sylvi") {
	$mp_table = TBL_SP_ORDERS;
	$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
	$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderNumberFormated", "LEFT");
	$db->where('(o.orderNumber LIKE ? OR o.orderNumberFormated LIKE ?)', array($order_id, $order_id));
	$fields = 'invoiceNumber, deliveredDate, expectedDate, invoiceDate, registration_id';
}


$order = $db->getOne($mp_table . ' o', $fields);
if ($order) {
	$response['order'] = true;
	$response['invoiceDate'] = $order['invoiceDate'];
	$response['exDeliveryDate'] = $order['expectedDate'];

	if ($marketplace == "sylvi" && strtotime($order['deliveredDate'] . ' - 10 days') > time()) {
		$response['returnable'] = true;
		$response['deliveredDate'] = $order['deliveredDate'];
	}

	if ($order['registration_id'])
		$response['warrantyRegistered'] = true;

	if (strtotime($order['invoiceDate'] . ' + 6 months') > time())
		$response['inWarranty'] = true;
}

echo json_encode($response);
$log->write("Response: " . json_encode($response), '/api/order-search');
