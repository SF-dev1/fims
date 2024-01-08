<?php

function get_uid_details($marketplace, $orderId = NULL, $orderItemId = NULL, $warrantyPeriod = "", $status = null)
{
	global $db;

	if (is_null($orderId) && is_null($orderItemId))
		return json_encode(array("type" => "error", "msg" => "No order details found."));

	$marketplace_table = TBL_SP_ORDERS;
	if ($marketplace != "shopify") {
		$sq = $db->subQuery("sq");
		$sq->groupBy('firm_id');
		$sq->get(TBL_SP_ACCOUNTS);

		if ($marketplace == "flipkart") {
			$db->join(TBL_FK_ACCOUNTS . ' ac', 'ac.account_id=o.account_id', 'INNER');
			$db->join(TBL_FIRMS . ' f', 'f.firm_id=ac.parent_firm_id', 'INNER');
			$db->join($sq, "sq.firm_id=f.firm_id", "INNER");
			$db->join(TBL_FK_ACCOUNT_LOCATIONS . ' fl', 'fl.locationId=o.locationId', 'LEFT');
			$db->joinWhere(TBL_FK_ACCOUNT_LOCATIONS . ' fl', 'fl.account_id=o.account_id');
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.sku=o.sku");

			$marketplace_table = TBL_FK_ORDERS;
			$location = 'fl.location_name';
			$account_name = 'ac.fk_account_name';
			$address = 'o.deliveryAddress';
			$mp_id = 'o.fsn';
			$invoice_number = 'o.invoiceNumber, ';
			$invoice_date = 'o.invoiceDate';
			$invoice_amount = 'o.invoiceNumber';
		}

		if ($marketplace == "amazon") {
			$db->join(TBL_AZ_ACCOUNTS . ' ac', 'ac.account_id=o.account_id', 'INNER');
			$db->join(TBL_FIRMS . ' f', 'f.firm_id=ac.parent_firm_id', 'INNER');
			$db->join($sq, "sq.firm_id=f.firm_id", "INNER");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.asin", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.sku=o.sku");

			$marketplace_table = TBL_AZ_ORDERS;
			$location = 'o.fulfilmentSite';
			$account_name = 'ac.az_account_name';
			$address = 'o.shippingAddress';
			$mp_id = 'o.asin';
			$invoice_number = '';
			$invoice_date = 'o.shippedDate';
			$invoice_amount = 'o.orderTotal';
		}

		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
		if (!is_null($orderId))
			$db->where('orderId', $orderId);

		if (!is_null($orderItemId))
			$db->where('orderItemId', $orderItemId);

		$db->where('status', 'shipped');
		$orders = $db->objectBuilder()->get($marketplace_table . ' o', NULL, '"' . ucfirst($marketplace) . '" as marketplace, o.orderId, o.orderItemId, ' . $account_name . ' as accountName, sq.account_id as accountId, ' . $location . ' as location, o.orderDate, o.quantity, o.title, ' . $mp_id . ' as mpId, o.sku, ' . $address . ' as deliveryAddress, ' . $invoice_number . ' ' . $invoice_date . ' as invoiceDate, ' . $invoice_amount . ' as invoiceAmount, uid as uids, c.catid, c.categoryName, pc.cid');
	} else {
		$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
		$db->join(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.locationId=o.locationId', 'LEFT');
		$db->joinWhere(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.accountId=o.accountId');
		// $db->join(TBL_PRODUCTS_ALIAS ." p", "p.mp_id=o.asin", "LEFT");
		// $db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id=o.accountId");
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
		// $db->join(TBL_PRODUCTS_COMBO ." pc", "pc.cid=p.cid", "LEFT");
		$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=pm.category", "LEFT");
		$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderNumberFormated", "LEFT");
		$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'fulfillmentType', 'forward');
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'fulfillmentStatus', 'shipped');
		if ($status) {
			$db->where('o.orderId', $orderId);
		} else {
			$db->where('(orderNumber = ? OR orderNumberFormated = ? OR invoiceNumber = ? )', array($orderId, $orderId, $orderId));
		}
		$db->where('(status = ? AND spStatus = ?)', array('shipped', 'delivered'));
		$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, '"Shopify" as marketplace, o.orderId, o.orderItemId, sa.account_name as accountName, o.accountId, sl.locationName as location, o.orderDate, o.quantity, o.title, "NA" as mpId, o.sku, o.deliveryAddress, o.invoiceNumber, o.invoiceDate, o.invoiceAmount, f.deliveredDate, o.uid as uids, o.variantId, c.catid, c.categoryName, 0 as cid, 6 as warrantyPeriod, if(isnull(w.insertDate), 0, 1) as warrantyRegistred');
	}

	if ($orders) {
		for ($i = 0; $i < count($orders); $i++) {
			if ($marketplace == "flipkart") {
				$orders[$i]->deliveryAddress = json_decode($orders[$i]->deliveryAddress);
				$orders[$i]->mpLink = str_replace('FSN', $orders[$i]->mpId, FK_PRODUCT_URL);
			} elseif ($marketplace == "amazon") {
				$orders[$i]->deliveryAddress = json_decode($orders[$i]->deliveryAddress);
				$orders[$i]->mpLink = str_replace('ASIN', $orders[$i]->mpId, AZ_PRODUCT_URL);
			} else {
				$orders[$i]->deliveryAddress = json_decode($orders[$i]->deliveryAddress);
				$orders[$i]->deliveryAddress->firstName = $orders[$i]->deliveryAddress->first_name;
				$orders[$i]->deliveryAddress->lastName = $orders[$i]->deliveryAddress->last_name;
				$orders[$i]->deliveryAddress->addressLine1 = $orders[$i]->deliveryAddress->address1;
				$orders[$i]->deliveryAddress->addressLine2 = $orders[$i]->deliveryAddress->address2;
				$orders[$i]->deliveryAddress->state = $orders[$i]->deliveryAddress->province;
				$orders[$i]->deliveryAddress->pinCode = $orders[$i]->deliveryAddress->zip;
				$orders[$i]->deliveryAddress->landmark = "";
				$orders[$i]->deliveryAddress->contactNumber = substr(str_replace(array('+', ' '), '', $orders[$i]->deliveryAddress->phone), -10);

				if (strtotime($orders[$i]->deliveredDate) > 1665792000 && strtotime($orders[$i]->deliveredDate) < 1667260800) { // Diwali Vacation 2022
					$diff = strtotime('2022-11-07 00:00:00 + ' . ceil((1667260800 - strtotime($orders[$i]->deliveredDate)) / 86400) . ' days');
					$orders[$i]->deliveredDate = date('Y-m-d', $diff) . ' ' . date('H:i:s', strtotime($orders[$i]->deliveredDate));
				}

				$orders[$i]->is_returnable = false;
				if (!is_null($orders[$i]->deliveredDate) && strtotime($orders[$i]->deliveredDate . ' + 10 days') > time()) {
					$orders[$i]->is_returnable = true;
				}
			}
			$orders[$i]->uids = json_decode($orders[$i]->uids);
			$orders[$i]->product_uids = "";
			$orders[$i]->uid_select = "";

			$is_combo = is_null($orders[$i]->cid) ? false : true;
			$orders[$i]->is_combo = $is_combo;
			$orders[$i]->uid_select = "";
			$orders[$i]->warrantyExpiry = date('Y-m-d', strtotime($orders[$i]->invoiceDate . '+6 months'));

			if ($orders[$i]->warrantyRegistred) {
				$orders[$i]->warrantyExpiry = date('Y-m-d', strtotime($orders[$i]->invoiceDate . '+12 months'));
				$orders[$i]->warrantyPeriod = 12;
			}

			if (!empty($warrantyPeriod))
				$orders[$i]->warrantyPeriod = $warrantyPeriod;

			$orders[$i]->invoiceDate = date('Y-m-d', strtotime($orders[$i]->invoiceDate));
			$orders[$i]->warrantyActive = 0;
			if (strtotime($orders[$i]->warrantyExpiry) > time())
				$orders[$i]->warrantyActive = 1;

			// GET CATEGORY ISSUES 
			$db->where('part_category', $orders[$i]->catid);
			$damage_issues = $db->get(TBL_PARTS_DETAILS, NULL, 'part_name, part_issue, in_warranty_price, out_warranty_price');
			// $damage_issues = json_decode(get_option('rma_product_issues'));

			// POPULATE DATA
			$orders[$i]->uid_select .= '<div class="form-group"><label class="col-md-3 control-label">Product UID<span class="required" aria-required="true">*</span></label><div class="col-md-9 radio-list">';

			if (!empty($orders[$i]->uids)) {
				$orders[$i]->uid_select .= '<div class="row">';
				foreach ($orders[$i]->uids as $key => $uid) {
					$orders[$i]->uid_selected = "";
					$db->join($marketplace_table . ' o', 'o.orderItemId=r.orderItemId');
					$db->where(('(r.status != ? AND r.status != ?)'), array('completed', 'cancelled'));
					$db->where('r.uid', '%' . $uid . '%', 'LIKE');
					$tooltip_content = '';
					if ($rmaNumber = $db->getValue(TBL_RMA . ' r', 'rmaNumber')) {
						$tooltip_content = 'data-placement="bottom" data-original-title="Active RMA# R-' . $rmaNumber . '"';
						$orders[$i]->uid_selected = "disabled";
					}

					$orders[$i]->uid_select .= '		<div class="col-md-4">
															<div class="checkbox-list">
																<label class="checkbox-inline rma-tooltip" ' . $tooltip_content . '>
																   <input class="checkbox product-uid" type="checkbox" name="uids" value="' . $uid . '" ' . $orders[$i]->uid_selected . ' data-msg="Please select atleast one Product UID" data-error-container=".product-uid-error" required>
																	' . $uid . ' 
																 </label>
															</div>
														</div>';

					$dyn_issues = "";
					foreach ($damage_issues as $issue) {
						$dyn_issues .= '<div class="col-md-4">
													<label class="checkbox-inline">
													<input class="checkboxes issue_checkboxes" data-msg="Please select atleast one Damage Type" data-error-container=".form_damage_product_error_' . $uid . '" type="checkbox" name="' . $uid . '[damaged]" data-group="' . $uid . '" data-partname="' . $issue['part_name'] . '" data-inwarrantyprice="' . $issue['in_warranty_price'] . '" data-outwarrantyprice="' . $issue['out_warranty_price'] . '"  value="' . $issue['part_issue'] . '" disabled> ' . $issue['part_issue'] . ' </label>
												</div>';
					}

					$dyn_issues .= '<div class="col-md-4">
										<label class="checkbox-inline"> 
										<input class="checkboxes other_issue" data-textbox=".issue_textbox_' . $uid . '" type="checkbox">Other</label>
									</div>
									<div class="col-md-4" style="margin-top: 4px;">
										<input type="text" name="' . $uid . '[damaged]" class="form-control input-medium hide issue_textbox issue_textbox_' . $uid . '" data-msg="Please enter issue details" placeholder="Issue Type" value="" tabindex="1"  disabled/>
									</div>';


					$saleable_disabled = '';
					if (!$orders[$i]->is_returnable)
						$saleable_disabled = 'disabled';
					$orders[$i]->product_uids .= '
							<fieldset class="rma_pid_fieldset fieldset_' . $uid . ' hide" id="fieldset_' . $uid . '_div">
								<legend>' . $uid . '</legend>
								<div class="form-group product_condition_list product_condition_list_group_' . $uid . '">
									<label class="col-md-3 control-label">Product Condition<span class="required">*</span></label>
									<div class="col-md-9" data-error-container=".form_product_condition_error_' . $uid . '">
										<div class="row">
											<div class="radio-list">
												<div class="col-md-4">
													<label class="radio-inline">
														<input class="radio product_condition_damaged" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_' . $uid . '" type="radio" name="' . $uid . '[product_condition]" data-group="' . $uid . '" value="damaged" checked />Product Issue
													</label>
												</div>
											</div>
										</div>
									</div>
									<div class="form_product_condition_error_' . $uid . ' col-md-12 col-md-offset-2"></div>
								</div>
								<div class="form-group issue_product_list_group_' . $uid . ' damaged_product_list_group_' . $uid . ' ">
									<label class="col-md-3 control-label">Issue Type<span class="required">*</span></label>
									<div class="col-md-9 checkbox-list">
										<div class="row">
											' . $dyn_issues . '
										</div>
										<div class="form_damage_product_error_' . $uid . '""></div>
									</div>
									
								</div>
							</fieldset>';
				}
				$orders[$i]->uid_select .= '</div><div class="product-uid-error"></div>';
			} else {
				$orders[$i]->uid_select .= '<a href="#" id="uidSelection" data-type="text" data-pk="1" data-placement="left" data-placeholder="Required" data-original-title="Enter Product UID"></a>
		   <a class="uid">check UID</a>';
			}
			$orders[$i]->uid_select .= '</div>';
		}
		if ($orders[0]->is_returnable) {
			return json_encode(array("type" => "error", "msg" => "Order still in returnable. RMA can't be created."));
		}
		return json_encode(array("type" => "success", "data" => $orders));
	} else {
		return json_encode(array("type" => "error", "msg" => "No order details found."));
	}
}

