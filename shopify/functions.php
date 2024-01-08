<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

use Razorpay\Api\Api;

function get_current_account($search, $lp_provider_id = "", $id = "account_id")
{
	global $db, $accounts, $current_user;
	foreach ($accounts as $account) {
		if ($account->{$id} == $search) {
			if (empty($lp_provider_id))
				$db->where('lp_provider_id', $account->account_active_logistic_id);
			else
				$db->where('lp_provider_id', $lp_provider_id);

			$account_logistics = $db->objectBuilder()->getOne(TBL_SP_LOGISTIC);

			$db->where('account_id', $account->account_id);
			$db->where('userID', $current_user['userID']);
			$account_location = $db->getOne(TBL_USER_LOCATIONS, 'locationId');

			// $account = (object) array_merge((array) $account, (array) $account_logistics, $account_location);
			// return $account;
			return (object) array_merge((array) $account_logistics, $account_location, (array) $account);
		}
	}
}

function view_order_query($type, $is_return = false, $is_claim = false)
{
	global $db;

	if ($is_return) {
		if ($type == "pending_pickup")
			$db->where('(r.r_status = ? OR r.r_status = ? OR r.r_status = ? )', array('pickup_exception', 'out_for_pickup', 'awb_assigned'));
		else
			$db->where('r.r_status', "%" . $type . "%", "LIKE");
		$db->join(TBL_SP_ORDERS . " o", "o.orderItemId=r.orderItemId", "INNER");
		$db->joinWhere(TBL_SP_ORDERS . " o", "r.r_sku=o.sku");
		if ($is_claim) {
			$db->join(TBL_SP_CLAIMS . " c", "r.orderItemId=c.orderItemId", "LEFT");
			$db->joinWhere(TBL_SP_CLAIMS . " c", "r.returnId=c.returnId");
			if ($type == "claimed")
				$db->joinWhere(TBL_SP_CLAIMS . " c", "c.claimStatus", "pending");
		}
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
		$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
		$db->join(TBL_SP_PRODUCTS . ' sp', 'sp.variantId=o.variantId', 'INNER');
		$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'INNER');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.shipmentStatus', 'cancelled', '!=');
		$db->joinWhere(TBL_FULFILLMENT . ' f', '(f.fulfillmentType = ? OR f.fulfillmentType = ? )', array('return', 'reverse'));
		// $db->joinWhere(TBL_FULFILLMENT.' f', 'f.fulfillmentType', 'return');
		// $db->join(TBL_SP_LOGISTIC . ' sl', 'sl.account_id=sa.account_id', 'LEFT');
		$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider');
		if ($type == "completed")
			$db->where('r.r_completionDate', array(date('Y-m-d H:i:s', strtotime('-14 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
		else if ($type == "delivered")
			$db->orderBy('f.deliveredDate', 'ASC');
		else if ($type == "received")
			$db->orderBy('r.r_receivedDate', 'ASC');
		else
			$db->orderBy('r.r_createdDate', 'ASC');
		// $db->groupBy('r.orderItemId');
	} else {
		if ($type != "new" && $type != "pending" && $type != 'cancelled') {
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
			$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider');
		}

		if ($type == "pending") {
			$db->where('o.status = ? AND o.dispatchAfterDate >= ?', array('new', date('Y-m-d H:i:s', time())));
			$db->orWhere('o.status != ? AND o.hold = ?', array('cancelled', "1"));
		} elseif ($type == 'pickup_pending') {
			$db->join(TBL_SP_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->where('r.orderItemId', NULL, 'IS');
			$db->where('o.status', 'shipped');
			$db->where('f.pickedUpDate', NULL, 'IS');
			$db->where('o.cancelledDate', NULL, 'IS');
			$db->joinWhere(TBL_FULFILLMENT . ' f', '(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus IS NULL)', array('awb_assigned', 'start'));
		} elseif ($type == 'in_transit') {
			$db->join(TBL_SP_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->where('r.orderItemId', NULL, 'IS');
			$db->where('o.status', 'shipped');
			$db->joinWhere(TBL_FULFILLMENT . ' f', '(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ?)', array('picked_up', 'in_transit', 'out_for_delivery'), 'INNER');
			// $db->where('f.shipmentStatus', 'delivered', '!=');
		} elseif ($type == "ndr") {
			$db->join(TBL_SP_RETURNS . " r", "o.orderItemId=r.orderItemId", "INNER");
			$db->joinWhere(TBL_SP_RETURNS . ' r', '(r_source != ?)', array('customer_return'));
			$db->where('r.orderItemId', NULL, 'IS NOT');
			$db->where('o.isReturnApproved', '0');
			$db->where('o.status', 'shipped');
			$db->joinWhere(TBL_FULFILLMENT . ' f', '(f.shipmentStatus = ? OR f.shipmentStatus = ?)', array('undelivered', 'undelivered-rto'));
			// $db->joinWhere('(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ?)', array('picked_up', 'in_transit', 'out_for_delivery', 'undelivered', 'undelivered-rto'), 'INNER');
			if (date('H', time()) < '09') {
				$db->where('f.pickedUpDate', date('Y-m-d H:i:s', strtotime('yesterday 09:00 AM') - 1), '<=');
			} else {
				$db->where('f.pickedUpDate', date('Y-m-d H:i:s', strtotime('today 09:00 AM') - 1), '<=');
			}
			$db->where('f.shipmentStatus', 'delivered', '!=');
			// } elseif ($type == "follow_up") {
			// 	$db->join(TBL_SP_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			// 	$db->where('o.isFlagged', '1');
		} else {
			$db->where('o.status', $type);
			$db->where('o.hold', '0');
		}

		if ($type == "new") {
			$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', time()), '<=');
		}

		if ($type == 'shipped') {
			if (date('H', time()) < '09') {
				$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('yesterday 09:00 AM')), date('Y-m-d H:i:s', strtotime('today 09:00 AM'))), 'BETWEEN');
			} else {
				$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('today 09:00 AM')), date('Y-m-d H:i:s', strtotime('tomorrow 09:00 AM'))), 'BETWEEN');
			}
		}

		if ($type == 'cancelled') {
			$db->where('o.cancelledDate', array(date('Y-m-d H:i:s', strtotime('-3 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
		}

		$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
		$db->join(TBL_SP_PRODUCTS . ' sp', 'sp.variantId=o.variantId', 'INNER');
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
		if ($type == 'pickup_pending' || $type == 'in_transit') {
			$db->orderBy('f.pickedUpDate', 'ASC');
		} else {
			$db->orderBy('o.orderDate', 'ASC');
			$db->orderBy('o.dispatchByDate', 'ASC');
		}
	}
}

function view_orders_count($order_types, $is_return = false)
{
	global $db;

	// error_reporting(E_ALL);
	// ini_set('display_errors', '1');
	// echo '<pre>';

	foreach ($order_types as $order_type) {
		view_order_query($order_type, $is_return);
		if ($is_return)
			$count = $db->ObjectBuilder()->getValue(TBL_SP_RETURNS . ' r', 'COUNT(DISTINCT(r.orderItemId))');
		else
			$count = $db->ObjectBuilder()->getValue(TBL_SP_ORDERS . ' o', 'COUNT(DISTINCT(o.orderId))');

		// var_dump($count);
		// echo $order_type .' :: COUNT :: '.$count.' :: '.$db->getLastQuery().'<br />';
		$return[$order_type] = $count;
	}

	return $return;
}

function view_orders($type, $is_return = false)
{
	global $db;

	$is_claim = false;
	if ($type == "claimed" || $type == "completed")
		$is_claim = true;

	view_order_query($type, $is_return, $is_claim);
	if ($is_claim)
		return $db->ObjectBuilder()->get(TBL_SP_RETURNS . ' r', null, "o.*, r.*, c.*, f.*, o.orderItemId as orderItemId, sa.account_store_url, sa.account_domain, sa.account_name, sp.handle, sl.*, pm.sku as sku, pm.thumb_image_url as thumb_image_url, c.createdDate as claimCreatedDate");
	if ($is_return)
		return $db->ObjectBuilder()->get(TBL_SP_RETURNS . ' r', null, "o.*, r.*, f.*, f.createdDate as fulfillmentCreatedDate, sa.account_store_url, sa.account_domain, sa.account_name, sp.handle, sl.*, pm.thumb_image_url as thumb_image_url");

	if ($type == "new" || $type == "pending" || $type == 'cancelled')
		return $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', null, "o.*, sa.account_store_url, sa.account_domain, sa.account_name, sp.handle, pm.thumb_image_url as thumb_image_url");

	// if ($type == "follow_up")
	// 	return $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', null, "o.*, r.*, f.*, sa.account_store_url, sa.account_domain, sa.account_name, sp.handle, sl.*, pm.thumb_image_url as thumb_image_url");

	return $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', null, "o.*, f.*, sa.account_store_url, sa.account_domain, sa.account_name, sp.handle, sl.*, pm.thumb_image_url as thumb_image_url");
}

function create_order_view($order, $type, $is_return = false, $hide_view_order_button = false)
{
	global $db;

	if ($is_return) {
		$multi_items = (isset($order->multiProducts) && $order->multiProducts) ? "<span class='label label-info'>Multiple Items</span>" : "";
		$flag_class = (isset($order->isFlagged) && $order->isFlagged) ? ' active' : '';
		$express = ((isset($order->shipServiceCategory) && $order->shipServiceCategory) == "Expedited" ? "<span class='label label-default'><i class='fa fa-shipping-fast'></i></span>" : "");
		$order_qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-warning'>" . $order->quantity . " units</span>";
		$return_qty = ($order->r_quantity == 1) ? $order->r_quantity . " unit" : "<span class='label label-warning'>" . $order->r_quantity . " units</span>";
		$return_approved = ($order->isReturnApproved) ? "" : "<span class='label label-warning'>NDR Not Approved</span>";
		$package_details = "";
		$delivered_details = "";
		$received_details = "";
		$return_type = "COURIER_RETURN";
		$order->logistic = $order->logistic;
		$sort_date = $order->r_createdDate;
		$lp_link = ($order->lp_provider_order_url != "" ? "<a href='" . $order->lp_provider_order_url . $order->lpOrderId . "' target='_blank'>" . $order->lp_provider_name . " <i class='fa fa-sm fa-external-link-alt'></i></a>" : $order->lp_provider_name);
		$return_tracking_details = "<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Forward</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='" . $order->lp_provider_tracking_url . $order->trackingId . "'>" . $order->trackingId . "</a></div>
										</div>";
		if ($order->r_source == "customer_return") {
			$return_approved = "";
			$return_type = "CUSTOMER_RETURN";

			$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider', 'LEFT');
			$db->where('(orderId = ? AND fulfillmentType = ? AND fulfillmentStatus != ?)', array($order->orderId, 'forward', 'cancelled'));
			$forward_logistic = $db->objectBuilder()->getOne(TBL_FULFILLMENT . ' f');
			$lp_link = ($order->lp_provider_order_url != "" ? "<a href='" . $order->lp_provider_order_url . $order->lpOrderId . "' target='_blank'>" . $order->lp_provider_name . " <i class='fa fa-sm fa-external-link-alt'></i></a>" : $order->lp_provider_name);

			$return_tracking_details = "<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Forward</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='" . $forward_logistic->lp_provider_tracking_url . $forward_logistic->trackingId . "'>" . $forward_logistic->trackingId . "</a></div>
										</div>";
		}

		$return_type_details = "&nbsp;<span class='badge badge-default'>" . $return_type . "</span>";
		$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement</span>" : "";

		$uids = json_decode($order->r_uid, true);
		$uids_checkbox = "";
		if (count($uids) > 1 && $return_type == "CUSTOMER_RETURN") {
			foreach ($uids as $uid) {
				$uids_checkbox .= '<label class="checkbox-inline"><input name="uids[]" type="checkbox" value="' . $uid . '" required>' . $uid . '</label>';
			}
		}

		$replacement_details = "";
		if ($order->r_returnType == "replace") {
			$db->where('orderId', $order->r_replacementOrderId);
			$replacement_order_detail = $db->objectBuilder()->getOne(TBL_SP_ORDERS, 'orderId, orderNumberFormated');
			$order->replacement_order_id = $replacement_order_detail->orderNumberFormated;
			$replacement_details = "<div class='ordered-status'>
					Replacement Order Id: <span class='bold-font ordered-status'><a href='order.php?orderId=" . $replacement_order_detail->orderId . "' target='_blank'>" . $replacement_order_detail->orderNumberFormated . "</a></span>
				</div>";
		}

		$sla = "";
		if ($type == 'start' || $type == 'in_transit' || $type == 'out_for_delivery') {
			if ($type == 'start' && strtotime($order->r_createdDate) + 60 * 60 * 24 * 2 < time()) {
				if ($order->r_source == "customer_return") {
					if ($order->lpProvider != "5")
						$sla = "<span class='label label-danger'>Pickup Breached!</span>";
					else if ($order->lpProvider == "5" && strtotime($order->fulfillmentCreatedDate) + 60 * 60 * 24 * 45 < time())
						$sla = "<span class='label label-danger'>Cancel Return!</span>";
					else if ($order->lpProvider == "5" && strtotime($order->fulfillmentCreatedDate) + 60 * 60 * 24 * 15 < time())
						$sla = "<span class='label label-danger'>Tracking Details Pending!</span>";
				} else
					$sla = "<span class='label label-danger'>No RTO Moment!</span>";
			} else {
				if (strtotime($order->r_createdDate) + 60 * 60 * 24 * 7 < time()) {
					$sla = "<span class='label label-danger'>Return Delivery Breached!</span>";
				}
			}
		} else if ($type == "pending_pickup" && $order->lpProvider != "5") {
			if (strtotime($order->fulfillmentCreatedDate) + 60 * 60 * 24 * 3 < time()) { // 3 DAYS
				$sla = "<span class='label label-danger'>Pickup Breached!</span>";
			}
		}

		if ($type == 'delivered' || $type == 'received' || $type == 'claimed') {
			$sort_date = $order->deliveredDate;
			$delivered_date = is_null($order->deliveredDate) ? "NA" : date("M d, Y", strtotime($order->deliveredDate));
			$delivered_details = "<div class='ordered-approval'>Delivered Date: " . $delivered_date . "</div>";
			if (strtotime($order->deliveredDate) + 60 * 60 * 24 * 1 < time()) { // 6 DAYS
				$sla = "<span class='label label-danger'>Undelivered Breached!</span>";
			}
		}

		if ($type == 'received' || $type == 'claimed' || $type == 'completed') {
			$sort_date = ($type == 'received') ? $order->r_receivedDate : (($type == 'claimed') ? $order->r_createdDate : $order->r_completionDate);
			$received_date = is_null($order->r_receivedDate) ? "NA" : date("M d, Y H:i", strtotime($order->r_receivedDate));
			$received_details = "<div class='ordered-approval'>Received Date: " . $received_date . "</div>";
		}

		$refund_payment_mode = "";
		if ($order->r_source == "customer_return" && $order->r_returnType == "refund") {
			if ($order->paymentType == "cod") {
				$refund_payment_mode = "[COD]";
			} else {
				$refund_payment_mode = (strpos(strtolower($order->tags), 'gokwik') !== FALSE) ? "[GoKwik]" : "";
				if ($refund_payment_mode == "")
					$refund_payment_mode = (strpos(strtolower($order->tags), 'magic checkout') !== FALSE) ? "[Magic Checkout]" : "";

				if ($refund_payment_mode == "")
					$refund_payment_mode = "[Razor Pay]";
			}
		}

		if ($order->r_source == "courier_return" && $order->r_returnType == "refund" && $order->paymentType == "cod")
			$order->r_returnType = "RTO - COD";
		else if ($order->r_source == "courier_return" && $order->r_returnType == "refund")
			$order->r_returnType = "RTO - PREPAY";

		$action = "";
		if ($order->shipmentStatus == "awaiting_tracking_details") {
			// if ($order->orderType == "SelfShip") {
			$db->where('is_selfShip', '1');
			$logistics = $db->get(TBL_SP_LOGISTIC);
			$logistic_options = '<option></option><option value="5">Self</option>'; // SELF SHIP
			// foreach ($logistics as $logistic) {
			// 	$logistic_options .= '<option value="'.$logistic["lp_provider_id"].'">'.$logistic["lp_provider_name"].'</option>';
			// }
			$action .= '<div class="btn-group dropup button-actions selfShip-actions-' . $order->orderId . '">
								<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Self Ship Order <i class="fa fa-angle-up"></i></button>
								<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-large hold-on-click form" role="menu">
									<form action="#" class="selfship_order form-horizontal" id="selfship_order_' . $order->orderId . '">
										<div class="form-body">
											<div class="form-group">
												<input type="text" name="trackingId" class="form-control input-large trackingId" placeholder="Tracking Id" autocomplete="off" required>
											</div>
											<div class="form-group">
												<input type="text" name="trackingLink" class="form-control input-large trackingLink" placeholder="Tracking URL" autocomplete="off" required>
											</div>
											<div class="form-group">
												<input type="text" name="logistic" class="form-control input-large logistic" placeholder="Logistic (eg: Speed Post)" value="" autocomplete="off" required>
											</div>
											<div class="form-group">
												<select class="form-control select2 input-large logisticProvider" name="logisticProvider" data-allowClear="true" placeholder="Logistic Provider (eg: India Post)" autocomplete="off" required>
													' . $logistic_options . '
												</select>
											</div>
											<div class="form-group">
												<input type="hidden" name="orderId" value="' . $order->orderId . '" />
												<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
												<input type="hidden" name="accountId" value="' . $order->accountId . '" />
												<input type="hidden" name="pickedUpDate" value="' . date("c") . '" />
												<input type="hidden" name="isReturn" value="1" />
												<input type="hidden" name="action" value="mark_orders" />
												<input type="hidden" name="type" value="self_shipped" />
												<button class="btn btn-success btn-block" id="order_submit_' . $order->orderId . '" type="submit">Update to In-Transit</button>
											</div>
										</div>
									</form>
								</div>
							</div>';
			// }
		}

		if (!empty($sla) && ($type == 'start' || $type == 'out_for_delivery' || $type == 'in_transit' || $type == 'delivered') && $order->shipmentStatus != "awaiting_tracking_details") {
			$action = '<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Accept Return/Add Case ID <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="add-claim-details form-horizontal" id="claim_form_' . $order->orderItemId . '_' . $order->returnId . '">
									<div class="form-body">
										<div class="input-group">
											<!--<label class="control-label">Product ID</label>-->
											<div class="checkbox-list">
												' . $uids_checkbox . '
											</div>
										</div>
										<div class="input-group">
											<!--<label class="control-label">Condition</label>-->
											<div class="radio-list">
												<label class="radio-inline">
													<input type="radio" name="productCondition" value="undelivered" required />Undelivered
												</label>
											</div>
										</div>
										<div class="input-group">
											<input type="text" name="claimId" class="form-control claimId" id="claimId_holder_' . $order->orderItemId . '_' . $order->returnId . '" placeholder="Case ID" autocomplete="off" required>
											<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
											<input type="hidden" name="returnId" value="' . $order->returnId . '" />
											<input type="hidden" name="trackingId" value="' . $order->trackingId . '" />
											<input type="hidden" name="action" value="update_returns_status" />
											<span class="input-group-btn">
												<button class="btn btn-info" id="claim_submit_' . $order->orderItemId . '_' . $order->returnId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
		}

		if ($type == 'received') {
			$action = '<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Accept Return/Add Case ID <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="add-claim-details form-horizontal" id="claim_form_' . $order->orderItemId . '_' . $order->returnId . '">
									<div class="form-body">
										<div class="input-group">
											<!--<label class="control-label">Product ID</label>-->
											<div class="checkbox-list">
												' . $uids_checkbox . '
											</div>
										</div>
										<div class="input-group">
											<!--<label class="control-label">Condition</label>-->
											<div class="radio-list">
												<label class="radio-inline">
													<input type="radio" name="productCondition" value="saleable" required />Saleable
												</label>
												<label class="radio-inline">
													<input type="radio" name="productCondition" value="damaged" required />Damaged
												</label>
												<label class="radio-inline">
													<input type="radio" name="productCondition" value="wrong" required />Wrong/Missing
												</label>
											</div>
										</div>
										<div class="input-group">
											<span class="input-group-addon"><input type="checkbox" class="is_claim" data-claimbox="claimId_holder_' . $order->orderItemId . '_' . $order->returnId . '"></span>
											<input type="text" name="claimId" class="form-control claimId" id="claimId_holder_' . $order->orderItemId . '_' . $order->returnId . '" placeholder="Case ID" autocomplete="off" disabled required>
											<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
											<input type="hidden" name="returnId" value="' . $order->returnId . '" />
											<input type="hidden" name="action" value="update_returns_status" />
											<span class="input-group-btn">
												<button class="btn btn-info" id="claim_submit_' . $order->orderItemId . '_' . $order->returnId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
		}

		$claim_details = "";
		$reimbursment_amount_details = "";
		$claim_approved_button = "";
		if (isset($order->claimId)) {
			$sla = "";
			if ($type == "claimed") {
				$action = '##CLAIM_APPROVED_BUTTON##
						<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Add Reimbursement & Complete <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-xlarge hold-on-click form" role="menu">
								<form action="#" class="add-reimbursement-complete" id="reimbursement_form_' . $order->orderItemId . '_' . $order->returnId . '">
									<div class="form-body">
										<div class="form-group">
											<input type="text" name="claimNotes" class="form-control" placeholder="Claim Notes" required />
										</div>
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon"><input type="checkbox" class="is_reimbursed" data-claimbox="reimbursement_amount_holder_' . $order->orderItemId . '_' . $order->returnId . '"></span>
												<input type="number" name="claimReimbursmentAmount" class="form-control" id="reimbursement_amount_holder_' . $order->orderItemId . '_' . $order->returnId . '" placeholder="Add Reimbursement Amount" step=".01" min="0" max="' . $order->totalPrice . '" disabled required>
												<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
												<input type="hidden" name="returnId" value="' . $order->returnId . '" />
												<input type="hidden" id="form_claim_id_' . $order->orderItemId . '_' . $order->returnId . '" name="claimId" value="' . $order->claimId . '" />
												<input type="hidden" name="claimProductCondition" value="' . $order->claimProductCondition . '" />
												<input type="hidden" name="shipmentStatus" value="return_completed" />
												<input type="hidden" name="action" value="update_claim_details" />
												<span class="input-group-btn">
													<button class="btn blue" id="reimbursement_submit_' . $order->orderItemId . '_' . $order->returnId . '" type="submit">Go!</button>
												</span>
											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
						<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Update Claim ID <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-medium hold-on-click form" role="menu">
								<form action="#" class="update-claim-id form-horizontal" id="update_claim_form_' . $order->orderItemId . '_' . $order->returnId . '">
									<div class="form-body">
										<div class="input-group">
											<input type="text" name="newClaimId" class="form-control claimId" placeholder="Case ID" autocomplete="off" required>
											<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
											<input type="hidden" name="returnId" value="' . $order->returnId . '" />
											<input type="hidden" name="claimId" value="' . $order->claimId . '" />
											<input type="hidden" name="action" value="update_claim_id" />
											<span class="input-group-btn">
											<button class="btn blue" id="update_claim_submit_' . $order->orderItemId . '_' . $order->returnId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
			}

			$reimbursment_details = "<div class='ordered-status'>
						Reimbursement Status: <span class='bold-font'>" . (($order->claimStatusLP == 'approved') ? 'Awaiting CN Details' : 'No Reimbursement') . "</span>
					</div>";
			if ($order->claimReimbursmentAmount) {
				$reimbursment_details = "<div class='ordered-status'>
						Reimbursement Status: <span class='bold-font ordered-status-value'>Reimbursed</span>
					</div>";
				$reimbursment_amount_details = "<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Reimbursed </div>
											<div class='order-item-field order-item-value '>&#8377 " . number_format($order->claimReimbursmentAmount, 2, '.', ',') . "</div>
										</div>";
			}

			$claim_status_class = ' claim_staus label-alert';
			$passbook_link = '';
			$claim_approved_button = '<div class="btn-group dropup button-actions" id="update_claim_status_button_' . $order->orderItemId . '_' . $order->returnId . '">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Mark Claim Approved <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-medium hold-on-click form" role="menu">
								<form action="#" class="update-claim-status" id="update_claim_status_form_' . $order->orderItemId . '_' . $order->returnId . '">
									<div class="form-body">
										<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
										<input type="hidden" name="returnId" value="' . $order->returnId . '" />
										<input type="hidden" name="trackingId" value="' . $order->forwardTrackingId . '" />
										<input type="hidden" name="claimId" value="' . $order->claimId . '" />
										<input type="hidden" name="claimStatus" value="approved" />
										<input type="hidden" name="action" value="update_claim_status" />
										<button class="btn btn-success btn-block" id="claim_approved_submit_' . $order->orderItemId . '_' . $order->returnId . '" type="submit">Approved!</button>
									</div>
								</form>
							</div>
						</div>';
			if ($order->claimStatusLP == "approved") {
				$claim_status_class = '';
				$passbook_link = "&nbsp;<a href='https://app.shiprocket.in/passbook?page=1&per_page=15&from=&to=&awb_code=" . $order->trackingId . "&category=&type=' target='_blank'><i class='fa fa-book' style='line-height: 7px;'></i></a>";
				$claim_approved_button = "";
			}

			if ($order->claimStatusLP == "rejected")
				$claim_status_class = ' claim_staus label-danger';

			$claim_details = "<div class='order-approval-container'>
					<div class='ordered-approval'>
						Claim Date: " . date("M d, Y", strtotime($order->claimCreatedDate)) . "
					</div>
					<div class='ordered-status'>
						Claim Status: <span class='bold-font ordered-status-value" . $claim_status_class . "' id='claim_status_" . $order->orderItemId . "_" . $order->returnId . "'>" . $order->claimStatusLP . "</span> " . $passbook_link . "
					</div>
					<div class='ordered-status' id='claim_id_" . $order->orderItemId . "_" . $order->returnId . "'>
						Claim ID: <a href='https://shiprocket.freshdesk.com/support/tickets/" . $order->claimId . "' target='_blank'>" . $order->claimId . "</a>
					</div>
					<div class='ordered-status'>
						Product Condition: <span class='bold-font'>" . ucfirst($order->claimProductCondition) . "</span>
					</div>
					" . $reimbursment_details . "
				</div>";

			if ($order->claimProductCondition == "undelivered")
				$received_details = "";
			else
				$delivered_details = "";
		}

		$content = "
					<div class='order-content order-content-" . $order->orderId . " row'>
						<div class='col-md-1'>
							<div class='bookmark'><a class='flag" . $flag_class . "' data-orderitemid='" . $order->orderItemId . "' href='javascript:void()'><i class='fa fa-bookmark'></i></a></div>
							<div class='ordered-product-image'>
								<img src='" . IMAGE_URL . "/uploads/products/" . $order->thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
							</div>
							<a class='view_order_link btn btn-primary' href='" . BASE_URL . "/shopify/order.php?orderId=" . $order->orderId . "' target='_blank'>View Order</a>
						</div>
						<div class='col-md-11'>
							<div class='order-content-container'>
								<div class='ordered-product-name'>
									<a target='_blank' href='//" . $order->account_domain . "/products/" . $order->handle . "'>" . $order->title . "</a> <div class='order_action_labels'><span class='label label-success'>" . $order->account_name . "</span> " . $sla . $return_approved . " " . $return_type_details . " " . $replacement_order . " <div class='order-button-actions'>" . str_replace('##CLAIM_APPROVED_BUTTON##', $claim_approved_button, $action) . "</div></div>
								</div>
								<div class='order-approval-container'>
									<div class='ordered-approval'>
										Return Date: " . date("M d, Y", strtotime($order->r_createdDate)) . "
									</div>
									" . $delivered_details . $received_details . "
									<div class='ordered-status'>
										Return Status: <span class='bold-font ordered-status-value'>" . $order->shipmentStatus . "</span>
									</div>
									<div class='ordered-status'>
										Product ID: <span class='bold-font'>" . implode(', ', $uids) . "</span>
									</div>
									" . $replacement_details . "
								</div>
								" . $claim_details . "
								<div class='order-complete-details'>
									<div class='order-details'>
										<div class='order-item-block-title'>ORDER DETAIL</div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Return ID </div><div class='order-item-field order-item-value'>" . $order->returnId . "</div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderItemId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order # </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderNumberFormated . "</a></div></div>
										" . $package_details . "
										<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sylvi.in/products/" . strtolower(str_replace(array(' - ', ' '), '-', $order->title)) . "'>" . $order->sku . "</a></div></div>
									</div>
									<div class='order-price-qty order-price-qty-" . $order->orderId . "'>
										<div class='order-item-block-title'>QTY</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Ordered </div>
											<div class='order-item-field order-item-value '>" . $order_qty . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Returned </div>
											<div class='order-item-field order-item-value '>" . $return_qty . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Amount </div>
											<div class='order-item-field order-item-value '>&#8377 " . $order->totalPrice . "</div>
										</div>
									</div>
									" . $reimbursment_amount_details . "
									<div class='order-dispatch'>
										<div class='order-item-block-title'>TRACKING DETAILS</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Logistics</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'>" . ($order->logistic == "5" ? "SelfShip" : $order->logistic) . " (" . $lp_link . ")</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Return</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='" . $order->lp_provider_tracking_url . $order->trackingId . "'>" . $order->trackingId . "</a></div>
										</div>
										" . $return_tracking_details . "
									</div>
									<div class='order-buyer-details'>
										<div class='order-item-block-title'>RETURN DETAILS</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Type:</div>
											<div class='order-item-field order-item-value order-item-type return_type'>" . ucwords(str_replace('_', ' ', $order->r_returnType)) . " " . $refund_payment_mode . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Reason:</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>" . $order->r_reason . "</div>
											<div class='order-item-field order-item-value order-item-sub-reason return_sub_reason'>" . str_replace(array('\r\n', '\n'), '<br/>', $order->r_subReason) . "</div>
										</div>
										<!--<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Comment:</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date comments'>" . str_replace(array('\r\n', '\n'), '<br/>', $order->r_comments) . "</div>
										</div>-->
									</div>
								</div>
							</div>
						</div>
					</div>
				";

		$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->orderId . "' data-account='" . $order->accountId . "' value='" . $order->orderId . "' />";
		$return['content'] = preg_replace('/\>\s+\</m', '><', $content);
		$return['multi_items'] = (!empty($multi_items) ? "Yes" : "No");
		$return['account'] = $order->account_name;
		$return['return_type'] = $return_type;
		$return['breached'] = empty($sla) ? "Yes" : "No";
		$return['sort_date'] = date('Ymd', strtotime($sort_date));
		$return['delivered_date'] = is_null($order->deliveredDate) ? "Undelivered" : date('M d, Y', strtotime($order->deliveredDate));
		$return['action'] = "";
	} else {
		$multi_items = (isset($order->multiProducts) && $order->multiProducts) ? "<span class='label label-info'>Multiple Items</span>" : "";
		$flag_class = ($order->isFlagged ? ' active' : '');
		$express = ($order->dispatchServiceTier == "Expedited" ? "<span class='label label-default'><i class='fa fa-shipping-fast'></i></span>" : "");
		$payment_type = "<span class='label label-info'>" . strtoupper($order->paymentType) . "</span>";
		$qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-warning'>" . $order->quantity . " units</span>";

		// SLA
		$sla = "";
		if ($type == 'new' || $type == 'packing' || $type == 'rtd') {
			if ((strtotime($order->dispatchByDate) - time() < 3600) && (strtotime($order->dispatchByDate) - time() > 0)) {
				$sla = "<span class='label label-danger'>SLA Breaching Soon!</span>";
			} else if (strtotime($order->dispatchByDate) - time() < 0) {
				$sla = "<span class='label label-danger'>SLA Breached!</span>";
			}
		}

		// CUSTOMER CONFIRMED
		$customer_confirmed = "";
		if ($order->isAutoApproved) {
			$customer_confirmed = '&nbsp;<span class="badge badge-success">Customer Confirmed</span>';
		}

		$replacement_order = "";
		$replacement_details = "";
		if ($order->replacementOrder) {
			$sq = $db->subQuery();
			$sq->where('r_replacementOrderId', $order->orderId);
			$sq->get(TBL_SP_RETURNS, null, 'orderItemId');

			$db->where("orderItemId", $sq);
			$order->replacement_order_id = $db->getValue(TBL_SP_ORDERS, 'orderNumberFormated');
			$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement</span>" : "";
			$replacement_details = "<div class='ordered-status'>
										Original Order Id: <span class='bold-font'>" . $order->replacement_order_id . "</span>
									</div>";
		}

		// ACTION
		$action = "";
		$shipped = "";
		if ($type == "pickup_pending") {
			if ($order->orderType == "SelfShip") {
				$db->where('is_selfShip', '1');
				$logistics = $db->get(TBL_SP_LOGISTIC);
				$logistic_options = '<option></option>';
				foreach ($logistics as $logistic) {
					$logistic_options .= '<option value="' . $logistic["lp_provider_id"] . '">' . $logistic["lp_provider_name"] . '</option>';
				}
				$action = '<div class="btn-group dropup button-actions selfShip-actions-' . $order->orderId . '">
								<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Self Ship Order <i class="fa fa-angle-up"></i></button>
								<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-large hold-on-click form" role="menu">
									<form action="#" class="selfship_order form-horizontal" id="selfship_order_' . $order->orderId . '">
										<div class="form-body">
											<div class="form-group">
												<input type="text" name="trackingId" class="form-control input-large trackingId" placeholder="Tracking Id" autocomplete="off" required>
											</div>
											<div class="form-group">
												<input type="text" name="trackingLink" class="form-control input-large trackingLink" placeholder="Tracking URL" autocomplete="off" required>
											</div>
											<div class="form-group">
												<input type="text" name="logistic" class="form-control input-large logistic" placeholder="Logistic (eg: Speed Post)" value="SpeedPost" autocomplete="off" required>
											</div>
											<div class="form-group">
												<select class="form-control select2 input-large logisticProvider" name="logisticProvider" data-allowClear="true" placeholder="Logistic Provider (eg: India Post)" autocomplete="off" required>
													' . $logistic_options . '
												</select>
											</div>
											<div class="form-group">
												<input type="hidden" name="orderId" value="' . $order->orderId . '" />
												<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
												<input type="hidden" name="accountId" value="' . $order->accountId . '" />
												<input type="hidden" name="pickedUpDate" value="' . date("c") . '" />
												<input type="hidden" name="action" value="mark_orders" />
												<input type="hidden" name="type" value="self_shipped" />
												<button class="btn btn-success btn-block" id="order_submit_' . $order->orderId . '" type="submit">Update to In-Transit</button>
											</div>
										</div>
									</form>
								</div>
							</div>';
			}
			$shipped = '<div class="ordered-approval">Shipped Date: ' . date("M d, Y H:i", strtotime($order->shippedDate)) . '</div>';

			if (time() > strtotime($order->shippedDate . ' +1 day'))
				$sla = "&nbsp;<span class='label label-danger'>Pickup Breached</span>";
		}

		// EDT & PICKUP DATE
		$edt = "";
		$pickeup = "";
		$tag = "";
		// $order_comments = json_decode($order->comments);
		$db->where('orderId', $order->orderId);
		$order_comments = $db->objectBuilder()->get(TBL_SP_COMMENTS);
		if ($type == "in_transit") {
			$pickeup = '<div class="ordered-approval">Pickup Date: ' . date("M d, Y H:i", strtotime($order->pickedUpDate)) . '</div>';
			$edt = $order->exDeliveryDate ? '<div class="ordered-approval">Expected Delivery: ' . date("M d, Y", strtotime($order->exDeliveryDate)) . '</div>' : "";
			if (time() - strtotime($order->pickedUpDate) > 432000) { // 5 DAYS
				$breach_days = round((time() - strtotime($order->pickedUpDate)) / 86400);
				$badge = 'default';
				if ($breach_days > 5) {
					$badge = 'warning';
				}
				if ($breach_days >= 10) {
					$badge = 'error';
				}
				$sla = "&nbsp;<span class='badge badge-" . $badge . "'>" . $breach_days . " days </span><span class='label label-danger'>Delivery SLA Breached</span>";
			}

			foreach ($order_comments as $comment) {
				if (strpos($comment->comment_for, 'call') !== FALSE)
					$tag = '&nbsp;<span class="badge badge-default badge-call-tag">' . ucwords(str_replace('_', ' ', $comment->comment_for)) . '</span>';
			}

			if ($order->orderType == "SelfShip") {
				$action = '<div class="btn-group button-actions selfShip-actions-' . $order->orderId . '">
								<form action="#" class="selfship_order selfship_order_delivered form-horizontal" id="selfship_order_' . $order->orderId . '">
									<input type="hidden" name="orderId" value="' . $order->orderId . '" />
									<input type="hidden" name="accountId" value="' . $order->accountId . '" />
									<input type="hidden" name="fulfillmentId" value="' . $order->fulfillmentId . '" />
									<input type="hidden" name="action" value="mark_orders" />
									<input type="hidden" name="type" value="self_delivered" />
									<button onclick="return confirm(\'Do you really want to mark this order as delivered?\')" class="btn btn-default btn-xs" id="update_ship_submit_' . $order->orderId . '" type="submit">Mark Delivered</button>
								</form>
							</div>';
			}
		}

		$ndr_status = "";
		if ($type == "ndr") {
			$db->where('orderId', $order->orderId);
			$db->where('commentFor', 'refund_escalation');
			if ($db->has(TBL_SP_COMMENTS))
				$ndr_status = '&nbsp;<span class="label label-warning">Refund Escalation Done </span>';
		}

		$shipment_status = "";
		$pickup_details = "";
		$buyer_details = "";
		$disbaled_checkbox = "";
		$fulfilment_sla = "";
		$address = json_decode($order->deliveryAddress);

		$google_map = " &nbsp;<a title='View Address on Map' href='https://maps.google.com/?q=" . $address->latitude . "," . $address->longitude . "&t=h&z=17' target='_blank'><svg xmlns='http://www.w3.org/2000/svg' height='12px' viewBox='0 0 576 512'><path d='M408 120C408 174.6 334.9 271.9 302.8 311.1C295.1 321.6 280.9 321.6 273.2 311.1C241.1 271.9 168 174.6 168 120C168 53.73 221.7 0 288 0C354.3 0 408 53.73 408 120zM288 152C310.1 152 328 134.1 328 112C328 89.91 310.1 72 288 72C265.9 72 248 89.91 248 112C248 134.1 265.9 152 288 152zM425.6 179.8C426.1 178.6 426.6 177.4 427.1 176.1L543.1 129.7C558.9 123.4 576 135 576 152V422.8C576 432.6 570 441.4 560.9 445.1L416 503V200.4C419.5 193.5 422.7 186.7 425.6 179.8zM150.4 179.8C153.3 186.7 156.5 193.5 160 200.4V451.8L32.91 502.7C17.15 508.1 0 497.4 0 480.4V209.6C0 199.8 5.975 190.1 15.09 187.3L137.6 138.3C140 152.5 144.9 166.6 150.4 179.8H150.4zM327.8 331.1C341.7 314.6 363.5 286.3 384 255V504.3L192 449.4V255C212.5 286.3 234.3 314.6 248.2 331.1C268.7 357.6 307.3 357.6 327.8 331.1L327.8 331.1z'/></svg></a>";
		if (empty($address->latitude) || empty($address->longitude))
			$google_map = " &nbsp;<a title='View Address on Map' href='https://maps.google.com/?q=" . urlencode(str_replace(array(',', '  '), array('', ' '), $address->address1 . ' ' . $address->address2 . ' ' . $address->city . ' ' . $address->province . ' ' . $address->zip)) . "&t=h&z=17' target='_blank'><svg xmlns='http://www.w3.org/2000/svg' height='15px' viewBox='0 0 576 512'><path d='M408 120C408 174.6 334.9 271.9 302.8 311.1C295.1 321.6 280.9 321.6 273.2 311.1C241.1 271.9 168 174.6 168 120C168 53.73 221.7 0 288 0C354.3 0 408 53.73 408 120zM288 152C310.1 152 328 134.1 328 112C328 89.91 310.1 72 288 72C265.9 72 248 89.91 248 112C248 134.1 265.9 152 288 152zM425.6 179.8C426.1 178.6 426.6 177.4 427.1 176.1L543.1 129.7C558.9 123.4 576 135 576 152V422.8C576 432.6 570 441.4 560.9 445.1L416 503V200.4C419.5 193.5 422.7 186.7 425.6 179.8zM150.4 179.8C153.3 186.7 156.5 193.5 160 200.4V451.8L32.91 502.7C17.15 508.1 0 497.4 0 480.4V209.6C0 199.8 5.975 190.1 15.09 187.3L137.6 138.3C140 152.5 144.9 166.6 150.4 179.8H150.4zM327.8 331.1C341.7 314.6 363.5 286.3 384 255V504.3L192 449.4V255C212.5 286.3 234.3 314.6 248.2 331.1C268.7 357.6 307.3 357.6 327.8 331.1L327.8 331.1z'/></svg></a>";

		if ($type != 'new' && $type != 'pending') {
			if ($type == 'packing' || $type == 'rtd' || $type == 'shipped') {
				if ((is_null($order->labelGeneratedDate) || !file_exists(ROOT_PATH . '/labels/single/FullLabel-Shopify-' . $order->orderId . '.pdf')) && $order->orderType != "pickup") {
					$label_class = 'generate_label';
					if ($order->enableSelfShip)
						$label_class = 'generate_selfship_label';
					$shipment_status = "<div class='label-status'>
											Label: <button class='btn tooltips " . $label_class . "' data-orderId=" . $order->orderId . " data-accountId=" . $order->accountId . " data-lpProvider='" . $order->lpProvider . "' data-placement='right' data-original-title='Generate Label'><i class='fa fa-xs fa-redo-alt'></i></button>
										</div>";
					$disbaled_checkbox = "disabled";
				}
				if ($order->orderType == "pickup")
					$disbaled_checkbox = "disabled";

				$fulfilment_sla = "<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>After</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchAfterDate)) . "</div>
										</div>
										<div class='order-item-block'>
											<b>
												<div class='order-item-field order-item-padding'>By</div>
												<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchByDate)) . "</div>
											</b>
										</div>";
			}

			if ($type != 'cancelled') {
				$lp_link = ($order->lp_provider_order_url != "" ? "<a href='" . $order->lp_provider_order_url . $order->lpOrderId . "' target='_blank'>" . $order->lp_provider_name . " <i class='fa fa-sm fa-external-link-alt'></i></a>" : $order->lp_provider_name);
				$pickup_details = "<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Logistic</div>
										<div class='order-item-field order-item-value order-item-confirm-by-date'>" . $order->logistic . " (" . $lp_link . ")</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Tracking</div>
										<div class='order-item-field order-item-value order-item-confirm-by-date'><a target='_blank' href='" . $order->lp_provider_tracking_url . $order->trackingId . "'>" . $order->trackingId . "</a></div>
									</div>";
				$buyer_details = "<div class='order-item-block-title buyer-details'>BUYER DETAILS</div>
									<div class='order-item-block'>
										<div class='result-style-address'>
											<div class='shipping-city-pincode-normal'>" . $address->City . ",<br />" . $address->StateOrRegion . "</div>
										</div>
									</div>";
			}
		} else if ($type == 'new') {
			$fulfilment_sla = "<div class='order-item-block'>
									<div class='order-item-field order-item-padding'>After</div>
									<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchAfterDate)) . "</div>
								</div>
								<div class='order-item-block'>
									<b>
										<div class='order-item-field order-item-padding'>By</div>
										<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchByDate)) . "</div>
									</b>
								</div>";
			$address_verification = "<div class='order-item-block-title buyer-details'>BUYER DETAILS &nbsp;<a title='Click to update address' class='view_address' data-accountid='" . $order->accountId . "' data-orderid='" . $order->orderId . "'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' height='12px'><path d='M362.7 19.32C387.7-5.678 428.3-5.678 453.3 19.32L492.7 58.75C517.7 83.74 517.7 124.3 492.7 149.3L444.3 197.7L314.3 67.72L362.7 19.32zM421.7 220.3L188.5 453.4C178.1 463.8 165.2 471.5 151.1 475.6L30.77 511C22.35 513.5 13.24 511.2 7.03 504.1C.8198 498.8-1.502 489.7 .976 481.2L36.37 360.9C40.53 346.8 48.16 333.9 58.57 323.5L291.7 90.34L421.7 220.3z'/></svg></a>" . $google_map . "</div>";
		}

		// COMMENTS
		$comments = "";
		$assuredDelivery = "";
		if ($type == "new" || $type == "packing" || $type == "in_transit" || $type == "ndr" || $type == "follow_up") {
			if ($order->isAssuredDelivery)
				$assuredDelivery = '&nbsp;<span class="badge badge-success">Assured Delivery </span>';
			$comments = "<div class='order-item-block'>
									<div class='order-item-field order-item-padding'><i class='fa fa-comment'></i></div>
									<div class='order-item-field order-item-value'><a class='view_comment' id='view_comment_" . $order->orderItemId . "' data-accountid='" . $order->accountId . "' data-orderitemid='" . $order->orderItemId . "' data-orderid='" . $order->orderId . "' data-type='" . $type . "'>Comments</a> " . (count($order_comments) > 0 ? '[' . count($order_comments) . ']' : '') . "</div>
								</div>";
		}

		$hasReturn = "";
		if ($type == "follow_up" && !is_null($order->returnId)) {
			$hasReturn = '&nbsp;<span class="badge badge-warning">Return </span>';
			$shipment_status .= "<div class='ordered-status'>
									Return Status: <span class='bold-font ordered-status-value'>" . $order->r_status . "</span>
								</div>
								<div class='ordered-status'>
									Return ID: <span class='bold-font ordered-status-value'>" . $order->returnId . "</span>
								</div>";
		}

		$checkoutVia = (strpos(strtolower($order->tags), 'gokwik') !== FALSE) ? "&nbsp;<span class='badge badge-success'>GoKwik</span> " : "";
		if ($checkoutVia == "")
			$checkoutVia = (strpos(strtolower($order->tags), 'magic checkout') !== FALSE) ? "&nbsp;<span class='badge badge-success'>Magic Checkout</span> " : "";

		$view_order_button = "";
		if (!$hide_view_order_button)
			$view_order_button = "<a class='view_order_link btn btn-primary' href='" . BASE_URL . "/shopify/order.php?orderId=" . $order->orderId . "' target='_blank'>View Order</a>";

		$content = "
					<div class='order-content order-content-" . $order->orderId . " row'>
						<div class='col-md-1'>
							<div class='bookmark'><a class='flag" . $flag_class . "' data-orderitemid='" . $order->orderItemId . "' href='javascript:void()'><i class='fa fa-bookmark'></i></a></div>
							<div class='ordered-product-image'>
								<img src='" . BASE_URL . '/' . loadImage(IMAGE_URL . "/uploads/products/" . $order->thumb_image_url, '100', '100') . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
							</div>
							" . $view_order_button . "
						</div>
						<div class='col-md-11'>
							<div class='order-content-container'>
								<div class='ordered-product-name'>
									<a target='_blank' href='//" . $order->account_domain . "/products/" . $order->handle . "'>" . $order->title . "</a> <div class='order_action_labels'><span class='label label-default'>" . $order->account_name . "</span> " . $checkoutVia . $customer_confirmed . $assuredDelivery . $hasReturn . $tag . $sla . " " . $express . $payment_type . " " . $replacement_order . " " . $ndr_status . " <div class='order-button-actions'>" . $action . "</div></div>
								</div>
								<div class='order-approval-container'>
									<div class='ordered-approval'>
										Order Date: " . date("M d, Y H:i", strtotime($order->orderDate)) . "
									</div>
									" . $replacement_details . $shipped . $pickeup . $edt . "
									<div class='ordered-status'>
										Shopify Status: <span class='bold-font ordered-status-value'>" . $order->spStatus . "</span>
									</div>
									" . $shipment_status . "
								</div>
								<div class='order-complete-details'>
									<div class='order-details'>
										<div class='order-item-block-title'>ORDER DETAIL</div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderItemId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order # </div><div class='order-item-field order-item-value'><a target='_blank' href='//" . $order->account_store_url . "/admin/orders/" . $order->orderId . "'>" . $order->orderNumberFormated . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>" . $order->sku . "</div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Fulfilment </div><div class='order-item-field order-item-value'>" . $order->orderType . "</div></div>
									</div>
									<div class='order-price-qty order-price-qty-" . $order->orderId . "'>
										<div class='order-item-block-title'>PRICE &amp; QTY</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Quantity </div>
											<div class='order-item-field order-item-value '>" . $qty . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Value </div>
											<div class='order-item-field order-item-value '>&#8377; " . number_format($order->sellingPrice * $order->quantity, 2, '.', '') . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Discount </div>
											<div class='order-item-field order-item-value '>&#8377; " . number_format($order->spDiscount * $order->quantity, 2, '.', '') . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Shipping </div>
											<div class='order-item-field order-item-value '>&#8377; " . number_format($order->shippingCharge, 2, '.', '') . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Total </div>
											<div class='order-item-field order-item-value'>&#8377; " . number_format($order->totalPrice * $order->quantity, 2, '.', '') . "</div>
										</div>
									</div>
									<div class='order-dispatch'>
										<div class='order-item-block-title'>SHIP</div>
										" . $fulfilment_sla . "
										" . $pickup_details . "
										" . $comments . "
									</div>
									<div class='order-buyer-details'>
										" . $address_verification . "
										<div class='order-item-block'>
											<div class='result-style-address'>
												<div class='shipping-name'>" . $address->name . "</div>
												<div class='shipping-address'>" . $address->address1 . "<br />" . $address->address2 . "</div>
												<div class='shipping-city-pincode-normal'>" . $address->city . " - " . $address->zip . " [" . $address->province_code . "]</div>
												<div class='buyer-mobile'><a href='tel:+91" . ((int) str_replace(array(" ", "+91"), "", $address->phone)) . "' rel='nofollow'>" . ((int) str_replace(array(" ", "+91"), "", $address->phone)) . "</a></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				";

		$return['checkbox'] = "<input type='checkbox' " . $disbaled_checkbox . " class='checkboxes grp_" . $order->orderId . "' data-group='grp_" . $order->orderId . "' data-account='" . $order->accountId . "' data-shipmentid='" . $order->lpShipmentId . "' value='" . $order->orderId . "' />";
		$return['content'] = trim(preg_replace('/\>\s+\</m', '><', $content));
		$return['multi_items'] = ($order->multiProducts ? "Yes" : "No");
		$return['account'] = 'Style Feathers';
		$return['payment_type'] = $order->paymentType;
		$return['order_date'] = date('YmdHis', strtotime($order->orderDate));
		$return['ship_date'] = date('YmdHis', strtotime($order->shippedDate));
		if ($type == "new" || $type == "packing" || $type == "rtd")
			$return['ship_date'] = date('YmdHis', strtotime($order->dispatchByDate));
		if ($type == "in_transit")
			$return['ship_date'] = date('YmdHis', strtotime($order->pickedUpDate));
		$return['logistic_provider'] = $order->lpProvider;
		// $return['tags'] = $order->lpProvider;
		$return['action'] = "";
	}
	return $return;
}

