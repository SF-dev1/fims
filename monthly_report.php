<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(__FILE__) . '/config.php');
$type = $_GET['type'];
switch ($type) {
	case 'stock':
		$client_id = 0;
		$customers = get_all_customers();
		$due = 0;
		$log_ = array();
		// $log_ = "\n";
		foreach ($customers as $customer) {
			if ($customer->party_id == "0")
				continue;
			$current_due = floatval($accountant->get_current_dues('customer', $customer->party_id));
			$log_[$customer->party_name] = $current_due;
			// $log_ .= ($customer->party_name .' '. $current_due)."\n";
			$due += $current_due;
		}

		$vendors = get_all_vendors();
		foreach ($vendors as $vendor) {
			if ($vendor->party_id == "0")
				continue;
			$current_due = floatval($accountant->get_current_dues('vendor', $vendor->party_id));
			$log_[$vendor->party_name . ' [AMS]'] = $current_due;
			// $log_ .= ($vendor->party_name .' '. $current_due)."\n";
			$due += $current_due;
		}

		$db->where('is_ams_inv', 0);
		$db->where('firm_id', 1);
		$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ? OR inv_status = ? OR inv_status = ?)', array('inbound', 'qc_verified', 'qc_failed', 'qc_pending', 'qc_cooling'));
		$stock = $db->getValue(TBL_INVENTORY, 'SUM(`item_price`)');
		$stock = 'STK: ' . $stock;

		arsort($log_);
		$log_ = array_map('strval', $log_);
		$output = implode("\n", array_map(
			function ($v, $k) {
				return sprintf("%s %s", $k, $v);
			},
			$log_,
			array_keys($log_)
		));

		$log->write($output . "\nDR: " . $due . "\n" . $stock, 'monthly-report');
		echo str_replace("\n", '</br>', $output) . "<br /><br />DR: " . $due . '<br />' . $stock;
		break;

	case 'marketplace':

		$start_date = date('Y-m-01 00:00:00', strtotime('-1 MONTH'));
		$end_date = date('Y-m-t 23:59:59', strtotime('-1 MONTH'));

		if (isset($_GET['month'])) {
			$start_date = date('Y-m-d 00:00:00', strtotime($_GET['month']));
			$end_date = date('Y-m-t 23:59:59', strtotime($_GET['month']));
		}
		if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
			$start_date = date('Y-m-d 00:00:00', strtotime($_GET['start_date']));
			$end_date = date('Y-m-d 23:59:59', strtotime($_GET['end_date']));
		}

		$details = array();

		// FLIPKART ORDERS
		$db->join(TBL_FK_ACCOUNTS . " p", "p.account_id=o.account_id", "INNER");
		$db->joinWhere(TBL_FK_ACCOUNTS . " p", "p.is_ams", 0);
		$db->where('o.shippedDate', array($start_date, $end_date), 'BETWEEN');
		$flipkart_orders = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.quantity as qty, o.costPrice as cp, o.totalPrice as sp');
		$response = new stdClass();
		foreach ($flipkart_orders as $flipkart_order) {
			$response->qty += $flipkart_order->qty;
			$response->cp += $flipkart_order->cp;
			$response->sp += $flipkart_order->sp;
		}
		$details['flipkart']['orders'] = $response;


		// FLIPKART RETURNS
		$db->join(TBL_FK_ORDERS . " o", "o.orderItemId=r.orderItemId");
		$db->join(TBL_FK_ACCOUNTS . " p", "p.account_id=o.account_id", "INNER");
		$db->joinWhere(TBL_FK_ACCOUNTS . " p", "p.is_ams", 0);
		$db->where('r.r_receivedDate', array($start_date, $end_date), 'BETWEEN');
		$flipkart_returns = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, 'r.r_source, r.r_quantity as qty, o.costPrice as cp, o.totalPrice as sp');
		$rto = new stdClass();
		$ctr = new stdClass();
		foreach ($flipkart_returns as $flipkart_return) {
			$rto->r_source = "courier_returns";
			$ctr->r_source = "customer_return";
			if (strtolower($flipkart_return->r_source) == "customer_return") {
				$ctr->qty += $flipkart_return->qty;
				$ctr->cp += $flipkart_return->cp;
				$ctr->sp += $flipkart_return->sp;
			} else {
				$rto->qty += $flipkart_return->qty;
				$rto->cp += $flipkart_return->cp;
				$rto->sp += $flipkart_return->sp;
			}
		}
		$details['flipkart']['returns'][] = $rto;
		$details['flipkart']['returns'][] = $ctr;

		// AMAZON ORDERS
		$db->join(TBL_AZ_ACCOUNTS . " p", "p.account_id=o.account_id", "INNER");
		$db->joinWhere(TBL_AZ_ACCOUNTS . " p", "p.is_ams", 0);
		$db->where('o.shippedDate', array($start_date, $end_date), 'BETWEEN');
		$amazon_orders = $db->objectBuilder()->get(TBL_AZ_ORDERS . ' o', NULL, 'o.quantity as qty, o.costPrice as cp, o.orderTotal as sp');
		$response = new stdClass();
		foreach ($amazon_orders as $amazon_order) {
			$response->qty += $amazon_order->qty;
			$response->cp += $amazon_order->cp;
			$response->sp += $amazon_order->sp;
		}
		$details['amazon']['orders'] = $response;

		// AMAZON RETURNS
		$db->join(TBL_AZ_ORDERS . " o", "o.orderId=r.orderId");
		$db->join(TBL_AZ_ACCOUNTS . " p", "p.account_id=o.account_id", "INNER");
		$db->joinWhere(TBL_AZ_ACCOUNTS . " p", "p.is_ams", 0);
		// $db->where('rShipmentStatus', 'return_completed');
		$db->where('r.rReceivedDate', array($start_date, $end_date), 'BETWEEN');
		$db->groupBy('r.rSource');
		$amazon_returns = $db->objectBuilder()->get(TBL_AZ_RETURNS . ' r', NULL, 'r.rSource as r_source, r.rQty as qty, o.costPrice as cp, o.orderTotal as sp');
		$rto = new stdClass();
		$ctr = new stdClass();
		foreach ($amazon_returns as $amazon_return) {
			$rto->r_source = "courier_returns";
			$ctr->r_source = "customer_return";
			if (strtolower($amazon_return->r_source) == "customer_return") {
				$ctr->qty += $amazon_return->qty;
				$ctr->cp += $amazon_return->cp;
				$ctr->sp += $amazon_return->sp;
			} else {
				$rto->qty += $amazon_return->qty;
				$rto->cp += $amazon_return->cp;
				$rto->sp += $amazon_return->sp;
			}
		}
		$details['amazon']['returns'][] = $rto;
		$details['amazon']['returns'][] = $ctr;

		// OFFLINE SALES
		$db->where('insertDate', array($start_date, $end_date), 'BETWEEN');
		$db->where('item_cp', '0', '>');
		$offline = $db->objectBuilder()->get(TBL_SALE_ORDER_ITEMS, NULL, 'item_qty, item_price, item_cp');
		// echo $db->getLastQuery();
		$offline_margin = 0;
		foreach ($offline as $off_trace) {
			$offline_margin += ($off_trace->item_price - $off_trace->item_cp) * $off_trace->item_qty;
		}

		$orders = 0;
		$average_cpt = 0;
		$average_spt = 0;

		$total_returns = 0;
		$customer_returns = 0;
		$courier_returns = 0;
		foreach ($details as $marketplace) {
			$orders += $marketplace['orders']->qty;
			$average_cpt += $marketplace['orders']->cp;
			$average_spt += $marketplace['orders']->sp;
			if (isset($marketplace['returns'])) {
				foreach ($marketplace['returns'] as $return) {
					$total_returns += $return->qty;
					if (strtolower($return->r_source) == "customer_return") {
						$customer_returns += $return->qty;
					} else {
						$courier_returns += $return->qty;
					}
				}
			}
		}
		$cp = (int)($average_cpt / $orders);
		$sp = (int)($average_spt / $orders);
		$net_orders = $orders - $total_returns;
		$gross_revenue = $net_orders * $sp;

		echo '======================================================<br/>';
		echo 'REPORT FROM ' . $start_date . ' TO ' . $end_date . '<br/>';
		echo '======================================================<br/>';
		echo 'Gross Sales Units	' . $orders . '<br />';
		echo 'Revenue per Product	' . $sp . '<br />';
		echo 'RTO Returns		' . $courier_returns . ' [' . number_format((($courier_returns / $orders) * 100), 2, ".", "") . '%]<br />';
		echo 'Customer Returns	' . $customer_returns . ' [' . number_format((($customer_returns / $orders) * 100), 2, ".", "") . '%]<br />';
		echo 'Total Returns		' . $total_returns . ' [' . number_format((($total_returns / $orders) * 100), 2, ".", "") . '%]<br />';
		echo 'Net Sales Units		' . $net_orders . '<br />';
		echo 'Return Revenue Loss	' . $total_returns * $sp . '<br />';
		echo 'Gross Revenue		' . $gross_revenue . '<br /><br />';

		$fixed_costs 	= 35000 // RENT
			+ 70000 // SALARY
			+ 1200	// INTERNET
			+ 9000	// ELECTRIC
			+ 2300	// UTILITY
			+ 4038	// MAINTAINANCE
			+ 1000;	// MISC

		$variable_costs	= 14000 // AWS
			+ 25000 // ADS
			+ 10000; // OD INTEREST 

		$end_date = date('Y-m-d 23:59:59', strtotime($end_date) + 1);
		$interval = date_diff(date_create($start_date), date_create($end_date));
		$assumed_cost = $fixed_costs + $variable_costs;
		$fixed_variable_costs = (int)(($fixed_costs + $variable_costs) / 30.5) * $interval->days;

		$return_cost = ($customer_returns * 164) + ($courier_returns * 2);
		$mp_commission = (int)(($orders - $courier_returns) * $sp * 0.31);
		$packaging_cost = (int)($orders * 13);
		$total_cost = $fixed_variable_costs + $return_cost + $mp_commission + $packaging_cost;
		$net_revenue = ($gross_revenue - $total_cost - ($net_orders * $cp));

		echo 'Fixed Cost		' . $fixed_variable_costs . '<br />';
		echo 'Return Cost		' . $return_cost . '<br />';
		echo 'MP Commission		' . $mp_commission . '<br />';
		echo 'Packaging Cost		' . $packaging_cost . '<br />';
		echo 'Total Cost		' . $total_cost . '<br />';
		echo 'Total Product Cost	' . ($net_orders * $cp) . '<br />';
		echo 'Total Online 		' . $net_revenue . '<br />';
		echo 'Total Offline 		' . (int)$offline_margin . '<br />';
		echo 'Net Revenue		<b>' . (int)((int)$net_revenue + (int)$offline_margin) . '</b><br /><br />';

		// MISC
		$product_margin = (number_format((($gross_revenue - $total_cost) / $net_orders) - $cp, 2, '.', ''));
		$breakeven_orders = (int)(($assumed_cost) / $product_margin);
		$cost_per_order = (number_format((($assumed_cost) / $orders), 2, '.', ''));

		echo 'Cost Price		' . $cp . '<br />';
		echo 'Current Margin/Order	' . $product_margin . '<br />';
		echo 'Break Even		' . $breakeven_orders . '<br />';
		echo 'Derived Cost/Order	' . $cost_per_order . '<br />';
		break;

	case 'audit_status':
		$current_audit_tag = get_option('current_audit_tag');
		$db->where('inv_status', 'sales', '!=');
		// $db->where('inv_status', 'inbound', '!=');
		$db->where('inv_status', 'lost', '!=');
		$db->where('inv_status', 'liquidated', '!=');
		$db->where('inv_status', 'outbound', '!=');
		$db->where('inv_status', 'sidlined', '!=');
		$db->groupBy('inv_status, inv_audit_tag');
		$inv_details = $db->get(TBL_INVENTORY, NULL, array('inv_status', 'COUNT(inv_id) as units', 'inv_audit_tag'));
		$inv_status = array();
		$totals = array('pending' => 0, 'audited' => 0, 'total' => 0);
		foreach ($inv_details as $details) {
			$audited = (is_null($details['inv_audit_tag']) || !in_array($current_audit_tag, json_decode($details['inv_audit_tag'], true))) ? 'pending' : 'audited';
			$inv_status[$details['inv_status']][$audited] += $details['units'];
			$totals[$audited] += $details['units'];
			$totals['total'] += $details['units'];
		}

		$dl_svg = '<svg data-name="Layer 3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="20px" height="20px" style="float: right;"><path d="M75.978 24.711H52.026a2.11 2.11 0 0 0-2.111 2.111V55h-9.33a2.111 2.111 0 0 0-1.658 3.418l23.42 29.694a2.111 2.111 0 0 0 3.315 0l23.411-29.695A2.111 2.111 0 0 0 87.415 55h-9.326V26.822a2.11 2.11 0 0 0-2.111-2.111zm7.086 34.51L64 83.4 44.939 59.221h7.088a2.11 2.11 0 0 0 2.111-2.111V28.933h19.728V57.11a2.11 2.11 0 0 0 2.111 2.111zM34.52 101.178a2.11 2.11 0 0 0 2.111 2.111h54.738a2.111 2.111 0 0 0 0-4.222H36.631a2.11 2.11 0 0 0-2.111 2.111z"/></svg>';

		echo '<table border="1"><tr><th>Status</th><th>Units</th><th>Pending</th><th>Audited</th><th>Pendency</th></tr>';
		foreach ($inv_status as $status => $count) {
			$dl_btn = "";
			if ($count['pending'])
				$dl_btn = '&nbsp;<a href="' . BASE_URL . '/monthly_report.php?type=pending_audit_uids&status=' . $status . '&export=" target="_blank">' . $dl_svg . '</a>';
			echo '<tr><td>' . $status . '</td><td>' . ($count['audited'] + $count['pending']) . '</td><td><a href="' . BASE_URL . '/monthly_report.php?type=pending_audit_uids&status=' . $status . '" target="_blank">' . $count['pending'] . '</a>' . $dl_btn . '</td><td>' . $count['audited'] . '</td><td>' . number_format((($count['pending'] / ($count['audited'] + $count['pending'])) * 100), 2, '.', '') . '%</td></tr>';
		}
		echo '<tr><th>Total</th><th>' . ($totals['audited'] + $totals['pending']) . '</th><th><a href="' . BASE_URL . '/monthly_report.php?type=pending_audit_uids" target="_blank">' . $totals['pending'] . '</a>&nbsp;<a href="' . BASE_URL . '/monthly_report.php?type=pending_audit_uids&export=" target="_blank">' . $dl_svg . '</a></th><th>' . $totals['audited'] . '</th><th>' . number_format((($totals['pending'] / ($totals['audited'] + $totals['pending'])) * 100), 2, '.', '') . '%</th></tr></table><br />';

		$db->where('is_ams_inv', 0);
		$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ? OR inv_status = ? OR inv_status = ?)', array('inbound', 'qc_verified', 'qc_failed', 'qc_pending', 'component_requested'));
		$db->where('(inv_audit_tag IS NULL OR inv_audit_tag NOT LIKE ?)', array('%' . $current_audit_tag . '%'));
		echo $stock = $db->getValue(TBL_INVENTORY, 'SUM(`item_price`)');


		break;

	case 'pending_audit_uids':
		// if (!isset($_REQUEST['status']))
		// 	exit('No status selected.');

		$current_audit_tag = get_option('current_audit_tag');
		$status = $_REQUEST['status'];
		$export = isset($_REQUEST['export']);
		if ($status)
			$db->where('inv_status', $status);
		else {
			$db->where('inv_status', 'sales', '!=');
			// $db->where('inv_status', 'inbound', '!=');
			$db->where('inv_status', 'lost', '!=');
			$db->where('inv_status', 'liquidated', '!=');
			$db->where('inv_status', 'outbound', '!=');
			$db->where('inv_status', 'sidlined', '!=');
		}

		$db->where('(inv_audit_tag NOT LIKE ? OR inv_audit_tag IS NULL)', array('%' . $current_audit_tag . '%'));
		$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
		$uids = $db->get(TBL_INVENTORY . ' i', NULL, 'i.inv_id, p.sku, i.item_price, inv_status');
		$output = array();

		if ($export) {
			$fileName = 'pending_audit_uids_' . ((!is_null($status)) ? $status . '-' : '') . date('Ymd') . '.csv';

			// disable caching
			$now = gmdate("D, d M Y H:i:s");
			header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
			header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
			header("Last-Modified: {$now} GMT");

			// force download  
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");

			// disposition / encoding on response body
			header("Content-Disposition: attachment;filename={$fileName}");
			header("Content-Transfer-Encoding: binary");

			$header = array("SKU", "UID", "Status");

			ob_start();
			$csvFile = fopen('php://output', 'w');
			fputcsv($csvFile, $header);
			foreach ($uids as $row) {
				$row_i = array();
				foreach ($row as $key => $value) {
					if ($key != 'item_price')
						$row_i[] = $value;
				}
				fputcsv($csvFile, $row_i);
			}
			fclose($csvFile);
			echo ob_get_clean();
			die();
		}

		$total = 0;
		foreach ($uids as $uid) {
			$total += $uid['item_price'];
			$output[$uid['sku']][] = $uid['inv_id'];
		}

		var_dump($total);

		foreach ($output as $sku => $uids) {
			echo '<table border="1">';
			foreach ($uids as $uid) {
				echo '<tr><th>' . $sku . '</th><td>' . $uid . '</td></tr>';
			}
			echo '</table><br />';
		}
		break;

	case 'inventory_status':
		$db->where('inv_status', 'sales', '!=');
		// $db->where('inv_status', 'inbound', '!=');
		$db->where('inv_status', 'lost', '!=');
		$db->where('inv_status', 'liquidated', '!=');
		$db->where('inv_status', 'outbound', '!=');
		$db->where('inv_status', 'sidlined', '!=');
		$db->groupBy('inv_status');
		$inv_details = $db->get(TBL_INVENTORY, NULL, array('inv_status', 'COUNT(inv_id) as units'));
		$inv_status = array();
		$total = 0;
		foreach ($inv_details as $details) {
			$inv_status[$details['inv_status']] = $details['units'];
			$total += $details['units'];
		}
		ksort($inv_status);

		echo '<table border="1"><tr><th>Status</th><th>Units</th><th>%</th></tr>';
		foreach ($inv_status as $status => $count) {
			echo '<tr><td>' . $status . '</td><td>' . ($count) . '</td><td>' . number_format(($count / $total * 100), 2, '.', '') . '%</td></tr>';
		}
		echo '<tr><th>Total</th><th>' . $total . '</th><th></th></tr></table>';
		break;
}