function view_order_query($type)
{
	global $db;

	// if ($is_return){
	$db->where('r.status', '%' . $type . '%', 'LIKE');
	$db->join(TBL_FULFILLMENT . " f", "r.rmaNumber = f.orderId", "LEFT");
	$db->joinwhere(TBL_FULFILLMENT . " f", "f.fulfillmentType", "rma_return");
	$db->join(TBL_SP_ORDERS . " o", "o.orderItemId=r.orderItemId", "LEFT");
	$db->join(TBL_FK_ORDERS . " fo", "fo.orderItemId=r.orderItemId", "LEFT");
	$db->join(TBL_AZ_ORDERS . " az", "az.orderItemId=r.orderItemId", "LEFT");

	$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
	$db->join(TBL_PRODUCTS_MASTER . " pmf", "pmf.sku=fo.sku", "LEFT");
	$db->join(TBL_PRODUCTS_MASTER . " pma", "pma.sku=az.sku", "LEFT");
	$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'LEFT');
	$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=fo.account_id', 'LEFT');
	$db->join(TBL_AZ_ACCOUNTS . ' aa', 'aa.account_id=az.account_id', 'LEFT');
	$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_account_id=sa.account_id', 'LEFT');
	// $db->joinWhere(TBL_SP_LOGISTIC.' sl', 'sl.lp_provider_id=r.r_lpProvider');
	if ($type == "completed")
		$db->where('r.completedDate', array(date('Y-m-d H:i:s', strtotime('-14 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
	else if ($type == "shipped")
		$db->orderBy('r.shippedDate', 'ASC');
	else if ($type == "received")
		$db->orderBy('r.receivedDate', 'ASC');
	else
		$db->orderBy('r.createdDate', 'ASC');
}

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

			$account = (object) array_merge((array) $account, (array) $account_logistics, $account_location);
			return $account;
		}
	}
}