function update_orders_to_rtd($account_id, $tracking_id)
{
	global $db;

	$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
	$db->where('f.trackingID', $tracking_id);
	$orders = $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, 'fulfillmentId, status, spStatus, o.orderId, accountId, title');
	$i = 0;
	foreach ($orders as $order) {
		if ($order->status == 'rtd' || $order->status == 'shipped') {
			$return[$i]['title'] = $order->title;
			$return[$i]['order_item_id'] = $order->orderId;
			$return[$i]['tracking_id'] = $tracking_id;
			$return[$i]['message'] = 'Order already marked as ' . ucwords($order->status);
			$return[$i]['type'] = "error";
		} else if ($order->accountId != $account_id) { // Other account order verification
			$return[$i]['title'] = $order->title;
			$return[$i]['order_item_id'] = $order->orderId;
			$return[$i]['tracking_id'] = $tracking_id;
			$return[$i]['message'] = 'Order of another account.';
			$return[$i]['type'] = "error";
		} else if ($order->spStatus == "CANCELED" || $order->status == "cancelled") { // CANCELLED ORDER
			$return[$i]['title'] = $order->title;
			$return[$i]['order_item_id'] = $order->orderId;
			$return[$i]['tracking_id'] = $tracking_id;
			$return[$i]['message'] = '<span class="label label-danger">Cancelled</span>';
			$return[$i]['type'] = "error";
		} else {
			$details = array('status' => 'rtd', 'rtdDate' => $db->now());
			$db->where('status', 'cancelled', '!=');
			$db->where('orderId', $order->orderId);
			if ($db->update(TBL_SP_ORDERS, $details)) {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderId;
				$return[$i]['tracking_id'] = $tracking_id;
				$return[$i]['message'] = 'Successful';
				$return[$i]['type'] = "success";
			}
		}
	}

	return $return;
}

