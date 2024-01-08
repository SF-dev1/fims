<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

if (isset($_REQUEST['action'])){
	include_once(dirname(dirname(__FILE__)).'/config.php');
	// include_once(ROOT_PATH.'/includes/connectors/paytm/vendor/autoload.php');
	include_once(ROOT_PATH.'/paytm/functions.php');
	global $db, $accounts;
	
	$accounts = $accounts['paytm'];
	// 

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
	switch ($action) {
		
		case 'import_orders':
			
		break;

		case 'get_orders_count':
			$handler = $_GET['handler'];
			$order_type = array("pending", "new", "packing", "rtd", "shipped", "cancelled");
			$is_returns = false;
			if ($handler == "return"){
				$order_type = array("start", "in_transit", "out_for_delivery", "delivered", "return_received", "return_claimed", "return_completed", "return_unexpected");
				$is_returns = true;
			}
			$orders_count = view_orders_count($order_type, $is_returns);
			echo json_encode(array('orders' => $orders_count));
		break;

		case 'view_orders':
			$type = $_GET['type'];
			$is_returns = false;
			$orders = view_orders($type);

			$output = array('data' => array());
			foreach ($orders as $order) {
				$output['data'][] = create_order_view($order, $type);
			}
			echo json_encode($output);
		break;

		case 'mark_orders':
			switch ($_POST['type']) {
				case 'rtd':
					$account_id = $_POST['account_id'];
					$tracking_id = $_POST['tracking_id'];
					$response = update_orders_to_rtd($account_id, $tracking_id);

					$return['type'] = "error";
					if ($response && $response['type'] == "success"){
						$return['type'] = "success";
					}
					$title = ($response && $response["title"] != "" ? $response["title"] : "Title Not Found");
					$order_item_id = ($response && $response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
					$message = ($response && $response["order_item_id"] != "" ? $response["message"] : "Not Found");
					$return['content'] = '
						<li class="order-item-card">
							<div class="order-title">'.$title.'</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">'.$order_item_id.'</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">'.$tracking_id.'</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">'.$message.'</span></div>
						</li>';

					echo json_encode($return);
					break;

				case 'shipped':
					$account_id = $_POST['account_id'];
					$tracking_id = $_POST['tracking_id'];
					$response = update_orders_to_shipped($account_id, $tracking_id);

					$return['type'] = "error";
					if ($response && $response['type'] == "success"){
						$return['type'] = "success";
					}
					$title = ($response && $response["title"] != "" ? $response["title"] : "Title Not Found");
					$order_item_id = ($response && $response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
					$message = ($response && $response["order_item_id"] != "" ? $response["message"] : "Not Found");
					$return['content'] = '
						<li class="order-item-card">
							<div class="order-title">'.$title.'</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">'.$order_item_id.'</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">'.$tracking_id.'</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">'.$message.'</span></div>
						</li>';

					echo json_encode($return);
					break;
			}
		break;

		// SCAN & SHIP
		case 'scan_ship':
			$tracking_id = $_GET['tracking_id'];

			$db->join(TBL_PRODUCTS_ALIAS ." p", "p.mp_id=o.asin", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id=o.account_id");
			$db->where('o.trackingID', $tracking_id);
			$db->where('o.status', "RTD");
			$orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS .' o', null, "o.orderId, o.account_id, o.quantity, o.uid, o.sku, p.corrected_sku, p.alias_id");
			if ($orders){
				$return = array();
				$content = "";
				foreach ($orders as $order) {
					$db->where('marketplace', 'paytm');
					$db->where('account_id', $order->account_id);
					$db->where('alias_id', $order->alias_id);
					$products = $db->getOne(TBL_PRODUCTS_ALIAS, array('pid, cid'));
					if ($products['cid']){
						$db->where('cid', $products['cid']);
						$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
						$products = json_decode($products['pid']);
						foreach ($products as $product) {
							$uids = json_decode($order->uid, true);
							$db->where('pid', $product);
							$item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
							$item['orderItemId'] = $order->orderItemId;
							$item['uids'] = [];//is_null($uids) ? [] : $uids;
							$item['quantity'] = $order->quantity;
							$item['scanned_qty'] = count($uids);
							$item['scan'] = ($order->quantity == count($uids)) ? true : false;
							$items[] = $item;
							$corrected_sku = "";
							if (!is_null($order->corrected_sku)){
								$corrected_sku = '<div class="form-group item-info corrected_sku">
													<div class="col-md-3">Corrected SKU:</div>
													<div class="col-md-9 sku">'.$order->corrected_sku.'</div>
												</div>';
							}
							$all_uids = "";
							if (!is_null($uids)){
								$all_uids = implode(', ', $uids);
							}
							$all_success = "hide";
							if ($order->quantity == count($uids))
								$all_success = "";

							$content .= '<div class="item_group item_'.$item["item_id"].'" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="'.IMAGE_URL.'/uploads/products/product-'.$item["item_id"].'.jpg"/>
													</div>
												</div>
												<div class="col-md-10">
													<div class="form-group item-info parent_sku">
														<div class="col-md-3">Parent SKU:</div>
														<div class="col-md-9 sku">'.$item["parent_sku"].'</div>
													</div>
													<div class="form-group item-info original_sku">
														<div class="col-md-3">SKU:</div>
														<div class="col-md-9 sku">'.$order->sku.'</div>
													</div>
													'.$corrected_sku.'
													<div class="form-group item-info qty">
														<div class="col-md-3">Quantity:</div>
														<div class="col-md-9">'.$order->quantity.'</div>
													</div>
													<div class="form-group item-info uids hide">
														<div class="col-md-3">UID:</div>
														<div class="col-md-8 uid">'.$all_uids.'</div>
														<div class="col-md-1 scanned text-right '.$all_success.'"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
													</div>
												</div>
											</div>
										</div>';
						}
					} else {
						$uids = json_decode($order->uid, true);
						$uid_count = is_null($uids) ? 0 : count($uids);
						$db->where('pid', $products['pid']);
						$item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
						$item['orderId'] = $order->orderId;
						$item['uids'] = array();//is_null($uids) ? [] : $uids;
						$item['quantity'] = $order->quantity;
						$item['scanned_qty'] = $uid_count;
						$item['scan'] = ($order->quantity == $uid_count) ? true : false;
						$items[] = $item;
						$corrected_sku = "";
						if (!is_null($order->corrected_sku)){
							$corrected_sku = '<div class="form-group item-info corrected_sku">
												<div class="col-md-3">Corrected SKU:</div>
												<div class="col-md-9 sku">'.$order->corrected_sku.'</div>
											</div>';
						}
						$all_uids = "";
						if (!is_null($uids)){
							$all_uids = implode(', ', $uids);
						}
						$all_success = "hide";
						if ($order->quantity == $uid_count)
							$all_success = "";

						$content .= '<div class="item_group item_'.$item["item_id"].'" data-scanned="false">
										<div class="form-group">
											<div class="col-md-2">
												<div class="product_image">
													<img src="'.IMAGE_URL.'/uploads/products/product-'.$item["item_id"].'.jpg"/>
												</div>
											</div>
											<div class="col-md-10">
												<div class="form-group item-info parent_sku">
													<div class="col-md-3">Parent SKU:</div>
													<div class="col-md-9 sku">'.$item["parent_sku"].'</div>
												</div>
												<div class="form-group item-info original_sku">
													<div class="col-md-3">SKU:</div>
													<div class="col-md-9 sku">'.$order->sku.'</div>
												</div>
												'.$corrected_sku.'
												<div class="form-group item-info qty">
													<div class="col-md-3">Quantity:</div>
													<div class="col-md-9">'.($order->quantity > 1 ? "<sapn class='label label-danger'>".$order->quantity."</span>" : $order->quantity).'</div>
												</div>
												<div class="form-group item-info uids hide">
													<div class="col-md-3">UID:</div>
													<div class="col-md-8 uid">'.$all_uids.'</div>
													<div class="col-md-1 scanned text-right '.$all_success.'"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
												</div>
											</div>
										</div>
									</div>';
					}
				}
				$response = array("type" => "success", "items" => $items, "content" => preg_replace('/\>\s+\</m', '><', $content));
			} else {
				$db->where('trackingID', $tracking_id);
				$order = $db->ObjectBuilder()->getOne(TBL_PT_ORDERS, "status, az_status");
				if ($order){
					if ($order->az_status == "RETURN_REQUESTED" || $order->az_status == "RETURNED" || $order->az_status == "CANCELLED" || $order->status == "CANCELLED"){
						$response = array("type" => "info", "msg" => "Order Cancelled<br />DO NOT PROCESS");
					} else {
						$response = array("type" => "error", "msg" => "Order status is ".strtoupper($order->status));
					}
				} else {
					$response = array("type" => "error", "msg" => "Invalid Tracking ID");
				}
			}
			echo json_encode($response);
		break;

		case 'get_uid_details':
			$uid = $_GET['uid'];
			$db->where('inv_status', 'qc_verified');
			$db->where('inv_id', $uid);
			$item_id = $db->getValue(TBL_INVENTORY, 'item_id');
			if ($item_id)
				echo json_encode(array('type' => 'success', 'item_id' => $item_id));
			else {
				$db->where('inv_id', $uid);
				$status = $db->getValue(TBL_INVENTORY, 'inv_status');
				echo json_encode(array('type' => 'error', 'msg' => 'Product status is '.strtoupper($status)));
			}
		break;

		case 'save_scan_ship':
			$scanned_orders = json_decode($_REQUEST['scanned_items']);
			$update_status = array();
			foreach ($scanned_orders as $orderId => $uids) {
				$db->where('orderId', $orderId);
				$order_detail = array(
					'uid' => json_encode($uids),
					'status' => 'shipped',
					'shippedDate' => date('Y-m-d H:i:s'),
				);
				if ($db->update(TBL_PT_ORDERS, $order_detail)) {
					foreach ($uids as $uid) {
						// CHANGE UID STATUS
						$inv_status = $stockist->update_inventory_status($uid, 'sales');
						// ADD TO LOG
						$log_status = $stockist->add_inventory_log($uid, 'sales', 'Paytm Sales :: '.$orderId);
						if ($inv_status && $log_status)
							$update_status[$orderId] = true;
						else
							$update_status[$orderId] = false;
					}
				} else {
					$update_status[$orderId] = false;
				}
			}

			if (in_array(false, $update_status))
				$return = array("type" => "error", "msg" => "Error Updating order", "error" => $update_status, "error_msg" => $db->getLastError());
			else 	
				$return = array("type" => "success", "msg" => "Order successfull shipped");
			
			echo json_encode($return);
		break;

		// SEARCH ORDER
		case 'search_order':
			$search_key = $_REQUEST['key'];
			$search_by = $_REQUEST['by'];

			// $db->join(TBL_FK_RETURNS ." r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->joinWhere('r.r_expectedDate', NULL, 'IS NOT'); // Delivery not expected. Promised qty not delivered/
			// $db->join(TBL_FK_PAYMENTS ." pa", "pa.orderItemId=o.orderItemId", "LEFT");
			// $db->join(TBL_FK_CLAIMS." c", "r.returnId=c.returnId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS ." p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id=o.account_id");
			$db->join(TBL_PRODUCTS_MASTER ." pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO ." pc", "pc.cid=p.cid", "LEFT");

			if ($search_by == "uid"){
				$db->where('o.uid', '%'.$search_key.'%', 'LIKE');
			} else if ($search_by == 'trackingId'){
				$db->where('o.pickupDetails', '%'.$search_key.'%', 'LIKE');
				$db->orWhere('o.deliveryDetails', '%'.$search_key.'%', 'LIKE');
				$db->orWhere('r.r_trackingId', '%'.$search_key.'%', 'LIKE');
			} else if ($search_by == 'returnId'){
				$db->where('r.'.$search_by, $search_key);
			} else if ($search_by == 'claimID'){
				$db->where('c.'.$search_by, '%'.$search_key.'%', 'LIKE');
				$db->orWhere('c.claimNotes', '%'.$search_key.'%', 'LIKE');
				$db->orWhere('o.settlementNotes', '%'.$search_key.'%', 'LIKE');
			} else {
				$db->where('o.'.$search_by, $search_key);
			}

			$db->orderBy('o.insertDate', "DESC");
			$db->orderBy('r.insertDate', "DESC");
			// $db->groupBy('OID');
			$orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS .' o', null, "o.*, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, COALESCE(p.corrected_sku, p.sku) as sku");
			// $orders = $db->ObjectBuilder()->get(TBL_PT_ORDERS .' o', null, "o.orderItemId as OID, r.returnId as RID, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, o.*, r.*, c.*, COALESCE(p.corrected_sku, p.sku) as sku");
			// echo $db->getLastQuery();
			// exit;
			// var_dump($orders);

			$return['type'] = "success";
			$return['content'] = "";
			$collapse_id = 1;
			foreach ($orders as $order) {
				// $order_type = $order->replacementOrder ? 'Replacement Order' : strtoupper($order->paymentType);
				$address = json_decode($order->deliveryAddress);
				$pickupDetails = json_decode($order->pickupDetails);
				$deliveryDetails = json_decode($order->deliveryDetails);
				$on_hold = ($order->hold ? '<i class="fas fa-hand-paper"></i>' : '');
				$flag_class = ($order->is_flagged == true ? ' active' : '');

				// var_dump($accounts);

				$account = findObjectById($accounts, 'account_id', $order->account_id);

				if(strpos($deliveryDetails->vendorName, 'E-Kart') !== FALSE)
					$delivery_link = 'https://www.ekartlogistics.com/track/'.$deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Delhivery') !== FALSE)
					$delivery_link = 'https://www.delhivery.com/track/package/'.$deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Ecom') !== FALSE)
					$delivery_link = 'https://ecomexpress.in/tracking/?awb_field='.$deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
					$delivery_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos='.$deliveryDetails->trackingId;

				if (strtolower($order->status) != "new"){
					$logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">'.$deliveryDetails->vendorName.'</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="'.$delivery_link.'" target="_blank">'.$deliveryDetails->trackingId.'</a></span>
																	</div>';

					if ($pickupDetails->trackingId != $deliveryDetails->trackingId){
						if(strpos($pickupDetails->vendorName, 'E-Kart') !== FALSE)
							$pickup_link = 'https://www.ekartlogistics.com/track/'.$pickupDetails->trackingId;
						elseif (strpos($pickupDetails->vendorName, 'Delhivery') !== FALSE)
							$pickup_link = 'https://www.delhivery.com/track/package/'.$pickupDetails->trackingId;
						elseif (strpos($pickupDetails->vendorName, 'Ecom') !== FALSE)
							$pickup_link = 'https://ecomexpress.in/tracking/?awb_field='.$pickupDetails->trackingId;
						elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
							$pickup_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos='.$pickupDetails->trackingId;

						$logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Pickup Logistic Partner:&nbsp;</span>
																		<span class="order_date">'.$pickupDetails->vendorName.'</span>
																		<span class="card-label">Pickup Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="'.$pickup_link.'" target="_blank">'.$pickupDetails->trackingId.'</a></span>
																	</div>
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">'.$deliveryDetails->vendorName.'</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="'.$delivery_link.'" target="_blank">'.$deliveryDetails->trackingId.'</a></span>
																	</div>';
					}

					$delivery_details = '<div class="col-md-12 sub-header">
																		<span class="card-label">Delivery Details:&nbsp;</span>
																		<span class="order_date">'.$address->firstName.' '.$address->lastName.', '.$address->city.', '.$address->state.'</span>
																		<span class="card-label">Contact No.:&nbsp;</span>
																		<span class="order_date">+91'.$address->contactNumber.'</span>
																	</div>';
				}
				$order_returns = '<dt class="font-rlight font-rgrey">No Return Details Found</dt>';
				if($order->RID != NULL){
					$order_returns = '
																	<dl><dt class="font-rlight font-rgrey">Return Status</dt>
																	<dd class="r_status">'.($order->r_shipmentStatus == "" ? "NA" : strtoupper($order->r_shipmentStatus)).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Action</dt>
																	<dd class="r_sub_reason">'.($order->r_action == "" ? "NA" : strtoupper($order->r_action)).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Tracking ID</dt>
																	<dd class="r_sub_reason">'.($order->r_trackingId == "" ? "NA" : $order->r_trackingId).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return ID</dt>
																	<dd class="r_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/return_orders/'.$account->locationId.'/search?type=return_id&id='.$order->RID.'">'.$order->RID.'</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Created Date</dt>
																	<dd class="r_sub_reason">'.($order->r_createdDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_createdDate))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Delivered Date</dt>
																	<dd class="r_sub_reason">'.($order->r_deliveredDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_deliveredDate))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Received Date</dt>
																	<dd class="r_sub_reason">'.($order->r_receivedDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_receivedDate))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Completed Date</dt>
																	<dd class="r_sub_reason">'.($order->r_completionDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_completionDate))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Type</dt>
																	<dd class="r_status">'.$order->r_source.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="r_reason">'.$order->r_reason.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="r_sub_reason">'.$order->r_subReason.'</dd></dl>
																	<dl><dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="r_comment">'.str_replace('\n', "<br />", $order->r_comments).'</dd></dl>';
				}

				$order_claims = '<dt class="font-rlight font-rgrey">No Claim Details Found</dt>';
				$claim_status = "";
				$approved_claim = "";
				if (!is_null($order->r_approvedSPFId)){
					$claim_status = "_INCOMPLETE_SPF";
					$approved_claim = '<dl><dt class="font-rlight font-rgrey">Approved Claim ID</dt><dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/'.$order->r_approvedSPFId.'/SELLER_SELF_SERVE/">'.$order->r_approvedSPFId.'</a></dd></dl>';
				}
				if(isset($order->claimID) && $order->claimID != NULL){
					$order_claims = '
																	<dl><dt class="font-rlight font-rgrey">Claim Status</dt>
																	<dd class="c_status">'.strtoupper($order->claimStatus).$claim_status.'</dd></dl>
																	'.$approved_claim.'
																	<dl><dt class="font-rlight font-rgrey">Claim ID</dt>
																	<dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/'.$order->claimID.'/SELLER_SELF_SERVE/">'.$order->claimID.'</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Date</dt>
																	<dd class="c_date">'.($order->claimDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->claimDate))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Updated</dt>
																	<dd class="c_date">'.($order->lastUpdateAt == "" ? "NA" : date("M d, Y h:i A", strtotime($order->lastUpdateAt))).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reason</dt>
																	<dd class="c_reason">'.$order->claimProductCondition.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reimbursement</dt>
																	<dd class="c_reimbursement">&#x20b9; '.(($order->claimReimbursmentAmount == "" || is_null($order->claimReimbursmentAmount)) ? "0.00" : $order->claimReimbursmentAmount).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Comments</dt>
																	<dd class="c_comments">'.($order->claimComments == "" ? "NA" : $order->claimComments).'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Notes</dt>
																	<dd class="c_comments">'.($order->claimNotes == "" ? "NA" : str_replace("\n", "<br />", $order->claimNotes)).'</dd></dl>';
				}

				$fk = new connector_flipkart($account);
				$payment_details = $fk->payments->get_difference_details($order->OID);
				$settlement_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['expected_payout'] as $key => $value) {
					if ($key == "settlement_date")
						continue;

					if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
						$settlement_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p>'. strtoupper($value).'</span>';
					else 
						$settlement_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p> &#x20b9;'. $value.'</span>';
				}
				$received_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['settlements'] as $settlement) {
					foreach ($settlement as $key => $value) {
						// if ($key == "settlement_date")
						// 	continue;
						
						if ($key == "settlement_date" || $key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
							$received_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p>'. strtoupper($value).'</span>';
						else 
							$received_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p> &#x20b9;'. $value.'</span>';
					}
				}
				$difference_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['difference'] as $key => $value) {
					if ($key == "settlement_date")
						continue;

					if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
						$difference_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p>'. strtoupper($value).'</span>';
					else 
						$difference_details .= '<span><p>'.ucwords(str_replace('_', ' ', $key)) .'</p> &#x20b9;'. $value.'</span>';
				}
				$order_payments = '
																	<dl><dt class="font-rlight font-rgrey">Payment Status</dt>
																	<dd class="p_status">'.($order->settlementStatus == 0 ? "PENDING" : "SETTLED").'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Expected Payout</dt>
																	<dd class="p_settlement">&#x20b9; '.$fk->payments->calculate_actual_payout($order->OID).' <a data-target="#p_settlement_details_'.$collapse_id.'" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_settlement_details_'.$collapse_id.'" class="accounts_details collapse">'.$settlement_details.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Amount Settled</dt>
																	<dd class="p_received">&#x20b9; '.$fk->payments->calculate_net_settlement($order->OID).' <a data-target="#p_received_details_'.$collapse_id.'" data-toggle="collapse">(View Details)</a></dd><!-- SHOW DROPDWON BREAKUP -->
																	<dd id="p_received_details_'.$collapse_id.'" class="accounts_details collapse">'.$received_details.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Difference</dt>
																	<dd class="p_difference">&#x20b9; '.$payment_details['difference']['total'].' <a data-target="#p_difference_details_'.$collapse_id.'" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_difference_details_'.$collapse_id.'" class="accounts_details collapse">'.$difference_details.'</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Settlement Notes</dt>
																	<dd class="p_notes">'.(empty($order->settlementNotes) ? "NA" : $order->settlementNotes).'</dd></dl>';



				$return['content'] .= '
													<article class="return-order-card clearfix detail-view">
														<div class="col-md-12 order-details-header">
															<div class="col-md-2 product-image"><div class="bookmark"><a class="flag'.$flag_class.'" data-itemid="'.$order->OID.'" href="#"><i class="fa fa-bookmark"></i></a></div><img src="'.IMAGE_URL.'/uploads/products/'.$order->produtImage.'" onerror="this.src=\'https://placehold.it/150x150\'"></div>
															<div class="col-md-8 order-details">
																<header class="col-md-7 return-card-header font-rlight">
																	<div>
																		<h4>
																			<div class="article-title font-rblue font-rreguler">'.$order->title.'&nbsp;&nbsp;<span class="label label-success">'.$account->account_name.'</span></div>
																		</h4>
																	</div>
																</header>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Shipment ID:&nbsp;</span>
																	<span class="order_id">'.$order->shipmentId.'</span>
																	<span class="card-label">Rating URL:&nbsp;</span>
																	<input id="p2" class="cpy_text" alt="Click to Copy Rating URL" value="https://www.flipkart.com/products/write-review/itme?pid='.$order->fsn.'&lid='.$order->listingId.'&source=od_sum&otracker=orders" />
																	<span class="order_id rating_url"><button title="Click to Copy Rating URL" class="cpy_btn btn btn-xs btn-default" data-clipboard-target="#p2"><i class="fa fa-copy"></i></button></span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">SKU:&nbsp;</span>
																	<span class="order_id">'.$order->sku.'</span>
																	<span class="card-label">FSN:&nbsp;</span>
																	<span class="order_item_id"><a class="font-rblue" href="https://www.flipkart.com/product/p/itme?pid='.$order->fsn.'" target="_blank">'.$order->fsn.'</a></span>
																	<span class="card-label">HSN:&nbsp;</span>
																	<span class="order_type">'.$order->hsn.'</span>
																	<span class="card-label">UID:&nbsp;</span>
																	<span class="order_type">'.(is_null($order->uid) ? "NA" : $order->uid).'</span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Quantity:&nbsp;</span>
																	<span class="quantity">'.$order->quantity.'/'.$order->ordered_quantity.'</span>
																	<span class="card-label">Price:&nbsp;</span>
																	<span class="sellingPrice">'.$order->sellingPrice.'</span>
																	<span class="card-label">Shipping:&nbsp;</span>
																	<span class="shippingCharge">'.$order->shippingCharge.'</span>
																	<span class="card-label">Total:&nbsp;</span>
																	<span class="totalPrice">'.$order->totalPrice.'</span>
																	<span class="card-label">Fulfilment Type:&nbsp;</span>
																	<span class="order_type">'.$order->order_type.'</span>
																</div>
																'.$logistic_details.'
																'.$delivery_details.'
															</div>
															<div class="col-md-2 order-incidents">
																<dl class="status">
																	<dd class="font-rlight font-rgrey">Order Incidents:&nbsp;</dd>
																	<dd class="amount">'.strtoupper($order->status).' '.$on_hold.'</dd>
																</dl>
															</div>
														</div>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-3 order_details">
																<dl class="status">
																	<dt class="font-rlight font-rgrey">Order Status:&nbsp;</dt>
																	<dd class="amount">'.strtoupper($order->status).' '.$on_hold.'</dd>
																</dl>
																<dl class="orderId">
																	<dt class="font-rlight font-rgrey">Order ID:&nbsp;</dt>
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/'.$account->locationId.'/main/search?type=order_id&id='.$order->orderId.'">'.$order->orderId.'</a></dd>
																</dl>
																<dl class="orderItemId">
																	<dt class="font-rlight font-rgrey">Order Item ID:&nbsp;</dt>
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/'.$account->locationId.'/main/search?type=order_item_id&id='.$order->OID.'">'.$order->OID.'</a></dd>
																</dl>
																<dl class="price">
																	<dt class="font-rlight font-rgrey">Order Type:&nbsp;</dt>
																	<dd class="amount">'.$order_type.'</dd>
																</dl>
																<dl class="quantity">
																	<dt class="font-rlight font-rgrey">Order Date:&nbsp;</dt>
																	<dd class="qty">'.date("M d, Y h:i A", strtotime($order->orderDate)).'</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Label Generated:&nbsp;</dt>
																	<dd class="amount">'.( $order->labelGeneratedDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->labelGeneratedDate)) ).'</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">RTD Date:&nbsp;</dt>
																	<dd class="amount">'.( $order->rtdDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->rtdDate)) ).'</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Shipped Date:&nbsp;</dt>
																	<dd class="amount">'.( $order->shippedDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->shippedDate)) ).'</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Date:&nbsp;</dt>
																	<dd class="amount">'.( $order->invoiceDate == "0000-00-00 00:00:00" ? "Not Generated" : date("M d, Y h:i A", strtotime($order->invoiceDate)) ).'</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Number:&nbsp;</dt>
																	<dd class="amount">'.($order->invoiceNumber == "" ? "Not Generated" : $order->invoiceNumber).'</dd>
																</dl>
															</div>
															<div class="col-md-3 order_returns">
																'.$order_returns.'
															</div>
															<div class="col-md-3 order_claims">
																'.$order_claims.'
															</div>
															<div class="col-md-3 order_payments">
																'.$order_payments.'
															</div>
														</section>
													</article>';
				
				$collapse_id++;
			}

			echo json_encode($return);
		break;
	}
} else {
	exit ('Invalid request!');
}

?>