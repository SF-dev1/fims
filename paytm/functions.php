<?php

function get_current_account($search, $id = "account_id"){
	global $accounts;
	foreach ($accounts as $account) {
		if ($account->{$id} == $search)
			return $account;
	}
}

function view_order_query($type){
	global $db;

	if (date('H', time()) < '12'){
		$shipdate = date('Y-m-d H:i:s', strtotime('today 11:59:59 PM'));
	} else {
		$shipdate = date('Y-m-d H:i:s', strtotime('tomorrow 11:59:59 PM'));
	}

	if ($type == "pending"){
		$db->where('(o.status = ? AND o.dispatchByDate >= ?)', array('new', $shipdate));
	} else {
		$db->where('o.status', $type);
	}

	if ($type == "new"){
		$db->where("o.dispatchByDate", $shipdate);
	}

	if ($type == 'shipped'){
		if (date('H', time()) < '09'){
			$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('yesterday 09:00 AM')), date('Y-m-d H:i:s', strtotime('today 09:00 AM'))), 'BETWEEN');
		} else {
			$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('today 09:00 AM')), date('Y-m-d H:i:s', strtotime('tomorrow 09:00 AM'))), 'BETWEEN');
		}
	}

	if ($type == 'cancelled'){
		$db->where('o.lastUpdateAt', array(date('Y-m-d H:i:s', strtotime('-3 days')), date('Y-m-d H:i:s', strtotime('tomorrow')-1)), 'BETWEEN');
	}

	$db->join(TBL_PRODUCTS_ALIAS ." p", "p.mp_id=o.productId", "LEFT");
	$db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id=o.accountId");
	$db->join(TBL_PT_ACCOUNTS.' pt', 'pt.account_id=o.accountId', 'INNER');
	$db->join(TBL_PRODUCTS_MASTER ." pm", "pm.pid=p.pid", "LEFT");
	$db->join(TBL_PRODUCTS_COMBO ." pc", "pc.cid=p.cid", "LEFT");
	$db->orderBy('o.orderDate', 'ASC');
	$db->orderBy('o.dispatchByDate', 'ASC');
}

function view_orders_count($order_types, $is_return = false){
	global $db;

	foreach ($order_types as $order_type) {
		view_order_query($order_type);
		$orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS. ' o', null, "COUNT(orderId) as orders_count");
		$count[$order_type] = $orders[0]->orders_count;
	}

	return $count;
}

function view_orders($type, $is_return = false){
	global $db;
	
	view_order_query($type);
	return $db->ObjectBuilder()->get(TBL_PT_ORDERS .' o', null, "o.*, pt.account_name, COALESCE(p.corrected_sku, p.sku) as sku, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as thumb_image_url");
}