function update_orders_to_shipped($account_id, $tracking_id, $order_id = "")
{
	global $db;

	$db->where('trackingID', $tracking_id);
	$orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS, NULL, 'status, orderId, account_id, title');
	foreach ($orders as $order) {
		if ($order->status == "packing") {
			// $log->write('UPDATE: Order not yet HANOVERED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId, 'shipped-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order not HANOVERED!!!';
			$return['type'] = "error";
			return $return;
		}

		if ($order->status == 'shipped') {
			// $log->write('UPDATE_FK: Order already marked as '.$order->status.' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order already marked as ' . ucwords($order->status);
			$return['type'] = "error";
			return $return;
		}

		// Other account order verification
		if ($order->account_id != $account_id) {
			// $log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order of another account.';
			$return['type'] = "error";
			return $return;
		}

		// CANCELLED ORDER
		if ($order->az_status == "CANCELED" || $order->status == "cancelled") {
			// $log->write('UPDATE_FK: Order marked cancelled with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId, 'rtd-orders');
			// $return = $this->update_order_to_cancel($trackingId, NULL, $order->quantity);
			$return['title'] = $order->title . ' <span class="label label-primary">' . $order->order_type . '</span>';
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = '<span class="label label-danger">Cancelled</span>';
			$return['type'] = "error";
			return $return;
		}

		$details = array('status' => 'shipped', 'shippedDate' => date('Y-m-d H:i:s'));
		$db->where('orderId', $order->orderId);
		if ($db->update(TBL_AZ_ORDERS, $details)) {
			// $log->write('OI: '.$order->orderId."\tRTD", 'order-status');
			// $log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId .' Response '.$rtd_return, 'amz-rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Successful';
			$return['type'] = "success";

			// @TODO: PAYMENT INTEGRATION
			// if ($this->payments)
			// 	$this->payments->update_order_payout($order->orderId);
		}
	}

	return $return;
}

