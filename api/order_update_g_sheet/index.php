<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
// include_once(dirname(dirname(__FILE__)).'/header-option.php');
// include_once(ROOT_PATH.'/includes/connectors/shopify/shopify.php');
// include_once(ROOT_PATH.'/includes/connectors/shopify/shopify-dashboard.php');
// include_once(ROOT_PATH.'/shopify/functions.php');
ignore_user_abort(true); // optional
global $log, $db, $accounts;

if ($_SERVER['REQUEST_METHOD'] == "post")
	$data = json_decode(file_get_contents("php://input"), 1);
else
	$data = json_decode($_GET['data'], 1);

$updatType = strtolower($data['updateType']);
$marketplace = $data['marketplace'];
$trackingId = $data['trackingId'];
$orderId = $data['orderId'];
$shipmentId = $data['shipmentId'];
$uid = $data['uid'];

// $response = array('type' => 'error', 'message' => 'Unable to update log OrderID: '.$orderId.' UID: '.$uid);
// exit(json_encode($response));
// exit;

if (!empty($uid)) {
	$db->where('inv_id', $uid);
	$item = $db->getOne(TBL_INVENTORY, 'inv_id, inv_status');
	if ($item) {
		if (($updatType == "sales" && $item['inv_status'] == "qc_verified") || ($updatType == "return" && $item['inv_status'] == "sales")) {
			$comments = $marketplace . ' Sales :: Order Id: ' . $orderId;
			$current_user['userID'] = 10; // REQUIRED TO ADD LOG
			if ($updatType == "return")
				$comments = $marketplace . ' Sales Return :: Order Id: ' . $orderId;

			if (!empty($trackingId))
				$comments .= ' :: Tracking Id: ' . $trackingId;

			if (!empty($shipmentId))
				$comments .= ' :: Shipment Id: ' . $shipmentId;

			if ($updatType == "sales") {
				$inv_status = $stockist->update_inventory_status($uid, 'sales'); // CHANGE UID STATUS
				$log_status = $stockist->add_inventory_log($uid, 'sales', $comments); // ADD TO LOG
			}

			if ($updatType == "return") {
				$inv_status = $stockist->update_inventory_status($uid, 'qc_pending'); // CHANGE UID STATUS
				$log_status = $stockist->add_inventory_log($uid, 'sales_return', $comments); // ADD TO LOG
			}

			if ($inv_status && $log_status)
				$response = array('type' => 'success', 'message' => 'Successfully updated log OrderID: ' . $orderId . ' UID: ' . $uid);
			else
				$response = array('type' => 'error', 'message' => 'Unable to update log OrderID: ' . $orderId . ' UID: ' . $uid);
		} elseif ($updatType == "sales" && $item['inv_status'] != "qc_verified") {
			$response = array(
				'type' => 'error',
				'message' => 'Product not QC Verified'
			);
		} elseif ($updatType == "return" && $item['inv_status'] != "sales") {
			$response = array(
				'type' => 'error',
				'message' => 'Product not Sales'
			);
		}

		if ($updatType == "sales" && $item['inv_status'] == "sales") {
			$response = array(
				'type' => 'error',
				'message' => 'Product already marked Sales'
			);
		}
		if ($updatType == "return" && $item['inv_status'] == "qc_pending") {
			$response = array(
				'type' => 'error',
				'message' => 'Product already Sales Return'
			);
		}
	} else {
		$response = array(
			'type' => 'error',
			'message' => 'Invalid UID'
		);
	}

	$log->write($response, 'g-sheet');
	exit(json_encode($response));
} else {
	$log->write('Invalid Request. Data: ' . $data, 'g-sheet');
	header('HTTP/1.1 404 Not Found', true, 404);
	exit;
}