function create_order_view($order, $type){
	$multi_items = ($order->isMultiItems) ? "<span class='label label-info'>Multiple Items</span>" : "";
	$flag_class = ($order->isFlagged ? ' active' : '');
	$express = ($order->shipServiceCategory == "Expedited" ? "<span class='label label-default'><i class='fa fa-shipping-fast'></i></span>" : "");
	$payment_type = $order->paymentMethod == "Other" ? "<span class='label label-info'>Prepaid</span>" : "<span class='label label-info'>".strtoupper($order->paymentMethod)."</span>";
	$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement</span>" : "";
	$qty = ($order->quantity == 1) ? $order->quantity ." unit" : "<span class='label label-warning'>".$order->quantity." units</span>";
	$sla = "";
	if ($type == 'new' || $type == 'packing' || $type == 'rtd'){
		if (( strtotime($order->shipByDate) - time() < 3600) && (strtotime($order->shipByDate) - time() > 0)){
			$sla = "<span class='label label-danger'>SLA Breaching Soon!</span>";
		} else if (strtotime($order->shipByDate) - time() < 0){
			$sla = "<span class='label label-danger'>SLA Breached!</span>";
		}
	}

	$shipment_status = "";
	$pickup_details = "";
	$buyer_details = "";
	$address = json_decode($order->shippingAddress);
	if ($type != 'new'){
		$shipment_status = 	"<div class='ordered-status'>
								EasyShip Status: <span class='bold-font ordered-status-value'>".$order->shipmentStatus."</span>
							</div>";
		$pickup_details = 	"<div class='order-item-block'>
								<div class='order-item-field order-item-padding'>Logistic</div>
								<div class='order-item-field order-item-value order-item-confirm-by-date'>".$order->courierName."</div>
							</div>
							<div class='order-item-block'>
								<div class='order-item-field order-item-padding'>Tracking</div>
								<div class='order-item-field order-item-value order-item-confirm-by-date'>".$order->trackingID."</div>
							</div>";
		$buyer_details = 	"<div class='order-item-block-title buyer-details'>BUYER DETAILS</div>
							<div class='order-item-block'>
								<div class='result-style-address'>
									<div class='shipping-city-pincode-normal'>".$address->City.",<br />".$address->StateOrRegion."</div>
								</div>
							</div>";
	}

	$content = "
				<div class='order-content row'>
					<div class='col-md-1'>
						<div class='bookmark'><a class='flag".$flag_class."' data-itemid='".$order->orderId."' href='#'><i class='fa fa-bookmark'></i></a></div>
						<div class='ordered-product-image'>
							<img src='".IMAGE_URL."/uploads/products/".$order->thumb_image_url."' onerror=\"this.onerror=null;this.src='https://placehold.it/100x100';\" />
						</div>
					</div>
					<div class='col-md-11'>
						<div class='order-content-container'>
							<div class='ordered-product-name'>
							</div>
							<div class='order-approval-container'>
								<div class='ordered-approval'>
									Order Date: ". date("M d, Y H:i", strtotime($order->orderDate))."
								</div>
								<div class='ordered-status'>
									Paytm Status: <span class='bold-font ordered-status-value'>".$order->pt_status."</span>
								</div>
								".$shipment_status."
							</div>
							<div class='order-complete-details'>
								<div class='order-details'>
									<div class='order-item-block-title'>ORDER DETAIL</div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>Order ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellercentral.amazon.in/orders-v3/order/".$order->orderId."'>".$order->orderId."</a></div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://sellercentral.amazon.in/orders-v3/order/".$order->orderId."'>".$order->orderItemId."</a></div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>ASIN </div><div class='order-item-field order-item-value'><a target='_blank' href='".str_replace('ASIN', $order->asin, AZ_PRODUCT_URL)."'>".$order->asin."</a></div></div>
									<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>".$order->sku."</div></div>
								</div>
								<div class='order-price-qty order-price-qty-".$order->orderItemId."'>
									<div class='order-item-block-title'>PRICE &amp; QTY</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Quantity </div>
										<div class='order-item-field order-item-value '>".$qty."</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Value </div>
										<div class='order-item-field order-item-value '>&#8377; ".number_format($order->itemPrice + $order->itemTax, 2, '.', '')."</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Shipping </div>
										<div class='order-item-field order-item-value '>&#8377; ".number_format($order->shippingPrice + $order->shippingTax, 2, '.', '')."</div>
									</div>
									<div class='order-item-block'>
										<div class='order-item-field order-item-padding'>Total </div>
										<div class='order-item-field order-item-value'>&#8377; ".$order->orderTotal."</div>
									</div>
								</div>
								<div class='order-dispatch'>
									<div class='order-item-block-title'>SHIP</div>
									<div class='order-item-block'>
										<b>
											<div class='order-item-field order-item-padding'>By</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'>". date("h:i A, M d, Y", strtotime($order->shipByDate))."</div>
										</b>
									</div>
									".$pickup_details."
								</div>
								<div class='order-buyer-details'>".$buyer_details."
								</div>
							</div>
						</div>
					</div>
				</div>
			";

	$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_".$order->orderId."' data-account='".$order->account_id."' value='".$order->orderId."' />";
	$return['content'] = $content;
	$return['multi_items'] = ($order->isMultiItems ? "Yes" : "No") ;
	$return['account'] = $order->account_name;
	$return['payment_type'] = $order->paymentMethod == "Other" ? "Prepaid" : $order->paymentMethod;
	$return['order_date'] = date('M d, Y', strtotime($order->orderDate));
	$return['ship_date'] = date('M d, Y', strtotime($order->shipByDate));
	$return['action'] = "";
	return $return;
}