function update_returns_status($tracking_id, $status, $is_order_id = false, $is_manual = false, $returnId = NULL, $productCondition = "", $refund = true)
{
	global $db, $stockist, $account, $log, $current_user;

	// http://localhost/fims-live/shopify/ajax_load.php?token=1680981541709&action=mark_orders&type=return_completed&tracking_id=243198221266684&account_id=undefined

	if ($status == "completed") {
		$db->where('(r.r_status = ? OR r.r_status = ? OR r.r_status LIKE ?)', array('return_received', 'return_completed', 'return_claimed%'));
	} else {
		$db->where('(r.r_status = ? OR r.r_status = ? OR r.r_status = ? OR r.r_status = ? OR r.r_status = ? OR r.r_status LIKE ? OR (r.r_status = ? AND f.shipmentStatus = ?) OR r.r_status IS NULL)', array('start', 'in_transit', 'out_for_delivery', 'delivered', 'return_received', 'return_claimed%', 'return_completed', 'lost'));
	}
	$db->join(TBL_SP_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
	$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
	$db->joinWhere(TBL_FULFILLMENT . ' f', '(f.fulfillmentType = ? OR f.fulfillmentType = ? )', array('return', 'reverse'));
	if ($is_order_id)
		$db->where('r.orderItemId', $tracking_id);
	else
		$db->where('f.trackingId', $tracking_id);

	if (!is_null($returnId))
		$db->where('r.returnId', $returnId);

	$db->join(TBL_SP_ACCOUNTS . ' a', 'a.account_id=o.accountId');
	$db->join(TBL_SP_REFUNDS . ' rf', 'rf.orderItemId=o.orderItemId', 'left');
	$db->join(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.pg_provider_id=a.account_active_refund_pg_id');
	if ($orders = $db->ObjectBuilder()->get(TBL_SP_RETURNS . ' r', null, "o.orderId, o.accountId, o.locationId, o.title, o.quantity, o.variantId, o.customerId, o.isFlagged, o.billingAddress, o.deliveryAddress, o.orderNumberFormated, o.totalPrice, o.shippingCharge, o.paymentType, o.paymentGateway, o.paymentGatewayOrder, o.paymentGatewayAmount, o.paymentGatewayDiscount, COALESCE(r.r_uid, o.uid) as uid, r.*, pg.pg_provider_id, pg.pg_provider_key, pg.pg_provider_secret, pg.pg_provider_account, rf.refundId, a.account_active_whatsapp_id, f.fulfillmentId, f.shipmentStatus, f.deliveredDate")) {
		$i = 0;
		$order_data = array();
		foreach ($orders as $order) {
			$log->write(json_encode($order), 'shopify-returns');

			if (($order->r_status == 'return_received' && $status == "received") || ($order->r_status == 'return_completed' && $status == "completed")) {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderItemId;
				$return[$i]['tracking_id'] = $tracking_id;
				$return[$i]['message'] = 'Return already marked ' . strtoupper(str_replace('_', ' ', $order->r_status));
				$return[$i]['type'] = "error";
			} else if (($order->r_status != 'return_received' && $order->r_status != 'return_claimed_undelivered' && $order->r_status != 'return_claimed') && $status == "completed") {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderItemId;
				$return[$i]['tracking_id'] = $tracking_id;
				$return[$i]['message'] = 'Return status is ' . $order->r_status;
				$return[$i]['type'] = "error";
			} else if ($order->r_source == "CUSTOMER_RETURN" && $status == "completed" && $order->r_quantity != $order->quantity && !$is_manual) {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderItemId;
				$return[$i]['trackingId'] = $trackingId;
				$return[$i]['message'] = 'Customer Return with qty mismatch. Acknowledge Manually.';
				$return[$i]['type'] = "error";
			} else {
				$details = array(
					'r_status' => 'return_' . $status,
				);

				if ($status == "received") {
					$details['r_receivedDate'] = date('Y-m-d H:i:s');
					if ($order->shipmentStatus != "delivered" && is_null($order->deliveredDate)) {
						$db->where('fulfillmentId', $order->fulfillmentId);
						$db->update(TBL_FULFILLMENT, array('shipmentStatus' => 'delivered', 'deliveredDate' => $db->now()));
					}
					// if (is_null($order->r_deliveredDate))
					// 	$details['r_deliveredDate'] = date('Y-m-d H:i:s');
				}

				if ($status == "completed")
					$details['r_completionDate'] = date('Y-m-d H:i:s');

				$db->where('returnId', $order->returnId);
				$db->where('r_status', 'cancelled', '!=');
				if ($db->update(TBL_SP_RETURNS, $details)) {
					if ($status == "received") {
						// RESOLVE CLAIM IF DONE FOR UNDELIVERD SHIPMENT
						$db->where('returnId', $order->returnId);
						// $db->where('claimStatus', 'pending');
						$db->where('claimProductCondition', 'undelivered');
						$claim = $db->ObjectBuilder()->getOne(TBL_SP_CLAIMS);
						if ($claim) {
							if ($claim->claimStatus == "pending") {
								$db->where('claimId', $claim->claimId);
								$update_details = array(
									'claimStatus' => 'resolved',
									'claimComments' => 'Return Received on ' . date('Y-m-d H:i:s') . ' (' . $current_user["user_nickname"] . ')',
									'closedBy' => $current_user['userID'],
									'closedDate' => $db->now()
								);
								$db->update(TBL_SP_CLAIMS, $update_details);
							}

							if ($claim->claimStatus == "resolved" && $order->shipmentStatus == "lost") {
								$db->where('claimId', $claim->claimId);
								$update_details = array(
									'claimStatus' => 'resolved',
									'claimComments' => 'Return Received on ' . date('Y-m-d H:i:s') . ' (' . $current_user["user_nickname"] . ')',
									'claimNotes' => 'Soft Refund reversed',
									'claimReimbursmentAmount' => '0',
									'updatedDate' => $db->now()
								);
								$db->update(TBL_SP_CLAIMS, $update_details);
							}
						}
					}

					if ($status == "completed") {
						// @TODO: PAYMENT INTEGRATION
						// if ($this->payments)
						// 	$this->payments->fetch_payout($order->orderId);
						$og_order = find_initial_order($order->orderItemId);

						// PROCESS THE REPLACEMTNT ORDER
						// if (!is_null($order->r_replacementOrderId)){
						if (strtolower($order->r_returnType) == "replace") {
							$account = get_current_account($order->accountId);
							$sp = new shopify_dashboard($account);

							$order_data["billingAddress"] = json_decode($order->billingAddress);
							$order_data["deliveryAddress"] = json_decode($order->deliveryAddress);
							$order_data['customerId'] = $order->customerId;

							// $key = array_search($order->variantId, array_column($order_data['lineItems'], 'variant_id'));
							// if ($key !== false){
							// 	$order_data['lineItems'][$key]['quantity'] += 1;
							// } else {
							$order_data['lineItems'][] = array(
								"variant_id" => $order->variantId,
								"quantity" => 1
							);
							// }

							if (is_null($order->r_replacementOrderId) && $i == count($orders) - 1) {
								$replacementOrderId = $sp->create_replacement_order_sp((object)$order_data);
								$db->where('returnId', $order->returnId);
								$db->where('r_returnType', 'replace');
								$db->update(TBL_SP_RETURNS, array('r_replacementOrderId' => $replacementOrderId));
							} else { // TEMP REMOVE IF ELSE AFTER 20 JUNE 2023
								$replacementOrderId = $order->r_replacementOrderId;
							}

							$dbd = date('H:m', time()) > '12' ? date('Y-m-d H:i:s', strtotime('tomorrow 12 PM')) : date('Y-m-d H:i:s', strtotime('today 12 PM'));
							$dbd = date('D', strtotime($dbd)) === 'Sun' ? date('Y-m-d H:i:s', strtotime($dbd . ' + 1 day')) : $dbd;
							$db->where('orderId', $replacementOrderId);
							$db->update(TBL_SP_ORDERS, array('hold' => 0, "dispatchByDate" => $dbd));

							$comment_data = array(
								'comment' => 'Return Received',
								'orderId' => $order->orderId,
								'commentFor' => 'order_alert',
								'userId' => $current_user['userID'],
							);
							$db->insert(TBL_SP_COMMENTS, $comment_data);

							$comment_data['comment'] = 'Return Received & Completed';
							$comment_data['orderId'] = $replacementOrderId;
							$db->insert(TBL_SP_COMMENTS, $comment_data);
						}

						$refund_amount = (($og_order->totalPrice - $og_order->shippingCharge - $og_order->paymentGatewayDiscount) / $order->r_quantity);
						// PROCESS REFUND FOR COD ORDERS
						if ($refund && $order->r_returnType == "refund" && $og_order->paymentType == "cod" && $order->r_source == "customer_return") {
							$api = new Api($order->pg_provider_key, $order->pg_provider_secret);
							if (!is_null($order->refundId)) { // CANCEL PREVIOUS LINK
								$response = $api->PayoutLink->fetch($order->refundId)->cancel();
								$data = array(
									'refundStatus' => $response->status,
									'cancelledDate' => $db->now()
								);
								$db->where('refundId', $order->refundId);
								$db->update(TBL_SP_REFUNDS, $data);
							}

							$customer_details = json_decode($order->billingAddress);
							$payment_details = array(
								'account_number' => $order->pg_provider_account,
								'amount' => (int) $refund_amount * 100,
								'currency' => 'INR',
								'accept_partial' => false,
								'description' => 'Refund for Sylvi Order #' . $order->orderNumberFormated,
								'contact' => array(
									"type" => "customer",
									'name' => $customer_details->name,
									'email' => $customer_details->email,
									'contact' => substr(str_replace(array('+', ' '), '', $customer_details->phone), -10),
								),
								'send_sms' => true,
								'send_email' => true,
								"purpose" => "refund",
								'reminder_enable' => true,
								'notes' => array(),
								// 'callback_url' => BASE_URL.'/',
								// 'callback_method' => 'POST'
							);
							$response = $api->PayoutLink->create($payment_details);
							$log->write(json_encode($response), 'shopify-refund');
							$data = array(
								'refundId' => $response->id,
								'orderId' => $order->orderId,
								'orderItemId' => $order->orderItemId,
								'refundLinkURL' => $response->short_url,
								'refundStatus' => $response->status,
								'refundAmount' => $refund_amount,
								'refundPg' => $order->pg_provider_id,
								'createdDate' => $db->now()
							);
							$db->insert(TBL_SP_REFUNDS, $data);

							// ADD REFUND DETAILS TO COMMENT 
							$comment_data = array(
								'comment' => 'Refund Initiated\r\nRef: ' . $response->short_url . '\r\nAmt: ' . $refund_amount,
								'orderId' => $order->orderId,
								'commentFor' => 'refund',
								'userId' => $current_user['userID'],
							);
							$db->insert(TBL_SP_COMMENTS, $comment_data);

							// // SEND WHATSAPP NOTIFICATION
							// $template = 'cod-refund';
							// $post_params = array(
							// 	'to' => '91' . substr(str_replace(array('+', ' '), '', $customer_details->phone), -10),
							// 	// SHOULD INCLUDE ISD CODE and +
							// 	'name' => $customer_details->name,
							// 	'params' => array(
							// 		$customer_details->name,
							// 		number_format($refund_amount, 2, '.', ','),
							// 		$order->orderNumberFormated,
							// 		$response->short_url
							// 	)
							// );
							// $whatsApp = new whatsApp($order->account_active_whatsapp_id);
							// $whatsApp->sendMessage($template, $post_params);
						}

						// PROCESS REFUND FOR PREPAID ORDER RTO/RVP RETURN
						if ($refund && $order->r_returnType == "refund" && $og_order->paymentType == "prepaid") {
							// REFUND VIA SHOPIFY
							$account = get_current_account($order->accountId);
							$sp = new shopify_dashboard($account);
							$data = array(
								"refund" => array(
									"shipping" => array(
										"full_refund" => false
									),
									"refund_line_items" => array(
										array(
											"line_item_id" => $og_order->orderItemId,
											"quantity" => $order->r_quantity,
											"restock_type" => "return"
										),
									)
								)
							);
							try {
								$refund_transactions = $sp->shopify->Order($og_order->orderId)->Refund()->calculate($data);
								if ($refund_transactions['transactions']) {
									$log->write("\nRefund Transactions\nResponse: " . json_encode($refund_transactions) . "\nOrder ID: " . $og_order->orderId . "\nOrderItemId: " . $og_order->orderItemId . "\nData:" . json_encode($data), 'shopify-refund');
									$refund_transaction = NULL;
									foreach ($refund_transactions['transactions'] as $transaction) {
										if ($transaction['kind'] == "suggested_refund") {
											$refund_transaction = $transaction;
											break;
										}
									}
									if ($refund_transaction) {
										$refund_details = array(
											"refund" => array(
												"currency" => $refund_transaction['currency'],
												"shipping" => array(
													"amount" => 0
												),
												"notify" => true,
												"refund_line_items" => array(
													array(
														"line_item_id" => $og_order->orderItemId,
														"quantity" => $order->r_quantity,
														"restock_type" => "return",
														"location_id" => $order->locationId
													),
												),
												"transactions" => array(
													array(
														"parent_id" => $refund_transaction['parent_id'],
														"amount" => $refund_amount,
														"kind" => "refund",
														"gateway" => $refund_transaction['gateway']
													)
												)
											)
										);
										try {
											$refund = $sp->shopify->Order($og_order->orderId)->Refund()->post($refund_details['refund']);
											sleep(2); // WAIT FOR TWO SECONDS TO COMPLETE THE REFUND PROCESS ON SHOPIFY AND PAYMENT GATEWAY
											//
											// TO DO: GET REFUND REFERENCE DETAILS FROM PAYMENT GATEWAY AND SEND NOTIFICATION CUSTOMER
											// 

											if (strpos(strtolower($order->paymentGateway), 'razorpay') !== FALSE) {
												$api = new Api($order->pg_provider_key, $order->pg_provider_secret);
												try {
													$payments = $api->order->fetch($og_order->paymentGatewayOrder)->payments();
													$log->write("\nRefund PG\nResponse: " . json_encode($payments["items"]) . "\nOrder ID: " . $og_order->orderId . "\nOrderItemId: " . $og_order->orderItemId . "\nPG Id: " . $og_order->paymentGatewayOrder, 'shopify-refund');
													// print_r($payments->items);
													foreach ($payments["items"] as $payment_item) {
														$log->write("\nRefund PG Item \nResponse: " . json_encode($payment_item), 'shopify-refund');
														// var_dump($payment_item->id); // string(18) "pay_LWgA7SLY0HNJa8"
														// var_dump($payment_item->status); // string(8) "refunded"
														// var_dump($payment_item->amount_refunded); //int(106165)
														// var_dump($payment_item->refund_status); // string(4) "full"

														if ($payment_item["status"] == "refunded") {
															try {
																$refunds = $api->payment->fetch($payment_item["id"])->fetchMultipleRefund();
																foreach ($refunds["items"] as $refund_item) {
																	if ($refund_item["status"] == "processed") {
																		// var_dump($refund_item->id); //string(19) "rfnd_LbbOv44w5BGBP3"
																		// var_dump($refund_item->amount); //int(106165)

																		// ADD REFUND DETAILS TO COMMENT 
																		$comment_data = array(
																			'comment' => 'Refund Initiated\r\nRef: ' . $refund_item["id"] . '\r\nAmt: ' . $refund_amount,
																			'orderId' => $order->orderId,
																			'commentFor' => 'refund',
																			'userId' => $current_user['userID'],
																		);
																		$db->insert(TBL_SP_COMMENTS, $comment_data);
																		continue;
																	}
																}
															} catch (Exception $e) {
																$log->write("\nRefund PG Details Error\nResponse: " . json_encode($refunds) . "\nOrder ID: " . $og_order->orderId . "\nOrderItemId: " . $og_order->orderItemId . "\nBackTrace: " . print_r(debug_backtrace(), true), 'shopify-refund');
															}
														}
													}
												} catch (Exception $e) {
													$log->write("\nRefund PG Error\nResponse: " . json_encode($payments) . "\nOrder ID: " . $og_order->orderId . "\nOrderItemId: " . $og_order->orderItemId . "\nBackTrace: " . print_r(debug_backtrace(), true), 'shopify-refund');
												}
											} else {
												// ADD REFUND DETAILS TO COMMENT 
												$comment_data = array(
													'comment' => 'Refund Initiated\r\nRef: \r\nAmt: ' . $refund_amount,
													'orderId' => $order->orderId,
													'commentFor' => 'refund',
													'userId' => $current_user['userID'],
												);
												$db->insert(TBL_SP_COMMENTS, $comment_data);
												// 	break;
											}
											// $refund = '{"refund":{"id":823679451345,"order_id":4083104448721,"created_at":"2022-06-13T19:34:44+05:30","note":null,"user_id":null,"processed_at":"2022-06-13T19:34:44+05:30","restock":false,"duties":[],"total_duties_set":{"shop_money":{"amount":"0.00","currency_code":"INR"},"presentment_money":{"amount":"0.00","currency_code":"INR"}},"admin_graphql_api_id":"gid:\/\/shopify\/Refund\/823679451345","refund_line_items":[],"transactions":[{"id":5123698720977,"order_id":4083104448721,"kind":"refund","gateway":"razorpay_cards_upi_netbanking_wallets_","status":"pending","message":"Transaction pending","created_at":"2022-06-13T19:34:40+05:30","test":false,"authorization":"order_JY7mkCm44kpBXX|pay_JY7t3pAf5gypK1","location_id":null,"user_id":null,"parent_id":5105096065233,"processed_at":"2022-06-13T19:34:40+05:30","device_id":null,"error_code":null,"source_name":"5396439","receipt":{},"amount":"664.05","currency":"INR","admin_graphql_api_id":"gid:\/\/shopify\/OrderTransaction\/5123698720977"}],"order_adjustments":[{"id":185932579025,"order_id":4083104448721,"refund_id":823679451345,"amount":"-664.05","tax_amount":"0.00","kind":"refund_discrepancy","reason":"Refund discrepancy","amount_set":{"shop_money":{"amount":"-664.05","currency_code":"INR"},"presentment_money":{"amount":"-664.05","currency_code":"INR"}},"tax_amount_set":{"shop_money":{"amount":"0.00","currency_code":"INR"},"presentment_money":{"amount":"0.00","currency_code":"INR"}}},{"id":185932611793,"order_id":4083104448721,"refund_id":823679451345,"amount":"664.05","tax_amount":"0.00","kind":"refund_discrepancy","reason":"Refund discrepancy","amount_set":{"shop_money":{"amount":"664.05","currency_code":"INR"},"presentment_money":{"amount":"664.05","currency_code":"INR"}},"tax_amount_set":{"shop_money":{"amount":"0.00","currency_code":"INR"},"presentment_money":{"amount":"0.00","currency_code":"INR"}}}]}}';
											$log->write("\nRefund\nResponse: " . json_encode($refund) . "\nOrder ID: " . $order->orderId . "\nOrderItemId: " . $order->orderItemId . "\nData:" . json_encode($refund_details), 'shopify-refund');
										} catch (Exception $e) {
											$log->write("\nRefund\nError: " . $e->getMessage() . "\nOrder ID: " . $order->orderId . "\nOrderItemId: " . $order->orderItemId . "\nData:" . json_encode($refund_details), 'shopify-refund');
										}
									}
								} else {
									$log->write("\nRefund Transactions\nError: Order already refunded\nResponse:" . json_encode($refund_transactions) . "\nOrder ID: " . $order->orderId . "\nOrderItemId: " . $order->orderItemId . "\nData:" . json_encode($data), 'shopify-refund');
								}
							} catch (Exception $e) {
								$return[$i]['refund']['error'] = $e->getMessage();
								$log->write("\nRefund Transactions\nError: " . $e->getMessage() . "\nOrder ID: " . $order->orderId . "\nOrderItemId: " . $order->orderItemId . "\nData:" . json_encode($data) . "\nBackTrace: " . print_r(debug_backtrace(), true), 'shopify-refund');
							}
						}

						// PROCESS GIFT CARD RVP RETURN
						if ($order->r_returnType == "gift_card" && $order->r_source == "customer_return") { // GIFT CARDS CAN BE ONLY ISSUED VIA API WITH SHOPIFY PLUS SUBSCRIPTION
							$comment_data = array(
								'comment' => 'Issue Gift Card worth &#8377;' . number_format($refund_amount, 2, '.', ','),
								'orderItemId' => $order->orderItemId,
								'comment_for' => 'gift_card',
								'created_on' => date('c'),
								'created_by' => 0
							);

							$db->where('orderId', $order->orderId);
							$current_comments = json_decode($db->getValue(TBL_SP_ORDERS, 'comments'));
							if ($current_comments)
								$comments = array_merge($current_comments, array($comment_data));
							else
								$comments = array($comment_data);

							$data['isFlagged'] = true;
							$data['comments'] = json_encode($comments);
							$db->where('orderId', $order->orderId);
							$db->update(TBL_SP_ORDERS, $data);
						}

						// UID DRIVE
						if (!is_null($order->uid) && ($productCondition != "undelivered" && $productCondition != "wrong")) {
							$uids = json_decode($order->uid, true);
							foreach ($uids as $uid) {
								$stockist->update_inventory_status($uid, 'qc_pending');
								$stockist->add_inventory_log($uid, 'sales_return', 'Shopify Return :: ' . $order->orderItemId);
							}
						}
					}

					$db->commit();
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['tracking_id'] = $tracking_id;
					$return[$i]['is_flagged'] = $order->isFlagged;

					if ($status == "received") {
						$return[$i]['message'] = 'Return Received Successfully';
					} else {
						$return[$i]['message'] = 'Return Completed Successfully';
					}
					$return[$i]['type'] = "success";
				} else {
					$db->rollback();
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['tracking_id'] = $tracking_id;
					$return[$i]['message'] = 'Return Not Updated. Please retry.';
					$return[$i]['type'] = "error";
					$return[$i]['error'] = $db->getLastError();
				}
			}
			$i++;
		}
	} else {
		$return[0]['title'] = "";
		$return[0]['order_item_id'] = "";
		$return[0]['tracking_id'] = $tracking_id;
		$return[0]['message'] = 'Return Not Found';
		$return[0]['type'] = "error";
		$return[0]['data'] = $db->getLastQuery();
	}
	return $return;
}

function update_order_inventory_status($order_id, $order_item_id, $type, $uid = NULL)
{
	global $db, $stockist;

	if (is_null($uids)) {
		$db->where('orderItemId', $order_item_id);
		$db->where('orderId', $order_id);
		$uid = $db->getValue(TBL_AZ_ORDERS, 'uid');
	}

	$uids = json_decode($uid, true);
	if (!empty($uids)) {
		$update_status = array();
		foreach ($uids as $uid) {
			// CHANGE UID STATUS
			$inv_status = $stockist->update_inventory_status($uid, 'qc_pending');
			if ($type == "Canceled")
				$status = "Cancellation";
			// ADD TO LOG
			$log_status = $stockist->add_inventory_log($uid, 'sales_return', 'Shopify ' . $status . ' :: ' . $order_id);
			if ($inv_status && $log_status)
				$update_status[$uid] = true;
			else
				$update_status[$uid] = false;
		}

		if (in_array(false, $update_status))
			return false;
	}

	return true;
}

function get_invoice_number($account_id)
{
	global $db;

	$db->where('account_id', $account_id);
	$invoice = $db->ObjectBuilder()->getOne(TBL_SP_ACCOUNTS, 'account_invoice_prefix, account_invoice_number, account_invoice_add_fy');

	$year = "";
	if ($invoice->account_invoice_add_fy)
		$year = date('m') > 3 ? '-' . date('y') . (date('y') + 1) : '-' . (date('y') - 1) . '' . date('y');

	$invoiceNumberFormated = $invoice->account_invoice_prefix . $year . '-' . sprintf('%06d', $invoice->account_invoice_number);

	$db->where('account_id', $account_id);
	$db->update(TBL_SP_ACCOUNTS, array('account_invoice_number' => (int) $invoice->account_invoice_number + 1));

	return $invoiceNumberFormated;
}

function get_comments($order_id, $output = false)
{
	global $db;

	$db->where('party_id', 0);
	$users = $db->objectBuilder()->get(TBL_USERS, NULL, 'userID, display_name');

	$db->where('orderId', $order_id);
	$db->where('commentFor', 'undelivered_shipment', '!=');
	$db->orderBy('createdDate', 'DESC');
	$comments = $db->objectBuilder()->get(TBL_SP_COMMENTS);

	$comment_data = "";
	if (!empty($comments)) :
		$comment_data .= '<div class="timeline timeline-1"><div class="timeline-sep bg-primary-opacity-20"></div>';
		foreach ($comments as $comment) {
			$assuredDelivery = "";
			$class = "";
			if ($comment->commentAdditional == 'assured_delivery')
				$assuredDelivery = '<span class="badge badge-warning">Assured Delivery</span>';

			switch ($comment->commentFor) {
				case 'confirmation_call':
					$class = '<i class="fa fa-phone fa-flip-horizontal"></i>';
					break;

				case 'auto_confirmed':
					$class = '<i class="fa fa-check-double"></i>';
					break;

				case 'self_pickup':
					$class = '<i class="fa fa-motorcycle"></i>';
					break;

				case 'call_attempted':
					$class = '<i class="fa fa-phone-slash fa-flip-horizontal"></i>';
					break;

				case 'request_address':
					$class = '<i class="fa fa-map-marker"></i>';
					break;

				case 'address_updated':
					$class = '<i class="fa fa-map-marker-alt"></i>';
					break;

				case 'call_rejected':
					$class = '<i class="fa fa-tty"></i>';
					break;

				case 'cancellation_call':
				case 'cancelled':
					$class = '<i class="fa fa-ban"></i>';
					break;

				case 'cancellation_request':
					$class = '<i class="fa fa-times-circle"></i>';
					break;

				case 'general_comment':
					$class = '<i class="fa fa-comment-dots"></i>';
					break;

				case 'return_initiated':
				case 'return':
					$class = '<i class="fa fa-reply-all"></i>';
					break;

				case 'return_cancelled':
					$class = '<span class="fa-stack">
					    <i class="fa fa-reply-all fa-stack-1x"></i>
					    <i class="fa fa-ban fa-stack-2x" style="background: none; transform: rotate(90deg); line-height: 0.95em;"></i>
					  </span>';
					break;

				case 'refund':
					$class = '<i class="fa fa-rupee-sign"></i>';
					// $class = '<span class="fa-stack fa-2x" style="font-size: 0.75em;"><i class="fa fa-undo fa-stack-2x"></i><i class="fa fa-rupee-sign fa-stack-1x"></i></span>';
					break;

				case 'order_alert':
					$class = '<i class="fa fa-bell"></i>';
					break;

				case 'assured_delivery':
					$class = '<i class="fa fa-shipping-fast"></i>';
					break;

				case '1st_call':
				case '2nd_call':
				case 'final_call':
					$class = '<i class="fa fa-phone"></i>';
					break;

				case 'ndr_approved':
					$class = '<i class="fa fa-warehouse"></i>';
					break;

				case 'escalation':
					$class = '<i class="fa fa-running"></i>';
					break;

				case 'follow_up':
					$class = '<i class="fa fa-user-plus"></i>';
					break;

				case 'gift_card':
					$class = '<i class="fa fa-gift"></i>';
					break;

				case 'error':
					$class = '<i class="fa fa-exclamation-circle"></i>';
					break;

				case 'refund_escalation':
					$class = '<i class="fa fa-undo-alt"></i>';
					// $class = '<div class="text-center fa-5x">
					// 			<span class="fa-stack">
					// 				<i class="fa fa-gear fa-stack-1x"></i>
					// 				<i class="fa fa-file-o fa-stack-2x"></i>
					// 			</span>
					// 		</div>';
					break;
			}

			$comment_data .= '<div class="timeline-items">
					<div class="timeline-label">' . date('Y M d <\b\\r>h:i A', strtotime($comment->createdDate)) . '</div>
					<div class="timeline-badge">
						' . $class . '
					</div>
					<div class="timeline-item timeline-content text-muted font-weight-normal">
						' . str_replace('\r\n', '<br />', $comment->comment) . '
					</div>
					<div class="timeline-user">
						<span class="badge badge-info">' . get_user_by_id($users, $comment->userId)->display_name . '</span>
					</div>
				</div>';
		}
		$comment_data .= '</div>';
	else :
		$comment_data .= '<center>No Comments!</center>';
	endif;

	if ($output)
		echo $comment_data;
	else
		return array('type' => 'success', 'data' => $comment_data);
}

function get_comments_v1($order_id, $output = false)
{
	global $db;

	$db->where('orderId', $order_id);
	$comments = $db->getValue(TBL_SP_ORDERS, 'comments');

	$comment_data = "";
	if (!empty($comments)) :
		$comments = array_reverse(json_decode($comments));
		$comment_data .= '<div class="timeline timeline-1"><div class="timeline-sep bg-primary-opacity-20"></div>';
		foreach ($comments as $comment) {
			$assuredDelivery = "";
			$class = "";
			if ($comment->assured_delivery)
				$assuredDelivery = '<span class="badge badge-warning">Assured Delivery</span>';

			switch ($comment->comment_for) {
				case 'confirmation_call':
					$class = ' class="fa fa-phone fa-flip-horizontal"';
					break;

				case 'call_rejected':
					$class = ' class="fa fa-tty"';
					break;

				case 'cancellation_call':
				case 'cancelled':
					$class = ' class="fa fa-ban"';
					break;

				case 'general_comment':
					$class = ' class="fa fa-comment-dots"';
					break;

				case '1st_call':
				case '2nd_call':
				case 'final_call':
					$class = ' class="fa fa-phone"';
					break;

				case 'ndr_approved':
					$class = ' class="fa fa-warehouse"';
					break;

				case 'escalation':
					$class = ' class="fa fa-running"';
					break;

				case 'follow_up':
					$class = ' class="fa fa-user-plus"';
					break;

				case 'gift_card':
					$class = ' class="fa fa-gift"';
					break;
			}

			$comment_data .= '<div class="timeline-items">
					<div class="timeline-label">' . date('Y M d <\b\\r>h:i A', strtotime($comment->created_on)) . '</div>
					<div class="timeline-badge">
						<i' . $class . '></i>
					</div>
					<div class="timeline-item timeline-content text-muted font-weight-normal">
						<i>' . ucwords(str_replace('_', ' ', $comment->comment_for)) . '</i>' . $assuredDelivery . '<br />
						' . str_replace('\r\n', '<br />', $comment->comment) . '
					</div>
					<div class="timeline-user">
						<span class="badge badge-info">' . get_user_by_id($users, $comment->created_by)->display_name . '</span>
					</div>
				</div>';
		}
		$comment_data .= '</div>';
	else :
		$comment_data .= '<center>No Comments!</center>';
	endif;

	if ($output)
		echo $comment_data;
	else
		return array('type' => 'success', 'data' => $comment_data);
}

function create_label($order_id)
{
	global $db, $account;

	$db->join(TBL_HSN_RATE . ' hsn', 'hsn.hsn_code=o.hsn');
	// $db->join(TBL_FULFILLMENT.' f', 'f.orderId=o.orderId');
	$db->where('o.orderId', $order_id);
	$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS . ' o', 'deliveryAddress, pickupDetails, invoiceAmount, invoiceNumber, invoiceDate, paymentType, totalPrice, sku, title, quantity, hsn_rate, hsn_code, orderNumberFormated, orderNumber');
	$deliveryAddress = json_decode($order->deliveryAddress);
	$pickupDetails = json_decode($order->pickupDetails);
	$company = strtolower($pickupDetails->company);
	$state = strtolower($deliveryAddress->province);
	$net_amount = number_format((($order->invoiceAmount / (100 + $order->hsn_rate)) * 100), 2, '.', '');
	$taxes = $order->invoiceAmount - $net_amount;
	if ($state == "gujarat") {
		$gst_slab = '<th>CGST</th><th>SGST</th>';
		$gst_value = '<td>' . number_format(($taxes / 2), 2, '.', '') . '</td><td>' . number_format(($taxes / 2), 2, '.', '') . '</td>';
	} else {
		$gst_slab = '<th>IGST</th>';
		$gst_value = '<td>' . $taxes . '</td>';
	}

	$content = '<!DOCTYPE html>
				<html>
				<head>
					<style type="text/css">
						body {
							font-size: 11px;
							font-family: \'Helvetica\';
						}
						table, tr, td, th {
							padding: 0;
							margin: 0;
							border-collapse:collapse;
						}
						.sub{
							font-size: 9px;
						}
						.main{
							width: 100mm;
							vertical-align: top;
						}
						.emphasis{
							font-style: italic; 
						}
						.align-center{
							text-align: center;
						}
						.align-left{
							text-align: left;
						}
						.align-right{
							text-align: right;
						}
						.vertical-top{
							vertical-align: top;
						}
						.vertical-center{
							vertical-align: middle;
						}
						.padding-left-5{
							padding-left: 5px;
						}
						.padding-top-5{
							padding-top: 5px;
						}
						.padding-top-10{
							padding-top: 10px;
						}
						.padding-right-10{
							padding-right: 10px;
						}
						.padding-left-10{
							padding-left: 10px;
						}
						.border-1{
							border: 1px solid #000;
						}
						.item-details{
							font-size: 9px;
							border-collapse: collapse;
							// position:relative;
							// display:inline-block;
							// width:calc(100% - 17px);
							// width:-webkit-calc(100% - 17px);
							// width:-moz-calc(100% - 17px);
						}
						.item-details td, .item-details th{
							border: 1px solid #000;
							padding: 2px;
						}
						.item-details td{
							word-wrap:break-all;
						}
						.inverse{
							background: #000;
							color: #FFF ;
						}
						.barcode {
							padding: 1.5mm 0;
							margin: 0;
							vertical-align: top;
							color: #000000;
						}
						.barcodecell {
							text-align: center;
							vertical-align: middle;
							padding: 0;
						}
					</style>
				</head>
				<body>
					<table class="main border-1" style="overflow: wrap" autosize="1">
						<tr>
							<td class="border-1" height="3.5cm" >
								<table>
									<tr>
										<td width="55%" class="vertical-top padding-left-5 padding-top-5">
											<table width="100%">
												<tr>
													<td><strong>Ship To</strong></td>
												</tr>
												<tr>
													<td class="emphasis">' . $deliveryAddress->name . '<br/>
													' . $deliveryAddress->address1 . ' ' . $deliveryAddress->address2 . '<br />
													' . $deliveryAddress->city . ', ' . $deliveryAddress->province . ', ' . $deliveryAddress->country . '<br />
													' . $deliveryAddress->zip . '<br />
													Phone No: ' . $deliveryAddress->phone . '</td>
												</tr>
											</table>
										</td>
										<td width="45%" class="align-right vertical-center padding-right-10 padding-top-10"><img src="' . ROOT_PATH . '/assets/img/' . $company . '_logo.png" height="2cm" /></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table class="main border-1">
						<tr>
							<td width="100%" height="2cm" class="border-1 align-center vertical-center">
								Self Ship<br />
								<div class="barcodecell"><barcode class="barcodecell" code="' . $order_id . '" type="C128B" quiet_zone_left="0" quiet_zone_right="0" pr="0.20" height="1"/></div>
								' . $order_id . '
							</td>
						</tr>
					</table>
					<table class="main">
						<tr>
							<td height="3.5cm" class="border-1">
								<table>
									<tr>
										<td class="vertical-top padding-left-5" width="50%"><br />
											<strong>Shipped By</strong> <span class="sub">(If undelivered, return to)</span><br />
											<span class="emphasis">
												' . $pickupDetails->company . '<br />
												' . $pickupDetails->address1 . ' ' . $pickupDetails->address2 . '<br />
												' . $pickupDetails->city . '<br />
												' . $pickupDetails->zip . '<br />
												GSTIN: <br />
												Phone No: 9687660234
											</span>
										</td>
										<td class="vertical-center padding-left-10" width="50%">
											<span class="align-center">Order #: ' . $order->orderNumberFormated . '</span><br />
											<div class="barcodecell"><barcode class="barcodecell" code="' . $order->orderNumberFormated . '" type="C39" size="0.8" quiet_zone_left="0" quiet_zone_right="0" pr="0.13" height="1.5" padding="0" style="text-align:left;" /></div>
											Invoice No.: ' . $order->invoiceNumber . '<br />
											Invoice Date: ' . date("Y-m-d", strtotime($order->invoiceDate)) . '
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table class="main border-1" style="display:inline-block;">
						<tr>
							<td width="100%" height="3.5cm">
								<table class="item-details border-1">
									<tr>
										<th width="">Product Name & SKU</th>
										<th width="">HSN</th>
										<th width="">Qty</th>
										<th width="">Unit Price</th>
										<th width="">Taxable Value</th>
										' . $gst_slab . '
										<th width="">Total</th>
									</tr>
									<tr>
										<td>' . $order->title . '<br /><span style="word-wrap:break-all;">SKU:' . $order->sku . '</span></td>
										<td>' . $order->hsn_code . '</td>
										<td>' . $order->quantity . '</td>
										<td>' . $order->invoiceAmount . '</td>
										<td>' . $net_amount . '</td>
										' . $gst_value . '
										<td>' . $order->invoiceAmount . '</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table class="main border-1">
						<tr>
							<td class="border-1 vertical-center padding-left-5" height="1cm">
								<table>
									<tr>
										<td>
											<span class="sub">All disputes are subject to Gujarat jurisdiction only. Goods once sold will only be taken back or exchanged as per the store\'s exchange/return policy.</span>	
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<table class="main border-1">
						<tr>
							<td class="border-1 vertical-center align-center" height="1cm">
								<span class="sub">THIS IS AN AUTO-GENERATED LABEL AND DOES NOT NEED SIGNATURE.</span>
							</td>
						</tr>
					</table>
					<table class="main border-1">
						<tr>
							<td class="border-1 vertical-center align-center inverse" height="0.5cm">
								<span>DO NOT ACCEPT SHIPMENT IF TEMPERED</span>
							</td>
						</tr>
					</table>
				</body>
				</html>';
	$mpdf = new \Mpdf\Mpdf([
		// 'format' => 'A4-P',
		'margin_left' => 2,
		'margin_right' => 2,
		'margin_top' => 2,
		'margin_bottom' => 2,
		// 'showBarcodeNumbers' => TRUE,
		'mode' => 'utf-8',
		'format' => [100, 150],
		'default_font' => 'Helvetica',
	]);

	$path = ROOT_PATH . '/labels/single/';
	$filename = 'FullLabel-Shopify-' . $order_id . '.pdf';
	$mpdf->WriteHTML($content);
	$mpdf->SetTitle($filename);
	$mpdf->SetDisplayMode('fullpage');
	$mpdf->SetCompression(true);
	$mpdf->mirrorMargins = true;
	$mpdf->Output($path . $filename, 'F');
	if (file_exists($path . $filename)) {
		return array(
			'type' => 'success',
			'message' => 'Label Generated'
		);
	} else {
		return array('type' => 'error', 'message' => 'Error downloading label');
	}
}

function create_invoice($order_id, $output = "")
{
	global $db;

	// $db->where('(orderNumberFormated = ? OR orderNumber = ?)', array($order_id, $order_id));
	$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
	$db->join(TBL_FIRMS . ' f', 'f.firm_id=sa.firm_id', 'INNER');
	$db->join(TBL_HSN_RATE . ' hr', 'hr.hsn_code=o.hsn');
	$db->join(TBL_FULFILLMENT . ' fl', 'fl.orderId=o.orderId');
	$db->joinWhere(TBL_FULFILLMENT . ' fl', '(fl.fulfillmentType = ? AND fl.fulfillmentStatus != ?)', array('forward', 'cancelled'));
	$db->where('o.orderId', $order_id);
	$db->where('status', 'shipped');
	// $db->where('shipmentStatus', 'delivered');
	$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', null, 'orderNumberFormated, orderDate, uid, quantity, title, sku, hsn, hr.hsn_rate, customerPrice, spDiscount, sellingPrice, shippingCharge, totalPrice, paymentType, deliveryAddress, billingAddress, trackingId, logistic, invoiceNumber, invoiceDate, invoiceAmount, sa.account_domain, sa.account_logo, firm_name, firm_addressJson, firm_gst');

	$is_shipping_billing = '';
	$table_body = "";
	$table_foot = "";
	$is_igst = true;
	$net_total = 0;
	$tax_slab = null;
	$sr_no = 1;
	foreach ($orders as $order) {
		if ($order->deliveryAddress == $order->billingAddress)
			$is_shipping_billing = ' & Shipped ';

		$order->billingAddress = json_decode($order->billingAddress);
		$bAddres = $order->billingAddress->address1 . ',<br />';
		if (!empty(trim($order->billingAddress->address2)))
			$bAddres = $order->billingAddress->address1 . ',<br />' . $order->billingAddress->address2 . ',<br />';

		$billingName = $order->billingAddress->name;
		$billingAddress = $bAddres . $order->billingAddress->city . ' - ' . $order->billingAddress->zip;
		$billingState = $order->billingAddress->province;
		$billingCountry = $order->billingAddress->country;

		$firmLogo = $order->account_logo;
		$firmName = $order->firm_name;
		$order->firmAddress = json_decode($order->firm_addressJson);
		$firmAddress = $order->firmAddress->address1 . ',<br />' . $order->firmAddress->address2 . ',<br />' . $order->firmAddress->city . ' - ' . $order->firmAddress->zip;
		$firmState = $order->firmAddress->province;
		$firmCountry = $order->firmAddress->country;
		$firmGst = $order->firm_gst;
		$firmWebsite = ucwords($order->account_domain);

		$invoiceNumber = $order->invoiceNumber;
		$invoiceDate = date('Y-m-d', strtotime($order->invoiceDate));

		$orderNumber = $order->orderNumberFormated;
		$orderDate = date('Y-m-d', strtotime($order->orderDate));

		$logistic = $order->logistic;
		$trackingId = $order->trackingId;

		$paymentType = $order->paymentType == "cod" ? "COD" : ucwords($order->paymentType);

		$net_total += ($order->customerPrice * $order->quantity);

		if ($order->account_domain == "sylvi.in")
			$warranty_clause = '<div class="sub warranty">WARRANTY : SIX (6) MONTHS</div>';


		if ($firmState == $billingState) {
			$is_igst = false;
		}
		$tax_slab = $order->hsn_rate;

		$unitPrice = (float) number_format(($order->sellingPrice / (100 + $tax_slab)) * 100, '2', '.', '');
		$unitDiscount = (float) number_format(($order->spDiscount / (100 + $tax_slab)) * 100, '2', '.', '');
		$taxableValue = ($unitPrice - $unitDiscount) * $order->quantity;
		$itemTaxes = (float) (number_format(($taxableValue * $tax_slab) / 100, '2', '.', ''));
		$gst_columns = '<td>&#8377;' . $itemTaxes . ' | ' . number_format($tax_slab, '2', '.', '') . '</td>';
		if (!$is_igst) {
			$gst_columns = '<td>&#8377;' . ($itemTaxes / 2) . ' | ' . number_format(($tax_slab / 2), '2', '.', '') . '</td><td>&#8377;' . ($itemTaxes / 2) . ' | ' . number_format(($tax_slab / 2), '2', '.', '') . '</td>';
		}
		$table_body .= '<tr>
			<td>' . $sr_no++ . '</td>
			<td class="particulars">
				<div class="title">' . $order->title . '</div>
				<div class="sub sku">SKU : ' . $order->sku . '</div>
				<div class="sub serial">SERIAL NO : ' . implode(json_decode($order->uid), ', ') . '</div>
				' . $warranty_clause . '
			</td>
			<td>' . $order->hsn . '</td>
			<td>' . $order->quantity . '</td>
			<td>&#8377;' . $unitPrice . '</td>
			<td>&#8377;' . $unitDiscount . '</td>
			<td>&#8377;' . $taxableValue . '</td>
			' . $gst_columns . '
			<td>&#8377;' . ($order->customerPrice * $order->quantity) . '</td>
		</tr>';

		$shippingCharge = ($order->shippingCharge * $order->quantity);
	}

	if ($shippingCharge > 0) {
		$shippingTaxes = (float) $shippingCharge - (number_format(($shippingCharge / (100 + $tax_slab)) * 100, '2', '.', ''));
		$shippingRate = $shippingCharge - $shippingTaxes;
		$gst_columns = '<td>&#8377;' . $shippingTaxes . ' | ' . number_format($tax_slab, '2', '.', '') . '</td>';
		if (!$is_igst) {
			$gst_columns = '<td>&#8377;' . ($shippingTaxes / 2) . ' | ' . number_format(($tax_slab / 2), '2', '.', '') . '</td><td>&#8377;' . ($shippingTaxes / 2) . ' | ' . number_format(($tax_slab / 2), '2', '.', '') . '</td>';
		}
		$table_body .= '<tr>
				<td> </td>
				<td class="particulars">
					Shipping Charges
				</td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td>&#8377;' . $shippingRate . '</td>
				' . $gst_columns . '
				<td>&#8377;' . number_format($shippingCharge, '2', '.', '') . '</td>
			</tr>';

		$net_total += $shippingCharge;
	}

	$table_head = '<tr>
		<th width="1%">#</th>
		<th width="36%" class="title">Particulars</th>
		<th width="6%">HSN</th>
		<th width="5%">QTY</th>
		<th width="7%">Unit Price</th>
		<th width="8%">Unit Discount</th>
		<th width="8%">Taxable Value</th>
		<th width="12%">IGST<br/>(Value | %)</th>
		<th width="12%">Total<br/>(Including GST)</th>
	</tr>';

	$table_foot = '<tr>
		<td></td>
		<td class="particulars">Net Total (In Value)</td>
		<td colspan="6"></td>
		<td>&#8377;' . number_format($net_total, '2', '.', '') . '</td>
	</tr>';
	if (!$is_igst) {
		$table_head = '<th width="1%">#</th>
				<th width="30%" class="title">Particulars</th>
				<th width="6%">HSN</th>
				<th width="5%">QTY</th>
				<th width="7%">Unit Price</th>
				<th width="8%">Unit Discount</th>
				<th width="7%">Taxable Value</th>
				<th width="12%">CGST<br/>(Value | %)</th>
				<th width="12%">SGST<br/>(Value | %)</th>
				<th width="12%">Total<br/>(Including GST)</th>
			</tr>';
		$table_foot = '<tr>
				<td></td>
				<td class="particulars">Net Total (In Value)</td>
				<td colspan="7"></td>
				<td>&#8377;' . $net_total . '</td>
			</tr>';
	}

	$find = array(
		"##AND SHIPPED##",
		"##BILLING NAME##",
		"##BILLING ADDRESS##",
		"##BILLING STATE##",
		"##BILLING COUNTRY##",
		"##FIRM LOGO##",
		"##FIRM NAME##",
		"##FIRM ADDRESS##",
		"##FIRM STATE##",
		"##FIRM COUNTRY##",
		"##FIRM GST##",
		"##FIRM WEBSITE##",
		"##INVOICE NUMBER##",
		"##INVOICE DATE##",
		"##ORDER NUMBER##",
		"##ORDER DATE##",
		"##FORWARD LOGISTIC##",
		"##FORWARD TRACKING##",
		"##PAYMENT TYPE##",
		"##TABLE HEAD##",
		"##ITEM DETAILS##",
		"##TABLE FOOT##"
	);
	$replace = array(
		$is_shipping_billing,
		$billingName,
		$billingAddress,
		$billingState,
		$billingCountry,
		BASE_URL . '/assets/img/' . $firmLogo,
		$firmName,
		$firmAddress,
		$firmState,
		$firmCountry,
		$firmGst,
		$firmWebsite,
		$invoiceNumber,
		$invoiceDate,
		$orderNumber,
		$orderDate,
		$logistic,
		$trackingId,
		$paymentType,
		$table_head,
		$table_body,
		$table_foot
	);

	$stylesheet = file_get_contents(BASE_URL . '/assets/templates/order-invoice.css'); // AMS CSS

	// $html = get_template('invoice', $type);
	$html = file_get_contents(BASE_URL . '/assets/templates/order-invoice.html');
	$html = str_replace($find, $replace, $html);

	$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
	$fontDirs = $defaultConfig['fontDir'];

	$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
	$fontData = $defaultFontConfig['fontdata'];

	$mpdf = new \Mpdf\Mpdf([
		'format' => 'A4-P',
		'margin_left' => 5,
		'margin_right' => 5,
		'margin_top' => 5,
		'margin_bottom' => 5,
		'margin_header' => 0,
		'margin_footer' => 0,
		'showBarcodeNumbers' => FALSE,
		'fontDir' => array_merge($fontDirs, [
			ROOT_PATH . '/assets/fonts',
		]),
		'fontdata' => [
			'nunito' => [
				'R' => 'Nunito-Regular.ttf',
				'I' => 'Nunito-Italic.ttf',
				'B' => 'Nunito-Bold.ttf',
				'BI' => 'Nunito-BoldItalic.ttf',
			]
		],
		'default_font' => 'nunito'
	]);

	$mpdf->WriteHTML($stylesheet, 1);
	$mpdf->WriteHTML($html);

	$title = $invoiceNumber;
	$mpdf->SetProtection(array('print'));
	$mpdf->SetTitle($title);
	$mpdf->SetAuthor('FIMS');
	$mpdf->SetDisplayMode('fullpage');
	if ($output == "save") {
		$file_name = UPLOAD_PATH . '/invoices/' . $title . '.pdf';
		$mpdf->Output($file_name, 'F');
		return $file_name;
	} else if ($output == "string") {
		return $mpdf->Output($title . '.pdf', 'S');
	} else {
		$mpdf->Output($title . '.pdf', 'I');
	}
}

function get_user_by_id($users_data, $user_id, $id = "userID")
{
	foreach ($users_data as $user_data) {
		if ($user_data->{$id} == $user_id)
			return $user_data;
	}
}

function find_initial_order($order_item_id)
{
	global $db;

	$db->where('orderItemId', $order_item_id);
	$rp_order = $db->objectBuilder()->getOne(TBL_SP_ORDERS);

	if ($rp_order->replacementOrder) {
		$db->where('r_replacementOrderId', $rp_order->orderId);
		$og_order = $db->objectBuilder()->getOne(TBL_SP_RETURNS);
		if (is_null($og_order->r_replacementOrderId)) {
			return $og_order;
		} else {
			return find_initial_order($og_order->orderItemId);
		}
	} else {
		return $rp_order;
	}
}

// PAYMENTS
function import_payments_v1($file, $account_id, $shopify_account = null)
{
	global $db, $log;
	if (file_exists($file)) {
		try {
			$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
			$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($file);
		} catch (Exception $e) {
			die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
		}

		$objWorksheet = $objPHPExcel->getSheet(0); // Orders - Payments Sheet

		$types = array();
		$orders = array();


		$worksheetTitle     = strtolower(str_replace(' ', '_', trim($objWorksheet->getTitle())));
		$highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
		$highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
		$orders = array();
		$head_line = array();
		$status = 0;
		$rowNum = 0;

		for ($row = 1; $row <= $highestRow; ++$row) {
			$line = array();
			for ($col = 0; $col <= $highestColumnIndex; ++$col) {
				$cell = $objWorksheet->getCellByColumnAndRow($col, $row);
				$val = $cell->getValue();
				if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText)
					$val = $val->getPlainText();

				if ($row == 1) {
					$text = substr($val, 0, strpos($val, "(Rs"));
					if (!$text) $text = preg_replace("/\([^)]+\)/", "", $val);
					if ((($col == 1 || $col == 2 || $col == 3) && $text == 'Date') || ($worksheetTitle == 'ads' && $col == 1))
						$text = 'neft_date';
					$head_line_txt = strtolower(str_replace(' ', '_', trim($text)));
					if (in_array($head_line_txt, $head_line))
						$head_line_txt = $head_line_txt . '_1';
					$head_line[] = $head_line_txt;
				} else {
					$line[$head_line[$col]] = $val;
				}
			}
			if (!empty($line))
				$orders[] = $line;
		}
		$types[$worksheetTitle] = $orders;

		$success = array();
		$existing = array();
		$update = array();
		$error = array();
		$error_neft = array();
		$total = count($orders);
		foreach ($types as $type => $type_orders) {
			foreach ($type_orders as  $order) {
				$amount = 0;
				if ($account_id == 'Gokwik') {
					if ($order['shopify_id'] != '' && $order['shopify_id']  != 0) {
						$orderNumberFormat = getOrderNumberFormat($order['shopify_id']);
						$db->where('orderNumberFormated', $orderNumberFormat, 'LIKE');
						if ($db->has(TBL_SP_ORDERS)) {
							$orderId = $order['shopify_id'];
							$settlementId = $order['merchant_trxn_id'];
							$amount = $order['transaction_amount'];
							$utr = $order['easebuzz_trxn_id'];
							$deliveredDate = date("Y-m-d H:i:s");
							$settledAt = isset($order['transaction_date']) ? date('Y-m-d H:i:s', strtotime($order['transaction_date'])) : '';
							$service_charge = isset($order['service_charge']) ? $order['service_charge'] : $amount * 2 / 100;
							$db->where('channel_order_id', $orderId);
							if (!$db->has(TBL_SP_CHARGE)) {
								$details_ser_charg = array(
									'channel_order_id' => $orderId,
									'category' => 'Service Charge',
									'amount' => $service_charge,
									'created_at' => isset($order['transaction_date']) ? date('Y-m-d H:i:s', strtotime($order['transaction_date'])) : '',
								);
								$gstAmount = isset($order['gst']) ? $order['gst'] : $service_charge * 18 / 100;
								$details_gst_charg = array(
									'channel_order_id' => $orderId,
									'category' => 'GST',
									'amount' => $gstAmount,
									'created_at' => isset($order['transaction_date']) ? (date('Y-m-d H:i:s', strtotime($order['transaction_date']))) : '',
								);
								$db->insert(TBL_SP_CHARGE, $details_ser_charg);
								$db->insert(TBL_SP_CHARGE, $details_gst_charg);
								$db->commit();
							}

							$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
							$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
							$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
							$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.orderId,o.totalPrice,o.status,o.customerPrice,
								f.fulfillmentType,o.paymentType,o.spStatus,o.paymentGatewayAmount,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
								r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');

							$net_payout = calculate_actual_payout($settlements);

							if (round_value($net_payout) == round_value($amount)) {
								$data = array(
									'is_difference_amt' => 1,
									'paymentGatewayOrder' => $order['merchant_trxn_id']
								);
								$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
								$db->update(TBL_SP_ORDERS, $data);
							} else {
								$data = array(
									'paymentGatewayOrder' => $order['merchant_trxn_id']
								);
								$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
								$db->update(TBL_SP_ORDERS, $data);
							}
							$db->startTransaction();
							//add database in refund amt
							// ccd($order);
							if (isset($order['refund_id']) && $order['refund_id'] != '') {
								$db->where('orderId', $order['shopify_id']);
								$db->where('isRefundAmt', 1);
								if (!$db->has(TBL_SP_REMITTANCE)) {
									$details2 = array(
										'orderId' => $orderId,
										'settlementId' => $settlementId,
										'remittedAmount' => $order['refund_amount'],
										'utr' => $utr,
										'channelName' => $account_id,
										'isSettled' => isset($isSettled) ? 1 : 0,
										'deliveredDate' => $deliveredDate,
										'remittanceDate' => date('Y-m-d H:i:s', strtotime($order['payout_date'])),
										'isRefundAmt' => 1,
										'accountId' => $shopify_account
									);
									$db->insert(TBL_SP_REMITTANCE, $details2);
									$db->commit();
								}
							} else {
								$db->where('orderId', $orderId);
								$db->where('remittanceDate', $settledAt);
								if (!$db->has(TBL_SP_REMITTANCE)) {
									$details = array(
										'orderId' => $orderId,
										'settlementId' => $settlementId,
										'remittedAmount' => $amount,
										'utr' => $utr,
										'channelName' => $account_id,
										'isSettled' => isset($isSettled) ? 1 : 0,
										'deliveredDate' => $deliveredDate,
										'remittanceDate' => $settledAt,
										'accountId' => $shopify_account
									);
									$db->insert(TBL_SP_REMITTANCE, $details);
									$db->commit();
								}
							}
						}
					}
				} else if ($account_id == 'RazorPay') {
					$orderId = $order['order_receipt'];
					$db->where('orderNumber', getOrderNumberFormat($orderId));
					if ($db->has(TBL_SP_ORDERS)) {
						$amount = $order['amount'];
						$utr = $order['settlement_utr'];
						$settlementId = $order['settlement_id'];
						$settledAt = date('Y-m-d H:i:s', strtotime($order['settled_at']));
						$deliveredDate = date('Y-m-d H:i:s', strtotime($order['payment_captured_at']));
						$orderdt = $order['settled_at'];
						$newdate = str_replace('/', '-', $orderdt);
						$dt_convert = date('Y-m-d H:i:s', strtotime($newdate));

						$db->where('channel_order_id', $order['order_receipt']);
						$db->where('category', 'PG Charges');
						$db->where('amount', $order['fee']);
						if (!$db->has(TBL_SP_CHARGE)) {
							$details_charges = array(
								'channel_order_id' => $order['order_receipt'],
								'category' => 'PG Charges',
								'amount' => $order['fee'],
								'created_at' => $dt_convert,
							);
							$db->insert(TBL_SP_CHARGE, $details_charges);
						}

						$db->where('channel_order_id', $order['order_receipt']);
						$db->where('category', 'GST');
						$db->where('amount', $order['tax']);
						if (!$db->has(TBL_SP_CHARGE)) {
							$details_gst = array(
								'channel_order_id' => $order['order_receipt'],
								'category' => 'GST',
								'amount' => $order['tax'],
								'created_at' => $dt_convert,
							);
							$db->insert(TBL_SP_CHARGE, $details_gst);
						}

						// CHECK DIFFERENCE
						$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
						$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
						$db->where('orderNumber', getOrderNumberFormat($orderId));
						$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
						$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.*, f.fulfillmentType, f.remainingCharge, f.orderId, f.shippingFee, f.codFees, f.rtoShippingFee, r.returnId as return_id, r.r_source, r.r_status, rm.remittedAmount');
						$net_payout = calculate_actual_payout($settlements);
						$net_payout_flt = number_format(($net_payout), '2', '.', '');
						if ($net_payout_flt == $amount) {
							$data = array(
								'is_difference_amt' => 1
							);
							$db->startTransaction();
							$db->where('orderNumber', getOrderNumberFormat($orderId));
							$db->update(TBL_SP_ORDERS, $data);
							$db->commit();
						}
					}
				} elseif ($account_id == 'Shiprocket') {
					$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
					if ($db->has(TBL_SP_ORDERS)) {
						$amount = $order['order_value'];
						$orderId = $order['order_id'];
						$utr = $order['utr'];
						$settlementId = $order['crf_id'];
						$settledAt = $order['remittance_date'];
						$deliveredDate = $order['delivered_date'];


						//check difference
						$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
						$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
						$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
						$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
						$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.orderId,o.totalPrice,o.status,o.customerPrice,
							f.fulfillmentType,o.paymentType,o.spStatus,o.paymentGatewayAmount,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
						   r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');
						$net_payout = calculate_actual_payout($settlements);
						$net_payout_flt = number_format(($net_payout), '2', '.', '');
						if ($net_payout_flt == $amount) {
							$data = array(
								'is_difference_amt' => 1
							);
							$db->startTransaction();
							$db->where('orderNumberFormated', getOrderNumberFormat($orderId));
							$db->update(TBL_SP_ORDERS, $data);
							$db->commit();
						}
					}
				}
				$db->startTransaction();
				$db->where('orderId', $orderId);
				$db->where('remittanceDate', $settledAt);

				if (!$db->has(TBL_SP_REMITTANCE)) {
					$details = array(
						'orderId' => $orderId,
						'settlementId' => $settlementId,
						'remittedAmount' => $amount,
						'utr' => $utr,
						'channelName' => $account_id,
						'isSettled' => isset($isSettled) ? 1 : 0,
						'deliveredDate' => $deliveredDate,
						'remittanceDate' => $settledAt,
						'accountId' => $shopify_account
					);
					$db->insert(TBL_SP_REMITTANCE, $details);
					$db->commit();
				}
			}
		}

		$return = array('type' => 'success', 'total' => $total, 'success' => count($success), 'existing' => count($existing), 'error' => count($error), 'updated' => count($update), 'invalid' => count($error_neft), 'error_data' => $error);
		$log->write($return, 'payments-import');
		return $return;
	} else {
		return array('type' => 'error', 'message' => 'Unable to locate file.');
	}
}