function view_orders_count($order_types, $is_return = false)
{
	global $db;

	foreach ($order_types as $order_type) {
		view_order_query($order_type);
		$orders = $db->ObjectBuilder()->get(TBL_RMA . ' r', null, "COUNT(r.rmaId) as orders_count");
		$count[$order_type] = $orders[0]->orders_count;
	}

	return $count;
}

function view_orders($type)
{
	global $db;

	// $is_claim = false;
	// if ($type == "claimed" || $type == "completed")
	// 	$is_claim = true;
	view_order_query($type);
	// if ($is_return)
	// 	return $db->ObjectBuilder()->get(TBL_SP_RETURNS .' r', null, "o.*, r.*, sa.account_store_url, sa.account_name, sl.*, pm.thumb_image_url as thumb_image_url");

	return $db->ObjectBuilder()->get(TBL_RMA . ' r', null, "r.*,f.trackingId,f.lpProvider,f.logistic");
}

function create_order_view($order, $type, $is_return = false)
{
	global $db;

	$db->where('o.orderId', $order->orderId);
	if ($order->marketplace == "amazon") {
		$db->join(TBL_PRODUCTS_ALIAS . " pa", "pa.mp_id=o.asin", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " pa", "pa.account_id=o.account_id");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " pa", "pa.sku=o.sku");
		$db->join(TBL_PRODUCTS_MASTER . " p", "p.pid=pa.pid", "LEFT");
		$marketplace_table = TBL_AZ_ORDERS;
		$image = 'p.thumb_image_url';
	} elseif ($order->marketplace == "shopify") {
		$db->join(TBL_SP_PRODUCTS . ' p', 'p.productId = o.productId');
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
		$marketplace_table = TBL_SP_ORDERS;
		$image = 'p.imageLink';
	} elseif ($order->marketplace == "flipkart") {
		$db->join(TBL_PRODUCTS_MASTER . " p", "p.sku=o.sku", "LEFT");
		$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=p.category", "LEFT");
		$marketplace_table = TBL_FK_ORDERS;
		$image = 'p.thumb_image_url';
	}
	$orders = $db->objectBuilder()->getone($marketplace_table . ' o', NULL, $image);

	// if ($is_return){
	$multi_items = (isset($order->multiProducts) && $order->multiProducts) ? "<span class='label label-info'>Multiple Items</span>" : "";
	$flag_class = (isset($order->isFlagged) && $order->isFlagged) ? ' active' : '';
	$package_details = "";
	$delivered_details = "";
	$received_details = "";
	$return_type = "COURIER_RETURN";
	$order->logistic = isset($order->forwardLogistic) ? $order->forwardLogistic : '';
	$sort_date = $order->createdDate;

	$uids = is_array($order->uid) ? json_decode($order->uid, true) : array($order->uid);

	$uids_checkbox = "";
	foreach ($uids as $uid) {
		$uids_checkbox .= '<label class="checkbox-inline">' . $uid . '</label>';
	}

	$sla = "";
	if ($type == 'delivered') {
		$sort_date = $order->deliveredDate;
		$delivered_date = is_null($order->deliveredDate) ? "NA" : date("M d, Y", strtotime($order->deliveredDate));
		$delivered_details = "<div class='ordered-approval'>Delivered Date: " . $delivered_date . "</div>";
		if (strtotime($order->deliveredDate) + 60 * 60 * 24 * 1 < time()) { // 6 DAYS
			$sla = "<span class='label label-danger'>Undelivered Breached!</span>";
		}
	}

	$image = "";
	if (isset($orders->thumb_image_url)) {
		$image = "<img src='" . IMAGE_URL . "/uploads/products/" . $orders->thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />";
	}
	if (isset($orders->imageLink)) {
		$image = "<img src='" . $orders->imageLink . "' style='height:100px!important'/>";
	}

	$content = "
					<div class='order-content order-content-" . $order->rmaId . " row'>
						<div class='col-md-1'>
							<div class='bookmark'><a class='flag" . $flag_class . "' data-orderitemid='" . $order->orderItemId . "' href='javascript:void()'><i class='fa fa-bookmark'></i></a></div>
							<div class='ordered-product-image'>
							" . $image . "
							</div>
							<a class='view_order_link btn btn-primary' href='" . BASE_URL . "/rma/order.php?rmaId=" . $order->rmaId . "' target='_blank'>View Order</a>

					</div>
					<div class='col-md-11'>
						<div class='order-content-container'>
							<div class='ordered-product-name'>
							</div>
							<div class='order-approval-container'>
								<div class='ordered-approval'>
									RMA Date: " . date("M d, Y", strtotime($order->createdDate)) . "
								</div>
								" . $delivered_details . "
								<div class='ordered-status'>
									RMA Status: <span class='bold-font ordered-status-value'>" . $order->status . "</span>
								</div>
								<div class='ordered-status'>
									Product ID: <span class='bold-font'>" . implode(', ', $uids) . "</span>
								</div>
							</div>
							<div class='order-complete-details'>
								<div class='order-details'>
									<div class='order-item-block-title'>RMA DETAIL</div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>RMA # </div><div class='order-item-field order-item-value'><a target='_blank' >" . $order->rmaNumber . "</a></div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' >" . $order->orderId . "</a></div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>Marketplace </div><div class='order-item-field order-item-value'>" . ucfirst($order->marketplace) . "</div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'>" . $order->orderItemId . "</div></div>
								</div>
								<div class='order-price-qty order-price-qty-" . $order->rmaId . "'>
									<div class='order-item-block-title'>Product Condition</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Condition </div>
										<div class='order-item-field order-item-value '>" . $order->productCondition . "</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field' style='width: 100px;'>Warranty Period </div>
										<div class='order-item-field order-item-value '>" . $order->warrantyPeriod . "</div>
									</div>
								</div>
								<div class='order-dispatch'>
									<div class='order-item-block-title'>TRACKING DETAILS</div>
									<div class='order-item-block'>
										<div class='order-item-field '>Return Logistic : </div>
										<div class='order-item-field order-item-value order-item-type return_type'>" . $order->logistic . "</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field '>Return Tracking ID : </div>
										<div class='order-item-field order-item-value order-item-type return_type'>" . $order->trackingId . "</div>
									</div>
								</div>
								<div class='order-buyer-details'>
									<div class='order-item-block-title'>RETURN DETAILS</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Type:</div>
										<div class='order-item-field order-item-value order-item-type return_type'>" . ucwords($order->returnType) . "</div>
										
									</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Reason:</div>
										<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>test</div>
										<div class='order-item-field order-item-value order-item-sub-reason return_sub_reason'>subReason</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			";

	$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->rmaId . "' data-account='" . $order->accountId . "' value='" . $order->rmaId . "' />";
	$return['content'] = preg_replace('/\>\s+\</m', '><', $content);
	$return['multi_items'] = (!empty($multi_items) ? "Yes" : "No");
	$return['account'] = isset($order->account_name) ?? '';
	$return['return_type'] = $return_type;
	$return['breached'] = empty($sla) ? "Yes" : "No";
	$return['sort_date'] = date('Ymd', strtotime($sort_date));
	$return['delivered_date'] = isset($order->r_deliveredDate) ? (is_null($order->r_deliveredDate) ? "Undelivered" : date('M d, Y', strtotime($order->r_deliveredDate))) : '';
	$return['action'] = "";
	return $return;
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

function get_comments($order_id, $output = false)
{
	global $db;

	$db->where('party_id', 0);
	$users = $db->objectBuilder()->get(TBL_USERS, NULL, 'userID, display_name');

	$db->where('rmaId', $order_id);
	$db->orderBy('createdDate', 'DESC');
	$comments = $db->objectBuilder()->get(TBL_RMA_COMMENTS);
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
function get_user_by_id($users_data, $user_id, $id = "userID")
{
	foreach ($users_data as $user_data) {
		if ($user_data->{$id} == $user_id)
			return $user_data;
	}
}
