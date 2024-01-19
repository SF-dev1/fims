<?php

function get_current_account($search, $id = "account_id")
{
	global $accounts;
	foreach ($accounts as $account) {
		if ($account->{$id} == $search)
			return $account;
	}
}

function view_order_query($type, $fulfillmentChannel, $is_return = false, $is_claim = false)
{
	global $db;

	if ($is_return) {
		$db->where('r.rShipmentStatus', '%' . $type . '%', 'LIKE');
		$db->join(TBL_AZ_ORDERS . " o", "o.orderId=r.orderId", "INNER");
		$db->joinWhere(TBL_AZ_ORDERS . " o", "r.rASIN=o.asin");
		$db->join(TBL_AZ_CLAIMS . " c", "r.orderId=c.orderId", "LEFT");
		$db->joinWhere(TBL_AZ_CLAIMS . " c", "r.rRMAId=c.rmaId");
		$db->joinWhere(TBL_AZ_CLAIMS . " c", "c.claimStatus", "pending");
		$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.asin", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.sku=o.sku");
		$db->join(TBL_AZ_ACCOUNTS . ' az', 'az.account_id=o.account_id', 'INNER');
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		if ($type == "completed")
			$db->where('r.rCompletionDate', array(date('Y-m-d H:i:s', strtotime('-14 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
		else if ($type == "delivered")
			$db->orderBy('r.rDeliveredDate', 'ASC');
		else if ($type == "received")
			$db->orderBy('r.rReceivedDate', 'ASC');
		else if ($type == "claimed" && $is_claim)
			$db->orderBy('c.createdDate', 'ASC');
		else
			$db->orderBy('r.rCreatedDate', 'ASC');
	} else {
		// if (date('H', time()) < '14'){
		// 	$shipdate = date('Y-m-d H:i:s', strtotime('today 11:59:59 PM'));
		// } else {
		// 	$shipdate = date('Y-m-d H:i:s', strtotime('tomorrow 11:59:59 PM'));
		// }

		if ($type == "pending") {
			$db->where('((o.az_status = ? OR o.az_status = ?) AND o.status = ? AND o.dispatchAfterDate >= ?)', array('Unshipped', 'Pending', 'new', date('Y-m-d H:i:s', time())));
		} else {
			$db->where('o.status', $type);
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
			$db->where('o.lastUpdateAt', array(date('Y-m-d H:i:s', strtotime('-3 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
		}

		$db->where('o.isAFNReturns', 0);
		if (!empty($fulfillmentChannel))
			$db->where('o.fulfillmentChannel', $fulfillmentChannel);
		$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.asin", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.sku=o.sku");
		$db->join(TBL_AZ_ACCOUNTS . ' az', 'az.account_id=o.account_id', 'INNER');
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		$db->orderBy('o.orderDate', 'ASC');
		$db->orderBy('o.shipByDate', 'ASC');
	}
}

function view_orders_count($order_types, $fulfillmentChannel, $is_return = false)
{
	global $db;

	foreach ($order_types as $order_type) {
		view_order_query($order_type, $fulfillmentChannel, $is_return);
		if ($is_return)
			$orders = $db->ObjectBuilder()->get(TBL_AZ_RETURNS . ' r', null, "COUNT(r.orderId) as orders_count");
		else
			$orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS . ' o', null, "COUNT(orderId) as orders_count");
		$count[$order_type] = $orders[0]->orders_count;
	}

	return $count;
}

function view_orders($type, $fulfillmentChannel, $is_return = false)
{
	global $db;

	$is_claim = false;
	if ($type == "claimed")
		$is_claim = true;

	view_order_query($type, $fulfillmentChannel, $is_return, $is_claim);
	if ($is_claim)
		return $db->ObjectBuilder()->get(TBL_AZ_RETURNS . ' r', null, "o.*, r.*, c.*, az.account_name, COALESCE(p.corrected_sku, p.sku) as sku, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as thumb_image_url");
	if ($is_return)
		return $db->ObjectBuilder()->get(TBL_AZ_RETURNS . ' r', null, "o.*, r.*, az.account_name, COALESCE(p.corrected_sku, p.sku) as sku, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as thumb_image_url");
	else
		return $db->ObjectBuilder()->get(TBL_AZ_ORDERS . ' o', null, "o.*, az.account_name, COALESCE(p.corrected_sku, p.sku) as sku, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as thumb_image_url");
}

function create_order_view($order, $type, $is_return = false)
{
	if ($is_return) {
		$multi_items = ($order->isMultiItems) ? "<span class='label label-info'>Multiple Items</span>" : "";
		$flag_class = ($order->isFlagged ? ' active' : '');
		$express = ($order->shipServiceCategory == "Expedited" ? "<span class='label label-default'><i class='fa fa-shipping-fast'></i></span>" : "");
		$fulfilment_type = "<span class='label label-info'>" . $order->fulfillmentChannel . "</span>";
		$order_qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-warning'>" . $order->quantity . " units</span>";
		$return_qty = ($order->rQty == 1) ? $order->rQty . " unit" : "<span class='label label-warning'>" . $order->rQty . " units</span>";
		$rma_details = "";
		$package_details = "";
		$delivered_details = "";
		$received_details = "";
		$return_type = "COURIER_RETURN";
		$sort_date = $order->rCreatedDate;
		if ($order->rSource == "CUSTOMER_RETURN") {
			$return_type = "CUSTOMER_RETURN";
			$rma_details = 	"<div class='ordered-status'>RMA ID: " . $order->rRMAId . "</div>";
		}
		if ($order->fulfillmentChannel == "AFN" && $order->fulfilmentSite == "ZWCI") {
			$package_details = "<div class='order-item-block'><div class='order-item-field order-item-padding'>Package ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellerflex.amazon.in/orderDetails?shipmentId=" . $order->packageId . "'>" . $order->packageId . "</a></div></div>";
			$fulfilment_type = "<span class='label label-info'>FLEX</span>";
			$order->fulfillmentChannel = "FLEX";
		}

		$return_type_details = "<span class='label label-default'>" . $return_type . "</span>";
		$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement</span>" : "";

		$uids = json_decode($order->uid, true);
		$uids_checkbox = "";
		if (count($uids) > 1 && $return_type == "CUSTOMER_RETURN") {
			foreach ($uids as $uid) {
				$uids_checkbox .= '<label class="checkbox-inline"><input name="uids[]" type="checkbox" value="' . $uid . '" required>' . $uid . '</label>';
			}
		}

		$sla = "";
		$allow_cancel = false;
		$active_claim_window = true;
		$update_refund_date = false;
		if ($type == 'start' || $type == 'in_transit' || $type == 'out_for_delivery') {
			if (strtotime($order->rCreatedDate) > strtotime('21 April, 2021') && strtotime($order->rCreatedDate) < strtotime('7 July, 2021')) { // COVID POLICY : https://sellercentral.amazon.in/gp/help/G8PJDJKTK3U82L7W?referral=APM4CN9239W8L_A2R3XORGHYA6L1
				if (strtotime($order->rCreatedDate) + 60 * 60 * 24 * 30 < time() && strtotime($order->rCreatedDate) + 60 * 60 * 24 * 110 > time()) {  // 110 - 140 DAYS
					$sla = "<span class='label label-warning'>Proactive Reimbursement Pending</span>";
					$allow_cancel = true;
				} else if (strtotime($order->rCreatedDate) + 60 * 60 * 24 * 50 + 1 < time()) {
					$sla = "<span class='label label-danger'>SLA Breached!</span>";
				}
			} else {
				if (is_null($order->rRefundDate)) {
					$update_refund_date = true;
					$allow_cancel = true;
				} else if (!is_null($order->rRefundDate) && (strtotime($order->rRefundDate) + 60 * 60 * 24 * 31 < time() && strtotime($order->rRefundDate) + 60 * 60 * 24 * 50 > time())) {   // 31 - 57 DAYS
					$sla = "<span class='label label-warning'>Proactive Reimbursement Pending</span>";
					$allow_cancel = false;
					// @TODO: 	IF REFUNDED 
					// 				PROACTIVE REIMBURSEMENT PENDING 
					// 			ELSE 
					// 				CANCEL BUTTON
				} else if (strtotime($order->rCreatedDate) + 60 * 60 * 24 * 50 + 1 < time()) {
					$sla = "<span class='label label-danger'>SLA Breached!</span>";
				}
			}
		}

		if ($type == 'in_transit' || $type == 'out_for_delivery')
			$allow_cancel = false;

		if ($type == 'delivered' || $type == 'received' || $type == 'claimed') {
			$sort_date = $order->rDeliveredDate;
			$delivered_date = is_null($order->rDeliveredDate) ? "NA" : date("M d, Y", strtotime($order->rDeliveredDate));
			$delivered_details = "<div class='ordered-approval'>Delivered Date: " . $delivered_date . "</div>";
			if (strtotime($order->rCreatedDate) > strtotime('21 April, 2021') && strtotime($order->rCreatedDate) < strtotime('7 July, 2021')) { // COVID POLICY : https://sellercentral.amazon.in/gp/help/G8PJDJKTK3U82L7W?referral=APM4CN9239W8L_A2R3XORGHYA6L1
				if (strtotime($order->rCreatedDate) + 60 * 60 * 24 * 30 < time() && strtotime($order->rCreatedDate) + 60 * 60 * 24 * 30 > time()) {  // 30 DAYS
					$sla = "<span class='label label-danger'>Undelivered Breached!</span>";
				} else {
					$sla = "<span class='label label-default'>Claim Window Expired!</span>";
					$active_claim_window = false;
				}
			} else {
				if ($type == 'delivered' && (strtotime($order->rDeliveredDate) + 60 * 60 * 24 < time() && strtotime($order->rDeliveredDate) + 60 * 60 * 24 * 7 > time())) { // 6 DAYS
					$sla = "<span class='label label-danger'>Undelivered Breached!</span>";
				} else {
					$sla = "<span class='label label-default'>Claim Window Expired!</span>";
					$active_claim_window = false;
				}
			}
		}

		if ($type == 'received' || $type == 'claimed' || $type == 'completed') {
			$sort_date = ($type == 'received') ? $order->rReceivedDate : (($type == 'claimed') ? $order->createdDate : $order->rCompletionDate);
			$received_date = is_null($order->rReceivedDate) ? "NA" : date("M d, Y H:i", strtotime($order->rReceivedDate));
			$received_details = "<div class='ordered-approval'>Received Date: " . $received_date . "</div>";
		}

		$action = "";
		if ($allow_cancel) {
			$action =	'<span class="label">&nbsp;</span>
						<div class="btn-group dropup button-actions">
							<button class="btn btn-danger btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Cancel Return <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="cancel-return form-horizontal" id="cancel_form_' . $order->orderId . '_' . $order->rRMAId . '">
									<div class="form-body pull-right">
										<input type="hidden" name="orderId" value="' . $order->orderId . '" />
										<input type="hidden" name="rmaId" value="' . $order->rRMAId . '" />
										<input type="hidden" name="cancelInit" value="1" />
										<input type="hidden" name="action" value="update_returns_status" />
										<button class="btn btn-info" id="cancel_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
									</div>
								</form>
							</div>
						</div>';
		} else if ($update_refund_date) {
			$action = '<span class="label">&nbsp;</span>';
			if ($allow_cancel) {
				$action =	'<div class="btn-group dropup button-actions">
							<button class="btn btn-danger btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Cancel Return <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="cancel-return form-horizontal" id="cancel_form_' . $order->orderId . '_' . $order->rRMAId . '">
									<div class="form-body pull-right">
										<input type="hidden" name="orderId" value="' . $order->orderId . '" />
										<input type="hidden" name="rmaId" value="' . $order->rRMAId . '" />
										<input type="hidden" name="cancelInit" value="1" />
										<input type="hidden" name="action" value="update_returns_status" />
										<button class="btn btn-info" id="cancel_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
									</div>
								</form>
							</div>
						</div>';
			}
			$action .=	'<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Update Refund Date <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="update-refund-date form-horizontal" id="refund_form_' . $order->orderId . '_' . $order->rRMAId . '">
									<div class="form-body">
										<div class="input-group">
											<input type="date" name="refundDate" class="form-control refundDate" id="refundDate_holder_' . $order->orderId . '_' . $order->rRMAId . '" placeholder="Refund Date" autocomplete="off" required>
											<input type="hidden" name="orderId" value="' . $order->orderId . '" />
											<input type="hidden" name="rmaId" value="' . $order->rRMAId . '" />
											<input type="hidden" name="action" value="update_refund_date" />
											<span class="input-group-btn">
												<button class="btn btn-info" id="claim_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
		} else if (!empty($sla) && $active_claim_window && ($type == 'start' || $type == 'out_for_delivery' || $type == 'in_transit' || $type == 'delivered')) {
			$action =	'<span class="label">&nbsp;</span>
						<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Accept Return/Add Case ID <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="add-claim-details form-horizontal" id="claim_form_' . $order->orderId . '_' . $order->rRMAId . '">
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
											<input type="text" name="claimId" class="form-control claimId" id="claimId_holder_' . $order->orderId . '_' . $order->rRMAId . '" placeholder="Case ID" autocomplete="off" required>
											<input type="hidden" name="orderId" value="' . $order->orderId . '" />
											<input type="hidden" name="rmaId" value="' . $order->rRMAId . '" />
											<input type="hidden" name="action" value="update_returns_status" />
											<span class="input-group-btn">
												<button class="btn btn-info" id="claim_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
		}

		if ($type == 'received') {
			$action =	'<span class="label">&nbsp;</span>
						<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Accept Return/Add Case ID <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right hold-on-click form" role="menu">
								<form action="#" class="add-claim-details form-horizontal" id="claim_form_' . $order->orderId . '_' . $order->rRMAId . '">
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
											<span class="input-group-addon"><input type="checkbox" class="is_claim" data-claimbox="claimId_holder_' . $order->orderId . '_' . $order->rRMAId . '"></span>
											<input type="text" name="claimId" class="form-control claimId" id="claimId_holder_' . $order->orderId . '_' . $order->rRMAId . '" placeholder="Case ID" autocomplete="off" disabled required>
											<input type="hidden" name="orderId" value="' . $order->orderId . '" />
											<input type="hidden" name="rmaId" value="' . $order->rRMAId . '" />
											<input type="hidden" name="action" value="update_returns_status" />
											<span class="input-group-btn">
												<button class="btn btn-info" id="claim_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';
		}

		$claim_details = "";
		if (isset($order->claimId)) {
			$sla = "";
			$action = 	'<span class="label">&nbsp;</span>
						<div class="btn-group dropup button-actions">
							<button class="btn btn-default btn-xs dropdown-toggle hold-on-click" data-toggle="dropdown">Add Reimbursement & Complete <i class="fa fa-angle-up"></i></button>
							<div class="dropdown-menu dropdown-content dropdown-menu-xs pull-right input-xlarge hold-on-click form" role="menu">
								<form action="#" class="add-reimbursement-complete" id="reimbursement_form_' . $order->orderId . '_' . $order->rRMAId . '">
									<div class="form-body">
										<div class="form-group">
											<input type="text" name="claimNotes" class="form-control" placeholder="Claim Notes" required />
										</div>
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon"><input type="checkbox" class="is_reimbursed" data-claimbox="reimbursement_amount_holder_' . $order->orderId . '_' . $order->rRMAId . '"></span>
												<input type="number" name="claimReimbursmentAmount" class="form-control" id="reimbursement_amount_holder_' . $order->orderId . '_' . $order->rRMAId . '" placeholder="Add Reimbursement Amount" step=".01" min="0" disabled required>
												<input type="hidden" name="orderId" value="' . $order->orderId . '" />
												<input type="hidden" name="rRMAId" value="' . $order->rRMAId . '" />
												<input type="hidden" id="form_claim_id_' . $order->orderId . '_' . $order->rRMAId . '" name="claimId" value="' . $order->claimId . '" />
												<input type="hidden" name="claimProductCondition" value="' . $order->claimProductCondition . '" />
												<input type="hidden" name="shipmentStatus" value="return_completed" />
												<input type="hidden" name="action" value="update_claim_details" />
												<span class="input-group-btn">
												<button class="btn blue" id="reimbursement_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
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
								<form action="#" class="update-claim-id form-horizontal" id="update_claim_form_' . $order->orderId . '_' . $order->rRMAId . '">
									<div class="form-body">
										<div class="input-group">
											<input type="text" name="newClaimId" class="form-control claimId" placeholder="Case ID" autocomplete="off" required>
											<input type="hidden" name="orderId" value="' . $order->orderId . '" />
											<input type="hidden" name="rmaId" value="' . $order->rmaId . '" />
											<input type="hidden" name="claimId" value="' . $order->claimId . '" />
											<input type="hidden" name="action" value="update_claim_id" />
											<span class="input-group-btn">
											<button class="btn blue" id="update_claim_submit_' . $order->orderId . '_' . $order->rRMAId . '" type="submit">Go!</button>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>';

			$claim_details = "<div class='order-approval-container'>
					<div class='ordered-approval'>
						Claim Date: " . date("M d, Y", strtotime($order->createdDate)) . "
					</div>
					<div class='ordered-status'>
						Claim Status: <span class='bold-font ordered-status-value' id='claim_status_" . $order->orderId . "_" . $order->rRMAId . "'>" . $order->claimStatusAZ . "</span>
					</div>
					<div class='ordered-status' id='claim_id_" . $order->orderId . "_" . $order->rRMAId . "'>
						Claim ID: <a href='https://sellercentral.amazon.in/safet-claims/claim/" . $order->claimId . "' target='_blank'>" . $order->claimId . "</a>
					</div>
					<div class='ordered-status'>
						Product Condition: <span class='bold-font'>" . ucfirst($order->claimProductCondition) . "</span>
					</div>
				</div>";

			if ($order->claimProductCondition == "undelivered")
				$received_details = "";
			else
				$delivered_details = "";
		}

		$content = "
					<div class='order-content row'>
						<div class='col-md-1'>
							<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
							<div class='ordered-product-image'>
								<img src='" . IMAGE_URL . "/uploads/products/" . $order->thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
							</div>
						</div>
						<div class='col-md-11'>
							<div class='order-content-container'>
								<div class='ordered-product-name'>
									<a target='_blank' href='" . str_replace('ASIN', $order->asin, AZ_PRODUCT_URL) . "'>" . $order->title . "</a> <span class='label label-success'>" . $order->account_name . "</span> " . $sla . " " . $return_type_details . $fulfilment_type . " " . $replacement_order . " " . $action . "
								</div>
								<div class='order-approval-container'>
									<div class='ordered-approval'>
										Return Date: " . date("M d, Y", strtotime($order->rCreatedDate)) . "
									</div>
									" . $delivered_details . $received_details . "
									<div class='ordered-status'>
										Return Status: <span class='bold-font ordered-status-value'>" . $order->rShipmentStatus . "</span>
									</div>
									" . $rma_details . "
									<div class='ordered-status'>
										Product ID: <span class='bold-font'>" . implode(', ', $uids) . "</span>
									</div>
								</div>"
			. $claim_details .
			"<div class='order-complete-details'>
									<div class='order-details'>
										<div class='order-item-block-title'>ORDER DETAIL</div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellercentral.amazon.in/orders-v3/order/" . $order->orderId . "'>" . $order->orderId . "</a> <a href='https://sellercentral.amazon.in/gp/payments-account/view-transactions.html?orderId=" . $order->orderId . "&ref_=myp_dash_ordsearch&view=search' target='_blank' title='View Payments Details'><i class='fab fa-amazon-pay'></i></a></div></div>
										" . $package_details . "
										<div class='order-item-block'><div class='order-item-field order-item-padding'>ASIN </div><div class='order-item-field order-item-value'><a target='_blank' href='" . str_replace('ASIN', $order->asin, AZ_PRODUCT_URL) . "'>" . $order->asin . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>" . $order->sku . "</div></div>
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
									</div>
									<div class='order-dispatch'>
										<div class='order-item-block-title'>TRACKING DETAILS</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Return</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' href='javascript:void(0)'>" . $order->rTrackingId . "</a></div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Forward</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' href='javascript:void(0)'>" . $order->trackingID . "</a></div>
										</div>
									</div>
									<div class='order-buyer-details'>
										<div class='order-item-block-title'>RETURN DETAILS</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Reason:</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>" . $order->rReason . "</div><br />
											<!--<div class='order-item-field order-item-padding order-item-sub-reason return_sub_reason'>(" . $order->rReason . ")</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Comment:</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date comments'>" . stripcslashes($order->rReason) . "</div>-->
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				";

		$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->orderId . "' data-account='" . $order->account_id . "' value='" . $order->orderId . "' />";
		$return['content'] = preg_replace('/\>\s+\</m', '><', $content);
		$return['multi_items'] = ($order->isMultiItems ? "Yes" : "No");
		$return['account'] = $order->account_name;
		$return['return_type'] = $return_type;
		$return['fulfillment_channel'] = $order->fulfillmentChannel;
		$return['breached'] = empty($sla) ? "Yes" : "No";
		$return['sort_date'] = date('Ymd', strtotime($sort_date));
		$return['delivered_date'] = is_null($order->rDeliveredDate) ? "Undelivered" : date('M d, Y', strtotime($order->rDeliveredDate));
		$return['action'] = "";
	} else {
		$multi_items = ($order->isMultiItems) ? "<span class='label label-info'>Multiple Items</span>" : "";
		$flag_class = ($order->isFlagged ? ' active' : '');
		$express = ($order->shipServiceCategory == "Expedited" ? "<span class='label label-default'><i class='fa fa-shipping-fast'></i></span>" : "");
		$payment_type = $order->paymentMethod == "Other" ? "<span class='label label-info'>Prepaid</span>" : "<span class='label label-info'>" . strtoupper($order->paymentMethod) . "</span>";
		$fulfilment_type = "<span class='label label-info'>" . $order->fulfillmentChannel . "</span>";
		$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement</span>" : "";
		$qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-warning'>" . $order->quantity . " units</span>";
		$sla = "";
		if ($type == 'new' || $type == 'packing' || $type == 'rtd') {
			if ((strtotime($order->shipByDate) - time() < 3600) && (strtotime($order->shipByDate) - time() > 0)) {
				$sla = "<span class='label label-danger'>SLA Breaching Soon!</span>";
			} else if (strtotime($order->shipByDate) - time() < 0) {
				$sla = "<span class='label label-danger'>SLA Breached!</span>";
			}
		}

		$shipment_status = "";
		$pickup_details = "";
		$buyer_details = "";
		$address = json_decode($order->shippingAddress);
		if ($type != 'new') {
			$shipment_status = 	"<div class='ordered-status'>
									EasyShip Status: <span class='bold-font ordered-status-value'>" . $order->shipmentStatus . "</span>
								</div>";
			$pickup_details = 	"<div class='order-item-block'>
									<div class='order-item-field order-item-padding'>Logistic</div>
									<div class='order-item-field order-item-value order-item-confirm-by-date'>" . $order->courierName . "</div>
								</div>
								<div class='order-item-block'>
									<div class='order-item-field order-item-padding'>Tracking</div>
									<div class='order-item-field order-item-value order-item-confirm-by-date'>" . $order->trackingID . "</div>
								</div>";
			$buyer_details = 	"<div class='order-item-block-title buyer-details'>BUYER DETAILS</div>
								<div class='order-item-block'>
									<div class='result-style-address'>
										<div class='shipping-city-pincode-normal'>" . $address->City . ",<br />" . $address->StateOrRegion . "</div>
									</div>
								</div>";
		}

		if ($type == 'packing') {
			$label_request_status = $order->labelFeedId ? "<span class='bold-font ordered-status-value'>Requested</span>" : "<span class='bold-font ordered-status-value label-alert'>Pending</span>";
			$shipment_status .= 	"<div class='ordered-status'>
									Label Request Status: " . $label_request_status . "
								</div>";
		}

		$content = "
					<div class='order-content row'>
						<div class='col-md-1'>
							<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
							<div class='ordered-product-image'>
								<img src='" . IMAGE_URL . "/uploads/products/" . $order->thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
							</div>
						</div>
						<div class='col-md-11'>
							<div class='order-content-container'>
								<div class='ordered-product-name'>
									<a target='_blank' href='" . str_replace('ASIN', $order->asin, AZ_PRODUCT_URL) . "'>" . $order->title . "</a> <span class='label label-success'>" . $order->account_name . "</span> " . $sla . " " . $express . $payment_type . $fulfilment_type . " " . $replacement_order . "
								</div>
								<div class='order-approval-container'>
									<div class='ordered-approval'>
										Order Date: " . date("M d, Y H:i", strtotime($order->orderDate)) . "
									</div>
									<div class='ordered-status'>
										Amazon Status: <span class='bold-font ordered-status-value'>" . $order->az_status . "</span>
									</div>
									" . $shipment_status . "
								</div>
								<div class='order-complete-details'>
									<div class='order-details'>
										<div class='order-item-block-title'>ORDER DETAIL</div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellercentral.amazon.in/orders-v3/order/" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellercentral.amazon.in/orders-v3/order/" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>ASIN </div><div class='order-item-field order-item-value'><a target='_blank' href='" . str_replace('ASIN', $order->asin, AZ_PRODUCT_URL) . "'>" . $order->asin . "</a></div></div>
										<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>" . $order->sku . "</div></div>
									</div>
									<div class='order-price-qty order-price-qty-" . $order->orderId . "'>
										<div class='order-item-block-title'>PRICE &amp; QTY</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Quantity </div>
											<div class='order-item-field order-item-value '>" . $qty . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Value </div>
											<div class='order-item-field order-item-value '>&#8377; " . number_format($order->itemPrice + $order->itemTax, 2, '.', '') . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Shipping </div>
											<div class='order-item-field order-item-value '>&#8377; " . number_format($order->shippingPrice + $order->shippingTax, 2, '.', '') . "</div>
										</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Total </div>
											<div class='order-item-field order-item-value'>&#8377; " . $order->orderTotal . "</div>
										</div>
									</div>
									<div class='order-dispatch'>
										<div class='order-item-block-title'>SHIP</div>
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>After</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchAfterDate)) . "</div>
										</div>
										<div class='order-item-block'>
											<b>
												<div class='order-item-field order-item-padding'>By</div>
												<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->shipByDate)) . "</div>
											</b>
										</div>
										" . $pickup_details . "
									</div>
									<div class='order-buyer-details'>" . $buyer_details . "
									</div>
								</div>
							</div>
						</div>
					</div>
				";

		$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->orderId . "' data-account='" . $order->account_id . "' value='" . $order->orderId . "' />";
		$return['content'] = trim(preg_replace('/\>\s+\</m', '><', $content));
		$return['multi_items'] = ($order->isMultiItems ? "Yes" : "No");
		$return['account'] = $order->account_name;
		$return['payment_type'] = $order->paymentMethod == "Other" ? "Prepaid" : $order->paymentMethod;
		$return['order_date'] = date('M d, Y', strtotime($order->orderDate));
		$return['ship_date'] = date('M d, Y', strtotime($order->shipByDate));
		$return['action'] = "";
	}
	return $return;
}

function update_orders_to_rtd($account_id, $tracking_id, $order_id = "")
{
	global $db;

	$db->where('trackingID', $tracking_id);
	$orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS, NULL, 'status, orderId, account_id, title');
	foreach ($orders as $order) {
		if ($order->status == 'rtd' || $order->status == 'shipped') {
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
			$return['title'] = $order->PDF_set_info_title() . ' <span class="label label-primary">' . $order->order_type . '</span>';
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = '<span class="label label-danger">Cancelled</span>';
			$return['type'] = "error";
			return $return;
		}

		$details = array('status' => 'rtd', 'rtdDate' => date('Y-m-d H:i:s'));
		$db->where('orderId', $order->orderId);
		if ($db->update(TBL_AZ_ORDERS, $details)) {
			// $log->write('OI: '.$order->orderId."\tRTD", 'order-status');
			// $log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderId .' Response '.$rtd_return, 'amz-rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Successful';
			$return['type'] = "success";
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
			$log_status = $stockist->add_inventory_log($uid, 'sales_return', 'Amazon ' . $status . ' :: ' . $order_id);
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

function update_returns_status($tracking_id, $status, $is_order_id = false, $is_manual = false, $rRMAId = NULL, $productCondition = "")
{
	global $db, $stockist;

	if ($status == "completed") {
		$db->where('(r.rShipmentStatus = ? OR r.rShipmentStatus = ? OR r.rShipmentStatus LIKE ?)', array('return_received', 'return_completed', 'return_claimed%'));
	} else {
		$db->where('(r.rShipmentStatus = ? OR r.rShipmentStatus = ? OR r.rShipmentStatus = ? OR r.rShipmentStatus = ? OR r.rShipmentStatus = ? OR r.rShipmentStatus LIKE ? OR r.rShipmentStatus IS NULL)', array('start', 'in_transit', 'out_for_delivery', 'delivered', 'return_received', 'return_claimed%'));
	}
	$db->join(TBL_AZ_ORDERS . " o", "r.orderId=o.orderId", "INNER");
	if ($is_order_id)
		$db->where('r.orderId', $tracking_id);
	else
		$db->where('(r.rTrackingId = ? OR o.trackingID = ?)', array($tracking_id, $tracking_id));

	if (!is_null($rRMAId))
		$db->where('r.rRMAId', $rRMAId);

	if ($orders = $db->ObjectBuilder()->get(TBL_AZ_RETURNS . ' r', null, "o.title, o.quantity, o.isFlagged, COALESCE(r.rUid, o.uid) as uid, r.*")) {
		$i = 0;
		foreach ($orders as $order) {
			if (($order->rShipmentStatus == 'return_received' && $status == "received") || ($order->rShipmentStatus == 'return_completed' && $status == "completed")) {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderId;
				$return[$i]['tracking_id'] = $tracking_id;
				$return[$i]['message'] = 'Return already marked ' . strtoupper(str_replace('_', ' ', $order->rShipmentStatus));
				$return[$i]['type'] = "error";
			} else if (($order->rShipmentStatus != 'return_received' && $order->rShipmentStatus != 'return_claimed_undelivered' && $order->rShipmentStatus != 'return_claimed') && $status == "completed") {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderId;
				$return[$i]['tracking_id'] = $tracking_id;
				$return[$i]['message'] = 'Return status is ' . $order->rShipmentStatus;
				$return[$i]['type'] = "error";
			} else if ($order->rSource == "CUSTOMER_RETURN" && $status == "completed" && $order->rQty != $order->quantity && !$is_manual) {
				$return[$i]['title'] = $order->title;
				$return[$i]['order_item_id'] = $order->orderId;
				$return[$i]['trackingId'] = $trackingId;
				$return[$i]['message'] = 'Customer Return with qty mismatch. Acknowledge Manually.';
				$return[$i]['type'] = "error";
			} else if ($status == "cancelled") {
				$details = array(
					'rStatus' => 'cancelled',
					'rShipmentStatus' => 'cancelled',
					'rUpdatedDate' => $db->now(),
				);

				$db->where('shipmentId', $order->shipmentId);
				$db->where('rShipmentStatus', 'cancelled', '!=');
				$db->where('rRMAId', $order->rRMAId);
				if ($db->update(TBL_AZ_RETURNS, $details)) {
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderId;
					$return[$i]['tracking_id'] = $tracking_id;
					$return[$i]['is_flagged'] = $order->isFlagged;
					$return[$i]['message'] = 'Return Canclled Successfully';
					$return[$i]['type'] = "success";
				}
			} else {
				$details = array(
					'rShipmentStatus' => 'return_' . $status,
				);

				if ($status == "received") {
					$details['rReceivedDate'] = date('Y-m-d H:i:s');
					if (is_null($order->rDeliveredDate))
						$details['rDeliveredDate'] = date('Y-m-d H:i:s');
				}

				if ($status == "completed")
					$details['rCompletionDate'] = date('Y-m-d H:i:s');

				$db->where('rTrackingId', $order->rTrackingId);
				$db->where('shipmentId', $order->shipmentId);
				$db->where('rShipmentStatus', 'cancelled', '!=');
				$db->where('rRMAId', $order->rRMAId);
				if ($db->update(TBL_AZ_RETURNS, $details)) {
					if ($status == "completed") {
						// @TODO: PAYMENT INTEGRATION
						// if ($this->payments)
						// 	$this->payments->fetch_payout($order->orderId);

						// UID DRIVE
						if (!is_null($order->uid) && ($productCondition != "undelivered" && $productCondition != "wrong")) {
							$uids = json_decode($order->uid, true);
							foreach ($uids as $uid) {
								$stockist->update_inventory_status($uid, 'qc_pending');
								$stockist->add_inventory_log($uid, 'sales_return', 'Amozon Return :: ' . $order->orderId);
							}
						}
					}

					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderId;
					$return[$i]['tracking_id'] = $tracking_id;
					$return[$i]['is_flagged'] = $order->isFlagged;

					if ($status == "received") {
						$return[$i]['message'] = 'Return Received Successfully';
					} else {
						$return[$i]['message'] = 'Return Completed Successfully';
					}
					$return[$i]['type'] = "success";
				} else {
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderId;
					$return[$i]['tracking_id'] = $tracking_id;
					$return[$i]['message'] = 'Return Not Updated. Please retry.';
					$return[$i]['type'] = "error";
					$return[$i]['error'] = $db->getLastError();
				}
			}
		}
	} else {
		$return[0]['title'] = "";
		$return[0]['order_item_id'] = "";
		$return[0]['tracking_id'] = $tracking_id;
		$return[0]['message'] = 'Return Not Found';
		$return[0]['type'] = "error";
	}
	return $return;
}

function update_claim($order_id, $rma_id, $case_id, $details)
{
	global $db;

	$db->where('orderId', $order_id);
	$db->where('rmaId', $rma_id);
	$db->where('claimId', $case_id);
	if ($db->update(TBL_AZ_CLAIMS, $details))
		return true;
}

function get_cost_price_by_asin($account_id, $asin)
{
	global $db, $stockist;

	$db->where('mp_id', $asin);
	$db->where('account_id', $account_id);
	$db->where('marketplace', 'amazon');
	$parentSkuID = $db->objectBuilder()->getOne(TBL_PRODUCTS_ALIAS, 'alias_id, pid, cid');
	if ($parentSkuID) {
		$cost_price = 0;
		if ($parentSkuID->cid) {
			$db->where('cid', $parentSkuID->cid);
			$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
			$products = json_decode($products['pid']);
			$units = count($products);

			foreach ($products as $pro) {
				$cost_price += $stockist->get_current_acp($pro);
			}
		} else {
			$cost_price = $stockist->get_current_acp($parentSkuID->pid);
		}
	} else {
		$cost_price = "Parent SKU not found";
	}

	return $cost_price;
}

function getUrlMimeType($url)
{
	$buffer = file_get_contents($url);
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	return $finfo->buffer($buffer);
}

function insert_payments($account_id, $data)
{
	global $db;
	$settlementDate = str_replace(".", "-", $data[0]["deposit_date"]);
	$settlementId = $data[0]["settlement_id"];
	// $totalSettledAmount = $data[0]["total_amount"];

	$payments = array();
	$insertData = array();

	for ($i = 1; $i < count($data); $i++) {
		$orderId = $data[$i]["order_id"];
		$itemCode = $data[$i]["order_item_code"];

		$index = $i;
		while ($orderId == $data[$i]["order_id"]) {
			$payments[$index][$orderId][$itemCode]["gst"] = 0;
			foreach ($data[$i] as $key => $value) {

				if ($key == "price_type") {
					$payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["price_amount"];
				} else if ($key == "item_related_fee_type") {
					$payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["item_related_fee_amount"];
				} else if ($key == "promotion_type") {
					$payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["promotion_amount"];
				} else {
					$payments[$index][$orderId][$itemCode][$key] = $value;
				}
			}
			$i++;
		}
		$insertData[] = array(
			"settlementId" => $settlementId,
			"accountId" => $account_id,
			"orderId" => $orderId,
			"itemId" => $itemCode,
			"settlementDate" => $settlementDate,
			"salesAmount" => (is_null($payments[$index][$orderId][$itemCode]["principal"]) ? 0 : $payments[$index][$orderId][$itemCode]["principal"]),
			"salesGst" => (is_null($payments[$index][$orderId][$itemCode]["product_tax"]) ? 0 : $payments[$index][$orderId][$itemCode]["product_tax"]),
			"tcs" => (is_null($payments[$index][$orderId][$itemCode]["tcs_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["tcs_igst"]),
			"tds" => ((is_null($payments[$index][$orderId][$itemCode]["tds_(section_194_o)"]) ? 0 : $payments[$index][$orderId][$itemCode]["tds_(section_194_o)"])),
			"shippingCharge" => (is_null($payments[$index][$orderId][$itemCode]["shipping"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping"]),
			"shippingTax" => (is_null($payments[$index][$orderId][$itemCode]["shipping_tax"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_tax"]),
			"shippingDiscount" => (is_null($payments[$index][$orderId][$itemCode]["shipping_discount"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_discount"]),
			"shippingTaxDiscount" => (is_null($payments[$index][$orderId][$itemCode]["shipping_tax_discount"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_tax_discount"]),
			"handlingCharge" => (is_null($payments[$index][$orderId][$itemCode]["fba_weight_handling_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee"]),
			"handlingTax" => $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee_cgst"] + $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee_sgst"],
			"pickPackCharge" => (is_null($payments[$index][$orderId][$itemCode]["fba_pick_pack_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee"]),
			"pickPackTax" => $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee_cgst"] + $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee_sgst"],
			"commission" => (is_null($payments[$index][$orderId][$itemCode]["commission"]) ? 0 : $payments[$index][$orderId][$itemCode]["commission"]),
			"commissionTax" => (is_null($payments[$index][$orderId][$itemCode]["commission_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["commission_igst"]),
			"closingFee" => (is_null($payments[$index][$orderId][$itemCode]["fixed_closing_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fixed_closing_fee"]),
			"closingFeeTax" => (is_null($payments[$index][$orderId][$itemCode]["fixed_closing_fee_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["fixed_closing_fee_igst"]),
		);
	}

	if ($db->insertMulti(TBL_AZ_PAYMENTS, $insertData)) {
		$return = array("type" => "success", "message" => "payments data successfully inserted!");
	} else {
		$return = array("type" => "Error", "message" => "Unable to insert", "error" => $db->getLastError());
	}
	return $return;
}