function import_payments($file, $account_id, $shopify_account = null)
{
	global $db, $log;

	if (file_exists($file)) {
		try {
			$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
			$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($file);
		} catch (Exception $e) {
			die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
		}

		$objWorksheet = $objPHPExcel->getSheet(0); // Orders - Payments Sheet

		$types = array();
		$orders = array();


		$worksheetTitle     = strtolower(str_replace(' ', '_', trim($objWorksheet->getTitle())));
		$highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
		$highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
		$orders = array();
		$head_line = array();

		for ($row = 1; $row <= $highestRow; ++$row) {
			$line = array();
			for ($col = 0; $col <= $highestColumnIndex; ++$col) {
				$cell = $objWorksheet->getCellByColumnAndRow($col, $row);
				$val = $cell->getValue();
				if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText)
					$val = $val->getPlainText();

				if ($row == 1) {
					$text = substr($val, 0, strpos($val, "(Rs"));
					if (!$text) $text = preg_replace("/\([^)]+\)/", "", $val);
					if ((($col == 1 || $col == 2 || $col == 3) && $text == 'Date') || ($worksheetTitle == 'ads' && $col == 1))
						$text = 'neft_date';
					$head_line_txt = strtolower(str_replace(' ', '_', trim($text)));
					if (in_array($head_line_txt, $head_line))
						$head_line_txt = $head_line_txt . '_1';
					$head_line[] = $head_line_txt;
				} else {
					$line[$head_line[$col]] = $val;
				}
			}
			if (!empty($line))
				$orders[] = $line;
		}
		$types[$worksheetTitle] = $orders;

		$success = array();
		$existing = array();
		$update = array();
		$error = array();
		$error_neft = array();
		$total = count($orders);
		foreach ($types as $type => $type_orders) {
			foreach ($type_orders as  $order) {
				// ccd($order);
				$amount = 0;
				$pg_charge = 0;
				$pg_gst = 0;
				// Check Payment Gateway Type
				if ($account_id == 'RazorPay') {
					$orderId = $order['order_receipt'];
					$db->where('orderNumber', getOrderNumber($orderId)); // check the data of that order is present or not
					if ($db->has(TBL_SP_ORDERS)) {
						$settlementId = $order['settlement_id'];
						$amount = $order['amount'];
						$pg_charge = $order['fee'];
						$pg_gst = $order['tax'];
						$utr = $order['settlement_utr'];
						$isSettled = 1;
						$settledAt = date('Y-m-d H:i:s', strtotime(str_replace("/", "-", $order['settled_at'])));
						$deliveredDate = date('Y-m-d H:i:s', strtotime(str_replace("/", "-", $order['payment_captured_at'])));

						// check difference
						$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
						$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
						$db->where('orderNumber', getOrderNumber($orderId));
						$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
						$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.*, f.fulfillmentType, f.remainingCharge, f.orderId, f.shippingFee, f.codFees, f.rtoShippingFee, r.returnId as return_id, r.r_source, r.r_status, rm.remittedAmount');
						$net_payout = calculate_actual_payout($settlements);
						$net_payout_flt = number_format(($net_payout), '2', '.', '');
						if ($net_payout_flt == $amount) {
							$data = array(
								'is_difference_amt' => 1
							);
							$db->startTransaction();
							$db->where('orderNumber', getOrderNumber($orderId));
							$db->update(TBL_SP_ORDERS, $data);
							$db->commit();
						}
					}
				} elseif ($account_id == 'Gokwik') {
					if ($order['shopify_id'] != '' && $order['shopify_id']  != 0) {
						$orderNumberFormat = getOrderNumber($order['shopify_id']);
						$db->where('orderNumber', $orderNumberFormat, 'LIKE');
						if ($db->has(TBL_SP_ORDERS)) {
							$orderId = $order['shopify_id'];
							$settlementId = $order['merchant_trxn_id'];
							$amount = $order['transaction_amount'];
							$utr = $order['easebuzz_trxn_id'];
							$deliveredDate = date("Y-m-d H:i:s");
							$settledAt = isset($order['transaction_date']) ? date('Y-m-d H:i:s', strtotime($order['transaction_date'])) : '';
							$service_charge = isset($order['service_charge']) ? $order['service_charge'] : $amount * 2 / 100;
							$gstAmount = isset($order['gst']) ? $order['gst'] : $service_charge * 0.18;
							$pg_charge = $service_charge;
							$pg_gst = $gstAmount;

							$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
							$db->where('orderNumberFormated', getOrderNumber($orderId));
							$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
							$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.orderId,o.totalPrice,o.status,o.customerPrice,
								f.fulfillmentType,o.paymentType,o.spStatus,o.paymentGatewayAmount,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
								r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');

							$net_payout = calculate_actual_payout($settlements);

							if (round_value($net_payout) == round_value($amount)) {
								$data = array(
									'is_difference_amt' => 1,
									'paymentGatewayOrder' => $order['merchant_trxn_id']
								);
								$db->where('orderNumberFormated', getOrderNumber($orderId));
								$db->update(TBL_SP_ORDERS, $data);
							} else {
								$data = array(
									'paymentGatewayOrder' => $order['merchant_trxn_id']
								);
								$db->where('orderNumberFormated', getOrderNumber($orderId));
								$db->update(TBL_SP_ORDERS, $data);
							}
							$db->startTransaction();
							//add database in refund amt
							if (isset($order['refund_id']) && $order['refund_id'] != '') {
								$db->where('orderId', $order['shopify_id']);
								$db->where('isRefundAmt', 1);
								if (!$db->has(TBL_SP_REMITTANCE)) {
									$details2 = array(
										'orderId' => $orderId,
										'settlementId' => $settlementId,
										'remittedAmount' => $order['refund_amount'],
										'pgCharge' => $service_charge,
										'pgGST' => $gstAmount,
										'utr' => $utr,
										'channelName' => $account_id,
										'isSettled' => isset($isSettled) ? 1 : 0,
										'deliveredDate' => $deliveredDate,
										'remittanceDate' => date('Y-m-d H:i:s', strtotime($order['Payout_date'])),
										'isRefundAmt' => 1,
										'accountId' => $shopify_account
									);
									$db->insert(TBL_SP_REMITTANCE, $details2);
									$db->commit();
								}
							} else {
								$db->where('orderId', $orderId);
								$db->where('remittanceDate', $settledAt);
								if (!$db->has(TBL_SP_REMITTANCE)) {
									$details = array(
										'orderId' => $orderId,
										'settlementId' => $settlementId,
										'remittedAmount' => $amount,
										'pgCharge' => $service_charge,
										'pgGST' => $gstAmount,
										'utr' => $utr,
										'channelName' => $account_id,
										'isSettled' => isset($isSettled) ? 1 : 0,
										'deliveredDate' => $deliveredDate,
										'remittanceDate' => $settledAt,
										'accountId' => $shopify_account
									);
									$db->insert(TBL_SP_REMITTANCE, $details);
									$db->commit();
								}
							}
						}
					}
				} elseif ($account_id == 'Shiprocket') {
					$orderId = $order["order_id"];
					$db->where('orderNumberFormated', $order["order_id"]);
					if ($db->has(TBL_SP_ORDERS)) {
						$amount = $order['order_value'];
						$orderId = $order['order_id'];
						$utr = $order['utr'];
						$settlementId = $order['crf_id'];
						$settledAt = $order['remittance_date'];
						$deliveredDate = $order['delivered_date'];
						$pg_charge = 0;
						$pg_gst = 0;

						//check difference
						$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
						$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
						$db->where('orderNumberFormated', getOrderNumber($orderId));
						$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
						$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.orderId,o.totalPrice,o.status,o.customerPrice,
							f.fulfillmentType,o.paymentType,o.spStatus,o.paymentGatewayAmount,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
							r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');
						$net_payout = calculate_actual_payout($settlements);
						$net_payout_flt = number_format(($net_payout), '2', '.', '');
						if ($net_payout_flt == $amount) {
							$data = array(
								'is_difference_amt' => 1
							);
							$db->startTransaction();
							$db->where('orderNumberFormated', getOrderNumber($orderId));
							$db->update(TBL_SP_ORDERS, $data);
							$db->commit();
						}
					}
				}

				$db->startTransaction();
				$db->where('orderId', $orderId);
				$db->where('remittanceDate', $settledAt);

				if (!$db->has(TBL_SP_REMITTANCE)) {
					$details = array(
						'accountId' => $shopify_account,
						'settlementId' => $settlementId,
						'orderId' => $orderId,
						'remittedAmount' => $amount,
						'pgCharge' => $pg_charge,
						'pgGST' => $pg_gst,
						'utr' => $utr,
						'channelName' => $account_id,
						'isSettled' => isset($isSettled) ? 1 : 0,
						'deliveredDate' => $deliveredDate,
						'remittanceDate' => $settledAt,
					);

					$db->insert(TBL_SP_REMITTANCE, $details);
					$db->commit();
				}
			}
		}

		$return = array('type' => 'success', 'total' => $total, 'success' => count($success), 'existing' => count($existing), 'error' => count($error), 'updated' => count($update), 'invalid' => count($error_neft), 'error_data' => $error);
		$log->write($return, 'payments-import');
		return $return;
	} else {
		return array('type' => 'error', 'message' => 'Unable to locate file.');
	}
}

