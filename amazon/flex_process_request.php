<?php
if (isset($_SERVER['HTTP_ORIGIN']) && ($_SERVER['HTTP_ORIGIN'] == "https://sellerflex.amazon.in" || $_SERVER['HTTP_ORIGIN'] == "https://beta-sellerflex.amazon.in")) {
	header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');

	include_once(dirname(dirname(__FILE__)) . '/config.php');
	$GLOBALS['current_user'] = NULL;
	switch ($_REQUEST['action']) {
		case 'add_details':
			if (empty($_POST['trackingId']))
				return;

			$current_user = array(
				"userID" => 26, // RAJVI
				"display_name" => "Seller Flex BOT",
				"user_role" => "bot",
				"user_status" => 0,
			);

			$orderId = $_POST['orderId'];
			$db->where('orderId', $orderId);
			if (!$db->has(TBL_AZ_ORDERS)) {
				$response = array("type" => "error", "msg" => "Error Updating order", "error" => "Order Not found " . $orderId);
				$log->write(json_encode($response), 'amazon-flex-orders');
				echo json_encode($response);
				exit;
			}

			$db->where('orderId', $orderId);
			$status = $db->getValue(TBL_AZ_ORDERS, 'status');
			if ($status == "shipped") {
				$response = array("type" => "error", "msg" => "Error Updating order", "error" => "Order already shipped " . $orderId);
				$log->write(json_encode($response), 'amazon-flex-orders');
				echo json_encode($response);
				exit;
			}

			$update_status = array();
			foreach ($_POST['skus'] as $sku) {
				$uids = $sku['uid'];
				$details = array(
					'uid' => json_encode($uids, true),
					'trackingID' => $_POST['trackingId'],
					'courierName' => 'ATS',
					'packageId' => $_POST['packageId'],
					'status' => 'shipped',
					'shipmentStatus' => 'PendingPickUp',
					'fulfilmentSite' => 'ZWCI',
					'labelGeneratedDate' => $db->now(),
					'rtdDate' => $db->now(),
					'shippedDate' => $db->now()
				);
				$db->where('sku', $sku['sku']);
				$db->where('orderId', $orderId);
				if ($db->update(TBL_AZ_ORDERS, $details)) {
					foreach ($uids as $uid) {
						// CHANGE UID STATUS
						$inv_status = $stockist->update_inventory_status($uid, 'sales');
						// ADD TO LOG
						$log_status = $stockist->add_inventory_log($uid, 'sales', 'Amazon Sales :: ' . $orderId);
						if ($inv_status && $log_status)
							$update_status[$orderId] = true;
						else
							$update_status[$orderId] = false;
					}
				} else {
					$update_status[$orderId] = false;
					// $response = array('type' => 'error', 'message' => 'Unable to add products details to the order.');
				}
			}

			if (in_array(false, $update_status))
				$response = array("type" => "error", "msg" => "Error Updating order", "error" => $update_status);
			else
				$response = array("type" => "success", "msg" => "Order successfull shipped");

			$log->write(json_encode($response), 'amazon-flex-orders');
			echo json_encode($response);
			break;

		case 'get_uid_details':

			$order_id = $_GET['orderId'];
			$db->where('o.orderId', $order_id);
			$db->where('o.status', "new");
			$orders = $db->get(TBL_AZ_ORDERS . ' o');
			$orders_count = $db->count;
			if ($orders_count > 0) {
				foreach ($orders as $order) {
					$uid = $_GET['uid'];
					$db->where('inv_id', $uid);
					$db->join(TBL_PRODUCTS_ALIAS . " a", "a.pid=i.item_id", "LEFT");
					$db->joinWhere(TBL_PRODUCTS_ALIAS . " a", "a.sku", $order['sku']);
					$db->joinWhere(TBL_PRODUCTS_ALIAS . " a", "a.mp_id", $order['asin']);
					$item = $db->getOne(TBL_INVENTORY . ' i', 'inv_id, inv_status, mp_id, a.sku');
					if ($item) {
						if (is_null($item['sku']) && $orders_count > 1)
							continue;

						if ($order && $item['inv_status'] == "qc_verified" && !is_null($item['sku'])) {
							$response = array(
								'type' => 'success',
								'data' => array(
									'sku' => $item['sku']
								)
							);
						} else if ($order && $item['inv_status'] != "qc_verified") {
							$response = array(
								'type' => 'error',
								'message' => 'Product not QC Verified'
							);
						} else {
							$response = array(
								'type' => 'error',
								'message' => 'Wrong Product'
							);
						}
					} else {
						$response = array(
							'type' => 'error',
							'message' => 'Invalid Product ID/Alias not found'
						);
					}
				}
			} else {
				$response = array(
					'type' => 'error',
					'message' => 'Order Not Found'
				);
			}

			echo json_encode($response);

			// if ($item_id)
			// 	echo json_encode(array('type' => 'success'));
			// else
			// 	echo json_encode(array('type' => 'error', 'message' => 'Wrong Product/Product not QC Verified.'));

			// $order_id = $_GET['orderId'];
			// $sku = $_GET['sku'];
			// $db->join(TBL_PRODUCTS_ALIAS ." p", "p.sku=o.sku", "LEFT");
			// $db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id=o.account_id");
			// $db->where('o.sku', $sku);
			// $db->where('o.orderId', $order_id);
			// $db->where('o.status', "new");
			// $item = $db->getValue(TBL_AZ_ORDERS .' o', "p.pid");
			// if ($item){
			// 	$uid = $_GET['uid'];
			// 	$db->where('inv_status', 'qc_verified');
			// 	$db->where('inv_id', $uid);
			// 	$db->where('item_id', $item);
			// 	$item_id = $db->getValue(TBL_INVENTORY, 'item_id');
			// 	if ($item_id)
			// 		echo json_encode(array('type' => 'success'));
			// 	else
			// 		echo json_encode(array('type' => 'error', 'message' => 'Wrong Product/Product not QC Verified.'));
			// } else {
			// 	echo json_encode(array('type' => 'error', 'message' => 'Order not found.'));
			// }
			break;
	}
} else {
	header('HTTP/1.0 401 Unauthorized');
	exit('Page doesn\'t exist');
}