function update_orders_to_rtd($account_id, $tracking_id, $order_id = ""){
	global $db;

	$db->where('trackingID', $tracking_id);
	$orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS, NULL, 'status, orderId, account_id, title');
	foreach ($orders as $order) {
		if ($order->status == 'rtd' || $order->status == 'shipped' ){
			// $log->write('UPDATE_FK: Order already marked as '.$order->status.' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order already marked as '.ucwords($order->status);
			$return['type'] = "error";
			return $return;
		}

		// Other account order verification
		if ($order->account_id != $account_id){
			// $log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order of another account.';
			$return['type'] = "error";
			return $return;
		}

		// CANCELLED ORDER
		if ($order->pt_status == "CANCELED" || $order->status == "cancelled"){
			// $log->write('UPDATE_FK: Order marked cancelled with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			// $return = $this->update_order_to_cancel($trackingId, NULL, $order->quantity);
			$return['title'] = $order->PDF_set_info_title() .' <span class="label label-primary">'.$order->order_type.'</span>';
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = '<span class="label label-danger">Cancelled</span>';
			$return['type'] = "error";
			return $return;
		}

		$details = array( 'status' => 'rtd', 'rtdDate' => date('Y-m-d H:i:s') );
		$db->where('orderId', $order->orderId);
		if ($db->update(TBL_PT_ORDERS, $details)){
			// $log->write('OI: '.$order->orderItemId."\tRTD", 'order-status');
			// $log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId .' Response '.$rtd_return, 'amz-rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Successful';
			$return['type'] = "success";
		}
	}

	return $return;
}

function update_orders_to_shipped($account_id, $tracking_id, $order_id = ""){
	global $db;

	$db->where('trackingID', $tracking_id);
	$orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS, NULL, 'status, orderId, account_id, title');
	foreach ($orders as $order) {
		if ($order->status == "packing") {
			// $log->write('UPDATE: Order not yet HANOVERED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderItemId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order not HANOVERED!!!';
			$return['type'] = "error";
			return $return;
		}

		if ($order->status == 'shipped' ){
			// $log->write('UPDATE_FK: Order already marked as '.$order->status.' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order already marked as '.ucwords($order->status);
			$return['type'] = "error";
			return $return;
		}

		// Other account order verification
		if ($order->account_id != $account_id){
			// $log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Order of another account.';
			$return['type'] = "error";
			return $return;
		}

		// CANCELLED ORDER
		if ($order->pt_status == "CANCELED" || $order->status == "cancelled"){
			// $log->write('UPDATE_FK: Order marked cancelled with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			// $return = $this->update_order_to_cancel($trackingId, NULL, $order->quantity);
			$return['title'] = $order->PDF_set_info_title() .' <span class="label label-primary">'.$order->order_type.'</span>';
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = '<span class="label label-danger">Cancelled</span>';
			$return['type'] = "error";
			return $return;
		}

		$details = array( 'status' => 'shipped', 'shippedDate' => date('Y-m-d H:i:s') );
		$db->where('orderId', $order->orderId);
		if ($db->update(TBL_PT_ORDERS, $details)){
			// $log->write('OI: '.$order->orderItemId."\tRTD", 'order-status');
			// $log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId .' Response '.$rtd_return, 'amz-rtd-orders');
			$return['title'] = $order->title;
			$return['order_item_id'] = $order->orderId;
			$return['trackingId'] = $trackingId;
			$return['message'] = 'Successful';
			$return['type'] = "success";

			// @TODO: PAYMENT INTEGRATION
			// if ($this->payments)
			// 	$this->payments->update_fk_payout($order->orderItemId);
		}
	}

	return $return;
}

function update_order_inventory_status($order_id, $order_item_id, $type, $uid = NULL){
	global $db, $stockist;
	
	if (is_null($uids)){
		$db->where('orderItemId', $order_item_id);
		$db->where('orderId', $order_id);
		$uid = $db->getValue(TBL_PT_ORDERS, 'uid');
	}

	$uids = json_decode($uid, true);
	if (!empty($uids)){
		$update_status = array();
		foreach ($uids as $uid) {
			// CHANGE UID STATUS
			$inv_status = $stockist->update_inventory_status($uid, 'qc_pending');
			if ($type == "Canceled")
				$status = "Cancellation";
			// ADD TO LOG
			$log_status = $stockist->add_inventory_log($uid, 'sales_return', 'Paytm '.$status.' :: '.$order_id);
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

?>