function insert_remittance_charge($types)
{
	global $db;

	foreach ($types as  $k => $type_orders) {
		//     ccd($type_orders);
		foreach ($type_orders as $order) {
			$db->where('orderNumberFormated', $order['channel_order_id']);
			if ($db->has(TBL_SP_ORDERS)) {
				$db->where('awb_code', $order['awb_code']);
				$db->where('category', $order['category']);
				if (!$db->has(TBL_SP_CHARGE)) {
					$details = array(
						'channel_order_id' => $order['channel_order_id'],
						'awb_code' => $order['awb_code'],
						'category' => $order['category'],
						'amount' => isset($order['debit']) ? -$order['debit'] : $order['credit'],
						'description' => $order['description'],
						'created_at' => $order['created_at'],
					);
					$db->insert(TBL_SP_CHARGE, $details);

					// Remitted amt calculate
					$db->where('orderId', $order['channel_order_id']);
					if ($db->has(TBL_SP_REMITTANCE)) {
						$db->where('orderId', $order['channel_order_id']);
						$old_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
						if ($order['category'] != 'Brand Boost') {
							$new_dbt_remitted_amt = (float)$old_remitted_amt['remittedAmount'] - (float)$order['debit'];
							$new_dbt_remitted_amt = $new_dbt_remitted_amt +  (float)$order['credit'];
							$dbt_amt = array(
								'remittedAmount' => $new_dbt_remitted_amt
							);
							$db->startTransaction();
							$db->where('orderId', $order['channel_order_id']);
							$db->update(TBL_SP_REMITTANCE, $dbt_amt);
							$db->commit();
							//check difference
							$db->where('orderId', $order['channel_order_id']);
							$nw_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
							$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
							$db->where('orderNumberFormated', $order['channel_order_id']);
							$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
							$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.*,
									f.fulfillmentType,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
									r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');
							$net_payout = calculate_actual_payout($settlements);
							$net_payout_flt = number_format(($net_payout), '2', '.', '');
							if ($net_payout_flt == $nw_remitted_amt['remittedAmount']) {
								$data = array(
									'is_difference_amt' => 1
								);
								$db->startTransaction();
								$db->where('orderNumberFormated', $order['channel_order_id']);
								$db->update(TBL_SP_ORDERS, $data);
								$db->commit();
							}
						}
					}
				} else {
					$details = array(
						'channel_order_id' => $order['channel_order_id'],
						//                 'awb_code' => $order['awb_code'],
						'category' => $order['category'],
						'amount' => isset($order['debit']) ? -$order['debit'] : $order['credit'],
						//                 'description' => $order['description'],
						'created_at' => $order['created_at'],
					);
					$db->where('awb_code', $order['awb_code']);
					$db->where('category', $order['category']);
					$db->update(TBL_SP_CHARGE, $details);
				}
			}
		}
	}
}

