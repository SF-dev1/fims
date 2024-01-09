<?php
// ------------------------------------------------------------------------------------------------
// uncomment the following line if you are getting 500 error messages
// 
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
// ------------------------------------------------------------------------------------------------

// Cheaking action
if (isset($_REQUEST['action'])) {
	// including files
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/connectors/ajio/ajio.php');
	include(ROOT_PATH . '/includes/connectors/ajio/ajio-dashboard.php');
	include(ROOT_PATH . '/ajio/functions.php');
	// global variables
	global $db, $accounts, $sms, $currentUser;
	// Ajio accounts
	$accounts = $accounts['ajio'];

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
	$handler = isset($_REQUEST['handler']) ? $_REQUEST['handler'] : "order";

	// actions
	switch ($action) {
		case 'get_orders':
			// chaking the return or new order
			$isReturn = ($_REQUEST['handler'] != "order") ? true : false;
			if (!$isReturn) {
				// New orders
				$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku = ajo.sellerSKU");
				$order_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "new";
				$db->where("ajo.status", $order_type);
				$orders = $db->ObjectBuilder()->get(TBL_AJ_ORDERS . " ajo", null, "ajo.itemCode, pm.sku, pm.thumb_image_url, ajo.description, ajo.orderDate, ajo.orderStatus, ajo.orderNo, ajo.hsn, ajo.quantity, ajo.totalValue, ajo.dispatchDate, ajo.fulfillmentType, ajo.shipmentId");
			} else {
				// returns
				$order_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "start";
				$db->join(TBL_AJ_ORDERS . " ajo", "ajr.custOrderNo = ajo.orderNo AND ajo.itemCode = ajr.itemCode");
				$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku = ajr.sellerSKU");
				// return status
				switch ($order_type) {
					case 'start':
						$db->where("ajr.return3plDeliveryStatus", "Shipment Picked");
						break;
					case 'out_for_delivery':
						$db->where("ajr.return3plDeliveryStatus", "Shipment Out For Delivery");
						break;
					case 'received':
						$db->where("ajr.return3plDeliveryStatus", "Shipment Returned");
						break;
					case 'in_transit':
						$db->where("ajr.return3plDeliveryStatus", "Return Intransit");
						break;
					case 'delivered':
						$db->where("ajr.return3plDeliveryStatus", "Return Delivered");
						break;
				}
				$orders = $db->ObjectBuilder()->get(TBL_AJ_RETURNS . " ajr", null, "pm.sku, pm.thumb_image_url, ajo.description, ajr.itemCode, ajr.returnCreateDate, ajr.returnStatus, ajr.custOrderNo, ajr.returnQty, ajr.creditNoteGenerationDate, ajr.creditNoteAcceptanceDate, ajr.returnOrderNo");
			}

			// generating output
			foreach ($orders as $order) {
				$return["data"][] = create_order_view($order, $isReturn);
			}
			echo json_encode($return);
			break;
		case 'get_orders_count':
			// getting orders count
			$handler = $_GET['handler'];
			// order types for new orders (Status)
			$order_type = array("pending", "new", "packing", "rtd", "shipped", "cancelled");
			$is_returns = false;

			if ($handler == "return") {
				// order types for return (Status)
				$order_type = array("start", "in_transit", "out_for_delivery", "delivered", "received", "claimed", "completed");
				$is_returns = true;
			}
			// getting orders count
			$orders_count = view_orders_count($order_type, $is_returns);
			echo json_encode(array('orders' => $orders_count));
			break;
		case 'get_all_settlements':
			$db->groupBy("clearingDocNo");
			$payments = $db->ObjectBuilder()->get(TBL_AJ_PAYMENTS, null, "clearingDocNo, clearingDate, (SUM(case when journeyType LIKE 'FORWARD' then value else 0 end) - SUM(case when journeyType LIKE 'RETURN' then value else 0 end)) as value");
			$return = array();
			$i = 0;
			foreach ($payments as $payment) {
				$return["data"][$i] = array(
					$payment->clearingDocNo,
					$payment->clearingDate,
					round($payment->value, 2)
				);
				$i++;
			}
			echo json_encode($return);
			break;
		case 'get_all_unsettled':
			$db->join(TBL_AJ_ORDERS . " ajo", "ajo.orderNo = ajp.custOrderNo");
			$db->where("(ajp.status = ? OR ajp.status = ? OR ajp.status = ?)", array("PAID", "DUE LATER", "OVERDUE"));
			$payments = $db->ObjectBuilder()->get(TBL_AJ_PAYMENTS . " ajp", null, "ajp.clearingDate, ajp.value, (ajp.value -  (SELECT SUM(invoiceValue) FROM bas_aj_orders WHERE orderNo = ajp.custOrderNo)) as difference, ajo.itemCode, ajo.orderNo, ajo.orderDate, ajo.shipmentDate, (SELECT SUM(invoiceValue) FROM bas_aj_orders WHERE orderNo = ajp.custOrderNo) as invoiceValue, ajp.expectedSettlementDate, ajp.value, ajp.status");
			$return = array();
			$i = 0;

			foreach ($payments as $payment) {
				if (abs($payment->difference) > 0.5 || $payment->status == "OVERDUE" || $payment->status == "DUE LATER") {
					$return["data"][$i] = array(
						$accounts[0]->account_name,
						$payment->itemCode,
						$payment->orderNo,
						$payment->orderDate,
						$payment->shipmentDate,
						(($payment->status == "OVERDUE" || $payment->status == "DUE LATER") ? $payment->expectedSettlementDate : $payment->clearingDate),
						round($payment->invoiceValue, 2),
						(($payment->status == "OVERDUE" || $payment->status == "DUE LATER") ? 0.00 : round($payment->value, 2)),
						(($payment->status == "OVERDUE" || $payment->status == "DUE LATER") ? -round($payment->invoiceValue, 2) : round($payment->difference, 2)),
						"",
					);
					$i++;
				}
			}
			echo json_encode($return);
			break;
		case 'get_all_search_payments':
			$db->join(TBL_AJ_ORDERS . " ajo", "ajo.orderNo = ajp.custOrderNo");
			$db->where($_REQUEST["search_by"], $_REQUEST["search_value"]);
			$payments = $db->ObjectBuilder()->get(TBL_AJ_PAYMENTS . " ajp", null, "ajo.itemCode, ajp.custOrderNo, ajo.orderDate, ajo.shipmentDate, ajp.clearingDate, ajp.value, (SELECT SUM(invoiceValue) FROM bas_aj_orders WHERE orderNo = ajp.custOrderNo) as invoiceValue, (ajp.value -  (SELECT SUM(invoiceValue) FROM bas_aj_orders WHERE orderNo = ajp.custOrderNo)) as difference");
			$return = array();
			$i = 0;

			foreach ($payments as $payment) {
				$return["data"][$i] = array(
					$accounts[0]->account_name,
					$payment->itemCode,
					$payment->custOrderNo,
					$payment->orderDate,
					$payment->shipmentDate,
					$payment->clearingDate,
					round($payment->invoiceValue, 2),
					round($payment->value, 2),
					round($payment->difference, 2),
				);
				$i++;
			}
			echo json_encode($return);
			break;
		case 'insert_orders':
			$account = $accounts[0];
			$aj = new ajio_dashboard($account);
			$data = json_decode($aj->get_orders($_GET['start_date'], $_GET['end_date'], false)); // DATE FORMAT YYYY-MM-DD
			if ($data->success) {
				foreach ($data->result->orderList as $order) {
					$response = $aj->insertOrder($order);
					var_dump($response);
				}
			} else {
				echo json_encode($data);
			}
			break;
		case 'insert_returns':
			$account = $accounts[0];
			$aj = new ajio_dashboard($account);
			$data = json_decode($aj->get_returns($_GET['start_date'], $_GET['end_date'], false)); // DATE FORMAT YYYY-MM-DD
			if ($data->success) {
				foreach ($data->result->rtvList as $order) {
					$response = $aj->insertReturn($order);
					var_dump($response);
				}
			} else {
				echo json_encode($data);
			}
			break;
		case 'insert_settlements':
			$account = $accounts[0];
			$aj = new ajio_dashboard($account);
			$data = json_decode($aj->get_settlements($_REQUEST['start_date'], $_REQUEST['end_date'], false)); // DATE FORMAT YYYY-MM-DD
			if ($data->success) {
				$total = 0;
				$insertSuccess = 0;
				$updateSuccess = 0;
				$insertError = 0;
				$updateError = 0;
				$exist = 0;
				foreach ($data->result->paymentList as $order) {
					$response = $aj->insertSettlement($order);
					if ($response["code"] == "1")
						$insertSuccess++;
					if ($response["code"] == "-1")
						$insertError++;
					if ($response["code"] == "2")
						$updateSuccess++;
					if ($response["code"] == "-2")
						$updateError++;
					if ($response["code"] == "0")
						$exist++;
					$total++;
				}
				$return = array('type' => 'success', 'total' => $total, 'success' => $insertSuccess, 'existing' => $exist, 'insError' => $insertError, 'updated' => $updateSuccess, 'updError' => $updateError);
				$log->write($return, 'payments-import');

				echo json_encode($return);
			} else {
				echo json_encode($data);
			}
			break;
	}
	exit;
} else {
	// unauthorized access message
	header("HTTP/1.1 401 Unauthorised");
	exit('hmmmm... trying to hack in ahh!');
}