function insert_payment_charge_v1($types)
{
	global $db;

	foreach ($types as  $k => $type_orders) {
		//     ccd($type_orders);
		foreach ($type_orders as $order) {
			$db->where('orderNumberFormated', $order['channel_order_id']);
			if ($db->has(TBL_SP_ORDERS)) {
				$db->where('awb_code', $order['awb_code']);
				$db->where('category', $order['category']);
				if (!$db->has(TBL_SP_CHARGE)) {
					$details = array(
						'channel_order_id' => $order['channel_order_id'],
						'awb_code' => $order['awb_code'],
						'category' => $order['category'],
						'amount' => isset($order['debit']) ? -$order['debit'] : $order['credit'],
						'description' => $order['description'],
						'created_at' => $order['created_at'],
					);
					$db->insert(TBL_SP_CHARGE, $details);

					// Remitted amt calculate
					$db->where('orderId', $order['channel_order_id']);
					if ($db->has(TBL_SP_REMITTANCE)) {
						$db->where('orderId', $order['channel_order_id']);
						$old_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
						if ($order['category'] != 'Brand Boost') {
							$new_dbt_remitted_amt = (float)$old_remitted_amt['remittedAmount'] - (float)$order['debit'];
							$new_dbt_remitted_amt = $new_dbt_remitted_amt +  (float)$order['credit'];
							$dbt_amt = array(
								'remittedAmount' => $new_dbt_remitted_amt
							);
							$db->startTransaction();
							$db->where('orderId', $order['channel_order_id']);
							$db->update(TBL_SP_REMITTANCE, $dbt_amt);
							$db->commit();
							//check difference
							$db->where('orderId', $order['channel_order_id']);
							$nw_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
							$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
							$db->where('orderNumberFormated', $order['channel_order_id']);
							$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
							$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.*,
									f.fulfillmentType,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
									r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');
							$net_payout = calculate_actual_payout($settlements);
							$net_payout_flt = number_format(($net_payout), '2', '.', '');
							if ($net_payout_flt == $nw_remitted_amt['remittedAmount']) {
								$data = array(
									'is_difference_amt' => 1
								);
								$db->startTransaction();
								$db->where('orderNumberFormated', $order['channel_order_id']);
								$db->update(TBL_SP_ORDERS, $data);
								$db->commit();
							}
						}
					}
				} else {
					$details = array(
						'channel_order_id' => $order['channel_order_id'],
						//                 'awb_code' => $order['awb_code'],
						'category' => $order['category'],
						'amount' => isset($order['debit']) ? -$order['debit'] : $order['credit'],
						//                 'description' => $order['description'],
						'created_at' => $order['created_at'],
					);
					$db->where('awb_code', $order['awb_code']);
					$db->where('category', $order['category']);
					$db->update(TBL_SP_CHARGE, $details);
				}
			}
		}
	}
}

function calculate_charge_amount($channel_order_id)
{
	global $db;

	$db->where('channel_order_id', "%" . $channel_order_id, "LIKE");
	$settlement = $db->getValue(TBL_SP_CHARGE, 'sum(amount)');
	if (is_null($settlement))
		$settlement = 0;
	$db->where('orderId', $channel_order_id);
	$charges = $db->get(TBL_SP_REMITTANCE, 'pgCharge, pgGST');
	foreach ($charges as $charge) {
		if ($charge['pgCharge'] != 0) {
			$settlement += $charge['pgCharge'];
		}
		if ($charge['pgGST'] != 0) {
			$settlement += $charge['pgGST'];
		}
	}

	return $settlement;
}

function calculate_actual_payout($order)
{
	global $db;
	$sale_amounts = (float)($order->customerPrice) + (float)($order->shippingCharge);

	$reverseShippingFee = 0;
	// variable "$other_order" not declared
	// if(isset($other_order)){
	// 	if($other_order->fulfillmentType == 'return'){
	// 		$reverseShippingFee = -(float)$other_order->shippingFee;
	// 	}
	// }
	// var_dump($reverseShippingFee);

	if (isset($order->return_id)) {
		if (isset($order->r_source) && isset($order->totalPrice) && $order->r_source == 'courier_return') {
			$refundAmount = -(float)($order->totalPrice);
		} elseif (isset($order->customerPrice)) {
			$refundAmount = -(float)$order->customerPrice;
		}
	} else {
		$refundAmount = 0;
	}

	$freightCharge = isset($order->shippingFee) ? -(float)$order->shippingFee : 0;

	$rtoFreightCharge = 0;
	$codFeesReversed = 0;
	if (isset($order->return_id) && $order->r_source != 'customer_return') {
		// if($order->returnId && $order->r_source != 'customer_return'){
		if (isset($order->paymentType) && $order->paymentType == 'cod') {
			$rtoFreightCharge = isset($order->rtoShippingFee) ? -(float)$order->rtoShippingFee : 0;
		}
		$codFeesReversed = (float)$order->codFees;
	}
	$codCharge = isset($order->codFees) && isset($order->paymentType) && $order->paymentType == 'cod' ? -(float)$order->codFees : 0;

	$total_shipping_charges = $freightCharge + $codCharge + $rtoFreightCharge + $codFeesReversed + $reverseShippingFee;

	$pg_charges = 0;

	if (strpos(($order->paymentGateway) ? (strtolower($order->paymentGateway)) : (""), "razorpay") !== false)
		$paymentGateway = "RazorPay";
	else
		$paymentGateway = "Gokwik";

	$db->where('fee_tier', ($paymentGateway) ? ($paymentGateway) : ("RazorPay"));
	$pgCharge = $db->getValue(TBL_MP_FEES, 'fee_value');

	if (isset($order->paymentType) && $order->paymentType == 'prepaid') {
		$pg_charges = -number_format($sale_amounts * $pgCharge / 100, 2, '.', '');
	}
	$pg_gst = $pg_charges * 0.18;
	if ($order->paymentGatewayMethod == "card" && $sale_amounts < 2000) {
		$pg_gst = 0;
	}
	$pg_gst = (float)number_format($pg_gst, 2);

	$paymentGatewayAmount = isset($order->paymentGatewayAmount) && isset($order->spStatus) && $order->spStatus != 'delivered' ? (float)$order->paymentGatewayAmount : 0;
	$payout = ($sale_amounts) + $refundAmount + $total_shipping_charges + $paymentGatewayAmount + (float)$pg_charges + $pg_gst;
	return $payout;
}

function get_difference_details($order, $other_order = null)
{
	global $db;

	// Calculate the total sale amount by adding the customer price and shipping charge
	$sale_amounts = (float)($order->customerPrice) + (float)($order->shippingCharge);
	$reverseShippingFee = 0;
	// If there is another order and its fulfillment type is 'return', calculate the reverse shipping fee
	if (isset($other_order)) {
		if ($other_order->fulfillmentType == 'return') {
			$reverseShippingFee = -(float)$other_order->shippingFee;
		}
	}

	// If the order has a return id, calculate the refund amount based on the source of the return
	if (isset($order->return_id)) {
		if ($order->r_source == 'courier_return') {
			$refundAmount = -(float)($order->totalPrice);
		} else {
			$refundAmount = -(float)$order->customerPrice;
		}
	} else {
		$refundAmount = 0;
	}

	$other_fees = 0;
	// Get the charges associated with the order
	$db->where('channel_order_id', "%" . getOrderNumber($order->orderNumberFormated) . "%", "LIKE");
	$charges = $db->get(TBL_SP_CHARGE, null);
	$charge = [];

	// Initialize variables for different types of charges
	$rtoFreightCharge = 0;
	$codCharge = 0;
	// If the order has a return id and the source of the return is not 'customer_return', calculate the RTO freight charge and COD charge
	if ($order->return_id && $order->r_source != 'customer_return') {
		if ($order->paymentType == 'cod') {
			$rtoFreightCharge = -(float)$order->rtoShippingFee;
		}
		$codCharge = (float)$order->codFees;
	}
	// Calculate the COD fees if the payment type is 'cod'
	$codFees = isset($order->codFees) && $order->paymentType == 'cod' ? -(float)$order->codFees : 0;
	// Calculate the total other fees
	$other_fees = -(float)$order->shippingFee + $codFees + $rtoFreightCharge + $codCharge + $reverseShippingFee;
	$pg_charges = 0;
	$pg_gst = 0;

	if (strpos(($order->paymentGateway) ? (strtolower($order->paymentGateway)) : (""), "razorpay") !== false)
		$paymentGateway = "RazorPay";
	else
		$paymentGateway = "Gokwik";

	$db->where('fee_tier', ($paymentGateway) ? ($paymentGateway) : ("RazorPay"));
	$pgCharge = $db->getValue(TBL_MP_FEES, 'fee_value');
	// If the payment type is 'prepaid', calculate the payment gateway charges
	if ($order->paymentType == 'prepaid') {
		$pg_charges = -number_format($sale_amounts * $pgCharge / 100, 2, '.', '');
	}
	// Calculate the GST for the payment gateway charges
	$pg_gst = number_format((float)$pg_charges * 0.18, 2, '.', '');
	if ($order->paymentGatewayMethod == "card" && $sale_amounts < 2000)
		$pg_gst = 0;

	// Calculate the payment gateway amount if the order status is not 'delivered'
	// $paymentGatewayAmount = $order->spStatus != 'delivered' ? (float)$order->paymentGatewayAmount : 0;
	// Calculate the expected payout
	// @TODO: GET CORRECT DUE DATE
	$expected_payout = array(
		'settlement_date' => '<b>Order Date: </b>' . date('d M, Y', strtotime($order->orderDate)) . ' <br />Due Date: ' . date('d M, Y', strtotime($order->orderDate)), // 8th day from shipping date
		'sale_amount' => $sale_amounts,
		'order_item_amount' => (float)($order->customerPrice),
		'shipping_charges' => (float)($order->shippingCharge),
		'refund_amount' => $refundAmount,
		'payment_type' => $order->paymentType,
		'shipping_charge' => $other_fees,
		'freight_charges' =>  isset($order->shippingFee) ? -(float)$order->shippingFee : 0,
		'cod_charge' => $codFees,
		'rto_freight' =>  $rtoFreightCharge,
		'cod_charge_reversed' =>  $codCharge,
		'reverse_freight_charges' =>  $reverseShippingFee,
		'reverse_freight_charges_reversed' =>  0,
		'reimbursements' => (float)$order->claimReimbursmentAmount,
		'lost_credit' => 0,
		'soft_refund' => 0,
		'payment_gateway' => (float)$pg_charges + (float)$pg_gst,
		'pg_charges' => (float)$pg_charges,
		'pg_gst' => $pg_gst,
		'other_fees' => 0,
		'gst' => 0,
		'total' => (float)($sale_amounts) + $refundAmount + $other_fees + (float)$pg_charges + (float)$pg_gst,
	);
	$ex_other_fees = $other_fees;

	$other_fees = 0;
	$freight_charges = 0;
	$cod_charges = 0;
	$rto_charges = 0;
	$cod_reverse = 0;
	$freight_charges_reverse = 0;

	foreach ($charges as $payment) {
		if (substr($payment['channel_order_id'], 0, 2) == 'R-') {
			$payment['category'] = 'Reverse ' . $payment['category'];
		}
		if ($payment['category'] == 'COD Charge') {
			$cod_charges = (float)$payment['amount'];
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'Freight Charges') {
			$freight_charges = (float)$payment['amount'];
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'RTO Freight Charge') {
			$rto_charges = (float)$payment['amount'];
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'COD Charge Reversed') {
			$cod_reverse =  (float)$payment['amount'];
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'Reverse Freight Charges') {
			$freight_charges_reverse = (float)$payment['amount'];
			$other_fees += (float)$payment['amount'];
		} else {
			$charge[str_replace(' ', '_', $payment['category'])] = (float)$payment['amount'];
		}
	}

	$total_gst_tax = (float)$other_fees;

	$settlements = array();
	$db->where('orderId', $order->orderNumberFormated, "LIKE");
	$pgCharges = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount, pgCharge, pgGST');

	if (!empty($charges) || !empty($pgCharges)) {
		$order_settlement = array(
			'settlement_date' => '<b>Order Date: </b>' . date('d M, Y', isset($charges) && !empty($charges) ? strtotime($charges[0]['created_at']) : strtotime($order->orderDate)),
			'sale_amount' => 0.00,
			'order_item_amount' => 0,
			'shipping_charges' => 0,
			'refund_amount' => 0,
			'payment_type' => $order->paymentType,
			'shipping_charge' => (float)$other_fees,
			'freight_charges' => round_value($freight_charges),
			'cod_charge' => round_value($cod_charges),
			'rto_freight' => round_value($rto_charges),
			'cod_charge_reversed' => round_value($cod_reverse),
			'reverse_freight_charges' => round_value($freight_charges_reverse),
			'reverse_freight_charges_reversed' => 0,
			'reimbursements' => 0,
			'lost_credit' => 0,
			'soft_refund' => 0,
			'payment_gateway' => 0,
			'pg_charges' => 0,
			'pg_gst' => 0,
			'other_fees' => 0,
			'gst' => 0,
			'total' => round_value($total_gst_tax),
		);
	} else {
		$order_settlement = array();
	}

	$channelName = '';
	if (isset($order->channelName)) {
		$channelName = $order->channelName;
	}
	$remittanceDate = isset($order->remittanceDate) ? date('d M, Y', strtotime($order->remittanceDate)) : '';
	$remittedAmount = isset($order->customerPrice) ? $order->customerPrice : 0;
	$settlements[] = $order_settlement;
	$total_sale_amount = round_value($remittedAmount);

	if ($total_sale_amount != 0) {
		$remittance = array(
			'settlement_date' => '<b>' . $channelName . ' </b><br />' . '<b>Order Date: </b>' . $remittanceDate, // 8th day from shipping date
			'sale_amount' => $pgCharges["remittedAmount"],
			'order_item_amount' => 0,
			'shipping_charges' => 0,
			// 'refund_amount' => 0,
			'refund_amount' => round_value($refundamt),
			'payment_type' => 0,
			'shipping_charge' => 0,
			'freight_charges' => 0,
			'cod_charge' => 0,
			'rto_freight' => 0,
			'cod_charge_reversed' => 0,
			'reverse_freight_charges' => 0,
			'reverse_freight_charges_reversed' => 0,
			'reimbursements' => 0,
			'lost_credit' => 0,
			'soft_refund' => 0,
			'payment_gateway' => $pgCharges["pgCharge"] + $pgCharges["pgGST"],
			'pg_charges' => $pgCharges["pgCharge"],
			'pg_gst' => $pgCharges["pgGST"],
			'other_fees' => 0,
			'gst' => 0,
			'total' => round_value($pgCharges["remittedAmount"] - ($pgCharges["pgCharge"] + $pgCharges["pgGST"])),
		);
	} else {
		$remittance = array();
	}
	$settlements[] = $remittance;

	if (!empty($settlements[0]) || !empty($settlements[1])) {
		foreach ($settlements as $k => $subArray) {
			foreach ($subArray as $id => $value) {
				if ($id == 'shipping_zone' || $id == 'shipping_slab' || $id == 'payment_type' || $id == 'settlement_date')
					continue;
				@$total_settlement[$id] += $value;
			}
		}
	}
	$total_settlement['payment_type'] = isset($settlements[0]['payment_type']) ? $settlements[0]['payment_type'] : 0;
	$return['order'] = (array)$order;
	$return['expected_payout'] = $expected_payout;
	$return['settlements'] = $settlements;
	$return['total_settlement'] = $total_settlement;
	$return['difference'] = array();
	$return['difference'] = array_diff($total_settlement, $expected_payout); // string value difference
	foreach ($expected_payout as $ep_key => $ep_value) { // float value difference with summation
		if ($ep_key == 'settlement_date' || $ep_key == 'shipping_zone' || $ep_key == 'payment_type')
			continue;
		if (isset($total_settlement[$ep_key])) {
			if (($ep_value - $total_settlement[$ep_key]) != 0)
				$return['difference'][$ep_key] = number_format((abs($total_settlement[$ep_key]) - abs($ep_value)), 2, '.', '');

			if ($ep_key == 'commission_rate' && $ep_value != $total_settlement[$ep_key])
				$return['difference'][$ep_key] = number_format($total_settlement[$ep_key], 2, '.', '');
		} else {
			$return['difference']['sale_amount'] = $sale_amounts;
			$return['difference']['refund_amount'] = $refundAmount;
			$return['difference']['shipping_charge'] = $ex_other_fees;
			$return['difference']['reimbursements'] = (float)$order->claimReimbursmentAmount;
			$return['difference']['payment_gateway'] = (float)$pg_charges + (float)$pg_refund_charge;
			$return['difference']['other_fees'] = 0;
			$return['difference']['gst'] = 0;
			$return['difference']['total'] = ($order->status == "CANCELLED") ? 0 : (float)($sale_amounts) + $refundAmount + $ex_other_fees + $order->claimReimbursmentAmount + (float)$pg_charges + (float)$pg_refund_charge;
		}
	}
	$return['difference']['settlement_date'] = ''; // Leave it empty for adding default difference line
	$return['chargesCount'] = count($charges);
	return $return;
}

function getSettlementAmount($order)
{
	global $db;

	$other_fees = 0;

	$db->where('channel_order_id', $order->orderNumberFormated);
	$charges = $db->get(TBL_SP_CHARGE);

	$db->where('orderId', $order->orderNumberFormated, "LIKE");
	$pgCharges = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount, pgCharge, pgGST');

	foreach ($charges as $payment) {
		if (substr($payment['channel_order_id'], 0, 2) == 'R-') {
			$payment['category'] = 'Reverse ' . $payment['category'];
		}
		if ($payment['category'] == 'COD Charge') {
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'Freight Charges') {
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'RTO Freight Charge') {
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'COD Charge Reversed') {
			$other_fees += (float)$payment['amount'];
		} elseif ($payment['category'] == 'Reverse Freight Charges') {
			$other_fees += (float)$payment['amount'];
		} else {
			$charge[str_replace(' ', '_', $payment['category'])] = (float)$payment['amount'];
		}
	}

	$other_amt = $pgCharges["pgCharge"];
	$gst_amt = $pgCharges["pgGST"];

	$total_gst_tax = (float)$other_amt + (float)$gst_amt - (float)$other_fees;

	return $pgCharges["remittedAmount"] - $total_gst_tax;
}

function import_payments_charges($file)
{
	global $db, $log;

	if (file_exists($file)) {
		try {
			$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
			$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($file);
		} catch (Exception $e) {
			die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
		}

		$objWorksheet = $objPHPExcel->getSheet(0);

		$types = array();

		$worksheetTitle = strtolower(str_replace(' ', '_', trim($objWorksheet->getTitle())));
		$highestRow = $objWorksheet->getHighestRow(); // e.g. 10
		$highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
		$orders = array();
		$head_line = array();

		for ($row = 1; $row <= $highestRow; ++$row) {
			$line = array();
			for ($col = 0; $col <= $highestColumnIndex; ++$col) {
				$cell = $objWorksheet->getCellByColumnAndRow($col, $row);
				$val = $cell->getValue();
				if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText)
					$val = $val->getPlainText();

				if ($row == 1) {
					$text = substr($val, 0, strpos($val, "(Rs"));
					if (!$text) $text = preg_replace("/\([^)]+\)/", "", $val);
					if ((($col == 1 || $col == 2 || $col == 3) && $text == 'Date') || ($worksheetTitle == 'ads' && $col == 1))
						$text = 'neft_date';
					$head_line_txt = strtolower(str_replace(' ', '_', trim($text)));
					if (in_array($head_line_txt, $head_line))
						$head_line_txt = $head_line_txt . '_1';
					$head_line[] = $head_line_txt;
				} else {
					$line[$head_line[$col]] = $val;
				}
			}
			if (!empty($line))
				$orders[] = $line;
		}
		$types[$worksheetTitle] = $orders;

		$total = count($orders);

		// ccd($types);
		insert_remittance_charge($types);

		$return = array('type' => 'success', 'total' => $total);
		$log->write($return, 'payments-import');
		return $return;
	} else {
		return array('type' => 'error', 'message' => 'Unable to locate file.');
	}
}

function round_value($value)
{
	return round($value, 2);
}

function getOrderNumber($value)
{
	$lastHyphenPos = strrpos($value, '-');
	$lastDotPos = strrpos($value, '.');
	if (strpos($value, '.') !== false) {
		$lastPosition = $lastDotPos;
	} else {
		$lastPosition = strlen($value);
	}
	$extractedValue = substr($value, $lastHyphenPos + 1, $lastPosition - $lastHyphenPos - 1);
	return $extractedValue;
}

/*function get_due_date($orderItemId, $isIncentive = false){
	global $db;

	$db->where('orderItemId', $orderItemId);
	$db->orderBy('paymentDate', 'DESC');
	$paymentDate = $db->getValue(TBL_FK_PAYMENTS, 'paymentDate');

	$db->join(TBL_FK_RETURNS ." r", "r.orderItemId=o.orderItemId", "LEFT");
	$db->where('o.orderItemId', $orderItemId);
	$date = $db->getOne(TBL_FK_ORDERS.' o', 'orderDate, shippedDate, r_createdDate, r_deliveredDate, r_shipmentStatus');
	$orderDate = $date['orderDate'];
	$shippedDate = date('Y-m-d', strtotime($date['shippedDate']));

	$max_beach_date = date('Y-m-d', strtotime($orderDate .' + 59 days'));
	if (!is_null($date['r_createdDate']) && is_null($date['r_deliveredDate']) && $date['r_shipmentStatus'] != 'return_cancelled'){
		return date('Y-m-d', strtotime($date['r_createdDate'] .' + 59 days'));
	} else if (!is_null($date['r_createdDate']) && !is_null($date['r_deliveredDate']) && $date['r_shipmentStatus'] != 'return_cancelled'){
		return date('Y-m-d', strtotime($date['r_deliveredDate'] .' + 29 days'));
	} else if ($max_beach_date < date('Y-m-d', strtotime($paymentDate .' + 29 days'))){
		return $max_beach_date;
	}

	if ($paymentDate){
		if ($isIncentive){
			return date('Y-m-d', strtotime(date('Y-m-t', strtotime($orderDate)) .' + 45 days'));
		}
		return date('Y-m-d', strtotime($paymentDate .' + 29 days'));
	} else {
		$this->order_date = date('Y-m-d H:i:s', strtotime($orderDate));
		$this->tier = $this->get_seller_tier(); // returns default gold.
		if ($isIncentive){
			return date('Y-m-d', strtotime(date('Y-m-t', strtotime($orderDate)) .' + 45 days'));
		}
		$start = new DateTime($shippedDate);
		$end = new DateTime(date('Y-m-d', strtotime($shippedDate.' +'.$this->get_payment_cycle().' days')));
		$days = $start->diff($end, true)->days;
		$days += intval($days / 7) + ($start->format('N') + $days % 7 >= 7);

		$due_date = date('Y-m-d', strtotime($shippedDate.' +'.$days.' days'));
		if (!date('w', strtotime($due_date)))
			$due_date = date('Y-m-d', strtotime($due_date.' +1 day'));

		return $due_date;
	}
}*/