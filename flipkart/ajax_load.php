<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart-dashboard.php');
	global $db, $accounts, $sms, $currentUser;

	$accounts = $accounts['flipkart'];

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
	$handler = isset($_REQUEST['handler']) ? $_REQUEST['handler'] : "order";

	switch ($action) {
		case 'get_orders':

			$order_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";

			if ($order_type == "")
				return;

			switch (strtolower($order_type)) {
				case 'new':
				case 'upcoming':
					// foreach ($accounts as $account) {
					$fk = new connector_flipkart($accounts[0]);
					$orders = $fk->view_orders($order_type);
					if ($orders) {
						$orders_count = count($orders);
						$return = "";
						$i = 0;

						foreach ($orders as $sub_order) {
							$multi_pro = (count($sub_order) > 1) ? true : false;
							$return .= "<tr class='gradeX'>";
							$order_item_ids = array();
							$mul_pro = ($multi_pro) ? "<span class='label label-info'>Multiple Products</span>" : "";

							$content = "";
							foreach ($sub_order as $order) {
								// $order_item_ids[] = $order->orderItemId;
								$order_item_ids[] = $order->shipmentId;
								$thumb_image_url = ($order->pti != NULL ? $order->pti : $order->cti);
								$flag_class = ($order->is_flagged == true ? ' active' : '');

								$address = json_decode($order->deliveryAddress);
								$express = ($order->dispatchServiceTier == "EXPRESS" ? "<span class='label label-default'>" . $order->dispatchServiceTier . "</span>" : "");
								$payment_type = "<span class='label label-info'>" . strtoupper($order->paymentType) . "</span>";
								$qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-warning'>" . $order->quantity . " units</span>";
								$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement Order</span>" : "";
								$sla = "";
								if ((strtotime($order->dispatchByDate) - time() < 3600) && (strtotime($order->dispatchByDate) - time() > 0)) {
									$sla = "<span class='label label-danger'>SLA Breaching Soon!</span>";
								} else if (strtotime($order->dispatchByDate) - time() < 0) {
									$sla = "<span class='label label-danger'>SLA Breached!</span>";
								}

								if ($order->hold) {
									$sla = "<span class='label label-warning'>ON HOLD <i class='fas fa-hand-paper'></i></span>";
								}

								$fulfilment_type = "";
								if ($order->order_type == "FBF_LITE")
									$fulfilment_type = "<span class='label label-info'>FBF LITE</span>";

								$content .= "
										<div class='order-content'>
											<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderItemId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
											<div class='ordered-product-image'>
												<img src='" . IMAGE_URL . "/uploads/products/" . $thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
											</div>
											<div class='order-content-container'>
												<div class='ordered-product-name'>
													<a target='_blank' href='" . str_replace('FSN', $order->fsn, FK_PRODUCT_URL) . "'>" . $order->title . "</a>" . $sla . " " . $express . " " . $payment_type . " <span class='label label-success'>" . $order->account_name . "</span> " . $fulfilment_type . " " . $replacement_order . "
												</div>
												<div class='order-approval-container'>
													<div class='ordered-approval'>
														Order Date: " . date("M d, Y H:i", strtotime($order->orderDate)) . "
													</div>
													<div class='ordered-status'>
														Flipkart Status: <span class='bold-font ordered-status-value'>" . $order->fk_status . "</span>
													</div>
												</div>
												<div class='order-complete-details'>
													<div class='order-details'>
														<div class='order-item-block-title'>ORDER DETAIL</div>
														<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/" . $order->locationId . "/main/search?type=order_item_id&id=" . $order->orderItemId . "'>" . $order->orderItemId . "</a></div></div>
														<div class='order-item-block'><div class='order-item-field order-item-padding'>ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/" . $order->locationId . "/main/search?type=order_id&id=" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
														<div class='order-item-block'><div class='order-item-field order-item-padding'>FSN </div><div class='order-item-field order-item-value'>" . $order->fsn . "</div></div>
														<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>" . $order->sku . "</div></div>
													</div>
													<div class='order-price-qty order-price-qty-" . $order->orderItemId . "'>
														<div class='order-item-block-title'>PRICE &amp; QTY</div>
														<div class='order-item-block'>
															<div class='order-item-field order-item-padding'>Quantity </div>
															<div class='order-item-field order-item-value '>" . $qty . "</div>
														</div>
														<div class='order-item-block'>
															<div class='order-item-field order-item-padding'>Value </div>
															<div class='order-item-field order-item-value '>Rs. " . $order->sellingPrice . "</div>
														</div>
														<div class='order-item-block'>
															<div class='order-item-field order-item-padding'>Shipping </div>
															<div class='order-item-field order-item-value '>Rs. " . $order->shippingCharge . "</div>
														</div>
														<div class='order-item-block'>
															<div class='order-item-field order-item-padding'>Total </div>
															<div class='order-item-field order-item-value'>Rs. " . $order->totalPrice . "</div>
														</div>
													</div>
													<div class='order-dispatch'>
														<div class='order-item-block-title'>DISPATCH</div>
														<div class='order-item-block'>
															<div class='order-item-field order-item-padding'>After</div>
															<div class='order-item-field order-item-value'>" . date("h:i A, M d, Y", strtotime($order->dispatchAfterDate)) . "</div>
														</div>
														<div class='order-item-block'>
															<b>
																<div class='order-item-field order-item-padding'>By</div>
																<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchByDate)) . "</div>
															</b>
														</div>
													</div>
													<div class='order-buyer-details'>
													</div>
												</div>
											</div>
										</div>
									";
							}

							$checkbox = "<td><input type='checkbox' class='checkboxes' name='orderItemId' value='" . implode(',', $order_item_ids) . "' /></td><td>";
							$return .= $checkbox . $content;
							$return .= "</td><td class='return_hide_column'><span class='label label-warning'>" . $order->status . "</span><br />" . $mul_pro . "</td><td style='display:none;'>" . $order->account_name . "</td><td style='display:none;'>" . $order->order_type . "</td>";
							$return .= "</tr>";

							$i++;
						}
						echo preg_replace('/\s+/S', " ", $return);
					}
					// }
					break;

				case 'packing':
				case 'rtd':
				case 'shipped':
				case 'cancelled':
					// foreach ($accounts as $account) {
					$fk = new connector_flipkart($accounts[0]);
					$orders = $fk->view_orders($order_type);
					if ($orders) {
						$return = "";
						$i = 0;

						foreach ($orders as $sub_order) {
							$multi_pro = (count($sub_order) > 1) ? true : false;
							$return .= "<tr class='gradeX'>";
							$order_item_ids = array();
							$mul_pro = ($multi_pro) ? "<span class='label label-info'>Multiple Products</span>" : "";

							$content = "";
							foreach ($sub_order as $order) {
								// $order_item_ids[] = $order->orderItemId;
								$order_item_ids[] = $order->shipmentId;
								$pickup = json_decode($order->pickupDetails);
								$pickup_div = "";
								if ($pickup->trackingId != $order->forwardTrackingId) {
									$pickup_div = "
										<div class='order-item-block'>
											<div class='order-item-field order-item-padding'>Pickup Tracking</div>
											<div class='order-item-field order-item-value order-item-confirm-by-date'><a target='_blank' href='https://www.ekartlogistics.com/shipmenttrack/" . $pickup->trackingId . "'>" . $pickup->trackingId . "</a></div>
										</div>";
								}

								$tracking_link = "<a target='_blank' href='https://www.ekartlogistics.com/shipmenttrack/" . $order->forwardTrackingId . "'>" . $order->forwardTrackingId . "</a>";
								if (strrpos($pickup->vendorName, "Ecom") !== FALSE)
									$tracking_link = "<a target='_blank' href='https://ecomexpress.in/tracking/?awb_field=" . $order->forwardTrackingId . "'>" . $order->forwardTrackingId . "</a>";

								$thumb_image_url = ($order->pti != NULL ? $order->pti : $order->cti);
								$flag_class = ($order->is_flagged == true ? ' active' : '');

								$label_status = "";
								if ($order_type == "packing") {
									$filename = ROOT_PATH . '/labels/single/FullLabel-' . $order->shipmentId . '.pdf';
									$is_valid_pdf = check_valid_pdf($filename);
									if (!$is_valid_pdf) {
										$label_status = "<div class='label-status'>
													Label: <button class='btn tooltips redownload_label' data-shipmentid=" . $order->shipmentId . " data-placement='right' data-original-title='Redownload Label'><i class='fa fa-xs fa-redo-alt'></i></button>
												</div>";
									}
								}

								$address = json_decode($order->deliveryAddress);
								$express = ($order->dispatchServiceTier == "EXPRESS" ? "<span class='label label-default'>" . $order->dispatchServiceTier . "</span>" : "");
								// $account_name = ($order->account_id == 1 ? "<span class='label label-info'>BabyNSmile</span>" : "<span class='label label-success'>Sylvi</span>");
								// $account = ($order->account_id == 1 ? "BabyNSmile" : "Sylvi");
								$payment_type = "<span class='label label-info'>" . strtoupper($order->paymentType) . "</span>";
								$replacement_order = ($order->replacementOrder) ? "<span class='label label-danger'>Replacement Order</span>" : "";
								$qty = ($order->quantity == 1) ? $order->quantity . " unit" : "<span class='label label-danger'>" . $order->quantity . " units</span>";
								$sla = "";
								if ($order_type == 'packing') {
									if ((strtotime($order->dispatchByDate) - time() < 3600) && (strtotime($order->dispatchByDate) - time() > 0)) {
										$sla = "<span class='label label-danger'><i>SLA Breaching Soon!</i></span>";
									} else if (strtotime($order->dispatchByDate) - time() < 0) {
										$sla = "<span class='label label-danger'><i>SLA Breached!</i></span>";
									}
								}

								$fulfilment_type = "";
								if ($order->order_type == "FBF_LITE")
									$fulfilment_type = "<span class='label label-info'>FBF LITE</span>";

								$content .= "
									<div class='order-content'>
										<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderItemId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
										<div class='ordered-product-image'>
											<img src='" . IMAGE_URL . "/uploads/products/" . $thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
										</div>
										<div class='order-content-container'>
											<div class='ordered-product-name'>
												<a target='_blank' href='" . str_replace('FSN', $order->fsn, FK_PRODUCT_URL) . "'>" . $order->title . "</a>" . $sla . " " . $express . " " . $payment_type . " <span class='label label-success'>" . $order->account_name . "</span> " . $fulfilment_type . " " . $replacement_order . "
											</div>
											<div class='order-approval-container'>
												<div class='ordered-approval'>
													Order Date: " . date("M d, Y", strtotime($order->orderDate)) . "
												</div>
												<div class='ordered-status'>
													Flipkart Status: <span class='bold-font ordered-status-value'>" . $order->fk_status . "</span>
												</div>
												" . $label_status . "
											</div>
											<div class='order-complete-details'>
												<div class='order-details'>
													<div class='order-item-block-title'>ORDER DETAIL</div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/" . $order->locationId . "/main/search?type=order_item_id&id=" . $order->orderItemId . "'>" . $order->orderItemId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/" . $order->locationId . "/main/search?type=order_id&id=" . $order->orderId . "'>" . $order->orderId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>FSN </div><div class='order-item-field order-item-value'>" . $order->fsn . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value'>" . $order->sku . "</div></div>
												</div>
												<div class='order-price-qty order-price-qty-" . $order->orderItemId . "'>
													<div class='order-item-block-title'>PRICE &amp; QTY</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Quantity </div>
														<div class='order-item-field order-item-value '>" . $qty . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Value </div>
														<div class='order-item-field order-item-value '>Rs. " . $order->sellingPrice . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Shipping </div>
														<div class='order-item-field order-item-value '>Rs. " . $order->shippingCharge . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Total </div>
														<div class='order-item-field order-item-value'>Rs. " . $order->totalPrice . "</div>
													</div>
												</div>
												<div class='order-dispatch'>
													<div class='order-item-block-title'>DISPATCH</div>
													<div class='order-item-block'>
													<div class='order-item-field order-item-padding'>After</div>
													<div class='order-item-field order-item-value'>" . date("h:i A, M d, Y", strtotime($order->dispatchAfterDate)) . "</div>
												</div>
												<div class='order-item-block'>
													<b>
														<div class='order-item-field order-item-padding'>By</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date'>" . date("h:i A, M d, Y", strtotime($order->dispatchByDate)) . "</div>
													</b>
												</div>
												<div class='order-item-block'>
													<div class='order-item-field order-item-padding'>Logistic</div>
													<div class='order-item-field order-item-value order-item-confirm-by-date'>" . $pickup->vendorName . "</div>
												</div>
												<div class='order-item-block'>
													<div class='order-item-field order-item-padding'>Tracking</div>
													<div class='order-item-field order-item-value order-item-confirm-by-date'>" . $tracking_link . "</div>
												</div>
												" . $pickup_div . "												
												</div>
												<div class='order-buyer-details'>
													<div class='order-item-block-title buyer-details'>BUYER DETAILS</div>
													<div class='order-item-block'>
														<div class='result-style-address'>
															<div class='shipping-name'>" . $address->firstName . " " . $address->lastName . "</div>
															<div class='shipping-city-pincode-normal'>" . $address->city . ", " . $address->state . "</div>
															<div class='buyer-mobile'>+91" . $address->contactNumber . "</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>";
							}

							$checkbox = "<td><input type='checkbox' class='checkboxes' name='orderItemId' value='" . implode(',', $order_item_ids) . "' /></td><td>";
							$return .= $checkbox . $content;
							// $return .= "</td><td class='return_hide_column'><span class='label label-warning'>".$order->status."</span><br />".$mul_pro."</td>";
							$return .= "</td><td class='return_hide_column'><span class='label label-warning'>" . $order->status . "</span><br />" . $mul_pro . "</td><td style='display:none;'>" . $order->account_name . "</td><td style='display:none;'>" . $order->order_type . "</td>";
							$return .= "</tr>";

							$i++;
						}
						echo preg_replace('/\s+/S', " ", $return);
					}
					// }
					break;

				case 'start':
				case 'in_transit':
				case 'out_for_delivery':
				case 'delivered':
					// foreach ($accounts as $account) {
					$fk = new connector_flipkart($accounts[0]);
					$orders = $fk->view_orders($order_type, true);

					if ($orders) {
						$return = "";
						$i = 0;

						foreach ($orders as $sub_order) {
							$multi_pro = (count($sub_order) > 1) ? true : false;
							$return .= "<tr class='gradeX'>";
							$order_item_ids = array();

							$content = "";
							foreach ($sub_order as $order) {
								$order_item_ids[] = $order->orderItemId;
								// $order_item_ids[] = $order->shipmentId;
								// $account_name = ($order->account_id == 1 ? "<span class='label label-info'>BabyNSmile</span>" : "<span class='label label-success'>Sylvi</span>");
								$return_type = "<span class='label label-info type'>" . strtoupper($order->r_source) . "</span>";
								$thumb_image_url = ($order->pti != NULL ? $order->pti : $order->cti);
								$flag_class = ($order->is_flagged == true ? ' active' : '');
								$fulfilment_type = "";
								$sla = "";
								$unshipped = "";
								$expected = "";
								$claim = "";
								$sort_date = strtotime($order->r_createdDate);

								if (!empty($order->combo_ids))
									$combo_ids = $fk->get_skus_pid($order->combo_ids);
								else
									$combo_ids = $fk->get_skus_pid(json_encode(array($order->pid)));


								if ($order->order_type == "FBF_LITE")
									$fulfilment_type = "<span class='label label-info'>FBF LITE</span>";

								$expected = "";
								if ($order_type == "delivered") {
									$expected = "<div class='ordered-approval'>
														Return Delivered On: <b>" . (($order->r_deliveredDate != NULL || $order->r_deliveredDate != "0000-00-00 00:00:00") ? date("M d, Y H:i", strtotime($order->r_deliveredDate)) : "NA") . "</b>
													</div>";

									$sort_date = strtotime($order->r_deliveredDate);

									if (strtotime($order->r_deliveredDate) + 60 * 60 * 24 * 14 < time()) { // 14 DAYS
										$sla = "<span class='label label-danger'><i>Claim Date Passed!</i></span>";
										$claim = "<div class='btn-group order-task'>
												<a href='#lost_claim' class='btn btn-success file_lost_claim' data-id='" . $order->returnId . "' data-toggle='modal' role='button'>File Claim</a>
											</div>";
									} else if (strtotime($order->r_deliveredDate) + 60 * 60 * 24 * 12 < time()) { // 12 DAYS
										$sla = "<span class='label label-warning'><i>SLA Breached!</i></span>";
										$claim = "<div class='btn-group order-task'>
												<a href='#lost_claim' class='btn btn-success file_lost_claim' data-id='" . $order->returnId . "' data-toggle='modal' role='button'>File Claim</a>
											</div>";
									} else if (strtotime($order->r_expectedDate) < time() || $order->r_subReason == "shipment_lost") {
										$sort_date = strtotime($order->r_expectedDate);
										$sla = "<span class='label label-warning'><i>Return Promise Breached!</i></span>";
										if ($order->r_subReason == "shipment_lost")
											$sla = "<span class='label label-warning'><i>Lost Shipment</i></span>";
										$expected .= "<div class='ordered-approval'>
														Return Expected On: <b>" . date("M d, Y", strtotime($order->r_expectedDate)) . "</b>
													</div>";
										$claim = "<div class='btn-group order-task'>
												<a href='#lost_claim' class='btn btn-success file_lost_claim' data-id='" . $order->returnId . "' data-toggle='modal' role='button'>File Claim</a>
											</div>";
									}
								}

								if ($order_type == "start" || $order_type == "in_transit" || $order_type == "out_for_delivery") {
									if (strtotime($order->r_createdDate) + (60 * 60 * 24) * 60 < time()) { // 60 DAYS
										$sla = "<span class='label label-danger'><i>Return Promise Breached!</i></span>";
										$claim = "<div class='btn-group order-task'>
												<a href='#lost_claim' class='btn btn-success file_lost_claim' data-id='" . $order->returnId . "' data-toggle='modal' role='button'>File Claim</a>
											</div>";
									}
								}

								if (is_null($order->shippedDate) || $order->shippedDate == "0000-00-00 00:00:00") {
									$unshipped = "<span class='label label-warning'>Not Shipped</span>";
								}

								$content .= "
									<div class='order-content' id='" . $order->returnId . "'>
										<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderItemId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
										<div class='ordered-product-image'>
											<img src='" . IMAGE_URL . "/uploads/products/" . $thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
										</div>
										<div class='order-content-container'>
											<div class='ordered-product-name'>
												<a class='product_name' target='_blank' href='" . str_replace('FSN', $order->fsn, FK_PRODUCT_URL) . "'>" . $order->title . "</a> " . $sla . $unshipped . " <span class='label label-success'>" . $order->account_name . "</span> " . $return_type . " " . $fulfilment_type . "											</div>
											<div class='order-approval-container'>
												<div class='ordered-approval'>
													Return Date: <b>" . date("M d, Y", strtotime($order->r_createdDate)) . "</b>
												</div>
												" . $expected . "
												<div class='ordered-approval'>
													Return Status: <span class='bold-font ordered-status-value'>" . $order->r_shipmentStatus . "</span>
												</div>
												<div class='ordered-status'>
													Return ID : <span class='bold-font ordered-status-value'><a target='_blank' class='return_id' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=return_id&id=" . $order->returnId . "'>" . $order->returnId . "</a></span>
												</div>
											</div>
											<div class='order-complete-details'>
												<div class='order-details'>
													<div class='order-item-block-title'>ORDER DETAIL</div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' class='order_item_id' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=order_item_id&id=" . $order->orderItemId . "'>" . $order->orderItemId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=order_id&id=" . $order->orderId . "' class='order_id'>" . $order->orderId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>FSN </div><div class='order-item-field order-item-value fsn'>" . $order->fsn . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value sku'>" . $order->sku . "</div></div>
													<div class='order-item-block hide'><div class='order-item-field order-item-padding'>COMBO </div><div class='order-item-field order-item-value combo_ids'>" . $combo_ids . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>UIDS </div><div class='order-item-field order-item-value uids'>" . implode(', ', json_decode($order->uid, true)) . "</div></div>
												</div>
												<div class='order-price-qty order-price-qty-" . $order->orderItemId . "'>
													<div class='order-item-block-title'>PRICE &amp; QTY</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Return Quantity </div>
														<div class='order-item-field order-item-value qty'>" . $order->r_quantity . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding total'>Total </div>
														<div class='order-item-field order-item-value amount'>Rs. " . ((int)$order->totalPrice) / ((int)$order->r_quantity) . "</div>
													</div>
												</div>
												<div class='order-dispatch'>
													<div class='order-item-block-title'>TRACKING DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Tracking</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='https://www.ekartlogistics.com/shipmenttrack/" . $order->r_trackingId . "'>" . $order->r_trackingId . "</a></div>
													</div>
												</div>
												<div class='order-buyer-details'>
													<div class='order-item-block-title'>RETURN DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Reason:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>" . $order->r_reason . "</div><br />
														<div class='order-item-field order-item-padding order-item-sub-reason return_sub_reason'>(" . $order->r_subReason . ")</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Comment:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date comments'>" . stripcslashes($order->r_comments) . "</div>
													</div>
												</div>
											</div>
											" . $claim . "
										</div>
									</div>";
							}

							$checkbox = "<td id='block-" . $order->returnId . "'><input type='checkbox' class='checkboxes' name='orderItemId' value='" . implode(',', $order_item_ids) . "' /></td><td>";
							$return .= $checkbox . $content;
							$return .= "</td><td>" . $sort_date . "</td><td>" . $order->account_name . "</td><td>" . $order->order_type . "</td>";
							$return .= "</tr>";

							$i++;
						}
						echo preg_replace('/\s+/S', " ", $return);
					}
					// }
					break;

				case 'return_received':
				case 'return_unexpected':
					// foreach ($accounts as $account) {
					$fk = new connector_flipkart($accounts[0]);
					$orders = $fk->view_orders($order_type, true);

					if ($orders) {
						$return = "";
						$i = 0;

						foreach ($orders as $sub_order) {
							$multi_pro = (count($sub_order) > 1) ? true : false;
							$return .= "<tr class='gradeX'>";
							$order_item_ids = array();
							$mul_pro = ($multi_pro) ? "<span class='label label-info'>Multiple Products</span>" : "";

							$content = "";
							foreach ($sub_order as $order) {
								$order_item_ids[] = $order->orderItemId;
								// $order_item_ids[] = $order->shipmentId;
								// $account_name = ($order->account_id == 1 ? "<span class='label label-info'>BabyNSmile</span>" : "<span class='label label-success'>Sylvi</span>");
								$return_type = "<span class='label label-info type'>" . strtoupper($order->r_source) . "</span>";
								$thumb_image_url = ($order->pti != NULL ? $order->pti : $order->cti);
								$flag_class = ($order->is_flagged == true ? ' active' : '');

								if (!empty($order->combo_ids))
									$combo_ids = $fk->get_skus_pid($order->combo_ids);
								else
									$combo_ids = $fk->get_skus_pid(json_encode(array($order->pid)));

								$fulfilment_type = "";
								$sort_date = "";
								if ($order->order_type == "FBF_LITE")
									$fulfilment_type = "<span class='label label-info'>FBF LITE</span>";
								if ($order->order_type == "FBF")
									$fulfilment_type = "<span class='label label-default'>FA</span>";

								$received_date = "<div class='ordered-approval'>
											Delivered Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_deliveredDate)) . "</span>
										</div><div class='ordered-approval'>
											Received Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_receivedDate)) . "</span>
										</div>";
								$sort_date = date("M d, Y h:i A", strtotime($order->r_receivedDate));

								$return_unexpected = '';
								if ($order_type == "return_unexpected") {
									$received_date = "<div class='ordered-approval'>
											Delivered Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_deliveredDate)) . "</span>
										</div>";
									$disabled = "";
									if (time() < strtotime($order->r_createdDate . ' +7 days'))
										$disabled = " disabled='disabled'";
									$return_unexpected = "data-type='unexpected'" . $disabled;
									$sort_date = date("M d, Y h:i A", strtotime($order->r_deliveredDate));
								}

								$content .= "
									<div class='order-content' id='" . $order->returnId . "'>
										<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderItemId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
										<div class='ordered-product-image'>
											<img src='" . IMAGE_URL . "/uploads/products/" . $thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
										</div>
										<div class='order-content-container'>
											<div class='ordered-product-name'>
												<a class='product_name' target='_blank' href='" . str_replace('FSN', $order->fsn, FK_PRODUCT_URL) . "'>" . $order->title . "</a> <span class='label label-success'>" . $order->account_name . "</span> " . $return_type . " " . $fulfilment_type . "
											</div>
											<div class='order-approval-container'>
												<div class='ordered-approval'>
													Created Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_createdDate)) . "</span>
												</div>
												<div class='ordered-approval'>
													Status: <span class='bold-font ordered-status-value'>" . $order->r_shipmentStatus . "</span>
												</div>
												" . $received_date . "
												<div class='ordered-status'>
													Return ID : <span class='bold-font ordered-status-value'><a target='_blank' class='return_id' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=return_id&id=" . $order->returnId . "'>" . $order->returnId . "</a></span>
												</div>
											</div>
											<div class='order-complete-details'>
												<div class='order-details'>
													<div class='order-item-block-title'>ORDER DETAIL</div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>Order Date </div><div class='order-item-field order-item-value'>" . date("M d, Y", strtotime($order->orderDate)) . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' class='order_item_id' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=order_item_id&id=" . $order->orderItemId . "'>" . $order->orderItemId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=order_id&id=" . $order->orderId . "' class='order_id'>" . $order->orderId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>FSN </div><div class='order-item-field order-item-value fsn'>" . $order->fsn . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value sku'>" . $order->sku . "</div></div>
													<div class='order-item-block hide'><div class='order-item-field order-item-padding'>COMBO </div><div class='order-item-field order-item-value combo_ids'>" . $combo_ids . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>UIDS </div><div class='order-item-field order-item-value uids'>" . implode(', ', json_decode($order->uid, true)) . "</div></div>
												</div>
												<div class='order-price-qty order-price-qty-" . $order->orderItemId . "'>
													<div class='order-item-block-title'>PRICE &amp; QTY</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Order Quantity </div>
														<div class='order-item-field order-item-value qty'>" . $order->quantity . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Return Quantity </div>
														<div class='order-item-field order-item-value return_qty'>" . $order->r_quantity . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding total'>Total </div>
														<div class='order-item-field order-item-value amount'>Rs. " . ((int)$order->totalPrice) / ((int)$order->r_quantity) . "</div>
													</div>
												</div>
												<div class='order-dispatch'>
													<div class='order-item-block-title'>TRACKING DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Tracking</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='https://www.ekartlogistics.com/shipmenttrack/" . $order->r_trackingId . "'>" . $order->r_trackingId . "</a></div>
													</div>
												</div>
												<div class='order-buyer-details'>
													<div class='order-item-block-title'>RETURN DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Reason:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>" . $order->r_reason . "</div><br />
														<div class='order-item-field order-item-padding order-item-sub-reason return_sub_reason'>(" . $order->r_subReason . ")</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Comment:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date comments'>" . stripcslashes($order->r_comments) . "</div>
													</div>
												</div>
											</div>
											<div class='btn-group order-task'>
												<a href='#mark_acknowledge_return_single' class='btn btn-success acknowledge_return' data-id='" . $order->returnId . "' " . $return_unexpected . " data-toggle='modal' role='button'>Acknoledge Return</a>
											</div>
										</div>
									</div>";
							}

							$checkbox = "<td><input type='checkbox' class='checkboxes' name='orderItemId' value='" . implode(',', $order_item_ids) . "' /></td><td>";
							$return .= $checkbox . $content;
							$return .= "<td>" . $sort_date . "</td><td>" . $order->account_name . "</td><td>" . $order->order_type . "</td>";
							$return .= "</tr>";

							$i++;
						}
						echo preg_replace('/\s+/S', " ", $return);
					}
					// }
					break;

				case 'return_claimed':
				case 'return_claimed_undelivered':
				case 'return_completed':
					// foreach ($accounts as $account) {
					$fk = new connector_flipkart($accounts[0]);
					$orders = $fk->view_orders($order_type, true);

					if ($orders) {
						$return = "";
						$i = 0;

						foreach ($orders as $sub_order) {
							$multi_pro = (count($sub_order) > 1) ? true : false;
							$return .= "<tr class='gradeX'>";
							$order_item_ids = array();
							$mul_pro = ($multi_pro) ? "<span class='label label-info'>Multiple Products</span>" : "";

							$content = "";
							foreach ($sub_order as $order) {
								$order_item_ids[] = $order->orderItemId;
								// $order_item_ids[] = $order->shipmentId;
								$return_type = "<span class='label label-info type'>" . strtoupper($order->r_source) . "</span>";
								$return_claim = "";
								$unshipped = "";
								$sort_field = $order->r_completionDate;
								if ($order_type == "return_claimed" || $order_type == "return_claimed_undelivered") {
									$button_spf_reclaim = "";
									if ($order->claimStatusFK == "claim_approved" || $order->claimStatusFK == "claim_rejected" || $order->claimStatusFK == "resolved") {
										$base_incident = is_null($order->r_approvedSPFId) ? $order->claimId : $order->r_approvedSPFId;
										$claimNote = $order->claimNotes;
										$needle = "IN";
										$lastPos = 0;
										$ids = array();
										$base_incident_content = "";
										while (($lastPos = strpos($claimNote, $needle, $lastPos)) !== false) {
											$lastPos = $lastPos + strlen($needle);
											$id = substr($claimNote, $lastPos - 2, 21);
											if (!in_array($id, $ids))
												$ids[] = $id;
										}

										$ids = array_reverse($ids);
										$base_ids = array();
										foreach ($ids as $id) {
											if ($id == $base_incident || in_array($base_incident, $base_ids))
												$base_ids[] = $id;
										}

										if ($base_ids)
											$base_incident_content = '\n\nBase Incident: ' . implode(', ', $base_ids);

										$order_amount = (((int)$order->totalPrice) / ((int)$order->quantity) * (int)$order->r_quantity);
										if ($order->r_source == "customer_return")
											$order_amount = (((int)$order->sellingPrice) / ((int)$order->quantity) * (int)$order->r_quantity);
										$expected_spf = $order_amount * 0.72;
										$ticket_content = 'Dear Team, ' . $base_incident_content . '\n\nItem ID: ' . $order->orderItemId . '\nOrder ID: ' . $order->orderId . '\nReturn ID: ' . $order->returnId . ' \nOrder Value: ' . $order_amount . '\nSPF % as per policy: 72%\nExpected SPF: ' . $expected_spf . '\nSettled/Approved SPF: ##APPROVED_SPF##\n\nSPF settled for the lost item as per the policy is 72% of the order value. The settlement here is not done as per the policy. Request you to settle the correct amount. \n\nRegards, \nSupport Team FIMS';
										$button_spf_reclaim = "<span class='hide reclaim_details reclaim_details_" . $order->returnId . "' data-account='acc_" . $order->account_id . "' data-content='" . $ticket_content . "' data-approved_spf='" . $order->r_approvedSPFId . "' data-issueType='I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT'></span>";
									}

									$return_claim = "<div class='btn-group order-task'>
												<a href='#update_claim' class='btn btn-success update_claim' data-id='" . $order->returnId . "' data-toggle='modal' role='button'>Update Claim</a>
												" . $button_spf_reclaim . "
											</div>";

									$sort_field = $order->claimId;
								}

								if (!empty($order->claimId)) {
									if ($order->claimStatusFK == "pending")
										$claim_status_class = 'pending';
									elseif ($order->claimStatusFK == "claim_rejected" || $order->claimStatusFK == "not_approved" || $order->claimStatusFK == "resolved")
										$claim_status_class = 'label-danger';
									elseif ($order->claimStatusFK == "seller_action_pending")
										$claim_status_class = 'label-alert';
									else {
										$claim_status_class = '';
									}

									$claim_status = "";
									if (!is_null($order->r_approvedSPFId))
										$claim_status = "_incomplete_spf";


									$claim_details = "<div class='order-approval-container'>
												<div class='ordered-approval'>
													Claim Date: <span class='claim_date'>" . date("M d, Y", strtotime($order->claimDate)) . "
												</div>
												<!--<div class='ordered-approval'>
													Claim Status: <span class='bold-font ordered-status-value claim_staus pending'>" . ucfirst($order->claimStatus) . "</span>
												</div>-->
												<div class='ordered-approval'>
													FK Claim Status: <span class='bold-font ordered-status-value claim_staus " . $claim_status_class . "'>" . ucfirst($order->claimStatusFK) . $claim_status . "</span>
												</div>
												<div class='ordered-approval'>
													Claim ID : <span class='bold-font ordered-status-value'><a class='claim_id' data-rid=" . $order->returnId . " id='claim_id_" . $order->claimId . "' data-type='text' data-pk='" . $order->returnId . "' data-placement='right' data-placeholder='Required' data-original-title='Enter New Claim ID' href='https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/" . $order->claimId . "/SELLER_SELF_SERVE'>" . $order->claimId . "</a></span> <a href='https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/" . $order->claimId . "/SELLER_SELF_SERVE' target='_blank'><i class='fa fa-external-link-alt' aria-hidden='true'></i></a>
												</div>
												<div class='ordered-status'>
													Product Condition: <b class='product_condition'>" . ucfirst($order->claimProductCondition) . "</b>
												</div>
											</div>";
									$sort_date = date("d/m/y", strtotime($order->claimDate));
								} else {
									$claim_details = "<div class='order-approval-container'>
												<div class='ordered-approval'>
													Claim : NO CLAIM
												</div>
											</div>";
								}

								$thumb_image_url = ($order->pti != NULL ? $order->pti : $order->cti);
								$flag_class = ($order->is_flagged == true ? ' active' : '');

								$received_on = "Received Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_receivedDate)) . "</span>";
								$delivered_on = ($order->r_deliveredDate != NULL) ? date("M d, Y H:i", strtotime($order->r_deliveredDate)) : "Undelivered";
								// if ($order->r_shipmentStatus == "return_claimed_undelivered")
								$delivered_on = "Delivered Date: <span class='order_date'>" . $delivered_on . "</span>";

								$fulfilment_type = "";
								if ($order->order_type == "FBF_LITE")
									$fulfilment_type = "<span class='label label-info'>FBF LITE</span>";
								if ($order->order_type == "FBF")
									$fulfilment_type = "<span class='label label-default'>FA</span>";

								if (is_null($order->shippedDate) || $order->shippedDate == "0000-00-00 00:00:00") {
									$unshipped = "<span class='label label-warning'>Not Shipped</span>";
								}

								$content .= "
									<div class='order-content' id='" . $order->returnId . "'>
										<div class='bookmark'><a class='flag" . $flag_class . "' data-itemid='" . $order->orderItemId . "' href='#'><i class='fa fa-bookmark'></i></a></div>
										<div class='ordered-product-image'>
											<img src='" . IMAGE_URL . "/uploads/products/" . $thumb_image_url . "' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/100x100';\" />
										</div>
										<div class='order-content-container'>
											<div class='ordered-product-name'>
												<a class='product_name' target='_blank' href='" . str_replace('FSN', $order->fsn, FK_PRODUCT_URL) . "'>" . $order->title . "</a>" . $unshipped . " <span class='label label-success account_name'>" . $order->account_name . "</span> " . $return_type . " " . $fulfilment_type . "
											</div>
											<div class='order-approval-container'>
												<div class='ordered-approval'>
													Created Date: <span class='order_date'>" . date("M d, Y", strtotime($order->r_createdDate)) . "</span>
												</div>
												<div class='ordered-approval'>
													" . $delivered_on . "
												</div>
												<div class='ordered-approval'>
													" . $received_on . "
												</div>
												<div class='ordered-approval'>
													Status: <span class='bold-font ordered-status-value'>" . $order->r_shipmentStatus . "</span>
												</div>
												<div class='ordered-status'>
													Return ID : <span class='bold-font ordered-status-value'><a target='_blank' class='return_id' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=return_id&id=" . $order->returnId . "'>" . $order->returnId . "</a></span>
												</div>
											</div>
											" . $claim_details . "
											<div class='order-complete-details'>
												<div class='order-details'>
													<div class='order-item-block-title'>ORDER DETAIL</div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>Item ID </div><div class='order-item-field order-item-value'><a target='_blank' class='order_item_id' href='https://seller.flipkart.com/index.html#dashboard/payments/transactions?filter=" . $order->orderItemId . "&startDate=start&endDate=end' class='order_item_id' >" . $order->orderItemId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>ID </div><div class='order-item-field order-item-value'><a target='_blank' href='https://seller.flipkart.com/index.html#dashboard/return_orders/" . $order->locationId . "/search?type=order_id&id=" . $order->orderId . "' class='order_id'>" . $order->orderId . "</a></div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>FSN </div><div class='order-item-field order-item-value fsn'>" . $order->fsn . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>SKU </div><div class='order-item-field order-item-value sku'>" . $order->sku . "</div></div>
													<div class='order-item-block'><div class='order-item-field order-item-padding'>UIDS </div><div class='order-item-field order-item-value uids'>" . implode(', ', json_decode($order->uid, true)) . "</div></div>
												</div>
												<div class='order-price-qty order-price-qty-" . $order->orderItemId . "'>
													<div class='order-item-block-title'>PRICE &amp; QTY</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding total'>Selling </div>
														<div class='order-item-field order-item-value item_amount' data-item_amount=" . ((((int)$order->sellingPrice) / ((int)$order->quantity)) * (int)$order->r_quantity) . ">Rs. " . ((((int)$order->sellingPrice) / ((int)$order->quantity)) * (int)$order->r_quantity) . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding total'>Shipping </div>
														<div class='order-item-field order-item-value shipping_amount' data-shipping_amount=" . ((((int)$order->shippingCharge) / ((int)$order->quantity)) * (int)$order->r_quantity) . ">Rs. " . ((((int)$order->shippingCharge) / ((int)$order->quantity)) * (int)$order->r_quantity) . "</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding total'>Total </div>
														<div class='order-item-field order-item-value amount' data-amount=" . ((((int)$order->totalPrice) / ((int)$order->quantity)) * (int)$order->r_quantity) . ">Rs. " . ((((int)$order->totalPrice) / ((int)$order->quantity)) * (int)$order->r_quantity) . "</div>
													</div>
												</div>
												<div class='order-dispatch'>
													<div class='order-item-block-title'>TRACKING DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Tracking</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date'><a class='tracking_id' target='_blank' href='https://www.ekartlogistics.com/shipmenttrack/" . $order->r_trackingId . "'>" . $order->r_trackingId . "</a></div>
													</div>
												</div>
												<div class='order-buyer-details'>
													<div class='order-item-block-title'>RETURN DETAILS</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Reason:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date return_reason'>" . $order->r_reason . "</div>
														<div class='order-item-field order-item-padding order-item-sub-reason return_sub_reason'>(" . $order->r_subReason . ")</div>
													</div>
													<div class='order-item-block'>
														<div class='order-item-field order-item-padding'>Comment:</div>
														<div class='order-item-field order-item-value order-item-confirm-by-date comments'>" . stripcslashes($order->r_comments) . "</div>
													</div>
												</div>
											</div>
											" . $return_claim . "
										</div>
									</div>";
							}

							$checkbox = "<td><input type='checkbox' class='checkboxes' name='orderItemId' value='" . implode(',', $order_item_ids) . "' /></td><td>";
							$return .= $checkbox . $content;
							$return .= "</td><td>" . $sort_field . "</td><td>" . $order->account_name . "</td><td>" . $order->order_type . "</td>";
							$return .= "</tr>";

							$i++;
						}
						echo preg_replace('/\s+/S', " ", $return);
					}
					// }
					break;
			}
			break;

		case 'get_count':

			$order_type = array("upcoming", "new", "packing", "rtd", "shipped", "cancelled");
			$is_returns = false;
			if ($handler == "return") {
				$order_type = array("start", "in_transit", "out_for_delivery", "delivered", "return_received", "return_claimed", "return_claimed_undelivered", "return_completed", "return_unexpected");
				$is_returns = true;
			}

			$return = array();
			$return['orders'] = array();
			$return['logistic'] = array();
			$return['dispatchByDates'] = array();
			$fk = new connector_flipkart($accounts[0]);
			foreach ($order_type as $type) {
				$orders = $fk->get_order_count($type, $is_returns);
				if (isset($return['orders'][$type])) {
					$return['orders'][$type] += (int)$orders['count'];
					if ($type == 'shipped') {
						foreach ($orders['logistic_partner'] as $la_key => $la_value) {
							foreach ($la_value as $lp_key => $lp_value) {
								foreach ($lp_value as $o_type => $o_value) {
									$return['logistic'][$la_key][$lp_key][$o_type] += $o_value;
								}
							}
						}
					}
					if ($type == 'upcoming') {
						$return['dispatchByDates'] = $orders['dispatchByDate'];
					}
				} else {
					$return['orders'][$type] = (int)$orders['count'];
					if ($type == 'shipped') {
						foreach ($orders['logistic_partner'] as $la_key => $la_value) {
							foreach ($la_value as $lp_key => $lp_value) {
								foreach ($lp_value as $o_type => $o_value) {
									$return['logistic'][$la_key][$lp_key][$o_type] += $o_value;
								}
							}
						}
					}
					if ($type == 'upcoming') {
						$return['dispatchByDates'] = $orders['dispatchByDate'];
					}
				}
			}

			echo json_encode($return);
			break;

		case 'get_rtd_breach_count':
			// ALL order where dispatch by date is between dispatch by date-900 and dispatch by date
			// SELECT fa.account_id, COUNT(o.orderItemId) AS breaching FROM bas_fk_orders o INNER JOIN bas_fk_account fa ON fa.account_id = o.account_id WHERE TIME_TO_SEC( TIMEDIFF(o.dispatchByDate, NOW())) < '87300' AND( o.status = 'NEW' OR o.status = 'PACKNIG' ) AND o.hold = '0' GROUP BY account_id
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'INNER');
			$db->where('TIME_TO_SEC(TIMEDIFF(o.dispatchByDate, now()))', 900, '<');
			$db->where('(o.status = ? OR o.status = ?)', array('NEW', 'PACKNIG'));
			$db->where('o.hold', 0);
			$db->groupBy('account_name');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', null, 'fa.account_name, count(o.orderItemId) as breaching');
			foreach ($orders as $order) {
				$msg = str_replace(array('##NUMBER##', '##ACCOUNT##'), array($order->breaching, $order->account_name), get_template('sms', 'rtd_breach'));
				$ssms = $sms->send_sms($msg, array(get_option('sms_notification_number')), 'IMSDAS');
				echo $order->account_name . ' - ' . $order->breaching . '<br />';
			}
			break;

		case 'get_return_breached_orders':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$db->where("((r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ?) AND r_createdDate < ?)", array("start", "in_transit", "out_for_delivery", date('Y-m-d H:i:s', strtotime("now -60 days")))); // FINAL
			$db->orWhere("(r.r_shipmentStatus = ? AND (r.r_deliveredDate IS NULL OR r.r_deliveredDate < ? OR r.r_expectedDate < ? ))", array("delivered", date('Y-m-d H:i:s', strtotime("now -14 days")), date('Y-m-d H:i:s', strtotime("now"))));
			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "RIGHT OUTER");
			$db->where('o.order_type', 'FBF', '!=');

			$type = (isset($_REQUEST['type']) && $_REQUEST['type'] != "") ? $_REQUEST['type'] : "";

			switch ($type) {
				case 'count':
					$count = $db->getValue(TBL_FK_RETURNS . ' r', 'count(*)');
					$return = array(
						"type" => "success",
						"count" => $count
					);
					echo json_encode($return);
					break;

				default:
					$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
					$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
					$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
					$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
					$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
					$db->orderBy('r.r_createdDate', 'ASC');
					$orders = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, 'fa.account_name, o.orderId, o.orderItemId, o.rtdDate, o.shippedDate, r.returnId, COALESCE(r.r_trackingId, o.forwardTrackingId) as trackingId, r.r_createdDate, r.r_deliveredDate, r.r_expectedDate, o.account_id, pc.pid as combo_ids, COALESCE(pc.pid, pm.pid) as pid');
					// echo $db->getLastQuery().'<br />';
					if ($orders) {
						$data = "<div class='table-responsive'><table class='table table-bordered table-striped'><thead><th>Account</th><th>Order Item ID</th><th>Return ID</th><th>Tracking ID</th><th>RTD Date</th><th>Shipped Date</th><th>Created Date</th><th>Delivered Date</th><th>Expected Date</th><th>Breach Reason</th><th></th></thead><tbody>";
						foreach ($orders as $order) {
							// $db->where('issueType', 'Marketplace cancellation%', "LIKE");
							$db->where('claimId', $order->returnId);
							if ($db->has(TBL_FK_CLAIMS))
								continue;

							// CROSSED EXPECTED DATE AND NOT DELIVERED
							if (strtotime($order->r_expectedDate) < time() && $order->r_deliveredDate == '') {
								if ($order->r_createdDate >= '2021-03-01 00:00:00' && $order->r_createdDate <= '2021-04-25 23:59:59' && time() < strtotime('2021-06-25 00:00:00')) // TEMP::SPF Claim window has now been extended for Unreturned shipments. Know more.
									continue;

								$reason = "<span class='label label-warning'><i>Return not received beyond Expected Date</i></span>"; // RETURN PROMISE BREACHED
								$issueType = "WHY_MY_RETURN_ORDER_IS_NOT_RECEIVED";

								// 	// DELIVERED BUT NOT RECEIVED
								// } else if (strtotime($order->r_deliveredDate) + 60*60*24*14 < time()) { // 14 DAYS 
								// 	$reason = "<span class='label label-danger'><i>Return delivered but not received</i></span>"; // LOST CLAIM
								// 	$issueType = "WHY_MY_RETURN_ORDER_IS_NOT_RECEIVED";

								// DELIVERED AND CROSSED EXPECTED DATE BUT NOT RECEIVED
							} else if (strtotime($order->r_deliveredDate) < strtotime('today -13 days') || strtotime($order->r_expectedDate) < time()) {
								$reason = "<span class='label label-danger'><i>Return created & delivered but not received</i></span>"; // LOST CLAIM
								$issueType = "WHY_MY_RETURN_ORDER_IS_NOT_RECEIVED";
							}

							// CROSSED EXPECTED DATE + 30 DAYS BUT NOT RECEIVED
							if (strtotime($order->r_createdDate) < strtotime('today -2 Month')) { // 61 DAYS
								$reason = "<span class='label label-danger'><i>Return 60 days SLA breached!</i></span>"; // SLA BREACHED
								$issueType = "I_HAVE_NOT_RECEIVED_THE_PRODUCT_POST_60_DAYS_HOW_TO_CLAIM_SPF";
							}

							$product_condition = array();
							if ($order->combo_ids) {
								$combos = json_decode($order->combo_ids);
								foreach ($combos as $product) {
									$product_condition[$product] = "undelivered";
								}
							} else {
								$product_condition[$order->pid] = "undelivered";
							}

							$content = str_replace(array("##RETURN_ID##", "##ORDER_ITEM_ID##", "##ORDER_ID##"), array($order->returnId, $order->orderItemId, $order->orderId), get_template('fk', 'undelivered_returns'));

							$data .= "<tr id='" . $order->returnId . "'>";
							$data .= "<td style='vertical-align'>" . $order->account_name . "</td>";
							$data .= "<td style='vertical-align'>" . $order->orderItemId . "</td>";
							$data .= "<td style='vertical-align'>" . $order->returnId . "</td>";
							$data .= "<td style='vertical-align'>" . $order->trackingId . "</td>";
							$data .= "<td style='vertical-align'>" . ((is_null($order->rtdDate) || $order->rtdDate == "0000-00-00 00:00:00") ? "Not RTD" : date('d M, Y - H:i', strtotime($order->rtdDate))) . "</td>";
							$data .= "<td style='vertical-align'>" . ((is_null($order->shippedDate) || $order->shippedDate == "0000-00-00 00:00:00") ? "Not Shipped" : date('d M, Y - H:i', strtotime($order->shippedDate))) . "</td>";
							$data .= "<td style='vertical-align'>" . date('d M, Y - H:i', strtotime($order->r_createdDate)) . "</td>";
							$data .= "<td style='vertical-align'>" . (($order->r_deliveredDate != '') ? date('d M, Y - H:i', strtotime($order->r_deliveredDate)) : 'Undelivered') . "</td>";
							$data .= "<td style='vertical-align'>" . date('d M, Y - H:i', strtotime($order->r_expectedDate)) . "</td>";
							$data .= "<td style='vertical-align'>" . $reason . "</td>";
							$data .= "<td style='vertical-align'>" . ((is_null($order->rtdDate) || $order->rtdDate == "0000-00-00 00:00:00" || is_null($order->shippedDate) || $order->shippedDate == "0000-00-00 00:00:00") ? "" : "<div><button type='button' class='btn btn-xs btn-success btn-submit raise_ticket raise_ticket_" . $order->returnId . "' data-returnid='" . $order->returnId . "' data-orderid='" . $order->orderId . "' data-orderItemId='" . $order->orderItemId . "' data-trackingid='" . $order->trackingId . "' data-acountid='acc_" . $order->account_id . "' data-condition='" . json_encode($product_condition) . "' data-content='" . $content . "' data-issueType='" . $issueType . "'><i class=''></i> Raise Ticket</button></div>") . "</td>";
							$data .= "</tr>";
						}
					} else {
						$data = "<center>No Return Delivery Breached Orders Found.</center>";
					}
					$data .= "</tbody></table></div>";
					// $data .= "<div class='col-md-12'></div>";
					echo $data;

					// $return = array(
					// 	"type" => "success",
					// 	"content" => $data
					// );
					break;
			}

			break;

		case 'get_tracking':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";

			$acc_orders = array();
			$success = 0;
			$error = 0;
			// find account id to construct fk
			foreach (array_unique($shipment_ids) as $shipment_id) {
				$db->where('shipmentId', $shipment_id);
				$order = $db->getOne(TBL_FK_ORDERS);
				$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
			}

			foreach ($acc_orders as $acc => $orders) {

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					$account_ky = (int) str_replace('acc-', '', $acc);
					if ($account_ky == $account->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);

				foreach ($orders as $shipment_id) {
					$db->where('shipmentId', $shipment_id);
					$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS);
					foreach ($orders as $order) {
						$return = $fk->update_orders(array($order));
						if (strpos($return, 'UPDATE: Updated Order with order ID') !== FALSE) {
							$success++;
						}
						if (strpos($return, 'UPDATE: Unable to update order with order ID') !== FALSE) {
							$error++;
						}
					}
				}
			}
			break;

			// SUITABLE FOR ANY NUMBER OF ACCOUNT
		case 'get-packlist-v1':
			$pdf = new FPDI('P', 'mm', 'A4');

			// // PACK LIST
			// foreach ($accounts as $account) {
			// 	$fk = new connector_flipkart($account);
			// 	$return = $fk->get_packlist_for_day();
			// 	$pdf->AddPage();
			// 	$pdf->SetMargins(10, 10);
			// 	$pdf->SetFontSize(10);
			// 	$pdf->SetTextColor(0, 0, 0);
			// 	$pdf->SetFont('Courier','B');
			// 	$pdf->cell(0,2,'PACK LIST - '.date("d", time()).' '.date("M", time()).', '.date("Y", time()).' - '.date("h", time()).':'.date("i", time())."\n",0,1,'C');
			// 	$pdf->SetFont('Courier');				
			// 	$pdf->MultiCell(190, 5, $return ,0,'L');
			// }

			// PICK LIST
			foreach ($accounts as $account) {
				$fk = new connector_flipkart($account);
				$return = $fk->get_packlist_for_day_v1(true);
				$pdf->AddPage();
				$pdf->SetFontSize(10);
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('Courier', 'B');
				$pdf->cell(0, 2, 'PICK LIST - ' . date("d", time()) . ' ' . date("M", time()) . ', ' . date("Y", time()) . ' - ' . date("h", time()) . ':' . date("i", time()) . "\n", 0, 1, 'C');
				$pdf->SetFont('Courier');
				$pdf->MultiCell(190, 5, $return, 0, 'L');
			}
			$pdf->Output('Packlist-' . date("d", time()) . '-' . date("M", time()) . '-' . date("Y", time()) . '.pdf', 'I');

			break;

			// PRINTS ON SINGLE PAGE BUT SUITABLE FOR LIMITED NUMBER OF ACCOUNT
		case 'get-packlist':

			$fk = new connector_flipkart($accounts[0]);
			$type = $_POST['type'];
			$dbd = "";
			if (isset($_POST['dbd']) && !empty($_POST['dbd']))
				$dbd = $_POST['dbd'];

			if ($type == "FBF_LITE") {
				$orders = array();
				foreach ($accounts as $account) {
					// if ($account->account_name == "Sylvi"){
					if ($account->is_sellerSmart)
						$orders[$account->account_name] = $fk->get_packlist_for_fbf_orders($account->account_id, $dbd);
					// }
				}

				$table_content = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
				foreach ($orders as $accountName => $order) {
					$table_content .= '<table><thead><tr><th colspan="3">' . $accountName . ' - PICK LIST - FBF_LITE - ' . date("d M, Y - H:i", time()) . '</th></tr>';
					if ($dbd)
						$table_content .= '<tr><th colspan="3">ADVANCE PICK LIST for ' . date("d M, Y", strtotime($dbd)) . '</th></tr>';
					$table_content .= "<tr><th>Listing ID</th><th>SKU</th><th>Quanity</th></tr></thead><tbody>";
					$total_qty = 0;
					foreach ($order as $lid => $listing) {
						$table_content .= "<tr><td>" . $lid . "</td><td>" . $listing['sku'] . "</td><td>" . $listing['qty'] . "</td></tr>";
						$total_qty += $listing['qty'];
					}
					$table_content .= "<tr><td colspan='2'><b>Total Quanity</b></td><td><b>" . $total_qty . "</b></td></tr>";
					$table_content .= "</tbody></table><br /><br />";
				}
			} else {
				$orders = $fk->get_packlist_for_day($type, $dbd);

				if (isset($orders['Total Multi Qty Orders']))
					$last = count($orders) - 2;
				else
					$last = count($orders) - 1;

				$colspan = count($accounts) + 3;

				$table_content = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
				$title = 'PICK LIST - ' . date("d M, Y - H:i", time());
				if ($type == "all")
					$title = 'UPCOMING ORDERS QUANTITIES';
				$table_content .= '<table><thead><tr><th colspan="' . $colspan . '">' . $title . '</th></tr>';
				if ($dbd)
					$table_content .= '<tr><th colspan="' . $colspan . '">ADVANCE PICK LIST for ' . date("d M, Y", strtotime($dbd)) . '</th></tr>';
				$table_content .= "<tr><th>SKU's/Account</th>";
				$row_content = array();
				$active_account_index = array();
				foreach ($accounts as $account) {
					$table_content .= '<th>' . $account->fk_account_name . '</th>';
					$row_content[] = "<td></td>";
					$active_account_index[] = $account->fk_account_name;
				}
				$table_content .= "<th>Total Quanity</th></tr>";
				$i = 0;
				foreach ($orders as $sku => $order_rows) {
					ksort($order_rows);
					$td_th = "d";
					if ($last <= $i) {
						$td_th = "h";
					}

					$table_content .= "<tr>";
					$table_content .= "<t" . $td_th . ">" . $sku . "</t" . $td_th . ">";

					$current_row_content = $row_content;
					foreach ($order_rows as $order_row_name => $order_row_value) {
						if ($order_row_name == "quantity_total" || $order_row_name == "total")
							continue;

						$order_row_number = array_search($order_row_name, $active_account_index);
						$current_row_content[$order_row_number] = str_replace("<td></td>", "<td>" . $order_row_value . "</td>", $current_row_content[$order_row_number]);

						// $table_content .= "<t".$td_th.">".$order_row_value."</t".$td_th.">";
					}
					$table_content .= implode(' ', $current_row_content);
					// $table_content .= "<t".$td_th.">".$order[1]."</t".$td_th.">";
					// $table_content .= "<t".$td_th.">".$order[2]."</t".$td_th.">";
					// $table_content .= "<t".$td_th.">".$order[3]."</t".$td_th.">";
					// $table_content .= "<t".$td_th.">".$order[4]."</t".$td_th.">";
					if ($i >= $last)
						$table_content .= "<t" . $td_th . ">" . $order_rows["quantity_total"] . "</t" . $td_th . ">";
					else
						$table_content .= "<t" . $td_th . ">" . $order_rows["total"] . "</t" . $td_th . ">";
					$table_content .= "</tr>";
					$i++;
				}

				$table_content .= "</table>";
			}

			echo $table_content;

			break;

		case 'get_replacement_orders':

			if (date('H', time()) < '14') {
				$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("today 02:00 PM")), "<=");
			} else {
				$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("tomorrow 02:00 PM")), "<=");
			}
			$db->where("(o.status = ? OR o.status = ? OR o.status = ?)", array("NEW", "PACKING", "RTD"));
			$db->where("o.fk_status", "CANCELLED", "!=");
			$db->where("o.hold", "0");
			$db->where("(o.quantity > ? OR o.replacementOrder = ?)", array("1", "1"));
			$db->join(TBL_FK_RETURNS . " r", "r.r_replacementOrderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
			// $db->join(TBL_FK_INCIDENTS.' fi', 'JSON_CONTAINS_PATH(o.orderItemId, CAST(fi.orderItemId as CHAR), "$" )', 'LEFT');
			$db->orderBy('o.account_id', 'ASC');
			$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . " o", null, "fa.account_name, o.account_id, o.orderId, o.orderItemId as newOrderItemId, o.quantity, o.sku, r.orderItemId, r.returnId, r.r_reason, r.r_subReason, r.r_comments");
			// echo $db->getLastQuery();

			// var_dump(json_decode('{"11524277887801700":false,"11524290642597800":false,"11524297595481200":false}'));

			if ($orders) {
				$data = "<b>Total Replacement & Multi Quanity Orders: " . count($orders) . "</b><br /><br /><table class='table table-bordered table-striped'><thead><th>Account</th><th>Order ID</th><th>Order Item ID</th><th>Quanity</th><th>SKU</th><th>Old Order Item ID</th><th>Return ID</th><th>Return Reason</th><th>Return Sub Reason</th><th>Comments</th><th></th></thead><tbody>";
				foreach ($orders as $order) {
					$db->where("(issueType LIKE ? OR issueType LIKE ? OR issueType LIKE ?)", array('Marketplace cancellation%', 'I WANT TO VERIFY%', 'I HAVE RECEIVED%'));
					$db->where('referenceId', '%' . $order->newOrderItemId . '%', "LIKE");
					if ($db->has(TBL_FK_INCIDENTS))
						continue;

					$data .= "<tr id='" . $order->newOrderItemId . "'>";
					$type = 'replacement';
					if ($order->quantity > 1)
						$type = 'multi_quantity';
					$data .= "<td>" . $order->account_name . "</td>";
					$data .= "<td>" . $order->orderId . "</td>";
					$data .= "<td>" . $order->newOrderItemId . "</td>";
					$data .= "<td>" . $order->quantity . "</td>";
					$data .= "<td>" . $order->sku . "</td>";
					$data .= "<td>" . $order->orderItemId . "</td>";
					$data .= "<td>" . $order->returnId . "</td>";
					$data .= "<td>" . $order->r_reason . "</td>";
					$data .= "<td>" . $order->r_subReason . "</td>";
					$data .= "<td>" . str_replace('\n', "<br />", $order->r_comments) . "</td>";
					$data .= "<td><div><input type='checkbox' class='checkboxes checkbox-" . $order->orderItemId . "' data-group='" . $type . "' data-quantity='" . $order->quantity . "' data-account='acc-" . $order->account_id . "' data-orderid='" . $order->orderId . "' name='orderItemId' value='" . $order->newOrderItemId . "' /></div></td>";
					$data .= "</tr>";
				}
				$data .= "</tbody></table>";
				$data .= "<div class='col-md-12'><button type='submit' class='btn btn-success btn-submit pull-right'><i class=''></i> Generate Ticket Content</button></div>";
			} else {
				$data = "<center>No Replacement & Multi Quanity Orders Found.</center>";
			}

			echo $data;
			break;

		case 'get_duplicate_orders':

			if (date('H', time()) < '14') {
				$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("today 02:00 PM")), "<=");
			} else {
				$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("tomorrow 02:00 PM")), "<=");
			}
			$db->where("(o.status = ? OR o.status = ?)", array("PACKING", "RTD"));
			$db->where("o.fk_status", "CANCELLED", "!=");
			$db->where("o.deliveryAddress", "", "!=");
			$db->where("o.hold", "0");
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
			$db->orderBy('o.account_id', 'ASC');
			$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . " o", null, 'fa.account_name, o.orderId, o.orderItemId, o.quantity, o.sku, CONCAT( o.account_id, "~", JSON_UNQUOTE(JSON_EXTRACT(o.deliveryAddress,"$.firstName"))," ",JSON_UNQUOTE(JSON_EXTRACT(o.deliveryAddress, "$.lastName")),"~",JSON_UNQUOTE(JSON_EXTRACT(o.deliveryAddress,"$.city")),"~",JSON_UNQUOTE(JSON_EXTRACT(o.deliveryAddress,"$.contactNumber")) ) AS buyerName');
			$dup_orders = array();
			$temp_order = array();
			if ($orders) {
				foreach ($orders as $order) {
					$db->where('issueType', 'Marketplace cancellation%', "LIKE");
					$db->where('referenceId', '%' . $order->orderItemId . '%', "LIKE");
					if ($db->has(TBL_FK_INCIDENTS))
						continue;

					if (isset($temp_order[$order->buyerName])) {
						if (!isset($dup_orders[$order->buyerName])) { // ADD 1st order to the dup array
							$dup_orders[$order->buyerName]['orders'][] = $temp_order[$order->buyerName];
							$dup_orders[$order->buyerName]['orderItemIds'][] = $temp_order[$order->buyerName]->orderItemId;
						}
						$dup_orders[$order->buyerName]['orders'][] = $order;
						$dup_orders[$order->buyerName]['orderItemIds'][] = $order->orderItemId;
					} else {
						$temp_order[$order->buyerName] = $order;
					}
				}
			}

			if ($dup_orders) {
				$data = "<b>Total Duplicate Orders: " . count($dup_orders) . "</b><br /><br /><table class='table table-bordered table-striped'><thead><th>Account</th><th>Order ID</th><th>Order Item ID</th><th>Quanity</th><th>SKU</th><th>Buyer Name</th><th>City</th><th></th></thead>";
				$i = 0;
				foreach ($dup_orders as $dup_order) {
					$alt_class = "odd";
					if ($i % 2 == 0)
						$alt_class = "even";
					$data .= "<tbody class='" . $alt_class . "'>";
					foreach ($dup_order['orders'] as $order) {
						list($account_id, $customerName, $city, $mobile) = explode('~', $order->buyerName);
						$data .= "<tr id='" . $order->orderItemId . "'>";
						$data .= "<td>" . $order->account_name . "</td>";
						$data .= "<td>" . $order->orderId . "</td>";
						$data .= "<td>" . $order->orderItemId . "</td>";
						$data .= "<td>" . $order->quantity . "</td>";
						$data .= "<td>" . $order->sku . "</td>";
						$data .= "<td>" . $customerName . "</td>";
						$data .= "<td>" . $city . "</td>";
						$data .= "<td><div><input type='checkbox' class='checkboxes checkbox-" . $order->orderItemId . "' data-group='" . str_replace(' ', '_', trim(strtolower($customerName))) . "' data-quantity='" . $order->quantity . "' data-account='acc-" . $account_id . "' data-dup_orderitemid='" . implode(',', $dup_order['orderItemIds']) . "' name='orderItemId' value='" . $order->orderItemId . "' /></div></td>";
						$data .= "</tr>";
					}
					$data .= "</tbody>";
					$i++;
				}
				$data .= "</table>";
				$data .= "<div class='col-md-12'><button type='submit' class='btn btn-success btn-submit pull-right'><i class=''></i> Generate Ticket Content</button></div>";
			} else {
				$data = "<center>No Duplicate Orders Found.</center>";
			}

			echo $data;
			break;

		case 'generate':

			$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";

			if ($type == "" && $shipment_ids == "")
				return;

			$fk = new connector_flipkart($accounts[0]);

			$columns = array('orderId', 'orderItemId', 'shipmentId', 'sku', 'title', 'quantity', 'dispatchByDate', 'replacementOrder', 'order_type', 'fsn', 'pickupDetails', 'deliveryAddress');

			switch ($type) {
				case 'label':
					$shipment_ids = $fk->arrange_orders($shipment_ids, $columns);

					$acc_orders = array();
					// find account id to construct fk
					foreach (array_unique($shipment_ids) as $shipment_id) {
						$db->where('orderItemId', $shipment_id['orderItemId']);
						$order = $db->getOne(TBL_FK_ORDERS);
						$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
					}

					foreach ($acc_orders as $acc => $orders) {

						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							$account_ky = (int) str_replace('acc-', '', $acc);
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						$fk = new connector_flipkart($accounts[$account_key]);
						$fk->generate_labels($shipment_ids, true, false);
					}
					break;

				case 'form':
					$shipment_ids = $fk->arrange_orders($shipment_ids, $columns);

					$acc_orders = array();
					// find account id to construct fk
					foreach (array_unique($shipment_ids) as $shipment_id) {
						$db->where('orderItemId', $shipment_id['orderItemId']);
						$order = $db->getOne(TBL_FK_ORDERS);
						$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
					}

					foreach ($acc_orders as $acc => $orders) {

						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							$account_ky = (int) str_replace('acc-', '', $acc);
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						$fk = new connector_flipkart($accounts[$account_key]);
						$fk->get_forms_invoices($shipment_ids, false, true);
					}
					break;

				case 'invoice':
					$shipment_ids = $fk->arrange_orders($shipment_ids, $columns);

					$acc_orders = array();
					// find account id to construct fk
					foreach (array_unique($shipment_ids) as $shipment_id) {
						$db->where('orderItemId', $shipment_id['orderItemId']);
						$order = $db->getOne(TBL_FK_ORDERS);
						$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
					}

					foreach ($acc_orders as $acc => $orders) {

						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							$account_ky = (int) str_replace('acc-', '', $acc);
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						$fk = new connector_flipkart($accounts[$account_key]);
						$fk->generate_labels($shipment_ids, false, true);
					}
					break;

				case 'label-invoice':
					// error_reporting(E_ALL);
					// ini_set('display_errors', '1');
					// echo '<pre>';
					$shipment_ids = $fk->arrange_orders($shipment_ids, $columns);

					$acc_orders = array();
					// find account id to construct fk
					foreach (array_unique($shipment_ids) as $shipment_id) {
						$db->where('orderItemId', $shipment_id['orderItemId']);
						$order = $db->getOne(TBL_FK_ORDERS);
						$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
					}

					foreach ($acc_orders as $acc => $orders) {

						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							$account_ky = (int) str_replace('acc-', '', $acc);
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						// if ($accounts[$account_key]->account_id == 1)
						// 	$fk = new flipkart_dashboard($accounts[$account_key]);
						// else
						$fk = new connector_flipkart($accounts[$account_key]);
						$fk->generate_labels($shipment_ids, true, true, true);
					}
					break;

				case 'redownload-label':
					// error_reporting(E_ALL);
					// ini_set('display_errors', '1');
					// echo '<pre>';
					$shipment_ids = $fk->arrange_orders($shipment_ids, $columns);
					$return = array("type" => "error", "message" => "Error Processing your Request! <br />Please try again.");

					$acc_orders = array();
					// find account id to construct fk
					foreach (array_unique($shipment_ids) as $shipment_id) {
						$db->where('orderItemId', $shipment_id['orderItemId']);
						$order = $db->getOne(TBL_FK_ORDERS);
						$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
					}

					foreach ($acc_orders as $acc => $orders) {

						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							$account_ky = (int) str_replace('acc-', '', $acc);
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						// if ($accounts[$account_key]->account_id == 1)
						// 	$fk = new flipkart_dashboard($accounts[$account_key]);
						// else
						$fk = new connector_flipkart($accounts[$account_key]);
						$fk->generate_labels($shipment_ids, true, false, true, false);
						$is_valid = check_valid_pdf(ROOT_PATH . '/labels/single/FullLabel-' . $shipment_ids[0]["shipmentId"] . '.pdf');
						if ($is_valid)
							$return = array("type" => "success", "message" => "Label successfully generated");
						else
							$return = array("type" => "error", "message" => "Unable to redownload label");
					}

					echo json_encode($return);
					break;

				case 'report':
					$report_type = $_POST['reportType'];
					$daterange = explode(' - ', $_POST['daterange']);
					$date_start = $daterange[0];
					$date_end = $daterange[1];
					$report_accounts = $_POST['reportAccount'];
					$sheets = array();

					ob_start();

					if ($report_accounts == "all") {
						$file_account = "all";
						foreach ($accounts as $account) {
							$fk = new connector_flipkart($account);
							$sheets[$account->account_name] = $fk->generate_report($date_start, $date_end, $report_type);
						}
					} else {
						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							if ($a_key == ($report_accounts - 1))
								$account_key = $a_key;
						}
						$file_account = $accounts[$account_key]->account_name;
						$fk = new connector_flipkart($accounts[$account_key]);
						$sheets[$accounts[$account_key]->account_name] = $fk->generate_report($date_start, $date_end, $report_type);
					}

					// $objPHPExcel = new PHPExcel();
					$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
					// Set document properties
					$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
						->setLastModifiedBy("Ishan Kukadia")
						->setTitle(ucfirst($report_type))
						->setSubject(ucfirst($report_type))
						->setDescription(ucfirst($report_type));

					$s = 0;
					$previous_row = array();
					foreach ($sheets as $sheet => $output) {
						$r = 1;
						$objPHPExcel->createSheet($s);
						$objPHPExcel->setActiveSheetIndex($s);
						$this_order = '';
						$i = 0;
						foreach ($output as $rows) {
							$c = "A";
							if ($r == 1) {
								$last_row = sizeof($rows);
								$last = chr(ord('A') + $last_row - 1);
								$multiplier = (int)($last_row / 26);
								if ($last_row % 26 == 0) {
									$last = chr(ord('A') + ($multiplier - 2)) . chr(ord('A') + 25);
								} else if ($last_row > 26) {
									$last = chr(ord('A') + ($multiplier - 1)) . chr(ord('A') + ($last_row - (26 * $multiplier)) - 1);
								}
							}
							// var_dump($previous_order);
							// var_dump($this_order);
							$previous_order = $this_order;
							$this_order = $rows[5]; // Order Item ID. Hard Fix. Need to be at place 5 in an array all the time.

							$row_type = 'even';
							if ($previous_order != $this_order && $r != 1) {
								$row_type = ($i % 2 == 0) ? 'even' : 'odd';
								$i++;
							}

							$objActiveSheet = $objPHPExcel->getActiveSheet();

							foreach ($rows as $row) {
								if ($r == 1) {
									$objActiveSheet->getColumnDimension($c)->setAutoSize(true);
									$objActiveSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

									// if ($c == 'A' || $c == 'B' || $c == 'C')
									// 	$objActiveSheet->getStyle($c.$r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
									// else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
									// 	$objActiveSheet->getStyle($c.$r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
									// else
									// 	$objActiveSheet->getStyle($c.$r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
								}

								if ($c == $last) {
									// var_dump($previous_order);
									// 	var_dump($this_order);
									if ($previous_order == $this_order) {
										$previous_row[] = $c . ($r - 1);
										if ($previous_row[0] != $c . ($r - 1))
											$objActiveSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
										$objActiveSheet->mergeCells($previous_row[0] . ':' . $c . $r);
										$objActiveSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
										$objActiveSheet->setCellValue($previous_row[0], $row);
										if ($row_type == 'odd' && $r != 1) // && !empty($row))
											$objActiveSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
									} else {
										// var_dump($this_order);
										// echo '<br />';
										$objActiveSheet->setCellValue($c . $r, $row);
										$previous_row = array();
										if ($row_type == 'odd' && $r != 1)
											$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
									}
									// var_dump($row);
									// var_dump($previous_row);
								} else {
									$objActiveSheet->setCellValue($c . $r, $row);
									if ($row_type == 'odd' && $r != 1)
										$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
								}
								$c++;
							}

							$r++;
						}

						$styleArray = array(
							'borders' => array(
								'allborders' => array(
									'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
								)
							)
						);

						$objActiveSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
						unset($styleArray);

						// $objActiveSheet->insertNewColumnBefore('D', 1);
						// $objActiveSheet->getColumnDimension('D')->setWidth("1");
						// $objActiveSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

						// $objActiveSheet->insertNewColumnBefore('L', 1);
						// $objActiveSheet->getColumnDimension('L')->setWidth("1");
						// $objActiveSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

						$objActiveSheet->getStyle('A1:' . $last . '1')->getFont()->setBold(true);
						$objActiveSheet->setTitle($sheet);

						$s++;
					}
					$objPHPExcel->setActiveSheetIndex(0);

					// REMOVE DEFAULT WORKSHEET
					$objPHPExcel->removeSheetByIndex(
						$objPHPExcel->getIndex(
							$objPHPExcel->getSheetByName('Worksheet')
						)
					);
					ob_clean();

					// Redirect output to a client’s web browser (Xlsx)
					header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
					header('Content-Disposition: attachment;filename="' . $report_type . '-report-' . $file_account . '-' . date('Ymd') . '.xlsx"');
					header('Cache-Control: max-age=0');
					// If you're serving to IE 9, then the following may be needed
					header('Cache-Control: max-age=1');

					// If you're serving to IE over SSL, then the following may be needed
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
					header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
					header('Pragma: public'); // HTTP/1.0
					$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
					$objWriter->save('php://output');
					break;

				case 'replacement':
					$sheets = array();
					foreach ($accounts as $account) {
						// $sheets[$account->account_name] = $fk->generate_report(date('Y-m-d H:i:s', strtotime('today')), date('Y-m-d H:i:s', strtotime('tomorrow')-1), 'replacement');
						if ($type == "new") {
							if (date('H', time()) < '14') {
								$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("today 02:00 PM")), "<=");
							} else {
								$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', strtotime("tomorrow 02:00 PM")), "<=");
							}
						}
					}
					break;

				case 'ticket_content':
					// error_reporting(E_ALL);
					// ini_set('display_errors', '1');
					// echo '<pre>';
					$order_details = json_decode($_POST['orders']);

					$return = array();
					foreach ($order_details as $acc => $orders) {
						$account_ky = (int) str_replace('acc-', '', $acc);
						$account_key = "";
						foreach ($accounts as $a_key => $account) {
							if ($account_ky == $account->account_id)
								$account_key = $a_key;
						}

						$fk = new connector_flipkart($accounts[$account_key]);

						$orderItemIds = array();
						if (isset($orders->replacement)) {
							$list = "";
							$template = "";
							$list .= '\nReason: Reason and comments differs or non replacable quality issue\n';
							foreach ($orders->replacement as $order) {
								$orderItemIds[] = $order;
								$list .= 'OrderItemID: ' . $order . '\n';
							}
							$template = get_template('fk', 'replacement_orders');
							$db->where('orderItemId', $orderItemIds[0]);
							$orderId = $db->getValue(TBL_FK_ORDERS, 'orderId');
							$return['acc_' . $account_ky]['replacement_order']['orderId'] = $orderId;
							$return['acc_' . $account_ky]['replacement_order']['orderItemIds'] = implode(',', $orderItemIds);
							$return['acc_' . $account_ky]['replacement_order']['subject'] = 'Buyer Verification for Replacement Orders ';
							$return['acc_' . $account_ky]['replacement_order']['ticket_content'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile), $template);
							$return['acc_' . $account_ky]['replacement_order']['html'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}', '\n'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile, '<br/>'), $template);
							$return['acc_' . $account_ky]['replacement_order']['issueType'] = 'I_WANT_TO_VERIFY_MY_REPLACEMENT_ORDER_DETAILS';
						}

						$orderItemIds = array();
						if (isset($orders->multi_quantity)) {
							$list = "";
							$template = "";
							foreach ($orders->multi_quantity as $orders_list) {
								foreach ($orders_list as $order => $qty) {
									$orderItemIds[] = $order;
									$list .= 'OrderItemID: ' . $order . '\n';
									$list .= 'Qty Ordered: ' . $qty . ' units\n\n';
								}
							}
							$template = get_template('fk', 'multi_quantity_orders');
							$db->where('orderItemId', $orderItemIds[0]);
							$orderId = $db->getValue(TBL_FK_ORDERS, 'orderId');
							$return['acc_' . $account_ky]['multi_quantity']['orderId'] = $orderId;
							$return['acc_' . $account_ky]['multi_quantity']['orderItemIds'] = implode(',', $orderItemIds);
							$return['acc_' . $account_ky]['multi_quantity']['subject'] = 'Buyer Verification for Multi Quanity Orders ';
							$return['acc_' . $account_ky]['multi_quantity']['ticket_content'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile), $template);
							$return['acc_' . $account_ky]['multi_quantity']['html'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}', '\n'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile, '<br/>'), $template);
							$return['acc_' . $account_ky]['multi_quantity']['issueType'] = 'I_WANT_TO_VERIFY_THE_ORDER_QUANTITY';
						}

						$orderItemIds = array();
						if (!isset($orders->multi_quantity) && !isset($orders->replacement)) {
							$list = "";
							$template = "";
							foreach ($orders as $group => $order) {
								$orderItemIds[] = implode(',', $order);
								$list .= ucwords(str_replace('_', ' ', $group)) . ' [' . count($order) . " orders] :: OrderItemID: " . implode(', ', $order) . '\n';
							}
							$template = get_template('fk', 'duplicate_orders');
							$db->where('orderItemId', $orderItemIds[0]);
							$orderId = $db->getValue(TBL_FK_ORDERS, 'orderId');
							$return['acc_' . $account_ky]['duplicate_orders']['orderId'] = $orderId;
							$return['acc_' . $account_ky]['duplicate_orders']['orderItemIds'] = implode(',', $orderItemIds);
							$return['acc_' . $account_ky]['duplicate_orders']['subject'] = 'Buyer Verification for Duplicate Orders ';
							$return['acc_' . $account_ky]['duplicate_orders']['ticket_content'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile), $template);
							$return['acc_' . $account_ky]['duplicate_orders']['html'] = str_replace(array('{order_item_ids_list}', '{nickname}', '{firm}', '{callback}', '\n'), array($list, $current_user['user_nickname'], $accounts[$account_key]->fk_account_name, $accounts[$account_key]->account_mobile, '<br/>'), $template);
							$return['acc_' . $account_ky]['duplicate_orders']['issueType'] = 'I_HAVE_RECEIVED_A_DUPLICATE_ORDER';
						}
					}
					echo json_encode(array("accounts" => $return));
					break;
			}

			break;

		case 'create_labels':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";
			if ($shipment_ids == "")
				return;

			$success = 0;
			$error = 0;
			$acc_orders = array();
			$error_details = array();
			$success_shipment_ids = array();

			// find account id to construct fk
			foreach (array_unique($shipment_ids) as $shipment_id) {
				$db->where('shipmentId', $shipment_id);
				$order = $db->getOne(TBL_FK_ORDERS);
				$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
			}

			foreach ($acc_orders as $acc => $orders) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					$account_ky = (int) str_replace('acc-', '', $acc);
					if ($account_ky == $account->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);
				$orders_item_ids = array_chunk($orders, 20);
				foreach ($orders_item_ids as $order_item_ids) {
					$returns = $fk->pack_orders($order_item_ids);
					$returns = json_decode($returns);
					foreach ($returns->shipments as $return) {
						if ($return->status == "SUCCESS") {
							$success_shipment_ids[] = $return->shipmentId;
							$success++;
						} else {
							$error++;
							$error_details[$return->shipmentId] = $return->errorMessage;
						}
					}
				}
			}

			$return = array('success' => $success, 'success_shipment_ids' => implode(',', $success_shipment_ids),  'error' => $error, 'error_details' => $error_details);
			echo json_encode($return);

			break;

		case 'mark_to_pack':
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";
			if ($shipment_ids == "")
				return;

			$success = 0;
			$error = 0;
			$cancel = 0;
			$acc_orders = array();

			// find account id to construct fk
			foreach (array_unique($shipment_ids) as $shipment_id) {
				$db->where('shipmentId', $shipment_id);
				$order = $db->getOne(TBL_FK_ORDERS);
				$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
			}

			foreach ($acc_orders as $acc => $orders) {

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					$account_ky = (int) str_replace('acc-', '', $acc);
					if ($account_ky == $account->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);

				$orders_item_ids = array_chunk($orders, 20);
				foreach ($orders_item_ids as $order_item_ids) {
					$order_item_ids = implode(',', $order_item_ids);
					$order_details = $fk->get_order_shipment_details($order_item_ids);
					$orders = $fk->get_order_details($order_item_ids);
					// if (count($order_item_ids) === 1){
					// 	$new_orders = new stdClass();
					// 	$new_orders->orderItems = array($orders);
					// 	$orders = array($new_orders);
					// }

					usort($orders, "cmp");
					usort($order_details->shipments, "cmp_s");

					if ($order_details->shipments) {
						foreach ($order_details->shipments as $shipment) {
							foreach ($orders as $order) {
								$return = $fk->update_order_to_pack($shipment->shipmentId, $shipment, $order);
								if ($return == "success")
									$success++;
								else if ($return == "cancel")
									$cancel++;
								else {
									$error_reasons[] = $return;
									$error++;
								}
							}
						}
					} else {
						$error_reasons[] = 'Shipment details not found';
						$error++;
					}
				}
			}

			$return = array('success' => $success, 'error' => $error, 'cancel' => $cancel, 'error_reasons' => $error_reasons);
			echo json_encode($return);

			break;

		case 'mark_to_cancel':
			// Cancel by Order ID
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";
			if ($shipment_ids == "")
				return;

			$success = 0;
			$error = 0;

			foreach (array_unique($shipment_ids) as $shipment_id) {
				$db->where('shipmentId', $shipment_id);
				$order = $db->ObjectBuilder()->getOne(TBL_FK_ORDERS);
				$selected_account = $accounts[$order->account_id - 1];
				$fk = new connector_flipkart($selected_account);
				$return = $fk->update_order_to_cancel(NULL, $shipment_id);
				if ($return['type'] == "success")
					$success++;
				else
					$error++;
			}

			$return = array('success' => $success, 'error' => $error);
			echo json_encode($return);

			break;

		case 'mark_rtd':
			$trackin_id = isset($_REQUEST['trackin_id']) ? trim($_REQUEST['trackin_id']) : "";
			$account = isset($_REQUEST['account']) ? $_REQUEST['account'] : "";
			if ($trackin_id == "" && $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$responses = $fk->update_orders_to_rtd($trackin_id);
			$i = 0;
			$return = array();
			foreach ($responses as $response) {
				$return[$i]['type'] = "error";
				if ($response['type'] == "success") {
					$return[$i]['type'] = "success";
				}
				$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
				$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
				$return[$i]['content'] = '
				<li class="order-item-card">
					<div class="order-title">' . $title . '</div>
					<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
					<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $trackin_id . '</span></div>
					<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
				</li>';
				$i++;
			}
			echo json_encode($return);
			break;

		case 'mark_shipped':
			$trackin_id = isset($_REQUEST['trackin_id']) ? $_REQUEST['trackin_id'] : "";
			$account = isset($_REQUEST['account']) ? (int)$_REQUEST['account'] : "";
			if ($trackin_id == "" || $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$response = $fk->update_orders_to_shipped($trackin_id);

			$return['type'] = "error";
			if ($response['type'] == "success") {
				$return['type'] = "success";
			}
			$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
			$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
			$return['content'] = '
			<li class="order-item-card">
				<div class="order-title">' . $title . '</div>
				<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
				<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $trackin_id . '</span></div>
				<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
			</li>';
			echo json_encode($return);

			break;

		case 'mark_cancel':
			// Cancel by Tracking ID
			$trackin_id = isset($_REQUEST['trackin_id']) ? $_REQUEST['trackin_id'] : "";
			$account = isset($_REQUEST['account']) ? (int)$_REQUEST['account'] : "";
			if ($trackin_id == "" && $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$response = $fk->update_order_to_cancel($trackin_id, null, "all");
			$return['type'] = "error";
			if ($response['type'] == "success") {
				$return['type'] = "success";
			}
			$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
			$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
			$return['content'] = '
			<li class="order-item-card">
				<div class="order-title">' . $title . '</div>
				<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
				<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $trackin_id . '</span></div>
				<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
			</li>';
			echo json_encode($return);

			break;

		case 'sync':

			$account = isset($_REQUEST['account']) ? (int)$_REQUEST['account'] : "";
			$order_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";
			if ($account == "" || $order_type == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id === $selected_account) {
					$account_key = $a_key;
					break;
				}
			}

			if (empty($account_key) && $account_key !== 0)
				exit('No active account found');

			if ($accounts[$account_key]->sandbox)
				break;

			switch ($order_type) {
				case 'new':
					$fk = new connector_flipkart($accounts[$account_key]);
					$fk->get_orders($order_type);
					break;

					// case 'packing':
					// 	$fk = new connector_flipkart($accounts[$account_key]);
					// 	$fk->get_orders($order_type);
					// 	break;
			}
			break;

		case 'update_status':
			$shipment_ids = isset($_REQUEST['shipment_ids']) ? explode(',', $_REQUEST['shipment_ids']) : "";

			$acc_orders = array();
			// find account id to construct fk
			foreach (array_unique($shipment_ids) as $shipment_id) {
				$db->where('shipmentId', $shipment_id);
				$order = $db->getOne(TBL_FK_ORDERS);
				$acc_orders["acc-" . ($order['account_id'])][] = $shipment_id;
			}

			foreach ($acc_orders as $acc => $orders) {

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					$account_ky = (int) str_replace('acc-', '', $acc);
					if ($account_ky == $account->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);

				foreach ($orders as $orderItemId) {
					$fk->update_order_status($orderItemId);
				}
			}
			break;

			// RETURNS
		case 'get_returns':
			ignore_user_abort(true); // optional
			foreach ($accounts as $account) {
				if ($account->sandbox)
					continue;
				$fk = new flipkart_dashboard($account);
				$fk->get_returns();
			}
			break;

		case 'update_created_returns':
			ignore_user_abort(true); // optional
			// CREATED
			foreach ($accounts as $account) {
				$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
				$db->where('r.r_status', 'created');
				$db->where('r.r_trackingId', NULL, 'IS');
				$db->where('o.account_id', $account->account_id);
				if ($orders = $db->get(TBL_FK_RETURNS . ' r', null, "r.returnId")) {
					$orders_chunk = array_chunk($orders, 20);
					$fk = new flipkart_dashboard($account);
					foreach ($orders_chunk as $order_chunk) {
						$s = implode(',', array_column($order_chunk, 'returnId'));
						$returns = $fk->get_return_by_id($s);
						foreach ($returns->returnItems as $return) {
							$fk->update_return_orders(array($return), $return->type);
						}
					}
				}
			}

			// PICKED UP/IN TRANSIT OR OUT FOR DELIVERY
			foreach ($accounts as $account) {
				$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
				$db->where("(r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus IS NULL)", array("start", "in_transit", "out_for_delivery", "delivered"));
				$db->where('account_id', $account->account_id);
				if ($orders = $db->get(TBL_FK_RETURNS . ' r', null, "r.returnId, r.r_shipmentStatus, r_status")) {
					$orders_chunk = array_chunk($orders, 20);
					$fk = new flipkart_dashboard($account);
					foreach ($orders_chunk as $order_chunk) {
						$s = implode(',', array_column($order_chunk, 'returnId'));
						$returns = $fk->get_return_by_id($s);
						foreach ($returns->returnItems as $return) {
							$fk->update_return_orders(array($return), $return->type);
						}
					}
				}
			}

			break;

		case 'update_return_delivered_date':
			ignore_user_abort(true); // optional
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			echo '<pre>';
			foreach ($accounts as $account) {
				if ($account->sandbox)
					continue;
				$fk = new flipkart_dashboard($account);

				$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
				$db->where("(r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus LIKE ? OR r.r_shipmentStatus = ?)", array("return_received", "delivered", "return_claimed%", "return_unexpected"));
				$db->where("(r.r_deliveredDate IS NULL OR r.r_deliveredDate = ?)", array('1970-01-01 05:30:00'));
				$db->where('o.account_id', $account->account_id);
				$db->orderBy('insertDate', 'DESC');
				if ($returns = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, "r.returnId, r.r_shipmentStatus, r.r_status, r.insertDate")) {
					echo '<br /><br />' . $account->account_name . '<br />';
					if ($returns) {
						foreach ($returns as $return) {
							$return_id = $return->returnId;
							$return_details = $fk->get_returns_details($return_id);

							if (empty($return_details)) {
								echo "'" . $return_id . ' Return detail not found<br />';
								continue;
							}

							$status = $return_details[0]->shipment_status;
							$delivered_date = $return_details[0]->completed_date;
							if (is_null($delivered_date)) {
								echo "'" . $return_id . ' Return not completed.<br />';
								continue;
							}
							$details = array('r_deliveredDate' => date('Y-m-d H:i:s', strtotime($delivered_date)));

							$db->where('returnId', $return_id);
							if ($db->update(TBL_FK_RETURNS, $details))
								echo "'" . $return_id . '	' . date('Y-m-d H:i:s', strtotime($delivered_date)) . '<br />';
							else
								echo "'" . $return_id . ' Unable to update return<br />';
						}
					} else {
						echo "No returns found<br />";
					}
				}
			}
			break;

		case 'update_return_null_action_shipmentStatus':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			echo '<pre>';
			foreach ($accounts as $account) {
				if ($account->sandbox)
					continue;
				$fk = new flipkart_dashboard($account);

				$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
				$db->where("(r.r_action = ? AND r.r_shipmentStatus IS NULL)", array(''));
				$db->where('o.account_id', $account->account_id);
				$db->orderBy('insertDate', 'DESC');
				if ($returns = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, "r.returnId, r.r_shipmentStatus, r.r_status, r.insertDate")) {
					echo $db->getLastQuery() . '<br />';
					echo $account->account_name . '<br />';
					var_dump($returns);
					echo '<br />';
					continue;
					if ($returns) {
						foreach ($returns as $return) {
							$return_id = $return->returnId;
							$return_details = $fk->get_returns_details($return_id);
							var_dump($return_details);
							exit;

							if (empty($return_details)) {
								echo "'" . $return_id . ' Return detail not found<br />';
								continue;
							}
						}
					}
				} else {
					echo $db->getLastQuery() . '<br />';
					echo $account->account_name . '<br />';
					echo '<br />';
				}
			}
			break;

		case 'mark_return_received':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$trackin_id = isset($_REQUEST['trackin_id']) ? $_REQUEST['trackin_id'] : "";
			if ($trackin_id == "")
				return;

			$selected_account = $accounts[0]; // Use any account as we are searching by return ID
			$fk = new connector_flipkart($selected_account);
			$responses = $fk->update_return_received($trackin_id, 'received');
			$i = 0;
			$return = array();
			foreach ($responses as $response) {
				$return[$i]['type'] = "error";
				if ($response['type'] == "success") {
					$return[$i]['type'] = "success";
				}
				$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
				$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
				$return[$i]['content'] = '
				<li class="order-item-card">
					<div class="order-title">' . $title . '</div>
					<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
					<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $trackin_id . '</span></div>
					<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
				</li>';
				$i++;
			}
			echo json_encode($return);

			break;

		case 'mark_return_complete': // SCAN AND COMPLETED RECEIVED NON DISPUTED RETURNS
			$trackin_id = isset($_REQUEST['trackin_id']) ? $_REQUEST['trackin_id'] : "";
			$account = isset($_REQUEST['account']) ? (int)$_REQUEST['account'] : "";
			if ($trackin_id == "" && $account == "")
				return;

			$selected_account = $accounts[0];
			$fk = new connector_flipkart($selected_account);
			$responses = $fk->update_return_received($trackin_id, 'completed');
			$i = 0;
			$return = array();
			foreach ($responses as $response) {
				$return[$i]['type'] = "error";
				if ($response['type'] == "success") {
					$return[$i]['type'] = "success";
				}
				$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
				$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
				$return[$i]['content'] = '
				<li class="order-item-card">
					<div class="order-title">' . $title . '</div>
					<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
					<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $trackin_id . '</span></div>
					<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
				</li>';
				$i++;
			}
			echo json_encode($return);

			break;

		case 'update_claim_status':
			$return_id = $_REQUEST['return_id'];
			$claim_id = trim($_REQUEST['claim_id']);
			$condition = json_decode($_REQUEST['condition'], true);
			$re_claim = trim($_REQUEST['re_claim']) == "re-claim" ? true : false;
			$uid = $_REQUEST['uid'];

			if (empty($return_id) || empty($condition) || (!in_array('missing', $condition) && $uid == "undefined")) {
				echo json_encode(array("type" => "error", "message" => 'Product UID not defined'));
				return;
			}

			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			$db->where('r.returnId', $return_id);

			if ($order = $db->ObjectBuilder()->getOne(TBL_FK_RETURNS . ' r', null, "o.*, r.*")) {

				// find account id to construct fk
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $order->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);

				$return = $fk->update_claim_status($order->orderItemId, $return_id, $claim_id, $condition, $uid, $re_claim);

				if (is_array($return)) {
					echo json_encode($return);
				} else {
					if ($return == 'success reclaim')
						echo json_encode(array("type" => "success", "message" => 'Successfully reclaimed with id ' . $claim_id));
					else if ($return == 'success')
						echo json_encode(array("type" => "success", "message" => 'Successfully created new claim with id ' . $claim_id));
					else if ($return == 'existing')
						echo json_encode(array("type" => "info", "message" => 'Claim with id ' . $claim_id . ' already existing'));
					else if ($return == 'error reclaim')
						echo json_encode(array("type" => "info", "message" => 'Unable to process reclaim request. Please retry.', "error" => $return));
					else
						echo json_encode(array("type" => "info", "message" => 'Unable to process request. Please retry.', "error" => $return));
				}
			}

			break;

		case 'update_claim_details':
			$return_id = $_REQUEST['return_id'];
			$claim_status = $_REQUEST['claim_status'];
			$claim_comments = $_REQUEST['claim_comments'];
			$claim_amount = $_REQUEST['claim_amount'];
			$received_on = $_REQUEST['received_on'];

			if (empty($return_id) || empty($claim_status) || empty($claim_comments))
				return;

			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "INNER");
			$db->where('r.returnId', $return_id);

			if ($order = $db->ObjectBuilder()->getOne(TBL_FK_RETURNS . ' r', null, "o.account_id, c.claimProductCondition")) {

				// find account id to construct fk
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $order->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);
				$return = $fk->update_claim_details($return_id, $claim_status, $claim_comments, $claim_amount, $received_on, "", $order->claimProductCondition);
				if ($return)
					echo json_encode(array("type" => "success", "message" => "Successfully updated claim."));
				else
					echo json_encode(array("type" => "error", "message" => "Unable to update claim. Please retry later."));
			}
			break;

		case 'update_claim_id':
			if (isset($_REQUEST['value']) && !empty($_REQUEST['value']) && isset($_REQUEST['pk']) && !empty($_REQUEST['pk'])) {
				$new_claim_id = trim($_REQUEST['value']);
				$return_id = $_REQUEST['pk'];
				if (isset($_REQUEST['approved_spf']) && $_REQUEST['approved_spf'] != "") {
					$db->where('returnId', $return_id);
					$db->update(TBL_FK_RETURNS, array('r_approvedSPFId' => $_REQUEST['approved_spf']));
				}

				$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
				$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "INNER");
				$db->where('r.returnId', $return_id);
				$db->where('c.claimStatus', 'pending');
				$order = $db->getOne(TBL_FK_RETURNS . ' r');
				$account_ky = $order['account_id'];
				$old_claim_id = $order['claimID'];
				$old_notes = $order['claimNotes'];

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account_ky == $account->account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);
				$return = $fk->update_claim_id($return_id, $new_claim_id, $old_claim_id, $old_notes);
				echo json_encode($return);
			} else {
				header('HTTP/1.0 400 Bad Request', true, 400);
				echo "Claim ID is required!";
			}
			break;

		case 'get_spf_amount':
			$orderItemId = isset($_REQUEST['orderItemId']) ? $_REQUEST['orderItemId'] : "";
			$accountName = isset($_REQUEST['accountName']) ? $_REQUEST['accountName'] : "";
			if ($orderItemId == "" && $account == "")
				return;

			// find account name to construct fk
			$selected_account = $accountName;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_name == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$spf = $fk->get_spf_amount($orderItemId);
			if ($spf['amount'] != 0) {
				$db->where('orderItemId', $orderItemId);
				$db->update(TBL_FK_RETURNS, array('r_approvedSPFAmt' => $spf['amount']));
			}
			echo json_encode($spf);
			break;

		case 'get_incident_status':
			$incidentId = isset($_REQUEST['incidentId']) ? $_REQUEST['incidentId'] : "";
			$accountName = isset($_REQUEST['accountName']) ? $_REQUEST['accountName'] : "";
			if ($incidentId == "" && $accountName == "")
				return;

			// find account name to construct fk
			$selected_account = $accountName;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_name == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$status = $fk->get_incident_status($incidentId);
			echo json_encode($status);
			break;

		case 'update_fk_incident_status':
			// echo "<pre>";
			ignore_user_abort(true); // optional
			$status = array();
			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				$fk = new flipkart_dashboard($account);
				$db->join(TBL_FK_ORDERS . " o", "c.orderItemId=o.orderItemId", "INNER");
				$db->joinWhere(TBL_FK_ORDERS . " o", 'o.account_id', $account->account_id);
				$db->where('c.claimStatus', 'pending');
				$db->where('(c.claimStatusFK != ?)', array('approved'));
				$db->orderBy('account_id', 'ASC');
				$incidents = $db->objectBuilder()->get(TBL_FK_CLAIMS . " c", NULL, array('DISTINCT(c.claimID) as claimID'));
				if ($incidents) {
					foreach ($incidents as $incident) {
						$status[$incident->claimID] = $fk->get_incident_status($incident->claimID);
					}
				}
			}

			$log->write(json_encode($status), 'claim-status-update');
			echo json_encode($status);
			break;

		case 'return_reconciliation':
			$account_id = $_GET['account_id'];
			$search_by = $_GET['search_by'];
			$search_value = $_GET['search_value'];

			if ($search_by == "r_trackingId") {
				$tracking_ids = explode(',', $_REQUEST['search_value']);
				foreach ($tracking_ids as $tracking_id) {
					if (!empty($tracking_id)) {
						$db->join(TBL_FK_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
						$db->joinWhere(TBL_FK_ORDERS . ' o', 'o.account_id', $account_id);
						$db->where('(o.pickupDetails LIKE ? OR o.deliveryDetails LIKE ? OR r.r_trackingId LIKE ?)', array('%' . trim($tracking_id) . '%', '%' . trim($tracking_id) . '%', trim($tracking_id)));
						$return = $db->objectBuilder()->getOne(TBL_FK_RETURNS . ' r', 'r.returnId, r.orderItemId, r.r_trackingId, o.pickupDetails, r.r_deliveredDate, r.r_receivedDate, o.status, r.r_shipmentStatus');
						if ($return) {
							$returns[] = $return;
						} else {
							$return = new stdClass();
							$return->r_trackingId = $tracking_id;
							$returns[] = $return;
						}
					}
				}
			} else {
				$db->join(TBL_FK_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
				$db->joinWhere(TBL_FK_ORDERS . ' o', 'o.account_id', $account_id);
				$db->where('r.' . $search_by, $search_value . '%', 'LIKE');
				$returns = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', null, 'r.returnId, r.orderItemId, r.r_trackingId, o.pickupDetails, r.r_deliveredDate, r.r_receivedDate, o.status, r.r_shipmentStatus');
			}
			if ($returns) {
				$output['data'] = '<hr /><table class="table table-bordered table-striped"><thead><tr><th>Return ID</th><th>OrderItem ID</th><th>Tracking ID</th><th>Forward Tracking ID</th><th>Delivered Date</th><th>Received Date</th><th>Order Status</th><th>Return Status</th></tr></thead><tbody>';
				foreach ($returns as $return) {
					$pickupDetails = json_decode($return->pickupDetails);
					$return->forwardTrackingId = $pickupDetails->trackingId;
					$output['data'] .= '<tr>';
					$output['data'] .= '<td>\'' . $return->returnId . '</td>';
					$output['data'] .= '<td>\'' . $return->orderItemId . '</td>';
					$output['data'] .= '<td>' . $return->r_trackingId . '</td>';
					$output['data'] .= '<td>' . $return->forwardTrackingId . '</td>';
					$output['data'] .= '<td>' . (empty($return->r_deliveredDate) ? 'Not Delivered' : date('Y-m-d H:m', strtotime($return->r_deliveredDate))) . '</td>';
					$output['data'] .= '<td>' . (empty($return->r_receivedDate) ? 'Not Received' : date('Y-m-d H:m', strtotime($return->r_receivedDate))) . '</td>';
					$output['data'] .= '<td>' . $return->status . '</td>';
					$output['data'] .= '<td>' . str_replace('_', ' ', strtoupper($return->r_shipmentStatus)) . '</td>';
					$output['data'] .= '</tr>';
				}
				$output['data'] .= '</tbody></table>';
				$output['type'] = 'success';
			} else {
				$output['type'] = 'error';
				$output['data'] = '<hr/><center>No data found</center>';
			}

			echo json_encode($output);
			break;

		case 'get_return_pod':
			$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : "";
			$logistic_name = isset($_GET['logistic_name']) ? $_GET['logistic_name'] : "";
			$pod_date = isset($_GET['pod_date']) ? $_GET['pod_date'] : "";
			if ($logistic_name == "" || $pod_date == "" || $account_id == "")
				return;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$selected_account = $accounts[$account_key];

			$fk = new flipkart_dashboard($selected_account);
			$pod = $fk->get_digital_pod($pod_date, $logistic_name);

			if ($pod['type'] == "error")
				exit($pod['msg']);

			$path = ROOT_PATH . '/labels/POD';
			$file = str_replace('-', '', $pod_date) . '_bulk_pod_' . $selected_account->account_name . '.pdf';

			// save PDF buffer
			file_put_contents($path . '/' . $file, $pod['data']);

			// ensure we don't have any previous output
			if (headers_sent()) {
				exit("PDF stream will be corrupted - there is already output from previous code.");
			}

			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($path . '/' . $file) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($path . '/' . $file));
			readfile($path . '/' . $file);
			unlink($path . '/' . $file); // delete temp file
			exit(); // make sure stream ended
			break;

		case 'search_return':
			$trackin_id = $_REQUEST['trackin_id'];
			$account = $_REQUEST['account'];

			if (empty($trackin_id))
				return;

			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
			$db->where('(r.r_trackingId = ? OR o.forwardTrackingId = ?) AND (r.r_shipmentStatus = ? OR r.r_shipmentStatus = ?)', array($trackin_id, $trackin_id, 'return_received', 'return_claimed'));
			$order = $db->ObjectBuilder()->getOne(TBL_FK_RETURNS . ' r', array("o.account_id", "o.costPrice", "o.fsn", "o.invoiceAmount", "o.locationId", "o.orderDate", "o.orderId", "o.orderItemId", "o.order_type", "o.ordered_quantity", "o.paymentType", "o.quantity", "o.sellingPrice", "o.sku", "o.title", "o.totalPrice", "o.uid", "r.r_action", "r.r_comments", "r.r_createdDate", "r.r_deliveredDate", "r.r_quantity", "r.r_reason", "r.r_receivedDate", "r.r_shipmentStatus", "r.r_source", "r.r_status", "r.r_subReason", "r.r_trackingId", "r.r_uid", "r.returnId", "CONCAT('" . IMAGE_URL . "/uploads/products/', COALESCE(pc.thumb_image_url, pm.thumb_image_url)) as productImage", "c.categoryName", 'pc.cid'));

			if (!$order) {
				echo json_encode(array("type" => "error", "message" => "No Return found with Return Received Status."));
				// } else if ($orzencode(array("type" => "error", "message" => "Return already claimed."));
			} else if ($order->r_status == "return_completed") {
				echo json_encode(array("type" => "error", "message" => "Return already completed."));
			} else {
				$is_combo = is_null($order->cid) ? ($order->r_quantity > 1 ? true : false) : true;
				$order->is_combo = $is_combo;
				$uid_select = "";
				// GET CATEGORY ISSUES 
				$damage_issues = json_decode(get_option('return_damage_product_issues'));
				if ($is_combo)
					$wrong_issues = json_decode(get_option('return_wrong_product_issues'));
				// POPULATE DATA
				$uids = json_decode($order->uid);
				$html = "";
				$uid_select .= '<div class="form-group"><label class="col-md-2 control-label">Product UID<span class="required" aria-required="true">*</span></label><div class="col-md-10 checkbox-list"><div class="row">';
				foreach ($uids as $uid) {
					$saleable_disabled = "";
					$uid_selected = "";
					$uid_readonly = "";
					$parent_hide = "hide";
					$rto_disabled = "disabled";
					if ($order->r_source == "courier_return" || $is_combo) {
						if (!$is_combo)
							$saleable_disabled = "disabled";
						$uid_readonly = "readonly";
						$uid_selected = "checked";
						$parent_hide = "";
						$rto_disabled = "";
					}

					$uid_select .= '<div class="col-md-3"><label class="checkbox-inline"><input class="checkboxes" data-msg="Please select atleast one UID" data-error-container=".form_uid_error" type="checkbox" name="uids[]" required ' . $uid_selected . ' ' . $uid_readonly . ' value="' . $uid . '"> ' . $uid . ' </label></div>';

					$dyn_issues = "";
					foreach ($damage_issues->{strtolower($order->categoryName)} as $issue) {
						$dyn_issues .= '<div class="col-md-3">
													<label class="checkbox-inline">
													<input class="checkboxes issue_checkboxes" data-msg="Please select atleast one Damage Type" data-error-container=".form_damage_product_error_' . $uid . '" type="checkbox" name="' . $uid . '[damaged][]" data-group="' . $uid . '" disabled value="' . $issue . '"> ' . $issue . ' </label>
												</div>';
					}

					$wrong_issue_form_group = "";
					if (!empty($wrong_issues)) {
						$wrng_issues = "";
						foreach ($wrong_issues->{strtolower($order->categoryName)} as $issue) {
							$wrng_issues .= '<div class="col-md-3">
														<label class="radio-inline">
															<input class="radio issue_checkboxes" data-msg="Please select atleast one Wrong Type" data-error-container=".form_wrong_product_error_' . $uid . '" type="radio" name="' . $uid . '[wrong][]" data-group="' . $uid . '" disabled value="' . $issue . '"> ' . $issue . ' 
														</label>
													</div>';
						}

						$wrong_issue_form_group = '<div class="form-group issue_product_list_group_' . $uid . ' wrong_product_list_group_' . $uid . ' hide">
									<label class="col-md-2 control-label">Wrong Type<span class="required">*</span></label>
									<div class="col-md-10 checkbox-list">
										<div class="row">
											' . $wrng_issues . '
										</div>
									</div>
									<div class="form_wrong_product_error_' . $uid . ' col-md-12 col-md-offset-2"></div>
								</div>';
					}

					$html .= '
							<fieldset class="spf_pid_fieldset fieldset_' . $uid . ' ' . $parent_hide . '">
								<legend>' . $uid . '</legend>
								<div class="form-group product_condition_list product_condition_list_group_' . $uid . '">
									<label class="col-md-2 control-label">Product Condition<span class="required">*</span></label>
									<div class="col-md-10 radio-list">
										<div class="row">
											<div class="col-md-3">
												<label class="radio-inline">
													<input class="radio" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_' . $uid . '" type="radio" name="' . $uid . '[product_condition]" data-group="' . $uid . '" value="saleable" ' . $saleable_disabled . ' required/>Saleable
												</label>
											</div>
											<div class="col-md-3">
												<label class="radio-inline">
													<input class="radio" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_' . $uid . '" type="radio" name="' . $uid . '[product_condition]" data-group="' . $uid . '" value="damaged" ' . $rto_disabled . ' required />Damage
												</label>
											</div>
											<div class="col-md-3">
												<label class="radio-inline">
													<input class="radio" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_' . $uid . '" type="radio" name="' . $uid . '[product_condition]" data-group="' . $uid . '" value="wrong" ' . $rto_disabled . ' required />Wrong
												</label>
											</div>
											<div class="col-md-3">
												<label class="radio-inline">
													<input class="radio" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_' . $uid . '" type="radio" name="' . $uid . '[product_condition]" data-group="' . $uid . '" value="missing" ' . $rto_disabled . ' required />Missing
												</label>
											</div>
										</div>
									</div>
									<div class="form_product_condition_error_' . $uid . ' col-md-12 col-md-offset-2"></div>
								</div>
								<div class="form-group issue_product_list_group_' . $uid . ' damaged_product_list_group_' . $uid . ' hide">
									<label class="col-md-2 control-label">Damage Type<span class="required">*</span></label>
									<div class="col-md-10 checkbox-list">
										<div class="row">
											' . $dyn_issues . '
										</div>
									</div>
									<div class="form_damage_product_error_' . $uid . ' col-md-12 col-md-offset-2"></div>
								</div>
								' . $wrong_issue_form_group . '
							</fieldset>';
				}
				$uid_select .= '</div><div class="form_uid_error"></div></div></div>';
				echo json_encode(array('order' => $order, 'product_uids' => $uid_select, 'data' => $html));
			}

			break;

		case 'spf_generate':
			switch ($_REQUEST['type']) {
				case 'upload_image_attachements':
					$account_id = isset($_GET['account_id']) ? $_GET['account_id'] : "";

					$account_key = "";
					foreach ($accounts as $a_key => $account) {
						if ($account->account_id == $account_id)
							$account_key = $a_key;
					}

					$selected_account = $accounts[$account_key];
					$fk = new flipkart_dashboard($selected_account);
					$response = $fk->create_spf_attachements($_FILES); // BINARY
					if (isset($response->url)) {
						echo json_encode(array('type' => 'success', 'url' => $response->url));
					} else {
						header("HTTP/1.0 400 Bad Request");
						echo json_encode(array('type' => 'error', 'message' => $response->message, 'error' => $response->code));
					}
					break;

				case 'create_spf':
					$mandatory_fields['return_id'] = $return_id = $_REQUEST['return_id'];
					$uids = $_REQUEST['uids'];
					$product_issue = array();
					$r_uid = array();
					if ($_REQUEST['spf_files'] == "[]" || empty($_REQUEST['spf_files'] || !isset($_REQUEST['spf_files']))) {
						echo json_encode(array('type' => 'error', 'message' => 'Cannot get attachments. Please refresh the page and try again.'));
						return;
					}
					foreach ($uids as $uid) {
						$r_uid[$uid] = $_REQUEST[$uid]['product_condition'];
						if ($_REQUEST[$uid]['product_condition'] == "saleable") // REQUIRED FOR COMBO PRODUCTS
							continue;
						$mandatory_fields['product_condition'] = $product_condition = $_REQUEST[$uid]['product_condition'];
						if (isset($_REQUEST{$uid}[$product_condition]))
							$product_issue = array_merge($product_issue, $_REQUEST{$uid}[$product_condition]);
						else
							$product_issue = array_merge($product_issue, array($product_condition));
					}
					$mandatory_fields['order_id'] = $order_id = $_REQUEST['order_id'];
					$mandatory_fields['order_item_id'] = $order_item_id = $_REQUEST['order_item_id'];
					$mandatory_fields['tracking_id'] = $tracking_id = $_REQUEST['tracking_id'];
					$account_id = $_REQUEST['account_id'];
					$mandatory_fields['attachments'] = $_REQUEST['spf_files'];
					$mandatory_fields['is_combo'] = $is_combo = filter_var($_REQUEST['is_combo'], FILTER_VALIDATE_BOOLEAN);
					$mandatory_fields['product_type'] = 'Main Product';
					if ($is_combo)
						$mandatory_fields['product_type'] = "Combo Products";

					$selected_account = "";
					foreach ($accounts as $a_key => $account) {
						if ($account->account_id == $account_id)
							$selected_account = $accounts[$a_key];
					}

					$fk = new flipkart_dashboard($selected_account);
					$return_details = $fk->get_returns_details($return_id);
					$mandatory_fields['is_return_delivered'] = true;
					if (!isset($return_details->error)) {
						$mandatory_fields['is_return_delivered'] = false;
						if (isset($return_details[0]->completed_date) && !is_null($return_details[0]->completed_date))
							$mandatory_fields['is_return_delivered'] = true;
					}

					$content = "";
					$subject = "";
					if ($product_condition == "damaged") {
						if (in_array("Scratches on Dial", $product_issue) || in_array("Scratches on Belt", $product_issue) || in_array("Used & Dusty Dial", $product_issue) || in_array("Dusty Belt", $product_issue) || in_array("Rust marks", $product_issue))
							$subject = 'I received a used product';

						if (in_array("Belt Cut", $product_issue) || in_array("Belt Short", $product_issue) || in_array("Water Damage", $product_issue) || in_array("Display Damage", $product_issue) || in_array("Physical Damage", $product_issue) || in_array("Physical Tempering", $product_issue))
							$subject = "I received a damaged product";

						if (in_array("Missing Glass", $product_issue) || in_array("Missing Top Ring", $product_issue) || in_array("Missing Back Cover", $product_issue) || in_array("Missing Crown", $product_issue) || in_array("Missing Button", $product_issue) || in_array("Missing Box", $product_issue) || in_array("Missing Manual", $product_issue) || in_array("Missing Brand Tag", $product_issue) || in_array("Missing Product Tag", $product_issue))
							$subject = "One of the components was missing from product";
					}

					if ($product_condition == "wrong") {
						if (in_array("fake", $product_issue))
							$subject = "I received a fake Product. ";

						if (in_array("missing", $product_issue))
							$subject .= "Product was missing from the received return. ";

						if (in_array("wrong", $product_issue))
							$subject .= "I received a wrong product";

						if (!$is_combo)
							$product_issue[] = $product_condition . ' product shipment received';
					}

					$rto_video_urls = "";
					if ($_REQUEST['return_type'] == "courier_return" && ($product_condition == "wrong" || $product_condition == "missing")) {
						if (strpos($_REQUEST['forward_url'], 'https://www.dropbox.com/s') === FALSE || strpos($_REQUEST['return_url'], 'https://www.dropbox.com/s') === FALSE) {
							echo json_encode(array('type' => 'error', 'message' => 'Video URL invalid.'));
							return;
						}

						$subject = "I received a wrong product";
						$product_issue[] = 'Wrong Product';
						$rto_video_urls = '\nForward URL: ' . $_REQUEST['forward_url'] . '\nReturn URL: ' . $_REQUEST['return_url'] . '\n\n';
					}

					if ($product_condition == "missing") {
						$subject = "Product was missing from the received return";
						$product_issue[] = 'Missing Product';
					}

					$content = 'Dear Team, \n\nOrder ID: ' . $order_id . '\nItem ID: ' . $order_item_id . '\nReturn ID: ' . $return_id . '\nSPF Reason: ' . implode(' + ', $product_issue) . '\n' . $rto_video_urls . 'Kindly reimburse the loss.\n\nRegards, \nORM Team (' . $current_user['user_nickname'] . ')';

					if (empty($subject) || empty($content)) {
						echo json_encode(array('type' => 'error', 'message' => 'Required field not set.'));
						return;
					}

					$response = $fk->create_ticket('SPF Claim', $mandatory_fields, $subject, $content);
					if ($response['type'] == "success") {
						$r_uid = json_encode($r_uid);
						$db->where('returnId', $return_id);
						if ($db->update(TBL_FK_RETURNS, array('r_uid' => $r_uid, 'r_status' => 'return_claimed', 'r_shipmentStatus' => 'return_claimed'))) {
							echo json_encode(array("type" => "success", "message" => "Claim Incident successfully created with id " . $response['incidentId']));
							$log->write('CLAIM: New claim logged for Return ID ' . $return_id . ' and Claim ID ' . $response['incidentId'], 'return-claims');
							$log->write('OI: ' . $order_item_id . "\tReturn Claimed", 'order-status');
						}
					} else {
						echo json_encode($response);
					}
					break;
			}
			break;

		case 'search_order':
			$search_key = $_REQUEST['key'];
			$search_by = $_REQUEST['by'];

			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->joinWhere('r.r_expectedDate', NULL, 'IS NOT'); // Delivery not expected. Promised qty not delivered/
			// $db->join(TBL_FK_PAYMENTS ." pa", "pa.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");

			if ($search_by == "uid") {
				$db->where('o.uid', '%' . $search_key . '%', 'LIKE');
			} else if ($search_by == 'trackingId') {
				$db->where('o.pickupDetails', '%' . $search_key . '%', 'LIKE');
				$db->orWhere('o.deliveryDetails', '%' . $search_key . '%', 'LIKE');
				$db->orWhere('r.r_trackingId', '%' . $search_key . '%', 'LIKE');
			} else if ($search_by == 'returnId') {
				$db->where('r.' . $search_by, $search_key);
			} else if ($search_by == 'claimID') {
				$db->where('c.' . $search_by, '%' . $search_key . '%', 'LIKE');
				$db->orWhere('c.claimNotes', '%' . $search_key . '%', 'LIKE');
				$db->orWhere('o.settlementNotes', '%' . $search_key . '%', 'LIKE');
			} else {
				$db->where('o.' . $search_by, $search_key);
			}

			$db->orderBy('o.insertDate', "DESC");
			$db->orderBy('r.insertDate', "DESC");
			// $db->groupBy('OID');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', null, "o.orderItemId as OID, r.returnId as RID, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, o.*, r.*, c.*, COALESCE(p.corrected_sku, p.sku) as sku");
			// echo $db->getLastQuery();
			// exit;
			// var_dump($orders);

			$return['type'] = "success";
			$return['content'] = "";
			$collapse_id = 1;
			foreach ($orders as $order) {
				$order_type = $order->replacementOrder ? 'Replacement Order' : strtoupper($order->paymentType);
				$address = json_decode($order->deliveryAddress);
				$pickupDetails = json_decode($order->pickupDetails);
				$deliveryDetails = json_decode($order->deliveryDetails);
				$on_hold = ($order->hold ? '<i class="fas fa-hand-paper"></i>' : '');
				$flag_class = ($order->is_flagged == true ? ' active' : '');
				$rma_id = "";
				$active_incidents = array();

				$db->where("referenceId", "%" . $order->OID . "%", "LIKE");
				$db->where('incidentStatus', 'active');
				$incidents = $db->objectBuilder()->get(TBL_FK_INCIDENTS, NULL, 'incidentId');
				if (!is_null($incidents)) {
					foreach ($incidents as $incident) {
						$active_incidents[] = sprintf('<a href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/%s/SELLER_SELF_SERVE" target="_blank">%s</a>', $incident->incidentId, $incident->incidentId);
					}
				}

				$uid_links = array();
				if (!is_null($order->uid) && $order->order_type != "FBF") {
					foreach (json_decode($order->uid, true) as $uid) {
						$uid_links[] = sprintf('<a href="' . BASE_URL . '/inventory/history.php?uid=%s" target="_blank">%s</a>', $uid, $uid);
					}
				}

				$account = findObjectById($accounts, 'account_id', $order->account_id);

				if (strpos($deliveryDetails->vendorName, 'E-Kart') !== FALSE)
					$delivery_link = 'https://www.ekartlogistics.com/shipmenttrack/' . $deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Delhivery') !== FALSE)
					$delivery_link = 'https://www.delhivery.com/track/package/' . $deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Ecom') !== FALSE)
					$delivery_link = 'https://ecomexpress.in/tracking/?awb_field=' . $deliveryDetails->trackingId;
				elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
					$delivery_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos=' . $deliveryDetails->trackingId;

				if (strtolower($order->status) != "new") {
					$logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $deliveryDetails->vendorName . '</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $delivery_link . '" target="_blank">' . $deliveryDetails->trackingId . '</a></span>
																	</div>';

					if ($pickupDetails->trackingId != $deliveryDetails->trackingId) {
						if (strpos($pickupDetails->vendorName, 'E-Kart') !== FALSE)
							$pickup_link = 'https://www.ekartlogistics.com/shipmenttrack/' . $pickupDetails->trackingId;
						elseif (strpos($pickupDetails->vendorName, 'Delhivery') !== FALSE)
							$pickup_link = 'https://www.delhivery.com/track/package/' . $pickupDetails->trackingId;
						elseif (strpos($pickupDetails->vendorName, 'Ecom') !== FALSE)
							$pickup_link = 'https://ecomexpress.in/tracking/?awb_field=' . $pickupDetails->trackingId;
						elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
							$pickup_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos=' . $pickupDetails->trackingId;

						$logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Pickup Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $pickupDetails->vendorName . '</span>
																		<span class="card-label">Pickup Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $pickup_link . '" target="_blank">' . $pickupDetails->trackingId . '</a></span>
																	</div>
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $deliveryDetails->vendorName . '</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $delivery_link . '" target="_blank">' . $deliveryDetails->trackingId . '</a></span>
																	</div>';
					}

					if ($order->order_type == "FBF") {
						$logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Fulfilment Location:&nbsp;</span>
																		<span class="order_date">' . ucwords(str_replace('_', ' ', $order->locationId)) . '</span>
																	</div>';
					}

					$delivery_details = '<div class="col-md-12 sub-header">
																		<span class="card-label">Delivery Details:&nbsp;</span>
																		<span class="order_date">' . $address->firstName . ' ' . $address->lastName . ', ' . $address->city . ', ' . $address->state . '</span>
																		<span class="card-label">Contact No.:&nbsp;</span>
																		<span class="order_date">' . (empty($address->contactNumber) ? "NA" : "+91" . $address->contactNumber) . '</span>
																	</div>';
				}
				$order_returns = '<dt class="font-rlight font-rgrey">No Return Details Found</dt>';
				if ($order->RID != NULL) {
					$order->r_completionDate = ($order->r_shipmentStatus == "return_cancelled") ? $order->r_cancellationDate : $order->r_completionDate;
					$r_uid_links = array();
					if (!is_null($order->r_uid) && $order->order_type != "FBF") {
						foreach (json_decode($order->r_uid, true) as $uid => $condition) {
							$r_uid_links[] = sprintf('<a class="tooltips" data-placement="right" data-original-title="%s" href="' . BASE_URL . '/inventory/history.php?uid=%s" target="_blank">%s</a>', ucfirst($condition), $uid, $uid);
						}
					}
					$order_returns = '
																	<dl><dt class="font-rlight font-rgrey">Return Status</dt>
																	<dd class="r_status">' . ($order->r_shipmentStatus == "" ? "NA" : strtoupper($order->r_shipmentStatus)) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Action</dt>
																	<dd class="r_sub_reason">' . ($order->r_action == "" ? "NA" : strtoupper($order->r_action)) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Tracking ID</dt>
																	<dd class="r_sub_reason">' . ($order->r_trackingId == "" ? "NA" : $order->r_trackingId) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return ID</dt>
																	<dd class="r_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/return_orders/' . $account->locationId . '/search?type=return_id&id=' . $order->RID . '">' . $order->RID . '</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Created Date</dt>
																	<dd class="r_sub_reason">' . (($order->r_createdDate == "" || is_null($order->r_createdDate)) ? "NA" : date("M d, Y h:i A", strtotime($order->r_createdDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Delivered Date</dt>
																	<dd class="r_sub_reason">' . (($order->r_deliveredDate == "" || is_null($order->r_deliveredDate)) ? "NA" : date("M d, Y h:i A", strtotime($order->r_deliveredDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Received Date</dt>
																	<dd class="r_sub_reason">' . (($order->r_receivedDate == "" || is_null($order->r_receivedDate)) ? "NA" : date("M d, Y h:i A", strtotime($order->r_receivedDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Completed Date</dt>
																	<dd class="r_sub_reason">' . (($order->r_completionDate == "" || is_null($order->r_completionDate)) ? "NA" : date("M d, Y h:i A", strtotime($order->r_completionDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return UID</dt>
																	<dd class="r_status">' . (!empty($r_uid_links) ? implode(', ', $r_uid_links) : "NA") . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Type</dt>
																	<dd class="r_status">' . $order->r_source . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="r_reason">' . $order->r_reason . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="r_sub_reason">' . $order->r_subReason . '</dd></dl>
																	<dl><dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="r_comment">' . str_replace('\n', "<br />", $order->r_comments) . '</dd></dl>';
				}

				$order_claims = '<dt class="font-rlight font-rgrey">No Claim Details Found</dt>';
				$claim_status = "";
				$approved_claim = "";
				if (!is_null($order->r_approvedSPFId)) {
					$claim_status = "_INCOMPLETE_SPF";
					$approved_claim = '<dl><dt class="font-rlight font-rgrey">Approved Claim ID</dt><dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $order->r_approvedSPFId . '/SELLER_SELF_SERVE/">' . $order->r_approvedSPFId . '</a></dd></dl>';
				}
				if (isset($order->claimID) && $order->claimID != NULL) {
					$order_claims = '
																	<dl><dt class="font-rlight font-rgrey">Claim Status</dt>
																	<dd class="c_status">' . strtoupper($order->claimStatus) . $claim_status . '</dd></dl>
																	' . $approved_claim . '
																	<dl><dt class="font-rlight font-rgrey">Claim ID</dt>
																	<dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $order->claimID . '/SELLER_SELF_SERVE/">' . $order->claimID . '</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Date</dt>
																	<dd class="c_date">' . ($order->claimDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->claimDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Updated</dt>
																	<dd class="c_date">' . ($order->lastUpdateAt == "" ? "NA" : date("M d, Y h:i A", strtotime($order->lastUpdateAt))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reason</dt>
																	<dd class="c_reason">' . $order->claimProductCondition . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reimbursement</dt>
																	<dd class="c_reimbursement">&#x20b9; ' . (($order->claimReimbursmentAmount == "" || is_null($order->claimReimbursmentAmount)) ? "0.00" : $order->claimReimbursmentAmount) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Comments</dt>
																	<dd class="c_comments">' . ($order->claimComments == "" ? "NA" : $order->claimComments) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Notes</dt>
																	<dd class="c_comments">' . ($order->claimNotes == "" ? "NA" : str_replace("\n", "<br />", $order->claimNotes)) . '</dd></dl>';
				}

				$fk = new connector_flipkart($account);
				$payment_details = $fk->payments->get_difference_details($order->OID);
				$settlement_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['expected_payout'] as $key => $value) {
					if ($key == "settlement_date")
						continue;

					if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
						$settlement_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
					else
						$settlement_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
				}
				$received_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['settlements'] as $settlement) {
					foreach ($settlement as $key => $value) {
						// if ($key == "settlement_date")
						// 	continue;

						if ($key == "settlement_date" || $key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
							$received_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
						else
							$received_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
					}
				}
				$difference_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
				foreach ($payment_details['difference'] as $key => $value) {
					if ($key == "settlement_date")
						continue;

					if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
						$difference_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
					else
						$difference_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
				}
				$order_payments = '
																	<dl><dt class="font-rlight font-rgrey">Payment Status</dt>
																	<dd class="p_status">' . ($order->settlementStatus == 0 ? "PENDING" : "SETTLED") . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Expected Payout</dt>
																	<dd class="p_settlement">&#x20b9; ' . $fk->payments->calculate_actual_payout($order->OID) . ' <a data-target="#p_settlement_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_settlement_details_' . $collapse_id . '" class="accounts_details collapse">' . $settlement_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Amount Settled</dt>
																	<dd class="p_received">&#x20b9; ' . $fk->payments->calculate_net_settlement($order->OID) . ' <a data-target="#p_received_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd><!-- SHOW DROPDWON BREAKUP -->
																	<dd id="p_received_details_' . $collapse_id . '" class="accounts_details collapse">' . $received_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Difference</dt>
																	<dd class="p_difference">&#x20b9; ' . $payment_details['difference']['total'] . ' <a data-target="#p_difference_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_difference_details_' . $collapse_id . '" class="accounts_details collapse">' . $difference_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Settlement Notes</dt>
																	<dd class="p_notes">' . (empty($order->settlementNotes) ? "NA" : $order->settlementNotes) . '</dd></dl>';


				$return['content'] .= '
													<article class="return-order-card clearfix detail-view">
														<div class="col-md-12 order-details-header">
															<div class="col-md-2 product-image"><div class="bookmark"><a class="flag' . $flag_class . '" data-itemid="' . $order->OID . '" href="#"><i class="fa fa-bookmark"></i></a></div><img src="' . IMAGE_URL . '/uploads/products/' . $order->produtImage . '" onerror="this.src=\'https://via.placeholder.com/150x150\'"></div>
															<div class="col-md-8 order-details">
																<header class="col-md-7 return-card-header font-rlight">
																	<div>
																		<h4>
																			<div class="article-title font-rblue font-rreguler">' . $order->title . '&nbsp;&nbsp;<span class="label label-success">' . $account->account_name . '</span></div>
																		</h4>
																	</div>
																</header>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Shipment ID:&nbsp;</span>
																	<span class="order_id">' . $order->shipmentId . '</span>
																	<span class="card-label">Rating URL:&nbsp;</span>
																	<input id="p2" class="cpy_text" alt="Click to Copy Rating URL" value="https://www.flipkart.com/products/write-review/itme?pid=' . $order->fsn . '&lid=' . $order->listingId . '&source=od_sum&otracker=orders" />
																	<span class="order_id rating_url"><button title="Click to Copy Rating URL" class="cpy_btn btn btn-xs btn-default" data-clipboard-target="#p2"><i class="fa fa-copy"></i></button></span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">SKU:&nbsp;</span>
																	<span class="order_id">' . $order->sku . '</span>
																	<span class="card-label">FSN:&nbsp;</span>
																	<span class="order_item_id"><a class="font-rblue" href="https://www.flipkart.com/product/p/itme?pid=' . $order->fsn . '" target="_blank">' . $order->fsn . '</a></span>
																	<span class="card-label">HSN:&nbsp;</span>
																	<span class="order_type">' . $order->hsn . '</span>
																	<span class="card-label">UID:&nbsp;</span>
																	<span class="order_type">' . (empty($uid_links) ? "NA" : implode(', ', $uid_links)) . '</span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Quantity:&nbsp;</span>
																	<span class="quantity">' . $order->quantity . '/' . $order->ordered_quantity . '</span>
																	<span class="card-label">Price:&nbsp;</span>
																	<span class="sellingPrice">' . $order->sellingPrice . '</span>
																	<span class="card-label">Shipping:&nbsp;</span>
																	<span class="shippingCharge">' . $order->shippingCharge . '</span>
																	<span class="card-label">Total:&nbsp;</span>
																	<span class="totalPrice">' . $order->totalPrice . '</span>
																	<span class="card-label">Fulfilment Type:&nbsp;</span>
																	<span class="order_type">' . $order->order_type . '</span>
																</div>
																' . $logistic_details . '
																' . $delivery_details . '
															</div>
															<div class="col-md-2 order-incidents">
																<dl class="status">
																	<dd class="font-rlight font-rgrey">Active Incidents:&nbsp;</dd>
																	<dd class="amount">' . (empty($active_incidents) ? "NA" : implode(', ', $active_incidents)) . '</dd>
																</dl>
																<dl class="status">
																	<dd class="font-rlight font-rgrey">RMA:&nbsp;</dd>
																	<dd class="amount">' . (empty($rma_id) ? "Create RMA" : $rma_id) . '</dd>
																</dl>
															</div>
														</div>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-3 order_details">
																<dl class="status">
																	<dt class="font-rlight font-rgrey">Order Status:&nbsp;</dt>
																	<dd class="amount">' . strtoupper($order->status) . ' ' . $on_hold . '</dd>
																</dl>
																<dl class="orderId">
																	<dt class="font-rlight font-rgrey">Order ID:&nbsp;</dt>
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/' . $account->locationId . '/main/search?type=order_id&id=' . $order->orderId . '">' . $order->orderId . '</a></dd>
																</dl>
																<dl class="orderItemId">
																	<dt class="font-rlight font-rgrey">Order Item ID:&nbsp;</dt>
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/' . $account->locationId . '/main/search?type=order_item_id&id=' . $order->OID . '">' . $order->OID . '</a></dd>
																</dl>
																<dl class="price">
																	<dt class="font-rlight font-rgrey">Order Type:&nbsp;</dt>
																	<dd class="amount">' . $order_type . '</dd>
																</dl>
																<dl class="quantity">
																	<dt class="font-rlight font-rgrey">Order Date:&nbsp;</dt>
																	<dd class="qty">' . date("M d, Y h:i A", strtotime($order->orderDate)) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Label Generated:&nbsp;</dt>
																	<dd class="amount">' . ((is_null($order->labelGeneratedDate) || $order->labelGeneratedDate == "0000-00-00 00:00:00") ? "NA" : date("M d, Y h:i A", strtotime($order->labelGeneratedDate))) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">RTD Date:&nbsp;</dt>
																	<dd class="amount">' . ((is_null($order->rtdDate) || $order->rtdDate == "0000-00-00 00:00:00") ? "NA" : date("M d, Y h:i A", strtotime($order->rtdDate))) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Shipped Date:&nbsp;</dt>
																	<dd class="amount">' . ((is_null($order->shippedDate) || $order->shippedDate == "0000-00-00 00:00:00") ? "NA" : date("M d, Y h:i A", strtotime($order->shippedDate))) . '</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Date:&nbsp;</dt>
																	<dd class="amount">' . ((is_null($order->invoiceDate) || $order->invoiceDate == "0000-00-00 00:00:00") ? "Not Generated" : date("M d, Y h:i A", strtotime($order->invoiceDate))) . '</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Number:&nbsp;</dt>
																	<dd class="amount">' . ($order->invoiceNumber == "" ? "Not Generated" : $order->invoiceNumber) . '</dd>
																</dl>
															</div>
															<div class="col-md-3 order_returns">
																' . $order_returns . '
															</div>
															<div class="col-md-3 order_claims">
																' . $order_claims . '
															</div>
															<div class="col-md-3 order_payments">
																' . $order_payments . '
															</div>
														</section>
													</article>';

				$collapse_id++;
			}

			echo json_encode($return);

			break;

		case 'set_flag':
			$orderItemId = $_POST['order_item_id'];
			$flag = $_POST['flag'];
			$data = array('is_flagged' => filter_var($flag, FILTER_VALIDATE_BOOLEAN));
			$db->where('orderItemId', $orderItemId);
			if ($db->update(TBL_FK_ORDERS, $data)) {
				$return = "success";
			} else {
				$return = "error";
			}
			echo json_encode(array("type" => $return, "flag" => $flag));
			break;

		case 'order_import':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$order_item_ids = explode(',', $_REQUEST['order_item_ids']);
			$account_id = (int)$_REQUEST['account_id'];
			$selected_account = "";
			foreach ($accounts as $a_key => $account) {
				if ($account_id === $account->account_id) {
					$selected_account = $accounts[$a_key];
					break;
				}
			}
			// $fk = new flipkart_dashboard($selected_account);
			$fk = new connector_flipkart($selected_account);
			// include_once(ROOT_PATH.'/flipkart/functions.php');
			$orders = array_chunk($order_item_ids, 20);
			$success = 0;
			$existing = 0;
			$error = 0;
			$error_message = array();
			$total = count($order_item_ids);

			foreach ($orders as $order) {
				$return = new stdClass();
				// if (count($order) === 1){
				// 	$return->orderItems[] = $fk->get_order_details(implode(',', $order), true);
				// } else {
				$return = $fk->get_order_details(implode(',', $order), true);
				// }
				$insert = $fk->insert_orders($return);
				if (strpos($insert, 'INSERT: New Order with order ID') !== FALSE || strpos($insert, 'UPDATE: Updated Order with order ID') !== FALSE) {
					$success++;
				} else if (strpos($insert, 'INSERT: Already exists order with order ID') !== FALSE) {
					$existing++;
				} else {
					$error_message[] = $insert;
					$error++;
				}
			}
			echo json_encode(array('total' => $total, 'success' => $success, 'existing' => $existing, 'error' => $error, 'error_message' => $error_message));
			break;

		case 'order_import_fbf':
			$account_id = $_REQUEST['account_id'];
			$selected_account = "";
			foreach ($accounts as $a_key => $account) {
				if ($account_id === $account->account_id) {
					$selected_account = $accounts[$a_key];
					break;
				}
			}
			$handle = new \Verot\Upload\Upload($_FILES['orders_csv']);
			if ($handle->uploaded) {
				// resize tool
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->file_new_name_ext = '';
				$handle->process(ROOT_PATH . "/uploads/order_csv/");
				$handle->clean();
				$file = ROOT_PATH . "/uploads/order_csv/" . $handle->file_dst_name;
				try {
					$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
					$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
					$objReader->setReadDataOnly(true);
					$objPHPExcel = $objReader->load($file);
				} catch (Exception $e) {
					die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
				}
				$objWorksheet = $objPHPExcel->getSheet(0); // First Sheet
				$objReader->setReadDataOnly(true);

				$highestRow = $objWorksheet->getHighestRow();
				$highestColumn = $objWorksheet->getHighestColumn();

				$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

				$order_item_ids = array();
				for ($row = 2; $row <= $highestRow; ++$row) {
					$order_item_ids[] = $objWorksheet->getCellByColumnAndRow(1, $row)->getValue();
				}

				$success = 0;
				$existing = 0;
				$skipped = 0;
				$total = count($order_item_ids);
				$orders = array_chunk($order_item_ids, 20);
				$selected_account = $accounts[$account_id - 1];
				$fk = new connector_flipkart($selected_account);
				foreach ($orders as $order) {
					$shipment_details = $fk->get_order_shipment_details(implode(',', $order));
					$order_details = $fk->get_order_details(implode(',', $order));
					usort($shipment_details->shipments, "cmp_s");
					usort($order_details, "cmp");

					$i = 0;
					foreach ($order_details as $shipments) {
						$insert = $fk->update_orders(array($shipments), 'FBF_LITE', 'new', $shipment_details->shipments[$i], true);
						if (strpos($insert, 'INSERT: New Order with order ID') !== FALSE || strpos($insert, 'UPDATE: Updated Order with order ID') !== FALSE) {
							$success++;
						}
						if (strpos($insert, 'INSERT: Already exists order with order ID') !== FALSE) {
							$existing++;
						}
						if (strpos($insert, 'IMPORT: Already SHIPPED or RTD') !== FALSE) {
							$skipped++;
						}
						$i++;
					}
				}

				echo json_encode(array('total' => $total, 'success' => $success, 'existing' => $existing, 'skipped' => $skipped));
			}
			break;

		case 'create_undelivered_approved_incomplete_spf_claim':
			$db->join(TBL_FK_RETURNS . " r", "r.returnId=c.returnId", "INNER");
			$db->joinWhere(TBL_FK_RETURNS . " r", "r.r_approvedSPFId IS NULL");
			// $db->joinWhere(TBL_FK_RETURNS ." r", "(r.r_shipmentStatus = ? OR r.r_shipmentStatus = ?)", array('return_claimed_undelivered', 'return_claimed'));
			$db->join(TBL_FK_ORDERS . " o", "c.orderItemId=o.orderItemId", "INNER");
			$db->where('(c.claimStatus = ? AND c.claimStatusFK = ? AND (c.claimProductCondition = ? OR c.claimProductCondition = ? OR c.claimProductCondition = ?))', array('pending', 'claim_approved', 'undelivered', 'wrong', 'missing'));
			$db->orderBy('account_id', 'ASC');
			$claims = $db->get(TBL_FK_CLAIMS . ' c', NULL, 'c.orderItemId, c.returnId, c.claimID, c.claimNotes, c.claimProductCondition, o.orderId, o.costPrice, o.sellingPrice, o.totalPrice, o.quantity, r.r_quantity, r.r_approvedSPFId, r.r_source, o.account_id');
			var_dump(count($claims));
			echo '<br />';
			// exit;

			if ($claims) {
				foreach ($claims as $claim) {
					$orderItemId = $claim['orderItemId'];
					$orderId = $claim['orderId'];
					$returnId = $claim['returnId'];
					$costPrice = $claim['costPrice'];
					$sellingPrice = $claim['sellingPrice'];
					$totalPrice = $claim['totalPrice'];
					$quantity = $claim['quantity'];
					$r_quantity = $claim['r_quantity'];
					$r_source = $claim['r_source'];
					$selected_account = "";
					foreach ($accounts as $a_key => $account) {
						if ($claim['account_id'] === $account->account_id) {
							$selected_account = $accounts[$a_key];
							break;
						}
					}

					$fk = new flipkart_dashboard($selected_account);

					$spf = $fk->get_spf_amount($orderItemId);
					if ($spf['amount'] != 0) {
						$base_incident = (is_null($claim['r_approvedSPFId']) ? $claim['claimID'] : $claim['r_approvedSPFId']);
						$db->where('orderItemId', $orderItemId);
						if ($db->update(TBL_FK_RETURNS, array('r_approvedSPFAmt' => $spf['amount'], 'r_approvedSPFId' => $base_incident))) {
							$claimNote = $claim['claimNotes'];
							$needle = "IN";
							$lastPos = 0;
							$ids = array($claim['claimID']);
							while (($lastPos = strpos($claimNote, $needle, $lastPos)) !== false) {
								$lastPos = $lastPos + strlen($needle);
								$id = substr($claimNote, $lastPos - 2, 21);
								if (!in_array($id, $ids))
									$ids[] = trim($id);
							}
							rsort($ids);
							$ids = implode(', ', array_unique($ids));
							$order_amount = (((int)$totalPrice) / ((int)$quantity) * (int)$r_quantity);
							if ($r_source == "courier_return")
								$order_amount = (int)$totalPrice;
							$selling_amount = (((int)$sellingPrice) / ((int)$quantity) * (int)$r_quantity);
							$expected_spf = $order_amount * 0.72;
							$expected_spf_selling = $selling_amount * 0.72;
							if ($expected_spf == $spf['amount'] || ($expected_spf_selling == $spf['amount'] && $expected_spf_selling >= $costPrice)) { // echo "SPF is settled to 72% for Item ID ".$orderItemId."<br />";
								$claim_status = "reimbursed";
								$claim_comments = "72% SPF Settled on Order value. FIMS [CRON]";
								if ($expected_spf_selling == $spf['amount'])
									$claim_comments = "72% SPF Settled on Item value. FIMS [CRON]";
								$db->where('returnId', $returnId);
								$re = $db->getOne(TBL_FK_RETURNS, 'r_approvedSPFAmt, r_shipmentStatus');
								if ($re['r_shipmentStatus'] != "return_completed") {
									$claim_amount = $re['r_approvedSPFAmt'];
									$response = $fk->update_claim_details($returnId, $claim_status, $claim_comments, $claim_amount, "", "", $order->claimProductCondition);
									if ($response)
										echo "Successfully updated claim. <br />";
									else
										echo "Unable to update claim. Please retry later. <br />";
								} else {
									echo "return already marked completed<br />";
								}
								continue;
							}

							$db->where('orderItemId', $orderItemId);
							$db->update(TBL_FK_ORDERS, array('protectionFund' => $spf['amount']));

							$content = 'Dear Team, \n\nBase Incident: ' . $ids . '\n\nItem ID: ' . $orderItemId . '\nOrder ID: ' . $orderId . '\nReturn ID: ' . $returnId . ' \nOrder Value: ' . $order_amount . '\nSPF % as per policy: 72%\nExpected SPF: ' . $expected_spf . '\nSettled/Approved SPF: ' . $spf['amount'] . '\n\nSPF settled for the lost item as per the policy is 72% of the order value. The settlement here is not done as per the policy. Request you to settle the correct amount. \n\nRegards,\nORM Team (' . $current_user['user_nickname'] . ')';
							$issueType = "I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT";
							$mandatory_fields = array("base_incident" => $base_incident);
							$subject = "Incorrect SPF settlement";

							$return = $fk->create_ticket($issueType, $mandatory_fields, $subject, $content, false);
							if ($return['incidentId']) {
								$message = $return['message'];
								$return = $fk->update_claim_id($returnId, $return['incidentId'], $claim['claimID'], $claimNote);
								if ($return['type'] == "success")
									echo $message . ' and update is ' . $return['type'] . ' for Item ID ' . $orderItemId . '<br />';
								else
									echo $message . ' and unable to udpate claim ID ' . $return['incidentId'] . ' and Item ID ' . $orderItemId . '<br />';
							} else {
								echo $return['message'] . ' Item ID ' . $orderItemId . '<br />';
							}
						} else
							echo 'error updated SPF amount for orderItemId ' . $orderItemId . '<br />';
					} else {
						echo 'no SPF amount for orderItemId ' . $orderItemId . '<br />';
					}
				}
			} else {
				echo 'No approved cases';
			}
			break;

		case 'create_undelivered_resolved_return_incomplete_spf_claims':
			$db->join(TBL_FK_RETURNS . " r", "r.returnId=c.returnId", "INNER");
			// $db->joinWhere(TBL_FK_RETURNS ." r", "r.r_shipmentStatus", 'return_claimed_undelivered');
			$db->join(TBL_FK_ORDERS . " o", "c.orderItemId=o.orderItemId", "INNER");
			if (isset($_GET['account_id']))
				$db->joinWhere(TBL_FK_ORDERS . " o", 'o.account_id', $_GET['account_id']);
			$db->where('(c.claimStatus = ? AND (c.claimStatusFK = ? OR c.claimStatusFK = ? OR c.claimStatusFK = ?) AND (c.claimProductCondition = ? OR c.claimProductCondition = ? OR c.claimProductCondition = ?))', array('pending', 'claim_approved', 'claim_rejected', 'resolved', 'undelivered', 'wrong', 'missing'));
			$db->orderBy('account_id', 'ASC');
			$claims = $db->get(TBL_FK_CLAIMS . ' c', NULL, 'c.orderItemId, c.returnId, c.claimID, c.claimNotes, c.claimProductCondition, o.orderId, o.costPrice, o.totalPrice, o.sellingPrice, o.quantity, r.r_quantity, r.r_approvedSPFId, r.r_source, o.account_id');
			var_dump(count($claims));
			echo '<br />';
			// exit;

			if ($claims) {
				foreach ($claims as $claim) {
					$orderItemId = $claim['orderItemId'];
					$orderId = $claim['orderId'];
					$returnId = $claim['returnId'];
					$costPrice = $claim['costPrice'];
					$sellingPrice = $claim['sellingPrice'];
					$totalPrice = $claim['totalPrice'];
					$quantity = $claim['quantity'];
					$r_quantity = $claim['r_quantity'];
					$r_source = $claim['r_source'];
					$selected_account = "";
					foreach ($accounts as $a_key => $account) {
						if ($claim['account_id'] === $account->account_id) {
							$selected_account = $accounts[$a_key];
							break;
						}
					}

					$fk = new flipkart_dashboard($selected_account);

					$spf = $fk->get_spf_amount($orderItemId);
					if ($spf['amount'] != 0) {
						$base_incident = (is_null($claim['r_approvedSPFId']) ? $claim['claimID'] : $claim['r_approvedSPFId']);
						$db->where('orderItemId', $orderItemId);
						if ($db->update(TBL_FK_RETURNS, array('r_approvedSPFAmt' => $spf['amount'], 'r_approvedSPFId' => $base_incident))) {
							$claimNote = $claim['claimNotes'];
							$needle = "IN";
							$lastPos = 0;
							$ids = array($claim['claimID']);
							while (($lastPos = strpos($claimNote, $needle, $lastPos)) !== false) {
								$lastPos = $lastPos + strlen($needle);
								$id = substr($claimNote, $lastPos - 2, 21);
								if (!in_array($id, $ids))
									$ids[] = trim($id);
							}
							rsort($ids);
							$ids = implode(', ', array_unique($ids));
							$order_amount = (((int)$totalPrice) / ((int)$quantity) * (int)$r_quantity);
							if ($r_source == "courier_return")
								$order_amount = (int)$totalPrice;
							$selling_amount = (((int)$sellingPrice) / ((int)$quantity) * (int)$r_quantity);
							$expected_spf = $order_amount * 0.72;
							$expected_spf_selling = $selling_amount * 0.72;
							if ($expected_spf == $spf['amount'] || ($expected_spf_selling == $spf['amount'] && $expected_spf_selling >= $costPrice)) { // echo "SPF is settled to 72% for Item ID ".$orderItemId."<br />";
								$claim_status = "reimbursed";
								$claim_comments = "72% SPF Settled on Order value. FIMS [CRON]";
								if ($expected_spf_selling == $spf['amount'])
									$claim_comments = "72% SPF Settled on Item value. FIMS [CRON]";
								$db->where('returnId', $returnId);
								$re = $db->getOne(TBL_FK_RETURNS, 'r_approvedSPFAmt, r_shipmentStatus');
								if ($re['r_shipmentStatus'] != "return_completed") {
									$claim_amount = $re['r_approvedSPFAmt'];
									$response = $fk->update_claim_details($returnId, $claim_status, $claim_comments, $claim_amount, "", "", $order->claimProductCondition);
									if ($response)
										echo "Successfully updated claim. <br />";
									else
										echo "Unable to update claim. Please retry later. <br />";
								} else {
									echo "return already marked completed<br />";
								}
								continue;
							}

							$db->where('orderItemId', $orderItemId);
							$db->update(TBL_FK_ORDERS, array('protectionFund' => $spf['amount']));

							$content = 'Dear Team, \n\nBase Incident: ' . $ids . '\n\nItem ID: ' . $orderItemId . '\nOrder ID: ' . $orderId . '\nReturn ID: ' . $returnId . ' \nOrder Value: ' . $totalPrice . '\nSPF % as per policy: 72%\nExpected SPF: ' . $expected_spf . '\nSettled/Approved SPF: ' . $spf['amount'] . '\n\nSPF settled for the lost item as per the policy is 72% of the order value. The settlement here is not done as per the policy. Request you to settle the correct amount. \n\nRegards, \nORM Team (' . $current_user['user_nickname'] . ')';
							$issueType = "I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT";
							$mandatory_fields = array("base_incident" => $base_incident);
							$subject = "Incorrect SPF settlement";

							$return = $fk->create_ticket($issueType, $mandatory_fields, $subject, $content, false);
							if ($return['incidentId']) {
								$message = $return['message'];
								$return = $fk->update_claim_id($returnId, $return['incidentId'], $claim['claimID'], $claimNote);
								if ($return['type'] == "success")
									echo $message . ' and update is ' . $return['type'] . ' for Item ID ' . $orderItemId . '<br />';
								else
									echo $message . ' and unable to udpate claim ID ' . $return['incidentId'] . ' and Item ID ' . $orderItemId . '<br />';
							} else {
								echo $return['message'] . ' Item ID ' . $orderItemId . '<br />';
							}
						} else
							echo 'error updated SPF amount for orderItemId ' . $orderItemId . '<br />';
					} else {
						echo 'no SPF amount for orderItemId ' . $orderItemId . '<br />';
					}
				}
			} else {
				echo 'No approved cases';
			}
			break;

			// FK PAYMENTS
		case 'get_all_search_payments':

			if ($_REQUEST['search_by'] == "pro.offerId") {
				$db->where('offerId', $_REQUEST['search_value']);
				$promoDates = $db->getOne(TBL_FK_PROMOTIONS, 'promoOptInDate, promoStartDate, promoPreSaleStarts, promoEndDate');
				if ($promoDates['promoOptInDate'] > $promoDates['promoStartDate'])
					$promoDates['promoStartDate'] = $promoDates['promoOptInDate'];
				if (is_null($promoDates['promoPreSaleStarts']))
					$promoDates['promoPreSaleStarts'] = date('Y-m-d H:i:s', strtotime($promoDates['promoStartDate'] . ' - 4 hours'));
			}

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			// $db->joinWhere('(r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ?)', array('start', 'in_transit', 'out_for_delivery', 'delivered'));
			if ($_REQUEST['search_by'] == "pro.offerId") {
				$db->join(TBL_FK_PROMOTIONS . " pro", "pro.listingId=o.listingId", "INNER");
				$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.promoType', 'MP_INC');
				$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.accountId=o.account_id');
				$db->where('o.orderDate', array($promoDates['promoPreSaleStarts'], $promoDates['promoEndDate']), 'BETWEEN');
			}
			$db->where($_REQUEST['search_by'], $_REQUEST['search_value']);
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, r.r_shipmentStatus, o.commissionIncentive, o.is_flipkartPlus, o.orderDate');
			// echo '<pre>';
			// echo $db->getLastQuery();
			// echo count($settlements);
			// exit;

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				if ($_REQUEST['search_by'] == "pro.offerId") {
					if ($order->orderDate >= $promoDates['promoPreSaleStarts'] && $order->orderDate <= $promoDates['promoStartDate'] && !$order->is_flipkartPlus)
						continue;
				}

				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.number_format($difference, 2, '.', '').' </span>' : '<span class="label label-sm label-warning">'.number_format($difference, 2, '.', '').' </span>';
				$isIncentive = ($settlement['commissionIncentive'] > 0) ? true : false;
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId'], $isIncentive);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
					'shippedDate' => date('Y-m-d', strtotime($settlement['shippedDate'])),
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Ymd', strtotime($dueDate)),
				);
			}

			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'import_payment':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			ignore_user_abort(true); // optional
			$account_id = (int)$_REQUEST['account_id'];
			if (isset($_REQUEST['reportId'])) {
				$reportId = isset($_REQUEST['reportId']) ? $_REQUEST['reportId'] : "";

				// find account id to construct fk
				$selected_account = (int)$account_id;

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $selected_account)
						$account_key = $a_key;
				}

				$fk = new flipkart_dashboard($accounts[$account_key]);
				$file_name = $fk->request_report('download_report', array('type' => 'payment_sheets', 'request_id' => $reportId, 'file_format' => 'xlsx'));
				$file = UPLOAD_PATH . "/payment_sheets/" . $file_name;
			} else {
				$handle = new \Verot\Upload\Upload($_FILES['orders_csv']);
				if ($handle->uploaded) {
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->process(ROOT_PATH . "/uploads/payment_sheets/");
					$handle->clean();
					$file = ROOT_PATH . "/uploads/payment_sheets/" . $handle->file_dst_name;
				} else {
					echo json_encode(array('error' => 'Unable to upload file'));
				}
			}

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$response = import_payments($file, new connector_flipkart($accounts[$account_key]), $reportId);

			echo json_encode($response);
			break;

		case 'get_all_settlements':
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=p.account_id");
			$db->orderBy('p.paymentDate', 'DESC');
			$db->groupBy('p.paymentId');
			$settlements = $db->get(TBL_FK_PAYMENTS . ' p', null, 'paymentId,  paymentDate, a.account_name, SUM(paymentValue) as paymentTotal, "" as dummy');

			$return = array();
			$i = 0;

			foreach ($settlements as $settlement) {
				$j = 0;
				foreach ($settlement as $s_value) {
					if ($j == 0)
						$return['data'][$i][] = '<a target="_blank" href="' . BASE_URL . '/flipkart/payments.php?search_by=neft_id&search_value=' . $s_value . '">' . $s_value . '</a>';
					else if ($j == 3)
						$return['data'][$i][] = '&#8377 ' . number_format((float)$s_value, 2, '.', ',');
					else
						$return['data'][$i][] = $s_value;

					$j++;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);

			break;

		case 'get_all_upcoming':
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', '');
			$db->where('p.orderItemId', NULL, 'IS');
			$db->where('(o.status = ? OR o.status = ?)', array('SHIPPED', 'CANCELLED'));
			$db->orderBy('o.shippedDate', 'ASC');
			$db->orderBy('o.dispatchByDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate');
			// echo $db->getLastQuery();
			// echo count($settlements);
			// return;

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				// $fk = new connector_flipkart($accounts[$account_key]);
				// $details = $fk->payments->fetch_payout($settlement['orderItemId']);
				// $fk->payments->update_settlement_status($settlement['orderItemId']);
				// continue;
				$fk = new connector_flipkart($accounts[$account_key]);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.number_format($difference, 2, '.', '').' </span>' : '<span class="label label-sm label-warning">'.number_format($difference, 2, '.', '').' </span>';
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId']);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
					'shippedDate' => date('Y-m-d', strtotime($settlement['shippedDate'])),
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Y-m-d', strtotime($dueDate)),
				);
			}

			$return = array();
			$r = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$r][] = $value;
				}
				$r++;
			}

			usort($myArray, function ($a, $b) {
				return $orders['order'] - $b['order'];
			});

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'get_all_unsettled':
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			// $db->where('(r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ?)', array('start', 'in_transit', 'out_for_delivery', 'delivered'));
			// $db->where('o.orderItemId', '11331033293802500');
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', '');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, r.r_shipmentStatus, o.commissionIncentive');
			// echo $db->getLastQuery();
			// echo count($settlements);
			// exit;
			// echo '<pre>';

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				// $fk = new connector_flipkart($accounts[$account_key]);
				// $details = $fk->payments->fetch_payout($settlement['orderItemId']);
				// $fk->payments->update_settlement_status($settlement['orderItemId']);
				// continue;
				$fk = new connector_flipkart($accounts[$account_key]);
				// $payout = $fk->payments->fetch_payout($settlement['orderItemId']);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// if ($difference <= 0.05 && $difference >= -0.05) continue;
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.number_format($difference, 2, '.', '').' </span>' : '<span class="label label-sm label-warning">'.number_format($difference, 2, '.', '').' </span>';
				$isIncentive = ($settlement['commissionIncentive'] > 0) ? true : false;
				// var_dump($isIncentive);
				// exit;
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId'], $isIncentive);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('d M, Y', strtotime($settlement['orderDate'])),
					'shippedDate' => date('d M, Y', strtotime($settlement['shippedDate'])),
					'dueDate' => date('d M, Y', strtotime($dueDate)),
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Y-m-d', strtotime($dueDate)),
				);
			}

			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);

			break;

		case 'get_all_to_claim':
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_INCIDENTS . " fi", "fi.referenceId LIKE CONCAT('%', o.orderItemId , '%')", "LEFT");
			$db->joinWhere(TBL_FK_INCIDENTS . " fi", '(fi.issueType LIKE ? AND fi.incidentStatus != ?)', array("%payment%", 'closed'));
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			// $db->where('o.orderItemId', '11669788421221300');
			$db->where('o.settlementNotes', '', '!=');
			$db->where('(fi.incidentId IS NULL OR fi.incidentId = ?)', array(''));
			$db->orderBy('o.shippedDate, o.dispatchAfterDate', 'DESC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, fi.incidentId, o.settlementNotes');
			// echo '<pre>';
			// echo $db->getLastQuery();
			// echo '<br />'.count($settlements);
			// exit;

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				// if (!is_null($settlement['incidentId']))
				// 	continue;

				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				// $fk = new connector_flipkart($accounts[$account_key]);
				// $details = $fk->payments->fetch_payout($settlement['orderItemId']);
				// $fk->payments->update_settlement_status($settlement['orderItemId']);
				// continue;
				$fk = new connector_flipkart($accounts[$account_key]);
				// $payout = $fk->payments->fetch_payout($settlement['orderItemId']);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// if ($difference <= 0.05 && $difference >= -0.05) continue;
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.number_format($difference, 2, '.', '').' </span>' : '<span class="label label-sm label-warning">'.number_format($difference, 2, '.', '').' </span>';
				$isIncentive = ($settlement['commissionIncentive'] > 0) ? true : false;
				// var_dump($isIncentive);
				// exit;
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId'], $isIncentive);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
					'shippedDate' => date('Y-m-d', strtotime($settlement['shippedDate'])),
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Y-m-d', strtotime($dueDate)),
				);
			}

			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'get_all_disputed':
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_INCIDENTS . " fi", "fi.referenceId LIKE CONCAT('%', o.orderItemId , '%')", "INNER");
			$db->joinWhere(TBL_FK_INCIDENTS . " fi", "fi.issueType", "%payment%", "LIKE");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			// $db->where('o.orderItemId', '11669788421221300');
			$db->where('o.settlementNotes', '', '!=');
			$db->orderBy('o.shippedDate, o.dispatchAfterDate', 'DESC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, fi.incidentId, o.settlementNotes');
			// echo '<pre>';
			// echo $db->getLastQuery();
			// echo count($settlements);
			// exit;

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				// if (strpos($settlement['settlementNotes'], 'BBD19') !== FALSE){
				// 	$fk = new connector_flipkart($accounts[$account_key]);
				// 	$details = $fk->payments->fetch_payout($settlement['orderItemId']);
				// 	$fk->payments->update_settlement_status($settlement['orderItemId']);
				// }
				// continue;
				$fk = new connector_flipkart($accounts[$account_key]);
				// $payout = $fk->payments->fetch_payout($settlement['orderItemId']);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// if ($difference <= 0.05 && $difference >= -0.05) continue;
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.$difference.' </span>' : '<span class="label label-sm label-warning">'.$difference.' </span>';
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId']);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				// $tag_contents = explode(',', $settlement['settlementNotes']);
				// $tags = array();
				// $html_tags = "";
				// foreach ($tag_contents as $tag) {
				// 	$html_tags .= '<span class="label label-default label-tags">'.trim($tag).'</span>';
				// 	$tags[] = $tag;
				// }
				$tags = explode(',', $settlement['settlementNotes']);


				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					// 'incidentId' => $settlement['incidentId'],
					// 'incidentTags' => $html_tags,
					'tags' => $tags,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Y-m-d', strtotime($dueDate)),
				);
			}

			$return = array();
			$r = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$r][] = $value;
				}
				$r++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'get_all_unsettled_incentive':

			$db->join(TBL_FK_PROMOTIONS . " pr", "o.listingId=pr.listingId", "INNER");
			$db->joinWhere(TBL_FK_PROMOTIONS . " pr", "pr.accountId=o.account_id");
			$db->joinWhere('pr.promoType', "MP_INC");
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->where('o.dispatchAfterDate', '2019-11-10 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', '');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, pr.offerId');
			echo $db->getLastQuery();
			// echo count($settlements);
			exit;

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->join(TBL_FK_PROMOTIONS . " pr", "o.listingId=pr.listingId", "LEFT");
			$db->joinWhere(TBL_FK_PROMOTIONS . " pr", "pr.accountId=o.account_id");
			$db->joinWhere(TBL_FK_PROMOTIONS . " pr", "pr.promoType", "MP_INC");
			// $db->joinWhere(TBL_FK_PROMOTIONS ." pr", "pr.promoEndDate <= o.orderDate");
			// $db->joinWhere(TBL_FK_PROMOTIONS ." pr", "pr.promoStartDate >= o.orderDate");
			// $db->joinWhere('pr.promoType', "MP_INC");
			// $db->joinWhere('(r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ?)', array('start', 'in_transit', 'out_for_delivery', 'delivered'));
			// $db->where('o.orderItemId', '11331033293802500');
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', '');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, r.r_shipmentStatus, o.commissionIncentive, pr.offerId');
			echo $db->getLastQuery();
			// echo count($settlements);
			exit;
			// echo '<pre>';

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				// $fk = new connector_flipkart($accounts[$account_key]);
				// $details = $fk->payments->fetch_payout($settlement['orderItemId']);
				// $fk->payments->update_settlement_status($settlement['orderItemId']);
				// continue;
				$fk = new connector_flipkart($accounts[$account_key]);
				// $payout = $fk->payments->fetch_payout($settlement['orderItemId']);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = $net_settlement - $net_payout;
				// if ($difference <= 0.05 && $difference >= -0.05) continue;
				$difference = ($difference > 0) ? '<span class="label label-sm label-success">' . number_format($difference, 2, '.', '') . ' </span>' : '<span class="label label-sm label-warning">' . number_format($difference, 2, '.', '') . ' </span>';
				$isIncentive = ($settlement['commissionIncentive'] > 0) ? true : false;
				// var_dump($isIncentive);
				// exit;
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId'], $isIncentive);
				if (strtotime($dueDate) < time())
					$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
					'shippedDate' => date('Y-m-d', strtotime($settlement['shippedDate'])),
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
				);
			}

			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);

			break;

		case 'get_difference_details':

			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			// $orderItemId = '1961269903795403'; //1965582174156300
			$account_id = $_REQUEST['account_id'];
			$type = $_REQUEST['type'];

			$account_key = "";
			foreach ($accounts as $a_key => $r_account) {
				if ($r_account->account_id == $account_id)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$return = $fk->payments->get_difference_details($orderItemId);
			$orders = $return['orders'];
			$order = (object)$return['order'];
			if (isset($return['difference']['payment_type']) && $return['difference']['payment_type'] == "cod")
				unset($return['difference']['payment_type']);

			if ($return['expected_payout']['payment_type'] == "cod" && !isset($return['difference']['payment_type']))
				$return['difference']['payment_type'] = "prepaid";

			$settlements = array_merge(array($return['expected_payout']), $return['settlements'], array($return['difference']));
			$key_order = array('settlement_date', 'sale_amount', 'refund_amount', 'flipkart_discount', 'payment_type', 'marketplace_fees', 'commission_rate', 'commission_fee', 'fixed_fee', 'collection_fee', 'pick_and_pack_fee', 'shipping_zone', 'shipping_slab', 'shipping_fee', 'reverse_shipping_fee', 'shopsy_marketing_fee', 'commission_incentive', 'protection_fund', 'fees_waiver', 'commission_fee_waiver', 'shipping_fee_waiver', 'other_fees', 'taxes', 'tcs', 'tds', 'gst', 'total');

			$output_content = array();
			foreach ($settlements as $settlement) {
				uksort($settlement, function ($key1, $key2) use ($key_order) {
					return ((array_search($key1, $key_order) > array_search($key2, $key_order)) ? 1 : -1);
				});
				foreach ($settlement as $s_key => $s_value) {
					$output_content[$s_key][] = $s_value;
				}
			}

			$j = 0;
			$last = count($output_content['settlement_date']) - 1;
			$inner_content = '<tbody>';
			foreach ($output_content as $level_key => $level_value) {
				// var_dump($level_value);
				$editables = array('sale_amount', 'refund_amount', 'protection_fund', 'payment_type', 'commission_rate', 'commission_fee', 'commission_incentive', 'collection_fee', 'pick_and_pack_fee', 'fixed_fee', 'shipping_slab', 'shipping_zone', 'shipping_fee', 'commission_fee_waiver', 'reverse_shipping_fee', 'shipping_fee_waiver', 'flipkart_discount', 'other_fees', 'shopsy_marketing_fee');
				$i = 0;
				if ($level_key == 'settlement_date')
					$inner_content .= '<tr class="accordion-title-container">';
				else if ($level_key == 'total')
					$inner_content .= '<tr class="net-earnings-row">';
				else if ($level_key == 'commission_rate' || $level_key == 'commission_fee' || $level_key == 'fixed_fee' || $level_key == 'collection_fee' || $level_key == 'pick_and_pack_fee' || $level_key == 'shipping_slab' || $level_key == 'shipping_zone' || $level_key == 'shipping_fee' || $level_key == 'reverse_shipping_fee' || $level_key == 'shopsy_marketing_fee')
					$inner_content .= '<tr class="marketplace-fee-child">';
				else if ($j == 5) // Marketplace Fees Row - Fixed at 5th position
					$inner_content .= '<tr class="grey-highlight marketplace-fee-row">';
				else if ($level_key == 'commission_fee_waiver' || $level_key == 'shipping_fee_waiver')
					$inner_content .= '<tr class="waiver-fee-child">';
				else if ($j == 18) // Waiver Fees Row - Fixed at 16th position
					$inner_content .= '<tr class="grey-highlight taxes-row">';
				else if ($level_key == 'tds' || $level_key == 'tcs' || $level_key == 'gst')
					$inner_content .= '<tr class="taxes-child">';
				else if (($order->order_type != "FBF" && $j == 23) || $j == 21) // Taxes Fees Row - Fixed at 20th position
					$inner_content .= '<tr class="grey-highlight taxes-row">';
				else
					$inner_content .= '<tr class="grey-highlight">';

				$available_width = 100;
				$width = ($available_width / ($last + 1)) >= 15 ? 15 : $available_width / ($last + 1);
				foreach ($level_value as $value) {
					if ($level_key == 'settlement_date') {
						if ($i == 0) {
							$payment_type = 'Expected Payout';
							$available_width -= $width;
						} elseif ($i == $last) {
							$payment_type = 'Difference';
							$width = $available_width;
						} else {
							$payment_type = 'Settlement';
							$available_width -= $width;
						}

						$inner_content .= '
									<td class="transaction-data-neft" style="width:' . $width . '%">
										<span class="transaction-neft-value">' . $payment_type . '</span>
										<div class="transaction-date">
											' . $value . '
										</div>
									</td>';
					} else {
						$editor = '';
						if ($i == $last && in_array($level_key, $editables)) {
							// if ($level_key == 'flipkart_discount')
							// 	$value = $value/$order->quantity;
							$editor = '<a class="update_difference" data-itemId="' . $orderItemId . '" data-accountId="' . $account_id . '" data-key="' . $level_key . '" data-value="' . ($level_value[0] - $value) . '">Update</a>&nbsp;';
							if ($level_key == 'commission_rate' || $level_key == 'shipping_slab' || $level_key == 'payment_type' || $level_key == 'shipping_zone'  || $level_key == 'shipping_zone')
								$editor = '<a class="update_difference" data-itemId="' . $orderItemId . '" data-accountId="' . $account_id . '" data-key="' . $level_key . '" data-value="' . $value . '">Update</a>&nbsp;';
							if ($level_key == 'sale_amount') {
								if ($order->is_shopsy)
									$level_key = 'shopsy_selling_price';
								$editor = '<a class="update_difference" data-itemId="' . $orderItemId . '" data-shopsy="' . $order->is_shopsy . '" data-key="' . $level_key . '" data-value="' . ($value * -1) . '">Update</a>&nbsp;';
							}
						}
						if ($i == $last && !empty($value))
							$inner_content .= '<td>' . $editor . $value . '</td>';
						elseif ($i != $last)
							$inner_content .= '<td>' . $value . '</td>';
					}
					$i++;
				}
				$inner_content .= '</tr>';
				$j++;
			}
			$inner_content .= '</tbody>';
			$returns = "";
			$claims = "";

			foreach ($orders as $rc_order) {
				if (isset($rc_order->returnId)) {
					$returns .= '

							<div class="details-col-title">
								<div class="order-title">Return ID </div>
								<div class="order-title">Status</div>
								<div class="order-title">Type</div>
								<div class="order-title">Delivered Date</div>
								<div class="order-title">Received Date</div>
								<div class="order-title">Completed Date</div>
							</div>
							<div class="details-col-value">
								<div class="heading-value">' . (isset($rc_order->returnId) ? '<a href="https://seller.flipkart.com/index.html#dashboard/return_orders/' . $accounts[$account_key]->locationId . '/search?type=return_id&id=' . $rc_order->returnId . '" target="_blank" >' . $rc_order->returnId . '</a>' : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->r_shipmentStatus) ? strtoupper(str_replace('return_', '', $rc_order->r_shipmentStatus)) : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->r_source) ? strtoupper(str_replace('_', ' ', $rc_order->r_source)) : "NA") . '</div>
								<div class="heading-value">' . (($rc_order->r_deliveredDate == "0000-00-00 00:00:00" || $rc_order->r_deliveredDate == NULL) ? "NA" : date('d M, Y', strtotime($rc_order->r_deliveredDate))) . '</div>
								<div class="heading-value">' . ((isset($rc_order->r_action) && strpos($rc_order->r_action, 'dnt_expect') !== false) ? 'RETURN NOT EXPECTED' : (isset($rc_order->returnId) ? ($rc_order->r_receivedDate == "" ? "NA" : date('d M, Y', strtotime($rc_order->r_receivedDate))) : "NA")) . '</div>
								<div class="heading-value">' . ((isset($rc_order->r_action) && strpos($rc_order->r_action, 'dnt_expect') !== false) ? 'RETURN NOT EXPECTED' : ($rc_order->r_shipmentStatus == "return_cancelled" ? date('d M, Y', strtotime($rc_order->r_cancellationDate)) : (isset($rc_order->returnId) ? ($rc_order->r_completionDate == "" ? "NA" : date('d M, Y', strtotime($rc_order->r_completionDate))) : "NA"))) . '</div>
							</div>';
				}

				if (isset($rc_order->claimID)) {
					$claims .= '

							<div class="details-col-title">
								<div class="order-title">Claim ID</div>
								<div class="order-title">Status</div>
								<div class="order-title">Condition</div>
								<div class="order-title">Claim Date</div>
								<div class="order-title">Refunded?</div>
								<div class="order-title">Claim Completed</div>
							</div>
							<div class="details-col-value">
								<div class="heading-value">' . (isset($rc_order->claimID) ? ('<a href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $rc_order->claimID . '/SELLER_SELF_SERVE" target="_blank" >' . $rc_order->claimID . '</a>') : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->claimStatus) ? strtoupper($rc_order->claimStatus) : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->claimProductCondition) ? strtoupper($rc_order->claimProductCondition) : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->claimDate) ? date('d M, Y', strtotime($rc_order->claimDate)) : "NA") . '</div>
								<div class="heading-value">' . (isset($rc_order->protectionFund) ? "Rs. " . $rc_order->protectionFund : "No") . '</div>
								<div class="heading-value">' . ($rc_order->claimStatus != 'pending' ? (isset($rc_order->lastUpdateAt) ? date('d M, Y', strtotime($rc_order->lastUpdateAt)) : "NA") : "No") . '</div>
							</div>';
				}
			}

			$flipkart_plus = "";
			if ($order->is_flipkartPlus)
				$flipkart_plus = '<div class="cod-prepaid order-type">FLIPKART PLUS</div>';

			$incentive_details = "";
			if ($order->isMpInc/* && !$order->replacementOrder*/) {
				$incentive_details = '[<span class="tooltips" data-container="body" data-placement="right" data-original-title="' . $order->offerId . ' (' . $order->offerMpIncRate . '%) ">?</span>]';
				// } else {
				// 	$incentive_details = '<span class="tooltips" data-placement="right" data-original-title="'.$order->offerId.' ('.$order->offerMpIncRate.'%) "><label class="badge badge-default-dot">&nbsp;</label></span>';
			}

			$replacement_tag = "";
			if ($order->replacementOrder)
				$replacement_tag = '<span class="replacement_tag label label-success label-sm">R</span>';

			$incident_disable = "disabled";
			if ($type != "upcoming" || $type != "unsettled")
				$incident_disable = 'name="incidentId"';

			$pick_and_pack_fee = "";
			if ($order->order_type == "FBF")
				$pick_and_pack_fee = '<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Pick And Pack Fees</td>
														</tr>';

			$shopsy_mark = "";
			if ($order->is_shopsy)
				$shopsy_mark = '<img src="' . BASE_URL . '/assets/img/ShopsyLogo.svg" alt="Shopsy Logo">';

			$content = '<div class="product-details-transaction-container">
							<div class="product-details-container">
								<div class="row product-container clearfix">
									<div class="col-md-1 product-image-container">
										<div class="product-img-holder">
											<img src="' . IMAGE_URL . '/uploads/products/' . $order->productImage . '" onerror="this.src=\'https://via.placeholder.com/100x100\'">
										</div>
										' . $replacement_tag . '
										<div class="cod-prepaid">' . strtoupper($order->paymentType) . '</div>
										<div class="cod-prepaid fulfilment-type">' . strtoupper($order->order_type) . '</div>
										' . $flipkart_plus . '
									</div>
									<div class="col-md-10 details-holder">
										<div class="product-title">
											<a target="_blank" href="http://flipkart.com/product/p/itme?pid=' . $order->fsn . '">' . $order->title . '</a>' . $shopsy_mark . '
										</div>
										<div class="product-order-details">
											<div class="order-details-container">
												<div class="details-col-title">
													<div class="order-title">Item ID </div>
													<div class="order-title">Status</div>
													<div class="order-title">SKU</div>
													<div class="order-title">Shipped Date </div>
													<div class="order-title">Invoice Number </div>
													<div class="order-title">Invoice Date </div>
												</div>
												<div class="details-col-value">
													<div class="heading-value"><a href="https://seller.flipkart.com/index.html#dashboard/payments/transactions?filter=' . $order->orderItemId . '&startDate=start&endDate=end" target="_black">' . $order->orderItemId . '</a></div>
													<div class="heading-value">' . $order->status . '</div>
													<div class="heading-value">' . $order->quantity . ' x ' . $order->sku . '</div>
													<div class="heading-value">' . date('d M, Y', ($order->shippedDate == NULL ? strtotime($order->dispatchByDate) : strtotime($order->dispatchByDate))) . '</div>
													<div class="heading-value">' . $order->invoiceNumber . '</div>
													<div class="heading-value">' . date('d M, Y', strtotime($order->invoiceDate)) . '</div>
												</div>
												' . $returns . $claims . '
											</div>
										</div>
									</div>
									<div class="col-md-1 product-container-buttons text-right">
										<a data-itemid="' . $orderItemId . '" class="mark_settled btn btn-default btn-xs" title="Mark Settled"><i class="fa fa-check" aria-hidden="true"></i></a><br />
										<a data-itemid="' . $orderItemId . '" class="refresh_payout btn btn-default btn-xs" title="Refresh Payout"><i class="fa fa-recycle" aria-hidden="true"></i></a><br />
										<a data-itemid="' . $orderItemId . '" class="reload btn btn-default btn-xs" title="Refresh Data"><i class="fa fa-sync" aria-hidden="true"></i></a><br />
										<a data-itemid="' . $orderItemId . '" class="refetch_billing btn btn-default btn-xs" title="Refetch Billing Data"><i class="fa fa-download" aria-hidden="true"></i></a><br />
										<a data-itemid="' . $orderItemId . '" data-isshopsy="' . !$order->is_shopsy . '" class="shopsy_order btn btn-default btn-xs" title="Update to Shopsy Order"><i class="fa" aria-hidden="true"><img src="' . BASE_URL . '/assets/img/ShopsyLogo.svg" alt="Shopsy Logo"></i></a>
									</div>
								</div>
								<div class="settlement-notes form clearfix">
									<form class="form-horizontal form-row-seperated">
										<div class="form-group">
											<label class="control-label col-md-1">IncidentID: </label>
											<div class="col-md-3">
												<input class="form-control incidentId" ' . $incident_disable . ' value="' . $order->incidentId . '" />
												<input type="hidden" name="account_id" value="' . $account_id . '" />
											</div>
											<div class="col-md-7">
												<input type="hidden" id="settlementNotes" name="settlementNotes" class="form-control select2" value="' . $order->settlementNotes . '">
												<input type="hidden" name="orderItemId" value="' . $order->orderItemId . '" />
											</div>
											<div class="col-md-1">
												<button type="submit" class="btn btn-default update_notes">Update</button>
											</div>
										</div>
									</form>
								</div>
							</div>
							<div class="transactions-history">
								<div class="transactions-history-details">
									<div class="transactions-accordion-container">
										<div class="transaction-table-container">
											<div class="transactions-table">
												<table class="table-head" style="margin-left: 0px; float: left;">
													<thead>
														<tr class="accordion-title-container">
															<td class="transaction-data-neft accordion-title" style="width:180px;">All Transactions</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Sale Amount</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Refund Amount</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Flipkart Discount</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Payment Type</td>
														</tr>
														<tr class="grey-highlight marketplace-fee-row">
															<td class="title" style="width:120px;">Marketplace Fees <i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Commission Rate</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Commission</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Fixed Fee</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Collection Fee</td>
														</tr>
														' . $pick_and_pack_fee . '
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Shipping Zone</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Shipping Slab</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Shipping Fee</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Reverse Shipping Fee</td>
														</tr>
														<tr class="marketplace-fee-child">
															<td class="title" style="width:120px;">Shopsy Marketing Fee</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Commission Incentive ' . $incentive_details . '</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Protection Fund</td>
														</tr>
														<tr class="grey-highlight waiver-fee-row">
															<td class="title" style="width:120px;">Waiver Fees <i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">Commission Fee Waiver</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">Shipping Fee Waiver</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Other Fees</td>
														</tr>
														<tr class="grey-highlight taxes-row">
															<td class="title" style="width:120px;">Taxes <i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="taxes-child">
															<td class="title" style="width:120px;">TCS</td>
														</tr>
														<tr class="taxes-child">
															<td class="title" style="width:120px;">TDS</td>
														</tr>
														<tr class="taxes-child">
															<td class="title" style="width:120px;">GST</td>
														</tr>
														<tr class="net-earnings-row">
															<td class="title">Net Earnings</td>
														</tr>
													</thead>
												</table>
												<table class="table-content">' .
				$inner_content
				. '</table>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>';

			echo json_encode(array('type' => 'success', 'data' => $content));
			break;

		case 'update_settlement_difference':

			$key = lcfirst(str_replace('_', '', ucwords($_REQUEST['key'], '_')));
			$key = ($key == "isShopsy" ? 'is_shopsy' :  $key);
			$key = ($key == "pickAndPackFee" ? 'pickPackFee' :  $key);
			$key = ($key == 'shippingFee' ? 'forwardShippingFee' : $key);
			$value = $_REQUEST['value'];
			$orderItemId = $_REQUEST['orderItemId'];

			$data = array($key => $value);
			$db->where('orderItemId', $orderItemId);
			if ($db->update(TBL_FK_ORDERS, $data)) {
				echo json_encode(array('type' => 'success', 'msg' => "Successfully updated fees"));
			} else {
				echo json_encode(array('type' => 'failed', 'msg' => "Error updating fees. Please retry again.", 'error' => $db->getLastError()));
			}

			break;

		case 'get_transaction_details':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$details = $fk->payments->get_settlement_details($orderItemId);
			$details[$orderItemId]['difference'] = ((float)$details[$orderItemId]['difference'] >= 0) ? '<span class="label label-sm label-success">' . $details[$orderItemId]['difference'] . ' </span>' : '<span class="label label-sm label-warning">' . $details[$orderItemId]['difference'] . ' </span>';
			echo json_encode(array('type' => 'success', 'data' => $details));

			break;

		case 'get_payment_transactions_details':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$details = $fk->get_transaction_history($orderItemId);
			$success = array();
			$existing = array();
			$update = array();
			$error = array();
			$error_neft = array();
			$total = count($details->settled_settlements);
			foreach ($details->settled_settlements as $transaction) {
				// $transaction = json_decode(json_encode($transaction), TRUE);
				$transaction = (array)$transaction;
				$update_data = array(
					"neft_id" => $transaction['neft_id'],
					// "neft_type" => $transaction[''],
					"neft_date" => $transaction['settlement_date'],
					"settlement_value" => $transaction['net_amount'],
					"order_item_id" => $orderItemId,
					"sale_amount" => $transaction['sale_amount'],
					"total_offer_amount" => $transaction['total_offer'],
					"my_share" => $transaction['seller_share'],
					"customer_shipping_amount" => $transaction['customer_shipping_amount'],
					"marketplace_fee" => $transaction['marketplace_fee']->net_amount,
					"tax_collected_at_source" => $transaction['tcs']->net_amount,
					"tds" => $transaction['tds']->net_amount,
					"taxes" => $transaction['taxes']->net_amount,
					"protection_fund" => $transaction['protection_fund'],
					"refund" => $transaction['refund_amount'],
					"additional_information" => "",
					// "commission_rate" => $transaction[''],
					"commission" => $transaction['marketplace_fee']->commission_fee,
					"commission_fee_waiver" => 0,
					"collection_fee" => $transaction['marketplace_fee']->collection_fee,
					"collection_fee_waiver" => 0,
					"fixed_fee" => $transaction['marketplace_fee']->fixed_fee,
					"pick_and_pack_fee" => $transaction['marketplace_fee']->pick_and_pack_fee,
					"customer_shipping_fee_type" => $transaction['marketplace_fee']->customer_shipping_fee_type,
					"customer_shipping_fee" => $transaction['marketplace_fee']->customer_shipping_fee_amount,
					"shipping_fee" => $transaction['marketplace_fee']->forward_shipping_fee,
					"shipping_fee_waiver" => 0,
					"reverse_shipping_fee" => $transaction['marketplace_fee']->reverse_shipping_fee,
					"product_cancellation_fee" => $transaction['marketplace_fee']->cancellation_fee,
					"chargeable_wt.slab" => $details->forward_shipping_details[0]->weight_details->chargeable_weight_slab,
					"shipping_zone" => $details->forward_shipping_details[0]->shipping_zone,
					"service_cancellation_fee" => $transaction['marketplace_fee']->service_cancellation_fee,
					"fee_discount" => $transaction['marketplace_fee']->fee_discount,
				);

				$insert = $fk->payments->insert_settlements($update_data, 'orders');
				if (strpos($insert, 'INSERT: New settlement for Order Item ID') !== FALSE) {
					$success[] = $order['order_item_id'];
				}
				if (strpos($insert, 'INSERT: Already exists settlement for Order Item ID') !== FALSE) {
					$existing[] = $order['order_item_id'];
				}
				if (strpos($insert, 'UPDATE: Updated settlement for Order Item ID') !== FALSE) {
					$update[] = $order['order_item_id'];
				}
				if (strpos($insert, 'INSERT: Unable to insert settlement for Order Item ID') !== FALSE) {
					$error[] = $order['order_item_id'];
				}
				if (strpos($insert, 'Invalid NEFT_ID') !== FALSE) {
					$error_neft[] = $order['order_item_id'];
				}
			}

			$return = array('type' => 'success', 'msg' => 'Successfully update settlemnts', 'total' => $total, 'success' => count($success), 'existing' => count($existing), 'error' => count($error), 'updated' => count($update), 'invalid' => count($error_neft), 'error_data' => $error);
			$log->write($return, 'payments-import');
			echo json_encode($return);
			// $details[$orderItemId]['difference'] = ((float)$details[$orderItemId]['difference'] >= 0) ? '<span class="label label-sm label-success">'.$details[$orderItemId]['difference'].' </span>' : '<span class="label label-sm label-warning">'.$details[$orderItemId]['difference'].' </span>';
			// echo json_encode(array('type' => 'success', 'data' => $details));

			break;

		case 'mark_order_settled':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$data['settlementStatus'] = 1;
			$data['settlementNotes'] = '';
			$db->where('orderItemId', $orderItemId);
			if ($db->update(TBL_FK_ORDERS, $data)) {
				// INCASE IF WE HAVE ANY INCIDENTED TAGGED TO THE ORDER
				$db->where('referenceId', '%' . $orderItemId . '%', 'LIKE');
				$db->where('issueType', 'Payments');
				$db->where('incidentStatus', 'active');
				$incident = $db->getOne(TBL_FK_INCIDENTS, 'incidentId, referenceId');
				if ($incident) {
					$referenceIds = str_replace('"' . $orderItemId . '": false', '"' . $orderItemId . '": true', $incident['referenceId']);

					$details = array('referenceId' => $referenceIds);
					$return = array();
					if (strpos($referenceIds, 'false') === false) {
						$return['all'] = 'closed';
						$details['incidentStatus'] = 'closed';
					}

					$db->where('incidentId', $incident['incidentId']);
					$db->update(TBL_FK_INCIDENTS, $details);
				}
				echo json_encode(array('type' => 'success'));
			} else {
				echo json_encode(array('type' => 'failed'));
			}

			break;

		case 'refresh_payout':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);
			$details = $fk->payments->fetch_payout($orderItemId);
			if (strpos($details, 'Successfully') !== FALSE)
				echo json_encode(array('type' => 'success'));
			else
				echo json_encode(array('type' => 'failed'));

			break;

		case 'update_pending_payment_shipped_orders':
			ignore_user_abort(true); // optional
			foreach ($accounts as $account) {
				$fk = new connector_flipkart($account);
				$db->where('shippedDate', array(date('Y-m-d', strtotime('yesterday')), date('Y-m-d', strtotime('today'))), 'BETWEEN');
				$db->where('status', 'SHIPPED');
				$db->where('netSettlement', 0);
				$db->where('settlementStatus', 0);
				$db->where('account_id', $account->account_id);
				$orderItemIds = $db->getValue(TBL_FK_ORDERS, 'orderItemId', NULL);
				var_dump($db->count);
				if ($db->count)
					foreach ($orderItemIds as $orderItemId) {
						$response = $fk->payments->fetch_payout($orderItemId);
						echo $orderItemId . ' RE: ' . $response . '<br />';
					}
				else
					echo 'No orders for ' . $account->account_name . '<br />';
			}
			break;

		case 'refetch_billing_details':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key]);

			$orders = $fk->get_order_details($orderItemId, true);
			if (isset($orders->errors)) {
				echo json_encode(array('type' => 'error', 'msg' => 'Error updating billing details', 'response' => $orders));
				break;
			}

			$details = array();
			if (isset($orders[0]->priceComponents)) {
				$details['sellingPrice'] = $orders[0]->priceComponents->sellingPrice;
				$details['totalPrice'] = $orders[0]->priceComponents->totalPrice;
				$details['shippingCharge'] = $orders[0]->priceComponents->shippingCharge;
				$details['customerPrice'] = $orders[0]->priceComponents->customerPrice;
				$details['flipkartDiscount'] = $orders[0]->priceComponents->flipkartDiscount;
				$invoice = $fk->get_order_invoice_details($orders[0]->shipmentId);
				if (isset($invoice->invoices)) {
					$details['invoiceNumber'] = $invoice->invoices[0]->invoiceNumber;
					$details['invoiceDate'] = $invoice->invoices[0]->invoiceDate;
				}
			}

			// echo '<pre>';
			// var_dump($details);
			if (!empty($details)) {
				$db->where('orderItemId', $orderItemId);
				if ($db->update(TBL_FK_ORDERS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully fetched billing details'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Error updating billing details', 'response' => $orders));
			} else
				echo json_encode(array('type' => 'error', 'msg' => 'Error fetching billing details', 'response' => $orders));

			break;

		case 'update_settlement_notes':

			$details = array(
				'settlementNotes' => $_REQUEST['settlementNotes']
			);

			// echo json_encode($details);
			$db->where('orderItemId', $_REQUEST['orderItemId']);
			if ($db->update(TBL_FK_ORDERS, $details)) {
				if (isset($_REQUEST['incidentId'])) {
					return add_new_incident($_REQUEST['accountId'], 'Payments', $_REQUEST['incidentId'], 'I have not received less payment for an order Id / MP fees overcharged', '', $_REQUEST['orderItemId']);
				}
				return json_encode(array('type' => 'success', 'msg' => 'Successfully updated transaction notes'));
			}
			return json_encode(array('type' => 'error', 'msg' => 'Unable to update details. Please try later.'));

			break;

		case 'export_to_claim_orders':

			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_INCIDENTS . " fi", "fi.referenceId LIKE CONCAT('%', o.orderItemId , '%')", "LEFT");
			$db->joinWhere(TBL_FK_INCIDENTS . " fi", '(fi.issueType LIKE ? AND fi.incidentStatus != ?)', array("%payment%", 'closed'));
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', '', '!=');
			$db->where('(fi.incidentId IS NULL OR fi.incidentId = ?)', array(''));
			$db->orderBy('o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderItemId, o.account_id, fi.incidentId');

			$acc_orders = array();
			// find account id to construct fk
			foreach ($orders as $order) {
				if (!is_null($order['incidentId']))
					continue;
				$acc_orders[($order['account_id'])][] = $order['orderItemId'];
			}

			$datas = array();
			foreach ($acc_orders as $acc_key => $settlements) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $acc_key)
						$account_key = $a_key;
				}
				$account_name = $accounts[$account_key]->fk_account_name;
				$header  = array('Account Name', 'Order Item ID', 'Order ID', 'Order Date', 'Shipped Date', 'Settlement Date', 'Payment Due Date', 'Expected Payout', 'Total Settlement', 'Difference', 'Fees Type', 'Expected Fees Payout', 'Acutal Fees Settlement', 'Fees Difference', 'Settlement Notes');
				$datas[$account_name][] = $header;
				$fk = new connector_flipkart($accounts[$account_key]);
				foreach ($settlements as $settlement) {
					$details = $fk->payments->get_settlement_details($settlement, 0);
					$detail_difference = $fk->payments->get_difference_details($settlement);
					// var_dump($detail_difference);

					unset($detail_difference['difference']['marketplace_fees']);
					unset($detail_difference['difference']['fees_waiver']);
					unset($detail_difference['difference']['total']);
					unset($detail_difference['difference']['payment_type']);
					unset($detail_difference['difference']['settlement_date']);
					unset($details[$settlement]['accountId']);

					$details[$settlement]['paymentType'] = $detail_difference['order']['paymentType'];
					foreach ($detail_difference['difference'] as $dif_k => $dif_v) {
						$diff = array(
							'fee_type' => $dif_k,
							'expected' => $detail_difference['expected_payout'][$dif_k],
							'settled' => $detail_difference['total_settlement'][$dif_k],
							'difference' => $dif_v
						);
						$details[$settlement]['differenceDetails'][] = $diff;
					}

					$incentive_note = ($detail_difference['order']['offerId'] != "" ? $detail_difference['order']['offerId'] . ',' : "");
					$details[$settlement]['settlementNotes'] = str_replace(',', ', ', $incentive_note . $detail_difference['order']['settlementNotes']);
					$details[$settlement][] = "";
					$details[$settlement]['fsn'] = $detail_difference['order']['fsn'];
					// $details[$settlement]['qty'] = $detail_difference['order']['quantity'];
					$details[$settlement]['offerId'] = $detail_difference['order']['offerId'];
					$datas[$account_name][] = $details[$settlement];
				}
			}

			if ($datas) {
				$sheets = array();
				$r = 0;
				foreach ($datas as $account_key => $account) {
					$sheets[$account_key] = array();
					foreach ($account as $data) {
						foreach ($data as $d_key => $d_value) {
							if (is_array($d_value)) {
								$count = count($d_value) - 1;
								$total_differences = count($d_value);
								foreach ($d_value as $inner_value) {
									$j = 10;
									if ($count !== $total_differences - 1) {
										$previous_array = $sheets[$account_key][$r - 1];
										unset($previous_array[14]);
										$sheets[$account_key][$r] = array_values($previous_array);
									}

									foreach ($inner_value as $key => $value) {
										$sheets[$account_key][$r][$j] = $value;
										$j++;
									}

									if ($count === 0)
										$r;
									else {
										$sheets[$account_key][$r][14] = "";
										$r++;
									}

									$count--;
								}
							} else {
								if ($d_key === 'orderItemId')
									$sheets[$account_key][$r][] = "OI:" . $d_value;
								else
									$sheets[$account_key][$r][] = $d_value;
							}
						}
						$r++;
					}
				}

				$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
				// Set document properties
				$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
					->setLastModifiedBy("Ishan Kukadia")
					->setTitle("All To Claim Orders")
					->setSubject("All To Claim Orders")
					->setDescription("All To Claim Orders");


				$s = 0;
				$previous_row = array();
				foreach ($sheets as $sheet => $output) {
					$r = 1;
					$objPHPExcel->createSheet($s);
					$objPHPExcel->setActiveSheetIndex($s);
					$this_order = '';
					$i = 0;
					foreach ($output as $rows) {
						$c = "A";
						if ($r == 1) {
							$last_row = sizeof($rows);
							$last = chr(ord('A') + $last_row - 1);
						}
						$previous_order = $this_order;
						$this_order = $rows[1]; // Order Item ID. Hard Fix. Need to be at place 2 in an array all the time.

						if ($previous_order != $this_order && $r != 1) {
							$row_type = ($i % 2 == 0) ? 'even' : 'odd';
							$i++;
						}

						$objActiveSheet = $objPHPExcel->getActiveSheet();
						foreach ($rows as $row) {
							if ($r == 1) {
								$objActiveSheet->getColumnDimension($c)->setAutoSize(true);
								$objActiveSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

								if ($c == 'A' || $c == 'B' || $c == 'C')
									$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
								else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
									$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
								else
									$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
							}

							if ($c == $last) {
								if ($previous_order == $this_order) {
									$previous_row[] = $c . ($r - 1);
									if ($previous_row[0] != $c . ($r - 1))
										$objActiveSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
									$objActiveSheet->mergeCells($previous_row[0] . ':' . $c . $r);
									$objActiveSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
									$objActiveSheet->setCellValue($previous_row[0], $row);
									if ($row_type == 'odd' && $r != 1)
										$objActiveSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
								} else {
									$objActiveSheet->setCellValue($c . $r, $row);
									$previous_row = array();
									if ($row_type == 'odd' && $r != 1)
										$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
								}
							} else {
								$objActiveSheet->setCellValue($c . $r, $row);
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							}
							$c++;
						}
						$r++;
					}

					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
							)
						)
					);

					$objActiveSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
					unset($styleArray);

					$objActiveSheet->insertNewColumnBefore('D', 1);
					$objActiveSheet->getColumnDimension('D')->setWidth("1");
					$objActiveSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

					$objActiveSheet->insertNewColumnBefore('L', 1);
					$objActiveSheet->getColumnDimension('L')->setWidth("1");
					$objActiveSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

					$objActiveSheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
					$objActiveSheet->setTitle($sheet);

					$s++;
				}
				$objPHPExcel->setActiveSheetIndex(0);

				// REMOVE DEFAULT WORKSHEET
				$objPHPExcel->removeSheetByIndex(
					$objPHPExcel->getIndex(
						$objPHPExcel->getSheetByName('Worksheet')
					)
				);

				// Redirect output to a client’s web browser (Xlsx)
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment;filename="to-claim-orders-report-' . date('Ymd') . '.xlsx"');
				header('Cache-Control: max-age=0');
				// If you're serving to IE 9, then the following may be needed
				header('Cache-Control: max-age=1');

				// If you're serving to IE over SSL, then the following may be needed
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
				header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				header('Pragma: public'); // HTTP/1.0
				$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
				$objWriter->save('php://output');
			} else {
				exit('No Order Found');
			}
			break;

		case 'export_unsettled_orders':

			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->where('o.settlementStatus', 0);
			$db->where('o.settlementNotes', ''); // FOR ALL UNSETTLED ORDERS
			// $db->where('o.settlementNotes', '', '!='); // FOR ALL DISPUTED ORDERS
			$db->orderBy('o.account_id, o.settlementNotes, o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');

			$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderItemId, o.account_id');
			$acc_orders = array();
			// find account id to construct fk
			foreach ($orders as $order) {
				$acc_orders[($order['account_id'])][] = $order['orderItemId'];
			}

			$datas = array();
			foreach ($acc_orders as $acc_key => $settlements) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $acc_key)
						$account_key = $a_key;
				}
				$account_name = $accounts[$account_key]->fk_account_name;
				$header  = array('Account Name', 'Order Item ID', 'Order ID', 'Order Date', 'Shipped Date', 'Settlement Date', 'Payment Due Date', 'Expected Payout', 'Total Settlement', 'Difference', 'Fees Type', 'Expected Fees Payout', 'Acutal Fees Settlement', 'Fees Difference', 'Settlement Notes');
				$datas[$account_name][] = $header;
				$fk = new connector_flipkart($accounts[$account_key]);
				foreach ($settlements as $settlement) {
					$details = $fk->payments->get_settlement_details($settlement, 0);
					$detail_difference = $fk->payments->get_difference_details($settlement);
					// var_dump($detail_difference);

					unset($detail_difference['difference']['marketplace_fees']);
					unset($detail_difference['difference']['fees_waiver']);
					unset($detail_difference['difference']['total']);
					unset($detail_difference['difference']['payment_type']);
					unset($detail_difference['difference']['settlement_date']);
					unset($details[$settlement]['accountId']);

					$details[$settlement]['paymentType'] = $detail_difference['order']['paymentType'];
					foreach ($detail_difference['difference'] as $dif_k => $dif_v) {
						$diff = array(
							'fee_type' => $dif_k,
							'expected' => $detail_difference['expected_payout'][$dif_k],
							'settled' => $detail_difference['total_settlement'][$dif_k],
							'difference' => $dif_v
						);
						$details[$settlement]['differenceDetails'][] = $diff;
					}

					$details[$settlement]['settlementNotes'] = $detail_difference['order']['settlementNotes'];
					$details[$settlement][] = "";
					$details[$settlement]['fsn'] = $detail_difference['order']['fsn'];
					// $details[$settlement]['qty'] = $detail_difference['order']['quantity'];
					$details[$settlement]['offerId'] = $detail_difference['order']['offerId'];
					$datas[$account_name][] = $details[$settlement];
				}
			}

			$sheets = array();
			$r = 0;
			foreach ($datas as $account_key => $account) {
				$sheets[$account_key] = array();
				foreach ($account as $data) {
					foreach ($data as $d_key => $d_value) {
						if (is_array($d_value)) {
							$count = count($d_value) - 1;
							$total_differences = count($d_value);
							foreach ($d_value as $inner_value) {
								$j = 10;
								if ($count !== $total_differences - 1) {
									$previous_array = $sheets[$account_key][$r - 1];
									unset($previous_array[14]);
									$sheets[$account_key][$r] = array_values($previous_array);
								}

								foreach ($inner_value as $key => $value) {
									$sheets[$account_key][$r][$j] = $value;
									$j++;
								}

								if ($count === 0)
									$r;
								else {
									$sheets[$account_key][$r][14] = "";
									$r++;
								}

								$count--;
							}
						} else {
							if ($d_key === 'orderItemId')
								$sheets[$account_key][$r][] = "OI:" . $d_value;
							else
								$sheets[$account_key][$r][] = $d_value;
						}
					}
					$r++;
				}
			}

			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("All Unsettled Orders")
				->setSubject("All Unsettled Orders")
				->setDescription("All Unsettled Orders");


			$s = 0;
			$previous_row = array();
			foreach ($sheets as $sheet => $output) {
				$r = 1;
				$objPHPExcel->createSheet($s);
				$objPHPExcel->setActiveSheetIndex($s);
				$this_order = '';
				$i = 0;
				foreach ($output as $rows) {
					$c = "A";
					if ($r == 1) {
						$last_row = sizeof($rows);
						$last = chr(ord('A') + $last_row - 1);
					}
					$previous_order = $this_order;
					$this_order = $rows[1]; // Order Item ID. Hard Fix. Need to be at place 2 in an array all the time.

					if ($previous_order != $this_order && $r != 1) {
						$row_type = ($i % 2 == 0) ? 'even' : 'odd';
						$i++;
					}

					$objActiveSheet = $objPHPExcel->getActiveSheet();

					foreach ($rows as $row) {
						if ($r == 1) {
							$objActiveSheet->getColumnDimension($c)->setAutoSize(true);
							$objActiveSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

							if ($c == 'A' || $c == 'B' || $c == 'C')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
							else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
							else
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
						}

						if ($c == $last) {
							if ($previous_order == $this_order) {
								$previous_row[] = $c . ($r - 1);
								if ($previous_row[0] != $c . ($r - 1))
									$objActiveSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
								$objActiveSheet->mergeCells($previous_row[0] . ':' . $c . $r);
								$objActiveSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
								$objActiveSheet->setCellValue($previous_row[0], $row);
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							} else {
								$objActiveSheet->setCellValue($c . $r, $row);
								$previous_row = array();
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							}
						} else {
							$objActiveSheet->setCellValue($c . $r, $row);
							if ($row_type == 'odd' && $r != 1)
								$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
						}
						$c++;
					}
					$r++;
				}

				$styleArray = array(
					'borders' => array(
						'allborders' => array(
							'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						)
					)
				);

				$objActiveSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
				unset($styleArray);

				$objActiveSheet->insertNewColumnBefore('D', 1);
				$objActiveSheet->getColumnDimension('D')->setWidth("1");
				$objActiveSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->insertNewColumnBefore('L', 1);
				$objActiveSheet->getColumnDimension('L')->setWidth("1");
				$objActiveSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
				$objActiveSheet->setTitle($sheet);

				$s++;
			}
			$objPHPExcel->setActiveSheetIndex(0);

			// REMOVE DEFAULT WORKSHEET
			$objPHPExcel->removeSheetByIndex(
				$objPHPExcel->getIndex(
					$objPHPExcel->getSheetByName('Worksheet')
				)
			);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="unsettled-orders-report-' . date('Ymd') . '.xlsx"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
			header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header('Pragma: public'); // HTTP/1.0
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
			$objWriter->save('php://output');
			break;

		case 'export_order_by_offer_id':

			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$offerId = $_GET['offer_id'];
			$pre_hours = 4;
			if (isset($_GET['pre_hours']))
				$pre_hours = $_GET['pre_hours'];
			$db->where('offerId', $_GET['offer_id']);
			$promoDates = $db->getOne(TBL_FK_PROMOTIONS, 'promoOptInDate, promoStartDate, promoEndDate');
			if ($promoDates['promoOptInDate'] > $promoDates['promoStartDate'])
				$promoDates['promoStartDate'] = $promoDates['promoOptInDate'];

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->join(TBL_FK_PROMOTIONS . " pro", "pro.listingId=o.listingId", "INNER");
			$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.promoType', 'MP_INC');
			$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.accountId=o.account_id');
			$db->where('o.orderDate', array(date('Y-m-d, H:i:s', strtotime($promoDates['promoStartDate'] . ' -' . $pre_hours . ' hours')), $promoDates['promoEndDate']), 'BETWEEN');
			// $db->where('o.orderDate', array($promoDates['promoStartDate'], $promoDates['promoEndDate']), 'BETWEEN');
			$db->where('pro.offerId', $offerId);
			// $db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			// $db->where('o.account_id', 2);
			// $db->where('o.orderItemId', '22001547264542100');
			$db->groupBy('o.orderItemId');
			$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderItemId, o.account_id, o.orderDate, o.is_flipkartPlus, r.r_shipmentStatus');
			// echo '<pre>';
			// var_dump(count($orders));
			// exit;
			$acc_orders = array();
			// find account id to construct fk
			foreach ($orders as $order) {
				if (is_null($order['r_shipmentStatus']) || $order['r_shipmentStatus'] == "return_cancelled") {
					if ($order['is_flipkartPlus'] == "1" && $order['orderDate'] < $promoDates['promoStartDate'])
						$acc_orders[($order['account_id'])][] = $order['orderItemId'];

					if ($order['orderDate'] > $promoDates['promoStartDate'])
						$acc_orders[($order['account_id'])][] = $order['orderItemId'];
				}
			}

			// var_dump(count($acc_orders));
			// exit;

			$datas = array();
			foreach ($acc_orders as $acc_key => $settlements) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $acc_key)
						$account_key = $a_key;
				}
				$account_name = $accounts[$account_key]->fk_account_name;
				$header  = array('Account Name', 'Order Item ID', 'Order ID', 'Order Date', 'Shipped Date', 'Settlement Date', 'Payment Due Date', 'Expected Payout', 'Total Settlement', 'Difference', 'Fees Type', 'Expected Fees Payout', 'Acutal Fees Settlement', 'Fees Difference', 'Settlement Notes', 'Order Total', 'LID', 'OfferId', 'Quantity', 'FK Plus');
				$datas[$account_name][] = $header;
				$fk = new connector_flipkart($accounts[$account_key]);
				foreach ($settlements as $settlement) {
					if ($fk->payments->has_returns($settlement))
						continue;

					$details = $fk->payments->get_settlement_details($settlement, 0);
					if ((int)$details[$settlement]['difference'] == (int)'0')
						continue;

					$detail_difference = $fk->payments->get_difference_details($settlement);

					unset($detail_difference['difference']['marketplace_fees']);
					unset($detail_difference['difference']['fees_waiver']);
					unset($detail_difference['difference']['total']);
					unset($detail_difference['difference']['payment_type']);
					unset($detail_difference['difference']['settlement_date']);
					unset($details[$settlement]['accountId']);

					// $details[$settlement]['paymentType'] = $detail_difference['order']['paymentType'];
					foreach ($detail_difference['difference'] as $dif_k => $dif_v) {
						if ($dif_k != "commission_incentive" || $dif_k == "")
							continue;

						$diff = array(
							'fee_type' => $dif_k,
							'expected' => $detail_difference['expected_payout'][$dif_k],
							'settled' => $detail_difference['total_settlement'][$dif_k],
							'difference' => $dif_v
						);
						$details[$settlement]['differenceDetails'][] = $diff;
					}

					// $details[$settlement]['settlementNotes'] = $detail_difference['order']['settlementNotes'];
					$details[$settlement]['settlementNotes'] = "";
					$details[$settlement]['orderValue'] = $detail_difference['order']['totalPrice'];
					// $details[$settlement]['fsn'] = $detail_difference['order']['fsn'];
					$details[$settlement]['lid'] = $detail_difference['order']['listingId'];
					$details[$settlement]['offerId'] = $detail_difference['order']['offerId'];
					$details[$settlement]['qty'] = $detail_difference['order']['quantity'];
					$details[$settlement]['is_flipkartPlus'] = ($detail_difference['order']['is_flipkartPlus']) ? "Yes" : "No";
					$datas[$account_name][] = $details[$settlement];
				}
			}

			// echo '<pre>';
			// var_dump($datas);
			// exit;

			$sheets = array();
			$r = 0;
			foreach ($datas as $account_key => $account) {
				$sheets[$account_key] = array();
				foreach ($account as $data) {
					foreach ($data as $d_key => $d_value) {
						if (is_array($d_value)) {
							$count = count($d_value) - 1;
							$total_differences = count($d_value);
							foreach ($d_value as $inner_value) {
								$j = 10;
								if ($count !== $total_differences - 1) {
									$previous_array = $sheets[$account_key][$r - 1];
									unset($previous_array[14]);
									$sheets[$account_key][$r] = array_values($previous_array);
								}

								foreach ($inner_value as $key => $value) {
									$sheets[$account_key][$r][$j] = $value;
									$j++;
								}

								if ($count === 0)
									$r;
								else {
									$sheets[$account_key][$r][14] = "";
									$r++;
								}

								$count--;
							}
						} else {
							if ($d_key === 'orderItemId')
								$sheets[$account_key][$r][] = "OI:" . $d_value;
							else
								$sheets[$account_key][$r][] = $d_value;
						}
					}
					$r++;
				}
			}

			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("Offer Payments Report Orders")
				->setSubject("Offer Payments Report Orders")
				->setDescription("Offer Payments Report Orders");


			$s = 0;
			$previous_row = array();
			foreach ($sheets as $sheet => $output) {
				$r = 1;
				$objPHPExcel->createSheet($s);
				$objPHPExcel->setActiveSheetIndex($s);
				$this_order = '';
				$i = 0;
				foreach ($output as $rows) {
					$c = "A";
					if ($r == 1) {
						$last_row = sizeof($rows);
						$last = chr(ord('A') + $last_row - 1);
					}

					$previous_order = $this_order;
					$this_order = $rows[1]; // Order Item ID. Hard Fix. Need to be at place 2 in an array all the time.

					if ($previous_order != $this_order && $r != 1) {
						$row_type = ($i % 2 == 0) ? 'even' : 'odd';
						$i++;
					}

					$objActiveSheet = $objPHPExcel->getActiveSheet();

					foreach ($rows as $row) {
						if ($r == 1) {
							$objActiveSheet->getColumnDimension($c)->setAutoSize(true);
							$objActiveSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

							if ($c == 'A' || $c == 'B' || $c == 'C')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
							else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
							else if ($c == "K" || $c == "L" || $c == "M" || $c == "N" || $c == "O")
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
							else
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('99ccff');
						}

						if ($c == $last) {
							if ($previous_order == $this_order) {
								$previous_row[] = $c . ($r - 1);
								if ($previous_row[0] != $c . ($r - 1))
									$objActiveSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
								$objActiveSheet->mergeCells($previous_row[0] . ':' . $c . $r);
								$objActiveSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
								$objActiveSheet->setCellValue($previous_row[0], $row);
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							} else {
								$objActiveSheet->setCellValue($c . $r, $row);
								$previous_row = array();
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							}
						} else {
							$objActiveSheet->setCellValue($c . $r, $row);
							if ($row_type == 'odd' && $r != 1)
								$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
						}
						$c++;
					}
					$r++;
				}

				$styleArray = array(
					'borders' => array(
						'allborders' => array(
							'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						)
					)
				);

				$objActiveSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
				unset($styleArray);

				$objActiveSheet->insertNewColumnBefore('D', 1);
				$objActiveSheet->getColumnDimension('D')->setWidth("1");
				$objActiveSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->insertNewColumnBefore('L', 1);
				$objActiveSheet->getColumnDimension('L')->setWidth("1");
				$objActiveSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->insertNewColumnBefore('R', 1);
				$objActiveSheet->getColumnDimension('R')->setWidth("1");
				$objActiveSheet->getStyle('R1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
				$objActiveSheet->setTitle($sheet);

				$s++;
			}
			$objPHPExcel->setActiveSheetIndex(0);

			// REMOVE DEFAULT WORKSHEET
			$objPHPExcel->removeSheetByIndex(
				$objPHPExcel->getIndex(
					$objPHPExcel->getSheetByName('Worksheet')
				)
			);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="offer-payments-report-' . str_replace(':', '-', $offerId) . '.xlsx"');
			header('Cache-Control: max-age=0');

			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
			header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header('Pragma: public'); // HTTP/1.0
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
			$objWriter->save('php://output');
			break;

		case 'export_order_by_item_id':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			$acc_orders = array(
				'1' => array("11911139549137600"),
				'2' => array("11911323577722000")
			);

			$datas = array();
			foreach ($acc_orders as $acc_key => $settlements) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $acc_key)
						$account_key = $a_key;
				}
				$account_name = $accounts[$account_key]->fk_account_name;
				$header  = array('Account Name', 'Order Item ID', 'Order ID', 'Order Date', 'Shipped Date', 'Settlement Date', 'Payment Due Date', 'Expected Payout', 'Total Settlement', 'Difference', 'Fees Type', 'Expected Fees Payout', 'Acutal Fees Settlement', 'Fees Difference', 'Settlement Notes', 'Order Total', 'LID', 'OfferId');
				$datas[$account_name][] = $header;
				$fk = new connector_flipkart($accounts[$account_key]);
				foreach ($settlements as $settlement) {
					$details = $fk->payments->get_settlement_details($settlement, 0);
					if ((int)$details[$settlement]['difference'] == (int)'0')
						continue;

					$detail_difference = $fk->payments->get_difference_details($settlement);

					unset($detail_difference['difference']['marketplace_fees']);
					unset($detail_difference['difference']['fees_waiver']);
					unset($detail_difference['difference']['total']);
					unset($detail_difference['difference']['payment_type']);
					unset($detail_difference['difference']['settlement_date']);
					unset($details[$settlement]['accountId']);

					// $details[$settlement]['paymentType'] = $detail_difference['order']['paymentType'];
					foreach ($detail_difference['difference'] as $dif_k => $dif_v) {
						// if ($dif_k != "commission_incentive" || $dif_k == "")
						// 	continue;

						$diff = array(
							'fee_type' => $dif_k,
							'expected' => $detail_difference['expected_payout'][$dif_k],
							'settled' => $detail_difference['total_settlement'][$dif_k],
							'difference' => $dif_v
						);
						$details[$settlement]['differenceDetails'][] = $diff;
					}

					// $details[$settlement]['settlementNotes'] = $detail_difference['order']['settlementNotes'];
					$details[$settlement]['settlementNotes'] = "";
					$details[$settlement]['orderValue'] = $detail_difference['order']['totalPrice'];
					// $details[$settlement]['fsn'] = $detail_difference['order']['fsn'];
					$details[$settlement]['lid'] = $detail_difference['order']['listingId'];
					// $details[$settlement]['qty'] = $detail_difference['order']['quantity'];
					$details[$settlement]['offerId'] = $detail_difference['order']['offerId'];
					$datas[$account_name][] = $details[$settlement];
				}
			}

			// echo '<pre>';
			// var_dump($datas);
			// exit;

			$sheets = array();
			$r = 0;
			foreach ($datas as $account_key => $account) {
				$sheets[$account_key] = array();
				foreach ($account as $data) {
					foreach ($data as $d_key => $d_value) {
						if (is_array($d_value)) {
							$count = count($d_value) - 1;
							$total_differences = count($d_value);
							foreach ($d_value as $inner_value) {
								$j = 10;
								if ($count !== $total_differences - 1) {
									$previous_array = $sheets[$account_key][$r - 1];
									unset($previous_array[14]);
									$sheets[$account_key][$r] = array_values($previous_array);
								}

								foreach ($inner_value as $key => $value) {
									$sheets[$account_key][$r][$j] = $value;
									$j++;
								}

								if ($count === 0)
									$r;
								else {
									$sheets[$account_key][$r][14] = "";
									$r++;
								}

								$count--;
							}
						} else {
							if ($d_key === 'orderItemId') {
								$sheets[$account_key][$r][] = "OI:" . $d_value;
							} else
								$sheets[$account_key][$r][] = $d_value;
						}
					}
					$r++;
				}
			}

			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("Offer Payments Report Orders")
				->setSubject("Offer Payments Report Orders")
				->setDescription("Offer Payments Report Orders");


			$s = 0;
			$previous_row = array();
			foreach ($sheets as $sheet => $output) {
				$r = 1;
				$objPHPExcel->createSheet($s);
				$objPHPExcel->setActiveSheetIndex($s);
				$currentSheet = $objPHPExcel->getActiveSheet();
				$this_order = '';
				$i = 0;
				foreach ($output as $rows) {
					$c = "A";
					if ($r == 1) {
						$last_row = sizeof($rows);
						$last = chr(ord('A') + $last_row - 1);
					}

					$previous_order = $this_order;
					$this_order = $rows[1]; // Order Item ID. Hard Fix. Need to be at place 2 in an array all the time.

					if ($previous_order != $this_order && $r != 1) {
						$row_type = ($i % 2 == 0) ? 'even' : 'odd';
						$i++;
					}

					foreach ($rows as $row) {
						if ($r == 1) {
							$currentSheet->getColumnDimension($c)->setAutoSize(true);
							$currentSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

							if ($c == 'A' || $c == 'B' || $c == 'C')
								$currentSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
							else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
								$currentSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
							else if ($c == "K" || $c == "L" || $c == "M" || $c == "N" || $c == "O")
								$currentSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
							else
								$currentSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('99ccff');
						}

						if ($c == $last) {
							if ($previous_order == $this_order) {
								$previous_row[] = $c . ($r - 1);
								if ($previous_row[0] != $c . ($r - 1)) {
									$is_merged_cells = true;
									$cell = $currentSheet->getCell($previous_row[0]);
									foreach ($currentSheet->getMergeCells() as $cells) {
										if ($cell->isInRange($cells)) {
											$is_merged_cells = false;
										}
									}
									if ($is_merged_cells)
										$currentSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
								}
								$currentSheet->mergeCells($previous_row[0] . ':' . $c . $r);
								$currentSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
								$currentSheet->setCellValue($previous_row[0], $row);
								if ($row_type == 'odd' && $r != 1)
									$currentSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							} else {
								$currentSheet->setCellValue($c . $r, $row);
								$previous_row = array();
								if ($row_type == 'odd' && $r != 1)
									$currentSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							}
						} else {
							$currentSheet->setCellValue($c . $r, $row);
							if ($row_type == 'odd' && $r != 1)
								$currentSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
						}
						$c++;
					}
					$r++;
				}

				$styleArray = array(
					'borders' => array(
						'allborders' => array(
							'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						)
					)
				);

				$currentSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
				unset($styleArray);

				$currentSheet->insertNewColumnBefore('D', 1);
				$currentSheet->getColumnDimension('D')->setWidth("1");
				$currentSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$currentSheet->insertNewColumnBefore('L', 1);
				$currentSheet->getColumnDimension('L')->setWidth("1");
				$currentSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$currentSheet->insertNewColumnBefore('R', 1);
				$currentSheet->getColumnDimension('R')->setWidth("1");
				$currentSheet->getStyle('R1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$currentSheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
				$currentSheet->setTitle($sheet);

				$s++;
			}
			$objPHPExcel->setActiveSheetIndex(0);

			// REMOVE DEFAULT WORKSHEET
			$objPHPExcel->removeSheetByIndex(
				$objPHPExcel->getIndex(
					$objPHPExcel->getSheetByName('Worksheet')
				)
			);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="offer-payments-report-orderlevel.xlsx"');
			header('Cache-Control: max-age=0');

			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
			header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header('Pragma: public'); // HTTP/1.0
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
			$objWriter->save('php://output');
			break;

		case 'export_order_by_order_date':
			$promoDates = array(
				'promoStartDate' => '2020-07-01 00:00:00',
				'promoEndDate' => '2020-10-15 23:59:59'
			);
			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->where('(o.account_id = ? OR o.account_id = ? )', array('1', '2'));
			$db->where('o.orderDate', array($promoDates['promoStartDate'], $promoDates['promoEndDate']), 'BETWEEN');
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderItemId, o.account_id');
			$acc_orders = array();
			// find account id to construct fk
			foreach ($orders as $order) {
				$acc_orders[($order['account_id'])][] = $order['orderItemId'];
			}

			$datas = array();
			foreach ($acc_orders as $acc_key => $settlements) {
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $acc_key)
						$account_key = $a_key;
				}
				$account_name = $accounts[$account_key]->fk_account_name;
				$header  = array('Account Name', 'Order Item ID', 'Order ID', 'Order Date', 'Shipped Date', 'Settlement Date', 'Payment Due Date', 'Expected Payout', 'Total Settlement', 'Difference', 'Fees Type', 'Expected Fees Payout', 'Acutal Fees Settlement', 'Fees Difference', 'Settlement Notes', 'Order Total', 'LID', 'OfferId');
				$datas[$account_name][] = $header;
				$fk = new connector_flipkart($accounts[$account_key]);
				foreach ($settlements as $settlement) {
					$details = $fk->payments->get_settlement_details($settlement, 0);
					if ((int)$details[$settlement]['difference'] == (int)'0')
						continue;

					$detail_difference = $fk->payments->get_difference_details($settlement);

					unset($detail_difference['difference']['marketplace_fees']);
					unset($detail_difference['difference']['fees_waiver']);
					unset($detail_difference['difference']['total']);
					unset($detail_difference['difference']['payment_type']);
					unset($detail_difference['difference']['settlement_date']);
					unset($details[$settlement]['accountId']);

					// $details[$settlement]['paymentType'] = $detail_difference['order']['paymentType'];
					foreach ($detail_difference['difference'] as $dif_k => $dif_v) {
						if ($dif_k != "commission_rate" || $dif_k == "")
							continue;

						$diff = array(
							'fee_type' => $dif_k,
							'expected' => $detail_difference['expected_payout'][$dif_k],
							'settled' => $detail_difference['total_settlement'][$dif_k],
							'difference' => $dif_v
						);
						$details[$settlement]['differenceDetails'][] = $diff;
					}

					// $details[$settlement]['settlementNotes'] = $detail_difference['order']['settlementNotes'];
					$details[$settlement]['settlementNotes'] = "";
					$details[$settlement]['orderValue'] = $detail_difference['order']['totalPrice'];
					// $details[$settlement]['fsn'] = $detail_difference['order']['fsn'];
					$details[$settlement]['lid'] = $detail_difference['order']['listingId'];
					// $details[$settlement]['qty'] = $detail_difference['order']['quantity'];
					$details[$settlement]['offerId'] = $detail_difference['order']['offerId'];
					$datas[$account_name][] = $details[$settlement];
				}
			}

			// echo '<pre>';
			// var_dump($datas);
			// exit;

			$sheets = array();
			$r = 0;
			foreach ($datas as $account_key => $account) {
				$sheets[$account_key] = array();
				foreach ($account as $data) {
					foreach ($data as $d_key => $d_value) {
						if (is_array($d_value)) {
							$count = count($d_value) - 1;
							$total_differences = count($d_value);
							foreach ($d_value as $inner_value) {
								$j = 10;
								if ($count !== $total_differences - 1) {
									$previous_array = $sheets[$account_key][$r - 1];
									unset($previous_array[14]);
									$sheets[$account_key][$r] = array_values($previous_array);
								}

								foreach ($inner_value as $key => $value) {
									$sheets[$account_key][$r][$j] = $value;
									$j++;
								}

								if ($count === 0)
									$r;
								else {
									$sheets[$account_key][$r][14] = "";
									$r++;
								}

								$count--;
							}
						} else {
							if ($d_key === 'orderItemId')
								$sheets[$account_key][$r][] = "OI:" . $d_value;
							else
								$sheets[$account_key][$r][] = $d_value;
						}
					}
					$r++;
				}
			}

			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("Offer Payments Report Orders")
				->setSubject("Offer Payments Report Orders")
				->setDescription("Offer Payments Report Orders");


			$s = 0;
			$previous_row = array();
			foreach ($sheets as $sheet => $output) {
				$r = 1;
				$objPHPExcel->createSheet($s);
				$objPHPExcel->setActiveSheetIndex($s);
				$this_order = '';
				$i = 0;
				foreach ($output as $rows) {
					$c = "A";
					if ($r == 1) {
						$last_row = sizeof($rows);
						$last = chr(ord('A') + $last_row - 1);
					}

					$previous_order = $this_order;
					$this_order = $rows[1]; // Order Item ID. Hard Fix. Need to be at place 2 in an array all the time.

					if ($previous_order != $this_order && $r != 1) {
						$row_type = ($i % 2 == 0) ? 'even' : 'odd';
						$i++;
					}

					$objActiveSheet = $objPHPExcel->getActiveSheet();
					foreach ($rows as $row) {
						if ($r == 1) {
							$objActiveSheet->getColumnDimension($c)->setAutoSize(true);
							$objActiveSheet->getStyle($c . $r)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

							if ($c == 'A' || $c == 'B' || $c == 'C')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dad2e7');
							else if ($c == 'D' || $c == 'E' || $c == 'F' || $c == 'G' || $c == 'H' || $c == 'I' || $c == 'J')
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('e1efdb');
							else if ($c == "K" || $c == "L" || $c == "M" || $c == "N" || $c == "O")
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('fef2cf');
							else
								$objActiveSheet->getStyle($c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('99ccff');
						}

						if ($c == $last) {
							if ($previous_order == $this_order) {
								$previous_row[] = $c . ($r - 1);
								if ($previous_row[0] != $c . ($r - 1))
									$objActiveSheet->unmergeCells($previous_row[0] . ':' . $c . ($r - 1));
								$objActiveSheet->mergeCells($previous_row[0] . ':' . $c . $r);
								$objActiveSheet->getStyle($previous_row[0])->getAlignment()->setWrapText(true);
								$objActiveSheet->setCellValue($previous_row[0], $row);
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle(str_replace('N', 'A', $previous_row[0]) . ':' . $c . $r)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							} else {
								$objActiveSheet->setCellValue($c . $r, $row);
								$previous_row = array();
								if ($row_type == 'odd' && $r != 1)
									$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
							}
						} else {
							$objActiveSheet->setCellValue($c . $r, $row);
							if ($row_type == 'odd' && $r != 1)
								$objActiveSheet->getStyle('A' . $r . ':' . $c . $r, $row)->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dddddd');
						}
						$c++;
					}
					$r++;
				}

				$styleArray = array(
					'borders' => array(
						'allborders' => array(
							'style' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
						)
					)
				);

				$objActiveSheet->getStyle('A1:' . $last . ($r - 1))->applyFromArray($styleArray);
				unset($styleArray);

				$objActiveSheet->insertNewColumnBefore('D', 1);
				$objActiveSheet->getColumnDimension('D')->setWidth("1");
				$objActiveSheet->getStyle('D1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->insertNewColumnBefore('L', 1);
				$objActiveSheet->getColumnDimension('L')->setWidth("1");
				$objActiveSheet->getStyle('L1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->insertNewColumnBefore('R', 1);
				$objActiveSheet->getColumnDimension('R')->setWidth("1");
				$objActiveSheet->getStyle('R1')->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ffffff');

				$objActiveSheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
				$objActiveSheet->setTitle($sheet);

				$s++;
			}
			$objPHPExcel->setActiveSheetIndex(0);

			// REMOVE DEFAULT WORKSHEET
			$objPHPExcel->removeSheetByIndex(
				$objPHPExcel->getIndex(
					$objPHPExcel->getSheetByName('Worksheet')
				)
			);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="offer-payments-report-' . str_replace(':', '-', $offerId) . '.xlsx"');
			header('Cache-Control: max-age=0');

			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
			header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header('Pragma: public'); // HTTP/1.0
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
			$objWriter->save('php://output');
			break;

			// FK Dashboard Section
		case 'request_report':
			$report_type = isset($_REQUEST['report_type']) ? str_replace(' ', '_', strtolower($_REQUEST['report_type'])) : "";
			$report_sub_type = isset($_REQUEST['report_sub_type']) ? str_replace(' ', '_', strtolower($_REQUEST['report_sub_type'])) : "";
			$report_date_range = isset($_REQUEST['report_date_range']) ? $_REQUEST['report_date_range'] : "";
			$account = isset($_REQUEST['account_id']) ? (int)$_REQUEST['account_id'] : "";

			if ($report_type == "" || $report_sub_type == "" || $account == "") {
				$return = array('type' => 'error', 'message' => 'One or more required fields value not received.');
			} else {
				// find account id to construct fk
				$selected_account = (int)$account;

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $selected_account)
						$account_key = $a_key;
				}

				$fk = new flipkart_dashboard($accounts[$account_key]);

				list($from_date, $to_date) = explode('-', $report_date_range);
				$data = array(
					'from_date' => date('Y-m-d', strtotime($from_date)),
					'to_date' => date('Y-m-d', strtotime($to_date)),
				);
				$return = $fk->request_report($report_sub_type, $data);
				// $return = array('type' => $reports['type'], 'message' => $reports['message'], 'response' => $reports);
			}
			echo json_encode($return);
			break;

		case 'get_all_reports':
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=r.accountId", "LEFT");
			$db->orderBy('r.createdDate', 'DESC');
			$reports = $db->objectBuilder()->get(TBL_FK_REPORTS . ' r', NULL, 'a.account_name, r.reportId, r.reportType, r.reportSubType, r.reportFromDate, r.reportToDate, r.reportStatus, coalesce("action") AS action, r.accountId, r.reportFileFormat');
			$return = array();
			$i = 0;

			foreach ($reports as $report) {
				$j = 0;
				foreach ($report as $s_value) {
					if ($j == 8) {
						continue;
					}

					if ($s_value == "action") {
						if ($report->reportStatus == "queued")
							$return['data'][$i][] = '<a class="update btn btn-default btn-xs purple" data-reportid="' . $report->reportId . '" data-accountid="' . $report->accountId . '" data-reporttype="' . $report->reportType . '" data-reportsubtype="' . $report->reportSubType . '"><i class=""></i> Update</a>';
						if ($report->reportStatus == "generated") {
							$button = '<a class="process btn btn-default btn-xs purple" data-reportid="' . $report->reportId . '" data-accountid="' . $report->accountId . '" data-reporttype="' . $report->reportType . '" data-reportsubtype="' . $report->reportSubType . '" data-reportfileformat="' . $report->reportFileFormat . '"><i class=""></i> Process</a>';
							if ($report->reportSubType == "Settle Transactions")
								$button .= '<a class="btn btn-default btn-xs purple"href="' . BASE_URL . '/uploads/payment_sheets/' . strtolower($report->reportId) . '.xlsx" target="_blank"><i class="fa fa-download"></i></a>';
							$return['data'][$i][] = $button;
						}
						if (($report->reportStatus == "completed") && $report->reportSubType == "Commission Invoice")
							$return['data'][$i][] = '<a class="download btn btn-default btn-xs purple" data-reportid="' . $report->reportId . '" data-accountid="' . $report->accountId . '" data-reporttype="' . $report->reportType . '" data-reportsubtype="' . $report->reportSubType . '" data-reportfileformat="' . $report->reportFileFormat . '"><i class=""></i> Download</a>';
						elseif ($report->reportStatus == "completed") {
							$button = '<a class="process btn btn-default btn-xs purple" data-reportid="' . $report->reportId . '" data-accountid="' . $report->accountId . '" data-reporttype="' . $report->reportType . '" data-reportsubtype="' . $report->reportSubType . '" data-reportfileformat="' . $report->reportFileFormat . '"><i class=""></i> Re-Process</a>';
							if ($report->reportSubType == "Settle Transactions")
								$button .= '&nbsp;<a class="btn btn-default btn-xs purple" href="' . BASE_URL . '/uploads/payment_sheets/' . strtolower($report->reportId) . '.xlsx" target="_blank"><i class="fa fa-download"></i></a>';
							$return['data'][$i][] = $button;
						}
					} else {
						$return['data'][$i][] = ucfirst($s_value);
					}

					$j++;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'get_report_status':
			$reportId = isset($_REQUEST['reportId']) ? $_REQUEST['reportId'] : "";
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$reportType = isset($_REQUEST['reportType']) ? $_REQUEST['reportType'] : "";
			$reportSubType = isset($_REQUEST['reportSubType']) ? $_REQUEST['reportSubType'] : "";
			if ($reportId == "" && $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			if ($reportType == "Flipkart Fulfilment Orders Report") {
				$data = array(
					'reportId' => $reportId,
					'warehouse_id' => $reportSubType
				);
				$reports = $fk->request_report('get_ff_orders_report_status', $data);
				$lookup_variable = 'job_id';
				$request_object = 'jobs';
			} else {
				$reports = $fk->request_report('get_reports');
				$lookup_variable = 'request_id';
				$request_object = 'report_requests';
			}

			$reports = json_decode($reports);
			$report_details = findObjectById($reports->{$request_object}, $lookup_variable, $reportId);
			$return = array('type' => 'info', 'message' => 'No update in status of report id ' . $reportId . '. Try again later.');
			if ($report_details->status == "FILE_STORED" || $report_details->status == "COMPLETED") {
				$details = array(
					'reportStatus' => 'generated',
					'reportFileFormat' => strtolower($report_details->file_format)
				);
				if ($reportType == "Invoices")
					$details['reportStatus'] = 'completed';
				$db->where('reportId', $reportId);
				if ($db->update(TBL_FK_REPORTS, $details)) {
					$return = array('type' => 'success', 'message' => 'Status of report id ' . $reportId . ' updated to generated.', 'reportFileFormat' => strtolower($report_details->file_format));
				} else {
					$return = array('type' => 'error', 'message' => 'Unable to update status of report id ' . $reportId . '. Try again later.');
				}
			}

			echo json_encode($return);
			break;

		case 'download_report':
			$selected_account = $_GET['account_id'];
			$reportId = $_GET['reportId'];
			$reportType = $_GET['reportType'];
			$reportFileFormat = $_GET['reportFileFormat'];
			$fileType = str_replace(array(' ', '-'), '_', strtolower($reportType));

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);

			$filename = $fk->request_report('download_report', array('type' => $fileType, 'request_id' => $reportId, 'file_format' => $reportFileFormat)); // DOWNLOAD REPORT
			if ($filename != "error download file") {
				$response = array(
					'type' => 'success',
					'message' => 'Successfully downloaded file',
					'filename' => $filename,
					'download_url' => BASE_URL . '/uploads/' . $fileType . '/' . $filename
				);
			} else {
				$response = array(
					'type' => 'error',
					'message' => 'Unable to downloaded file. Please try again later.'
				);
			}
			echo json_encode($response);
			break;

		case 'request_settled_transaction_report': // PAYMENTS REPORT
			$report_sub_type = 'settled_transactions';
			foreach ($accounts as $account) {
				$fk = new flipkart_dashboard($account);
				$date = array(
					'from_date' => date('Y-m-d', strtotime("-9 day")),
					'to_date' => date('Y-m-d', strtotime("today") - 2),
				);
				$reports = $fk->request_report('settled_transactions', $date);
				echo $return = json_encode(array('type' => $reports['type'], 'message' => 'New report with ID ' . $reports['reportId'] . ' successfully requested.', 'response' => json_encode($reports)));
			}
			break;

		case 'request_settled_transaction_report_status':
			$db->where('reportSubType', 'Settle Transactions');
			$db->where('reportStatus', 'queued');
			$reports = $db->objectBuilder()->get(TBL_FK_REPORTS);
			$success = 0;
			$error = 0;
			$generating = 0;
			$return = array();
			$error_message = array();
			foreach ($reports as $report) {
				$selected_account = $report->accountId;

				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $selected_account)
						$account_key = $a_key;
				}

				$fk = new flipkart_dashboard($accounts[$account_key]);

				$fk_reports = $fk->request_report('get_reports');
				$fk_reports = json_decode($fk_reports);
				$lookup_variable = 'request_id';
				$request_object = 'report_requests';
				$reportId = $report->reportId;
				$report_details = findObjectById($fk_reports->{$request_object}, $lookup_variable, $reportId);
				if ($report_details->status == "FILE_STORED" || $report_details->status == "COMPLETED") {
					$file_format = ($report_details->file_format == "excel" ? 'xlsx' : strtolower($report_details->file_format));
					$details = array(
						'reportStatus' => 'generated',
						'reportFileFormat' => $file_format
					);
					$db->where('reportId', $reportId);
					if ($db->update(TBL_FK_REPORTS, $details)) {
						$return[] = array('type' => 'success', 'message' => 'Status of report id ' . $reportId . ' updated to generated.');
						$success++;
						$fk->request_report('download_report', array('type' => 'payment_sheets', 'request_id' => $reportId, 'file_format' => $file_format)); // DOWNLOAD REPORT
					} else {
						$error_message[] = array('type' => 'error', 'message' => 'Unable to update status of report id ' . $reportId . '. Try again later.');
						$error++;
					}
				} else {
					$error_message[] = array('type' => 'info', 'message' => 'No update in status of report id ' . $reportId . '. Try again later.');
					$generating++;
				}
			}

			echo json_encode(array('type' => 'success', 'total' => count($reports), 'success' => $success, 'error' => $error, 'generating' => $generating, 'error_message' => $error_message));
			break;

		case 'import_settled_transaction_report':
			ignore_user_abort(true); // optional
			$db->where('reportSubType', 'Settle Transactions');
			$db->where('reportStatus', 'generated');
			$reports = $db->objectBuilder()->get(TBL_FK_REPORTS);

			foreach ($reports as $report) {
				$reportId = $report->reportId;
				$reportFileFormat = $report->reportFileFormat;
				$file = UPLOAD_PATH . "/payment_sheets/" . $reportId . '.' . $reportFileFormat;

				// find account id to construct fk
				$selected_account = (int)$report->accountId;
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $selected_account)
						$account_key = $a_key;
				}

				if (!file_exists($file)) {
					$fk = new flipkart_dashboard($accounts[$account_key]);
					$fk->request_report('download_report', array('type' => 'payment_sheets', 'request_id' => $reportId, 'file_format' => $reportFileFormat)); // DOWNLOAD REPORT
				}

				$response[$reportId] = import_payments($file, new connector_flipkart($accounts[$account_key]), $reportId);
			}
			echo json_encode($response);
			break;

		case 'get_all_promotions':
			$i = 0;
			$return = array();
			$additional_mp_inc_offers = json_decode(get_option('fk_additional_mp_inc_offers'), true);
			foreach ($accounts as $account) {
				$fk = new flipkart_dashboard($account);
				$promos = $fk->get_flipkart_promotions();
				$promos = $promos->result->promotionDataList;
				$promos_opted = $fk->get_flipkart_promotions('OPTED_IN');
				$promos_opted = $promos_opted->result->promotionDataList;
				$promos = array_merge($promos, $promos_opted);
				foreach ($promos as $promo) {
					$id = $promo->offerId . ':' . $account->account_id;
					$type = ((strpos(strtolower($promo->sellerOfferTitle), 'inc') !== false || strpos(strtolower($promo->sellerOfferTitle), 'mpinc') !== false || strpos(strtolower($promo->sellerOfferTitle), 'mp_inc') !== false) ? 'MP_INC' : $promo->offerClassification);
					if (in_array($promo->offerId, $additional_mp_inc_offers))
						$type = 'MP_INC';

					if (strpos($promo->sellerOfferTitle, 'DOTH') !== false)
						$type = "DOTH";

					if (strpos($promo->sellerOfferTitle, 'DOTD') !== false)
						$type = "DOTD";

					if (strpos($promo->sellerOfferTitle, 'DOTW') !== false)
						$type = "DOTW";

					$discountType = (($promo->discountType == 'AP_LISTING_DISCOUNT') ? 'Variable' : ($promo->discountType == 'AP_SNAPSHOT_LISTING_DISCOUNT' ? 'Fixed' : ($promo->discountType == 'PREBOOK_LISTING_DISCOUNT' ? 'PreBook' : "")));
					$return['resources'][$i]['id'] = $id;
					$return['resources'][$i]['offerId'] = $promo->offerId . "\n" . $promo->promotionLabel;
					$return['resources'][$i]['accountName'] = $account->account_name;
					$return['resources'][$i]['type'] = $type . ' ' . $discountType;

					$return['events'][$i]['resourceId'] = $id;
					$return['events'][$i]['title'] = $promo->sellerOfferTitle;

					$is_mpInc = 'data-ismpinc="false"';
					if ($type == 'MP_INC')
						$is_mpInc = 'data-ismpinc="true"';

					if (isset($promo->valueToBuyer->tiers[0]->giveAway->percentage)) {
						$giveAway = $promo->valueToBuyer->tiers[0]->giveAway->percentage;
						$giveAwayFormated = $giveAway . '%';
						$giveAwayType = 'percentage';
					} else {
						$giveAway = $promo->valueToBuyer->tiers[0]->giveAway->finalPrice;
						$giveAwayFormated = 'Flat ₹' . $giveAway;
						$giveAwayType = 'flat';
					}

					$description = '<b>Promo Title: </b>' . $promo->sellerOfferTitle . ' <a target="_blank" href="https://seller.flipkart.com/index.html#dashboard/promo/fk/details/' . str_replace(':', '-', $promo->offerId) . '"/><i class="fa fa-external-link-alt"></i></a><br/>';
					$description .= '<b>Offer Title: </b>' . $promo->offerDescription . '<br/>';
					$description .= '<b>Discount Value: </b>' . $giveAwayFormated . '<br/>';
					$description .= '<b>Start Date: </b>' . date('d M, Y H:i:s', ($promo->startDate / 1000)) . '<br/>';
					$description .= '<b>End Date: </b>' . date('d M, Y H:i:s', ($promo->endDate / 1000)) . '<br/>';
					$description .= '<b>Type: </b>' . $type . '<br/>';
					$description .= '<b>Discount Type: </b>' . $discountType . '<br/><br/>';


					$filename = UPLOAD_PATH . '/promotion_offer/' . str_replace(':', '-', $promo->offerId) . '-' . $account->seller_id . '.csv';
					if (file_exists($filename))
						$description .= '<a  href="' . str_replace(UPLOAD_PATH, BASE_URL . '/uploads', $filename) . '" target="_blank"  class="btn btn-xs btn-default get_promotion_lid" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" data-entitytype="' . $promo->entityType . '" data-opted="false"><i class="fa fa-download"></i> Download Listings</a>&nbsp;';
					else
						$description .= '<a class="btn btn-xs btn-default get_promotion_lid" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" data-entitytype="' . $promo->entityType . '" data-opted="false"><i class="fa fa-exchange-alt"></i> Get Listings</a>&nbsp;';

					if ($promo->offerStatus == "DISABLED") {
						$description .= '<a class="btn btn-xs btn-default" disabled href="javascript:void(0)"><i></i> DISABLED OFFER</a>&nbsp;';
					} else {
						if ($promo->participationStatus == "NOT_OPTED") {
							$description .= '<a class="btn btn-xs btn-default promotion_opt_in_manual" data-ismanual="true" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" ' . $is_mpInc . ' data-offerValue="' . $giveAway . '" data-offerValueType="' . $giveAwayType . '" data-offerType="' . $type . '" data-startdate="' . ($promo->startDate / 1000) . '" data-enddate="' . ($promo->endDate / 1000) . '" data-entitytype="' . $promo->entityType . '"><i></i> Manual Opt In</a>&nbsp;';
							$description .= '<a class="btn btn-xs btn-default promotion_opt_in" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" ' . $is_mpInc . ' data-offerValue="' . $giveAway . '" data-offerValueType="' . $giveAwayType . '"  data-offerType="' . $type . '" data-startdate="' . ($promo->startDate / 1000) . '" data-enddate="' . ($promo->endDate / 1000) . '" data-entitytype="' . $promo->entityType . '"><i></i> Opt In</a>';
						} else {
							$opted_filename = UPLOAD_PATH . '/promotion_offer/' . str_replace(':', '-', $promo->offerId) . '-' . $account->seller_id . '-opted-fk.csv';
							if (file_exists($opted_filename))
								$description .= '<a  href="' . str_replace(UPLOAD_PATH, BASE_URL . '/uploads', $opted_filename) . '" target="_blank"  class="btn btn-xs btn-default get_promotion_lid" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" data-entitytype="' . $promo->entityType . '" data-opted="true"><i class="fa fa-download"></i> Download Opted Listings</a>&nbsp;';
							else
								$description .= '<a class="btn btn-xs btn-default get_promotion_lid" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" data-entitytype="' . $promo->entityType . '" data-opted="true"><i class="fa fa-exchange-alt"></i> Get Opted Listings</a>&nbsp;';
							// $description .= '<a class="btn btn-xs btn-warning promotion_opt_out" data-accountid="'.$account->account_id.'" data-offerid="'.$promo->offerId.'"><i></i> Opt Out</a>';
							$description .= '<a class="btn btn-xs btn-info promotion_re_opt_in" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" ' . $is_mpInc . ' data-offerValue="' . $giveAway . '" data-offerValueType="' . $giveAwayType . '" data-offerType="' . $type . '" data-startdate="' . ($promo->startDate / 1000) . '" data-enddate="' . ($promo->endDate / 1000) . '" data-entitytype="' . $promo->entityType . '"><i></i> Recheck & Opt In</a>';
							$description .= '<a class="btn btn-xs btn-info promotion_re_opt_in_manual" data-ismanual="true" data-accountid="' . $account->account_id . '" data-offerid="' . $promo->offerId . '" ' . $is_mpInc . ' data-offerValue="' . $giveAway . '" data-offerValueType="' . $giveAwayType . '" data-offerType="' . $type . '" data-startdate="' . ($promo->startDate / 1000) . '" data-enddate="' . ($promo->endDate / 1000) . '" data-entitytype="' . $promo->entityType . '"><i></i>Add Listings</a>';
						}
					}
					$description .= '<div class="promotion_response"></div>';


					$return['events'][$i]['description'] = $description;
					$return['events'][$i]['entityType'] = $promo->entityType;
					$return['events'][$i]['start'] = date('Y-m-d\TH:i:s', ($promo->startDate / 1000));
					$return['events'][$i]['end'] = date('Y-m-d\TH:i:s', ($promo->endDate / 1000));
					$color = "lightblue";
					if ($type == "MP_INC")
						$color = "purple";
					if ($type == "DOTW")
						$color = "#dbaf2c";
					if ($type == "DOTD")
						$color = "lightpink";
					if ($type == "DOTH")
						$color = "orange";
					// if ($type == "None")
					// 	$color = "lightblue";

					if ($promo->participationStatus == "OPTED_IN")
						$color = "lightgreen";

					$return['events'][$i]['color'] = $color;

					$i++;
				}
			}
			echo json_encode($return);
			break;

		case 'promotion_opt_in':
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : "";
			$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : "";
			$isMPInc = (isset($_REQUEST['isMPInc']) && ($_REQUEST['isMPInc'] === 'true'));
			$mpIncAmt = isset($_REQUEST['mpIncAmt']) ? (int)$_REQUEST['mpIncAmt'] : NULL;
			$offerType = isset($_REQUEST['offerType']) ? $_REQUEST['offerType'] : "";
			$offerValue = isset($_REQUEST['offerValue']) ? (int)$_REQUEST['offerValue'] : NULL;
			$offerValueType = isset($_REQUEST['offerValueType']) ? (int)$_REQUEST['offerValueType'] : NULL;
			$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : "";

			if ($offerId == "" || $account == "" || $startDate == "" || $endDate == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$filename = $fk->get_flipkart_promotion_lid($offerId, false, "", $entityType);
			if (isset($filename['error'])) {
				$return = array("type" => "error", "title" => "Eligible Listings", "message" => $filename['error']);
				echo json_encode($return);
				exit;
			}
			// $filename = 'nb-mp-03e9da6710-3e16084f68fb4e38.csv';
			$listings = $fk->check_promotion_eligibility(UPLOAD_PATH . '/promotion_offer/' . $filename, $offerValue, $offerValueType, $startDate, $endDate, $mpIncAmt);
			$optin_listings = array();
			if ($listings->eligible) {
				$optin_listings = array_keys(json_decode(json_encode($listings->eligible), true));

				$response = $fk->optin_flipkart_promotion($offerId, $optin_listings, $entityType);
				if ($response) {
					$opted = array();
					$errors = array();
					foreach ($optin_listings as $listing) {
						$details = array(
							'offerId' => $offerId,
							'listingId' => $listing,
							'accountId' => $selected_account,
							'promoOptInDate' => date('Y-m-d H:i:s', time()),
							'promoStartDate' => date('Y-m-d H:i:s', ($startDate)),
							'promoEndDate' => date('Y-m-d H:i:s', ($endDate)),
							'promoType' => $offerType,
							'promoValue' => $offerValue,
							'promoValueType' => $offerValueType,
							'promoMpInc' => $mpIncAmt,
						);
						if ($db->insert(TBL_FK_PROMOTIONS, $details)) {
							$opted[] = $listing;
						} else {
							$errors[] = $db->getLastError();
						}
					}
					$return = array("type" => "success", "title" => "Promotions Optin", "message" => "Successfully opted into promotion for " . count($opted) . " listings", "errors" => $errors, "listings" => json_encode($opted));
				} else {
					$return = array("type" => "error", "title" => "Promotions Optin", "message" => "Unable to opt in to promotion", "listings" => json_encode($opted), "response" => $response);
				}
			} else {
				$return = array("type" => "info", "title" => "Promotions Optin", "message" => "Unable to optin. No eligible listings found.");
			}

			echo json_encode($return);
			break;

		case 'promotion_opt_in_manual':
			$handle = new \Verot\Upload\Upload($_FILES['optin_csv']);
			if ($handle->uploaded) {
				// resize tool
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->file_new_name_ext = '';
				$handle->process(UPLOAD_PATH . "/promotion_offer/optin_manual/");
				$handle->clean();
				$filename = UPLOAD_PATH . "/promotion_offer/optin_manual/" . $handle->file_dst_name;
				$listings = csv_to_array($filename);
			}

			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : "";
			$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : "";
			$preSaleStarts = isset($_REQUEST['preSaleStarts']) ? $_REQUEST['preSaleStarts'] : "";
			$isMPInc = (isset($_REQUEST['isMPInc']) && ($_REQUEST['isMPInc'] === 'true'));
			$mpIncAmt = isset($_REQUEST['mpIncAmt']) ? (int)$_REQUEST['mpIncAmt'] : NULL;
			$offerType = isset($_REQUEST['offerType']) ? $_REQUEST['offerType'] : "";
			$offerValue = isset($_REQUEST['offerValue']) ? (int)$_REQUEST['offerValue'] : NULL;
			$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : "";

			if ($offerId == "" || $account == "" || $startDate == "" || $endDate == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$details = array();
			$optin_listings = array();

			if ($listings) {
				foreach ($listings as $listing) {
					if ($listing['Is Eligible?'] == "No")
						continue;

					$optin_listings[] = $listing['LID'];

					$details[] = array(
						'offerId' => $offerId,
						'listingId' => $listing['LID'],
						'accountId' => $selected_account,
						'promoOptInDate' => date('Y-m-d H:i:s', time()),
						'promoPreSaleStarts' => date('Y-m-d H:i:s', ($preSaleStarts)),
						'promoStartDate' => date('Y-m-d H:i:s', ($startDate)),
						'promoEndDate' => date('Y-m-d H:i:s', ($endDate)),
						'promoType' => $offerType,
						'promoValue' => $offerValue,
						'promoMpInc' => $mpIncAmt,
					);
				}

				$response = $fk->optin_flipkart_promotion($offerId, $optin_listings, $entityType);
				if ($response) {
					if ($db->insertMulti(TBL_FK_PROMOTIONS, $details)) {
						$return = array("type" => "success", "title" => "Promotions Optin", "message" => "Successfully opted into promotion for " . count($optin_listings) . " listings", "errors" => $errors, "listings" => json_encode($optin_listings));
					} else {
						$return = array("type" => "warning", "title" => "Promotions Optin", "message" => "Unable to add optin listings.", "details" => json_encode($details));
					}
				}
			} else {
				$return = array("type" => "info", "title" => "Promotions Optin", "message" => "Unable to optin. No eligible listings found.");
			}

			echo json_encode($return);
			break;

		case 'promotion_opt_out':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			if ($offerId == "" || $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$filename = $fk->get_flipkart_promotion_lid($offerId, true);
			var_dump($filename);
			// $fk->update_flipkart_promotion($offerId, $filename);
			break;

		case 'promotion_update':
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : "";
			$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : "";
			$isMPInc = (isset($_REQUEST['isMPInc']) && ($_REQUEST['isMPInc'] === 'true'));
			$mpIncAmt = isset($_REQUEST['mpIncAmt']) ? (int)$_REQUEST['mpIncAmt'] : NULL;
			$offerType = isset($_REQUEST['offerType']) ? $_REQUEST['offerType'] : "";
			$offerValue = isset($_REQUEST['offerValue']) ? (int)$_REQUEST['offerValue'] : NULL;
			$offerValueType = isset($_REQUEST['offerValueType']) ? (int)$_REQUEST['offerValueType'] : NULL;
			$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : "";
			$listingIds = isset($_REQUEST['listingIds']) ? $_REQUEST['listingIds'] : "";
			$updateType = isset($_REQUEST['updateType']) ? $_REQUEST['updateType'] : "";

			if ($offerId == "" || $account == "" || $startDate == "" || $endDate == "" || $updateType == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$elegible_listings = array();
			if (empty($listingIds)) {
				$fk = new flipkart_dashboard($accounts[$account_key]);
				$filename = $fk->get_flipkart_promotion_lid($offerId);
				$listings = $fk->check_promotion_eligibility(UPLOAD_PATH . '/promotion_offer/' . $filename, $offerValue, $offerValueType, $startDate, $endDate, $mpIncAmt);
				if ($listings->eligible) {
					foreach ($listings->eligible as $listing) {
						$elegible_listings[] = $listing->listing_id;
					}
				}
			} else {
				$elegible_listings = explode(',', str_replace(' ', '', $listingIds));
			}

			$optin_listings = array();
			$errors = array();
			if (!empty($elegible_listings)) {
				$response = $fk->update_flipkart_promotion($offerId, $updateType, $elegible_listings, $entityType); // REMOVE LID FROM OFFER
				if ($response) {
					foreach ($elegible_listings as $listing) {
						$db->where('offerId', $offerId);
						$db->where('listingId', $listing);
						if ($db->has(TBL_FK_PROMOTIONS))
							continue;

						$details = array(
							'offerId' => $offerId,
							'listingId' => $listing,
							'accountId' => $selected_account,
							'promoOptInDate' => date('Y-m-d H:i:s', time()),
							'promoStartDate' => date('Y-m-d H:i:s', $startDate), //UNIX TIMESTAMP
							'promoEndDate' => date('Y-m-d H:i:s', $endDate), //UNIX TIMESTAMP
							'promoType' => $offerType,
							'promoValue' => $offerValue,
							'promoValueType' => $offerValueType,
						);
						if ($db->insert(TBL_FK_PROMOTIONS, $details)) {
							$optin_listings[] = $listing;
						} else {
							$errors[] = $db->getLastQuery();
						}
					}
					$return = array("type" => "success", "title" => "OptedIn Promotion Update", "message" => "Successfully updated promotion for " . count($optin_listings) . " listings", "errors" => $errors, "listings" => json_encode($elegible_listings));
				} else {
					$return = array("type" => "error", "title" => "OptedIn Promotion Update", "message" => "Unable to update listings on Flipkart", "listings" => json_encode($elegible_listings));
				}
			} else {
				$return =  array("type" => "info", "title" => "OptedIn Promotion Update", "message" => "Unable to optin. No eligible listings found.");
			}

			echo json_encode($return);

			// $optin_listings = array();
			// $errors = array();
			// if ($listings->eligible){
			// 	foreach ($listings->eligible as $listing) {
			// 		$details = array(
			// 			'offerId' => $offerId,
			// 			'listingId' => $listing->listing_id,
			// 			'accountId' => $selected_account,
			// 			'promoOptInDate' => date('Y-m-d H:i:s', time()),
			// 			'promoStartDate' => date('Y-m-d H:i:s', ($startDate)),
			// 			'promoEndDate' => date('Y-m-d H:i:s', ($endDate)),
			// 			'promoType' => $offerType,
			// 			'promoValue' => $offerValue,
			// 		);
			// 		if ($db->insert(TBL_FK_PROMOTIONS, $details)){
			// 			$optin_listings[] = $listing->listing_id;
			// 		} else {
			// 			$errors[] = $db->getLastQuery();
			// 		}
			// 	}
			// }

			// if ($response)
			// 	echo json_encode(array("type" => "success", "message" => "Successfully updated promotion for ".count($optin_listings)." listings", "errors" => $errors, "listings" => json_encode($listingIds)));
			// else
			// 	echo json_encode(array("type" => "error", "message" => "Unable to update promotion", "listings" => json_encode($listingIds)));


			break;

		case 'promotion_update_manual':
			$handle = new \Verot\Upload\Upload($_FILES['optin_csv']);
			if ($handle->uploaded) {
				// resize tool
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->file_new_name_ext = '';
				$handle->process(UPLOAD_PATH . "/promotion_offer/optin_manual/");
				$handle->clean();
				$filename = UPLOAD_PATH . "/promotion_offer/optin_manual/" . $handle->file_dst_name;
				$listings = csv_to_array($filename);
			}
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : "";
			$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : "";
			$isMPInc = (isset($_REQUEST['isMPInc']) && ($_REQUEST['isMPInc'] === 'true'));
			$mpIncAmt = isset($_REQUEST['mpIncAmt']) ? (int)$_REQUEST['mpIncAmt'] : NULL;
			$offerType = isset($_REQUEST['offerType']) ? $_REQUEST['offerType'] : "";
			$offerValue = isset($_REQUEST['offerValue']) ? (int)$_REQUEST['offerValue'] : NULL;
			$listingIds = isset($_REQUEST['listingIds']) ? $_REQUEST['listingIds'] : "";
			$updateType = isset($_REQUEST['updateType']) ? $_REQUEST['updateType'] : "";
			$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : "";

			if ($offerId == "" || $account == "" || $startDate == "" || $endDate == "" || $updateType == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$details = array();
			$optin_listings = array();

			if ($listings) {
				foreach ($listings as $listing) {
					if ($listing['Is Eligible?'] == "No")
						continue;

					$db->where('offerId', $offerId);
					$db->where('listingId', $listing['LID']);
					if ($db->has(TBL_FK_PROMOTIONS))
						continue;

					$optin_listings[] = $listing['LID'];

					$details[] = array(
						'offerId' => $offerId,
						'listingId' => $listing['LID'],
						'accountId' => $selected_account,
						'promoOptInDate' => date('Y-m-d H:i:s', time()),
						'promoStartDate' => date('Y-m-d H:i:s', ($startDate)),
						'promoEndDate' => date('Y-m-d H:i:s', ($endDate)),
						'promoType' => $offerType,
						'promoValue' => $offerValue,
						'promoMpInc' => $mpIncAmt,
					);
				}

				$response = $fk->update_flipkart_promotion($offerId, $updateType, $optin_listings, $entityType); // ADD LID TO OFFER
				if ($response) {
					$db->setQueryOption('IGNORE');
					if ($db->insertMulti(TBL_FK_PROMOTIONS, $details)) {
						$return = array("type" => "success", "title" => "OptedIn Promotion Update", "message" => "Successfully updated promotion for " . count($optin_listings) . " listings", "listings" => json_encode($optin_listings));
					} else {
						$return = array("type" => "error", "title" => "OptedIn Promotion Update", "message" => "Unable to update listings on Flipkart", "listings" => json_encode($optin_listings), "details" => json_encode($details), "response" => $response, "db" => $db->getLastError());
					}
				}
			} else {
				$return =  array("type" => "info", "title" => "OptedIn Promotion Update", "message" => "Unable to optin. No eligible listings found.");
			}

			echo json_encode($return);

			///////////

			// // find account id to construct fk
			// $selected_account = (int)$account;

			// $account_key = "";
			// foreach ($accounts as $a_key => $account) {
			// 	if ($account->account_id == $selected_account)
			// 		$account_key = $a_key;
			// }

			// $elegible_listings = array();
			// if (empty($listingIds)){
			// 	$fk = new flipkart_dashboard($accounts[$account_key]);
			// 	$filename = $fk->get_flipkart_promotion_lid($offerId);
			// 	$listings = $fk->check_promotion_eligibility(UPLOAD_PATH.'/promotion_offer/'.$filename, $offerValue, $startDate, $endDate, $mpIncAmt);
			// 	if ($listings->eligible){
			// 		foreach ($listings->eligible as $listing) {
			// 			$elegible_listings[] = $listing->listing_id;
			// 		}
			// 	}
			// } else {
			// 	$elegible_listings = explode(',', str_replace(' ', '', $listingIds));
			// }

			// $optin_listings = array();
			// $errors = array();
			// if (!empty($elegible_listings)){
			// 	$response = $fk->update_flipkart_promotion($offerId, $updateType, $elegible_listings); // REMOVE LID FROM OFFER
			// 	if ($response){
			// 		foreach ($elegible_listings as $listing) {
			// 			$db->where('offerId', $offerId);
			// 			$db->where('listingId', $listing);
			// 			if ($db->has(TBL_FK_PROMOTIONS))
			// 				continue;

			// 			$details = array(
			// 				'offerId' => $offerId,
			// 				'listingId' => $listing,
			// 				'accountId' => $selected_account,
			// 				'promoOptInDate' => date('Y-m-d H:i:s', time()),
			// 				'promoStartDate' => date('Y-m-d H:i:s', $startDate), //UNIX TIMESTAMP
			// 				'promoEndDate' => date('Y-m-d H:i:s', $endDate), //UNIX TIMESTAMP
			// 				'promoType' => $offerType,
			// 				'promoValue' => $offerValue,
			// 			);
			// 			if ($db->insert(TBL_FK_PROMOTIONS, $details)){
			// 				$optin_listings[] = $listing;
			// 			} else {
			// 				$errors[] = $db->getLastQuery();
			// 			}
			// 		}
			// 		$return = array("type" => "success", "title" => "OptedIn Promotion Update", "message" => "Successfully updated promotion for ".count($optin_listings)." listings", "errors" => $errors, "listings" => json_encode($elegible_listings));
			// 	} else {
			// 		$return = array("type" => "error", "title" => "OptedIn Promotion Update", "message" => "Unable to update listings on Flipkart", "listings" => json_encode($elegible_listings));
			// 	}
			// } else {
			// 	$return =  array("type" => "info", "title" => "OptedIn Promotion Update", "message" => "Unable to optin. No eligible listings found.");
			// }

			// echo json_encode($return);
			break;

		case 'get_promotion_lid':

			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$offerId = isset($_REQUEST['offerId']) ? $_REQUEST['offerId'] : "";
			$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : "";
			$opted = isset($_REQUEST['opted']) ? filter_var($_REQUEST['opted'], FILTER_VALIDATE_BOOLEAN) : false;

			if ($offerId == "" || $account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$filename = $fk->get_flipkart_promotion_lid($offerId, $opted, "", $entityType);
			if (isset($filename['error'])) {
				$return = array("type" => "error", "title" => "Eligible Listings", "message" => $filename['error']);
				echo json_encode($return);
				exit;
			}
			echo json_encode(array('type' => 'success', 'message' => 'Successfully Downloaded Eligible Listings for offer ID: ' . $offerId, 'file_path' => BASE_URL . '/uploads/promotion_offer/' . $filename));

			break;

		case 'update_promotion_lid':
			$errors = array();
			$success = array();
			foreach ($accounts as $account) {
				$db->where('accountId', $account->account_id);
				$db->where('promoEndDate', date('Y-m-t 23:59:59', time())); // Last day of the month
				$offer = $db->objectBuilder()->getOne(TBL_FK_PROMOTIONS, array('offerId, promoStartDate, promoEndDate, promoMpInc'));
				if ($offer->offerId) {
					$fk = new flipkart_dashboard($account);
					$filename = $fk->get_flipkart_promotion_lid($offer->offerId, true, time());
					$listings = csv_to_array(UPLOAD_PATH . '/promotion_offer/' . $filename);
					foreach ($listings as $listing) {
						$db->where('offerId', $offer->offerId);
						$db->where('accountId', $account->account_id);
						$db->where('listingId', $listing['Listing ID']);
						if (!$db->has(TBL_FK_PROMOTIONS)) {
							$details = array(
								'offerId' => $offer->offerId,
								'listingId' => $listing['Listing ID'],
								'accountId' => $account->account_id,
								'promoOptInDate' => date('Y-m-d H:i:s', time()),
								'promoStartDate' => $offer->promoStartDate,
								'promoEndDate' => $offer->promoEndDate,
								'promoType' => 'MP_INC',
								'promoMpInc' => $offer->promoMpInc,
							);
							if (!$db->insert(TBL_FK_PROMOTIONS, $details)) {
								$errors[] = $db->getLastQuery();
							} else {
								$success[] = 'Successfully added ' . $listing['Listing ID'] . ' of offer ID ' . $offer->offerId . ' for account ' . $account->account_name;
							}
						}
					}
				}
			}
			echo '<pre>';
			echo 'Success:<br />';
			print_r($success);
			echo '<br /><br />Error:<br />';
			print_r($error);
			break;

		case 'check_promotion_status': // INCOMPLETE

			$db->where('promoEndDate', date('Y-m-t H:i:s', time()), "<=");
			$offer = $db->objectBuilder()->getOne(TBL_FK_PROMOTIONS, array('offerId, promoStartDate, promoEndDate, promoMpInc'));
			$fk = new flipkart_dashboard($accounts[$account_key]);
			foreach ($offers as $offer) {
				// $fk->check_flipkart_promotion($offerId);
				// if ($response->result->offerStatus == "PAUSED"){
				// 	$db->where('offerId', $offerId);
				// 	if ($db->has(TBL_FK_PROMOTIONS_STATUS)){
				// 		$db->update();
				// 	} else {
				// 		$db->insert();
				// 	}
				// }
			}

			break;

		case 'set_weekly_holiday':
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			// $enable = 0;
			// if (date('D') == 'Fri'){
			$enable = 1;
			// }

			if ($account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$response = $fk->update_holiday($enable);
			if ($response == "{}")
				echo json_encode(array('type' => 'success', 'message' => 'Successfully updated weekly holiday to ' . $enable));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Error updating weekly holiday. Error: ' . $response));

			break;

		case 'get_sunday_orders_count':
			$db->where('dispatchByDate', date('c', strtotime("tomorrow 02:00 PM"))); // Specify the date);
			$orders = $db->getValue(TBL_FK_ORDERS, 'COUNT(*)');
			if ($orders > (int)0) {
				$msg = str_replace(array('##NUMBER##', '##ACCOUNT##'), array($orders, "Sunday Pickup"), get_template('sms', 'rtd_breach'));
				$ssms = $sms->send_sms($msg, array(get_option('sms_notification_number')), 'IMSDAS');
				echo $msg . '<br />';
			}
			break;

		case 'request_flipkart_fulfilment_report': // FA
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			$warehouseId = isset($_REQUEST['warehouseId']) ? $_REQUEST['warehouseId'] : "";
			$from_date = isset($_REQUEST['from_date']) ? $_REQUEST['from_date'] : "";
			$to_date = isset($_REQUEST['to_date']) ? $_REQUEST['to_date'] : "";

			if ($account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);

			$warehouses = array();
			if (empty($warehouseId))
				$warehouses = json_decode($accounts[$account_key]->mp_warehouses, true);
			else
				$warehouses[] = $warehouseId;

			foreach ($warehouses as $warehouse) {
				$data['warehouse_id'] = $warehouse;
				if (empty($from_date)) {
					$data['from_date'] = date('Y-m-d', strtotime('-3 days'));
					$data['to_date'] = date('Y-m-d', strtotime('-2 days'));
				} else {
					$data['from_date'] = date('Y-m-d', strtotime($from_date));
					$data['to_date'] = date('Y-m-d', strtotime($to_date));
				}
				$return = $fk->request_report('create_ff_orders_report', $data);
				if ($return['status'] == "Error")
					echo 'Error generating orders report for ' . $warehouse . '. Error: ' . $return['response']->error->message . '<br />';
				else
					echo 'New report with ID ' . $return['reportId'] . ' successfully requested for ' . $warehouse . '.<br />';
			}

			break;

		case 'get_flipkart_fulfilment_report_status': // FA
			$account = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : "";
			if ($account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);

			$db->where('reportType', 'Flipkart Fulfilment Orders Report');
			$db->where('reportStatus', 'queued');
			$db->where('accountId', $accounts[$account_key]->account_id);
			$reports = $db->objectBuilder()->get(TBL_FK_REPORTS);
			if ($reports) {
				foreach ($reports as $report) {
					$data = array(
						'reportId' => $report->reportId,
						'warehouse_id' => $report->reportSubType
					);
					$report_details = $fk->request_report('get_ff_orders_report_status', $data);
					$lookup_variable = 'job_id';
					$request_object = 'jobs';

					$report_details = json_decode($report_details);
					$report_details = findObjectById($report_details->{$request_object}, $lookup_variable, $report->reportId);
					$return = array('type' => 'info', 'message' => 'No update in status of report id ' . $report->reportId . '. Try again later.');
					if ($report_details->status == "FILE_STORED" || $report_details->status == "COMPLETED") {
						$details = array(
							'reportStatus' => 'generated',
							'reportFileFormat' => 'csv'
						);
						$db->where('reportId', $report->reportId);
						if ($db->update(TBL_FK_REPORTS, $details)) {
							$return = 'Status of report id ' . $report->reportId . ' updated to generated.';
						} else {
							$return = 'Unable to update status of report id ' . $report->reportId . '. Try again later.';
						}
					}
					echo $return . '<br />';
				}
			} else {
				echo 'No reports are queued.<br />';
			}
			break;

		case 'import_flipkart_fulfilment_orders': // FA
			ignore_user_abort(true); // optional
			$reportId = isset($_REQUEST['reportId']) ? $_REQUEST['reportId'] : "";
			$warehouse_id = isset($_REQUEST['warehouse_id']) ? $_REQUEST['warehouse_id'] : "";
			$account = isset($_REQUEST['account_id']) ? (int)$_REQUEST['account_id'] : "";
			if ($account == "")
				return;

			// find account id to construct fk
			$selected_account = (int)$account;

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key]);
			$order_files = array();
			if (empty($reportId)) {
				$db->where('accountId', $selected_account);
				$db->where('reportType', 'Flipkart Fulfilment Orders Report');
				$db->where('reportStatus', 'generated');
				$db->orderBy('createdDate', 'DESC');
				$reports = $db->objectBuilder()->get(TBL_FK_REPORTS, NULL, 'reportId, reportSubType');
				$reports_processed = $reports;
				if (!$reports) {
					echo json_encode(array('type' => 'error', 'message' => 'Nothing to import'));
					return;
				}
				foreach ($reports as $report) {
					if (!file_exists(UPLOAD_PATH . '/ff_orders/' . $report->reportId . '.csv'))
						$order_files[$report->reportSubType][] = $fk->request_report('download_ff_orders_report', (array)$report);
					else
						$order_files[$report->reportSubType][] = $report->reportId . '.csv';
				}
			} else {
				$reports = array();
				$report = new stdClass();
				$report->reportId = $reportId;
				$reports[0] = $report;
				$reports_processed = $reports;
				if (!file_exists(UPLOAD_PATH . '/ff_orders/' . $reportId . '.csv'))
					$order_files[$warehouse_id][] = $fk->request_report('download_ff_orders_report', array('reportId' => $reportId, 'warehouse_id' => $warehouse_id, 'file_format' => 'csv'));
				else
					$order_files[$warehouse_id][] = $reportId . '.csv';
			}

			if (!empty($order_files)) {
				$file_orders = array();
				foreach ($order_files as $warehouse_id => $warehouse_order_files) {
					foreach ($warehouse_order_files as $order_file) {
						$warehouses_orderes = array();
						$filename = UPLOAD_PATH . '/ff_orders/' . $order_file;
						if (file_exists($filename)) {
							$orders_list = csv_to_array($filename);
							foreach ($orders_list as $order) {
								if ($order["Type"] == "SalesInvoice") {
									$file_orders[str_replace('.csv', '', $order_file)][$warehouse_id][] = $order;
									$warehouses_orderes[] = $order;
								}
							}

							if (empty($warehouses_orderes)) {
								$db->where('reportId', str_replace('.csv', '', $order_file));
								$db->update(TBL_FK_REPORTS, array('reportStatus' => 'completed'));
							}
						}
					}
				}
			} else {
				echo json_encode(array('type' => 'error', 'message' => 'Unable to locate file or nothing to import'));
				return;
			}

			$success = 0;
			$existing = 0;
			$error = 0;
			$error_report_id = array();
			$report_errors = array();
			$error_message = array();
			$odrs = array();
			$total = 0;
			foreach ($file_orders as $report_id => $orders) {
				if ($orders) {
					foreach ($orders as $warehouse_id => $warehouse_orders) {
						if ($warehouse_orders) {
							$total += count($warehouse_orders);
							foreach ($warehouse_orders as $order) {
								$orderItemId = trim(str_replace('"', '', $order['Order Item ID']));
								$db->where('orderItemId', $orderItemId);
								if (!$db->has(TBL_FK_ORDERS)) {
									$return = $fk->order_details_convert_fbf_order_to_v3_api_response($order, $warehouse_id);
									if (isset($return->shipments) && !is_null($return->shipments)) {
										$insert = $fk->insert_orders($return->shipments, 'FBF');
										if (strpos($insert, 'INSERT: New Order with order ID') !== FALSE || strpos($insert, 'UPDATE: Updated Order with order ID') !== FALSE) {
											$success++;
										} else if (strpos($insert, 'INSERT: Already exists order with order ID') !== FALSE) {
											$existing++;
										} else {
											$report_errors[$report_id][] = $insert;
											$error_message[] = $insert;
											$error++;
										}
										$log->write($insert, 'fbf-import');
									} else {
										$report_errors[$report->reportId][] = $insert;
										$error_message[] = $return;
										$log->write($return, 'fbf-import');
										$error++;
									}
								} else {
									$existing++;
								}
							}


							if (empty($error_message)) {
								$db->where('reportId', $report_id);
								$db->update(TBL_FK_REPORTS, array('reportStatus' => 'completed'));
							} else {
								$error_report_id[] = $report_id;
							}
							$error_message = array();
						}
					}
				} else {
					$db->where('reportId', $report_id);
					$db->update(TBL_FK_REPORTS, array('reportStatus' => 'completed'));
				}
			}

			$response = array('type' => 'success', 'total' => $total, 'success' => $success, 'existing' => $existing, 'error' => $error, 'reports_processed' => $reports_processed, 'report_errors' => $report_errors, 'error_report_id' => $error_report_id);
			$log->write($response, 'fbf-import');
			echo json_encode($response);
			break;

		case 'get_closed_incidents':
			$db->where('incidentStatus', 'closed');
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=fi.accountId");
			$db->orderBy('fi.closedDate', 'DESC');
			$incidents = $db->get(TBL_FK_INCIDENTS . ' fi', null, 'a.account_name,  fi.incidentId, fi.issueType, fi.subject, fi.referenceId, fi.baseIncident, fi.mergedToIncident, fi.createdDate, fi.updatedDate, fi.closedDate');

			$return = array();
			$i = 0;
			foreach ($incidents as $incident) {
				$j = 0;
				$incidentId = "";
				foreach ($incident as $s_value) {
					if ($j == 1 || $j == 6) {
						$return['data'][$i][] = '<a target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $s_value . '/SELLER_SELF_SERVE">' . $s_value . '</a>';
					} else if ($j == 4) {
						$itemIds = json_decode($s_value);
						$list = "";
						foreach ($itemIds as $orderItemId => $status) {
							if ($status)
								$list .= '<a disabled="disabled" class="btn btn-xs btn-success btn-tags">' . $orderItemId . '</a>';
							else
								$list .= '<a disabled="disabled" data-incidentid="' . $incident["incidentId"] . '" data-referenceId="' . $referenceId . '" class="btn btn-xs btn-default btn-tags">' . $orderItemId . ' <i class="fa fa-check"></i></a>';
						}
						$return['data'][$i][] = $list;
					} else if ($j == 7 || $j == 8 || $j == 9)
						$return['data'][$i][] = date("d M Y, H:i:s", strtotime($s_value));
					else {
						$return['data'][$i][] = $s_value;
					}
					$j++;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'create_support_ticket':
			$account = isset($_REQUEST['account']) ? $_REQUEST['account'] : "";
			$issueType = isset($_REQUEST['issueType']) ? $_REQUEST['issueType'] : "";
			$subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : "";
			$content = isset($_REQUEST['content']) ? $_REQUEST['content'] : "";
			$mandatory_fields = isset($_REQUEST['mandatory_fields']) ? json_decode($_REQUEST['mandatory_fields'], true) : "";
			$insert = isset($_REQUEST['insert']) ? filter_var($_REQUEST['insert'], FILTER_VALIDATE_BOOLEAN) : true;

			if ($account == "" || $content == "" || $issueType == "" || $mandatory_fields == "" || empty($mandatory_fields))
				return;

			// find account id to construct fk
			$account_ky = (int) str_replace('acc_', '', $account);
			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account_ky == $account->account_id)
					$account_key = $a_key;
			}

			$return = array();
			$fk = new flipkart_dashboard($accounts[$account_key]);
			$return = $fk->create_ticket($issueType, $mandatory_fields, $subject, $content, $insert);
			echo json_encode($return);
			break;

		case 'update_incident_status':
			$incidentId = $_POST['incidentId'];

			$db->where('incidentId', $incidentId);
			$referenceIds = $db->getValue(TBL_FK_INCIDENTS, 'referenceId');
			$referenceIds = str_replace('false', 'true', $referenceIds);

			$details = array(
				'referenceId' => $referenceIds,
				'incidentStatus' => 'closed',
				'closedDate' => $db->now(),
			);
			$return = array();
			$db->where('incidentId', $incidentId);
			if ($db->update(TBL_FK_INCIDENTS, $details)) {
				$return["type"] = "success";
				$return["message"] = "Successfully updated status of Incident " . $incidentId;
			} else {
				$return["type"] = "error";
				$return["message"] = "Unable to updated status of Incident " . $incidentId . " ERROR:" . $db->getLastError();
			}
			echo json_encode($return);
			break;

		case 'update_incident_order_status':
			$referenceId = $_POST['referenceId'];
			$incidentId = $_POST['incidentId'];

			$db->where('incidentId', $incidentId);
			$referenceIds = $db->getValue(TBL_FK_INCIDENTS, 'referenceId');
			$referenceIds = str_replace('"' . $referenceId . '": false', '"' . $referenceId . '": true', $referenceIds);

			$details = array('referenceId' => $referenceIds);
			$return = array();
			if (strpos($referenceIds, 'false') === false) {
				$return['all'] = 'closed';
				$details['incidentStatus'] = 'closed';
			}

			$db->where('incidentId', $incidentId);
			if ($db->update(TBL_FK_INCIDENTS, $details)) {
				$return["type"] = "success";
				$return["message"] = "Successfully updated status of referenceId " . $referenceId . " for Incident " . $incidentId;
			} else {
				$return["type"] = "error";
				$return["message"] = "Unable to updated status of " . $referenceId . " for Incident " . $incidentId . " ERROR:" . $db->getLastError();
			}
			echo json_encode($return);
			break;

		case 'merge_incidents':
			$incidentId = $_REQUEST['incidentId'];
			$mergeToIncidentId = trim($_REQUEST['mergeToIncidentId']);
			$merge_references = array();

			$db->where('incidentId', $mergeToIncidentId);
			if ($db->has(TBL_FK_INCIDENTS)) {
				$db->where('incidentId', $incidentId);
				$incidentIdData = $db->getOne(TBL_FK_INCIDENTS, 'referenceId, baseIncident');
				$merge_references[] = json_decode($incidentIdData['referenceId'], true);
				$baseIncidents[] = $incidentIdData['baseIncident'];

				$db->where('incidentId', $mergeToIncidentId);
				$mergeToIncidentIdData = $db->getOne(TBL_FK_INCIDENTS, 'referenceId, baseIncident');
				$merge_references[] = json_decode($mergeToIncidentIdData['referenceId'], true);
				$baseIncidents[] = $mergeToIncidentIdData['baseIncident'];

				$referenceIncidents = array();
				if (!empty($baseIncidents)) {
					foreach ($baseIncidents as $baseIncident) {
						if (!is_null($baseIncident) && !empty($baseIncident)) {
							$base = explode(', ', $baseIncident);
							$referenceIncidents += $base;
						}
					}
				}
				$referenceIncidents[] = $incidentId;

				$referenceIds = array();
				foreach ($merge_references as $merge_reference) {
					if (!is_null($merge_reference))
						$referenceIds += $merge_reference;
				}

				$db->where('incidentId', $incidentId);
				$details = array(
					'incidentStatus' => 'closed',
					'closedDate' => $db->now(),
					'mergedToIncident' => $mergeToIncidentId,
				);

				if ($db->update(TBL_FK_INCIDENTS, $details)) {
					$db->where('incidentId', $mergeToIncidentId);
					$details = array(
						'referenceId' => json_encode($referenceIds),
						'baseIncident' => implode(', ', $referenceIncidents)
					);
					if ($db->update(TBL_FK_INCIDENTS, $details)) {
						$return["type"] = "success";
						$return["message"] = "Successfully merged Incident " . $mergeToIncidentId . " to Incident " . $incidentId;
					} else {
						$return["type"] = "error";
						$return["message"] = "Unable to merge incident " . $mergeToIncidentId . " to incident " . $incidentId . " ERROR:" . $db->getLastError();
					}
				} else {
					$return["type"] = "error";
					$return["message"] = "Unable to merge incident " . $mergeToIncidentId . " to incident " . $incidentId . " ERROR:" . $db->getLastError();
				}
			} else {
				$return["type"] = "error";
				$return["message"] = "Merge to incident " . $mergeToIncidentId . " not found.";
			}

			echo json_encode($return);

			break;

		case 'add_new_incident':
			$accountId = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : "";
			$issueType = isset($_REQUEST['incident_type']) ? $_REQUEST['incident_type'] : "";
			$incidentId = isset($_REQUEST['incident_id']) ? $_REQUEST['incident_id'] : "";
			$subject = isset($_REQUEST['subject']) ? $_REQUEST['subject'] : "";
			$baseIncident = isset($_REQUEST['base_incident']) ? $_REQUEST['base_incident'] : NULL;
			$order_item_ids = isset($_REQUEST['order_item_ids']) ? $_REQUEST['order_item_ids'] : "";

			if (empty($accountId) || empty($issueType) || empty($incidentId) || empty($subject))
				return;

			add_new_incident($accountId, $issueType, $incidentId, $subject, $baseIncident, $order_item_ids);
			break;

		case 'create_new_incident':
			# code...
			break;

			// OVERVIEW
		case 'overview_get_overall':
			// ORDERS - FBF
			// ORDERS - NON-FBF
			// AVERAGE SP - HIDDEN
			// SALES - HIDDEN

			// RETURNS - CUSTOMER
			// RETURNS - COURIER
			// REPLACEMENTS - HIDDEN
			// CLAIMS - HIDDEN

			// UPCOMING PAYMENTS - HIDDEN
			// SETTLED PAYMENTS - HIDDEN
			// SETTLED CLAIMS - HIDDEN
			// SETTLED CLAIMS AMOUNT - HIDDEN
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');	
			// echo '<pre>';

			if (empty($_REQUEST['start_date']))
				$start_date = strtotime('today -1 Month');
			else
				$start_date = strtotime($_REQUEST['start_date']);

			if (empty($_REQUEST['end_date']))
				$end_date = strtotime('today');
			else
				$end_date = strtotime($_REQUEST['end_date']);

			$diff = date_diff(date_create(date('Y-m-d', $start_date)), date_create(date('Y-m-d', $end_date)));
			$previous_start_date = date_sub(date_create(date('Y-m-d', $start_date)), date_interval_create_from_date_string(((int)$diff->days + 1) . ' days'));
			$previous_start_date = strtotime(date_format($previous_start_date, 'Y-m-d'));
			$previous_end_date = date_sub(date_create(date('Y-m-d', $end_date)), date_interval_create_from_date_string(((int)$diff->days + 1) . ' days'));
			$previous_end_date = strtotime(date_format($previous_end_date, 'Y-m-d'));

			// NON-FBF & FBF COUNT
			// $db->where('o.status', "SHIPPED");
			// $db->where('o.account_id', '1');
			$db->where('CAST(o.orderDate AS DATE)', array(date('Y-m-d', $start_date), date('Y-m-d', $end_date)), 'BETWEEN');
			$db->where('o.status', 'CANCELLED', '!=');
			$db->groupBy('o.order_type');
			$order_count = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.order_type, count(o.order_type) as count');
			// echo $db->getLastQuery();

			// AVERAGE SP, TOTAL SALES & REPLACEMENTS
			// $db->where('o.status', "SHIPPED");
			// $db->where('o.account_id', '1');
			$db->where('CAST(o.orderDate AS DATE)', array(date('Y-m-d', $start_date), date('Y-m-d', $end_date)), 'BETWEEN');
			$db->where('o.status', 'CANCELLED', '!=');
			$order_deno = $db->get(TBL_FK_ORDERS . ' o', NULL, 'ROUND(AVG(o.costPrice), 2) as average_cp, ROUND(AVG(o.invoiceAmount), 2) as average_sp, SUM(o.invoiceAmount) as sales, SUM(o.replacementOrder) as replacements');
			// echo $db->getLastQuery();

			// COURIER & CUSTOMER RETURNS COUNT
			// $db->where('o.status', "SHIPPED");
			// $db->where('o.account_id', '1');
			$db->where('o.status', 'CANCELLED', '!=');
			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			$db->where('r.r_shipmentStatus', "return_completed");
			$db->where('CAST(r.r_receivedDate AS DATE)', array(date('Y-m-d', $start_date), date('Y-m-d', $end_date)), 'BETWEEN');
			$db->groupBy('r.r_source');
			$return_count = $db->get(TBL_FK_ORDERS . ' o', NULL, 'r.r_source as order_type, count(r.r_source) as count');

			// CLAIMS
			$db->where('c.claimDate', array(date('Y-m-d', $start_date), date('Y-m-d', $end_date)), 'BETWEEN');
			$claims_deno = $db->get(TBL_FK_CLAIMS . ' c', NULL, 'count(c.claimID) as claims');

			$count = array_merge($order_count, $return_count);
			$current_counts = array_merge($order_deno[0], $claims_deno[0]);
			foreach ($count as $c_key => $c_value) {
				$current_counts[strtolower($c_value['order_type'])] = $c_value['count'];
			}

			// PREVIOUS
			// NON-FBF & FBF COUNT
			// $db->where('o.status', "SHIPPED");
			$db->where('CAST(o.orderDate AS DATE)', array(date('Y-m-d', $previous_start_date), date('Y-m-d', $previous_end_date)), 'BETWEEN');
			$db->where('o.status', 'CANCELLED', '!=');
			$db->groupBy('o.order_type');
			$order_count = $db->get(TBL_FK_ORDERS . ' o', NULL, 'o.order_type, count(o.order_type) as count');

			// AVERAGE SP, TOTAL SALES & REPLACEMENTS
			// $db->where('o.status', "SHIPPED");
			$db->where('o.status', 'CANCELLED', '!=');
			$db->where('CAST(o.orderDate AS DATE)', array(date('Y-m-d', $previous_start_date), date('Y-m-d', $previous_end_date)), 'BETWEEN');
			$order_deno = $db->get(TBL_FK_ORDERS . ' o', NULL, 'ROUND(AVG(o.costPrice), 2) as average_cp, ROUND(AVG(o.invoiceAmount), 2) as average_sp, SUM(o.invoiceAmount) as sales, SUM(o.replacementOrder) as replacements');

			// COURIER & CUSTOMER RETURNS COUNT
			// $db->where('o.status', "SHIPPED");
			$db->where('o.status', 'CANCELLED', '!=');
			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			// $db->where('r.r_shipmentStatus', "return_completed");
			$db->where('CAST(r.r_receivedDate AS DATE)', array(date('Y-m-d', $previous_start_date), date('Y-m-d', $previous_end_date)), 'BETWEEN');
			$db->groupBy('r.r_source');
			$return_count = $db->get(TBL_FK_ORDERS . ' o', NULL, 'r.r_source as order_type, count(r.r_source) as count');

			// CLAIMS
			$db->where('c.claimDate', array(date('Y-m-d', $previous_start_date), date('Y-m-d', $previous_end_date)), 'BETWEEN');
			$claims_deno = $db->get(TBL_FK_CLAIMS . ' c', NULL, 'count(c.claimID) as claims');

			$count = array_merge($order_count, $return_count);
			$previous_counts = array_merge($order_deno[0], $claims_deno[0]);
			foreach ($count as $c_key => $c_value) {
				$previous_counts[strtolower($c_value['order_type'])] = $c_value['count'];
			}

			$difference = array(
				'average_cp' => number_format((($current_counts['average_cp'] - $previous_counts['average_cp']) / $current_counts['average_cp']) * 100, 2, '.', ''),
				'average_sp' => number_format((($current_counts['average_sp'] - $previous_counts['average_sp']) / $current_counts['average_sp']) * 100, 2, '.', ''),
				'sales' => number_format((($current_counts['sales'] - $previous_counts['sales']) / $current_counts['sales']) * 100, 2, '.', ''),
				'replacements' => number_format((($current_counts['replacements'] - $previous_counts['replacements']) / $current_counts['replacements']) * 100, 2, '.', ''),
				'claims' => number_format((($current_counts['claims'] - $previous_counts['claims']) / $current_counts['claims']) * 100, 2, '.', ''),
				'fbf_lite' => number_format((($current_counts['fbf_lite'] - $previous_counts['fbf_lite']) / $current_counts['fbf_lite']) * 100, 2, '.', ''),
				'non_fbf' => number_format((($current_counts['non_fbf'] - $previous_counts['non_fbf']) / $current_counts['non_fbf']) * 100, 2, '.', ''),
				'courier_return' => number_format((($current_counts['courier_return'] - $previous_counts['courier_return']) / $current_counts['courier_return']) * 100, 2, '.', ''),
				'customer_return' => number_format((($current_counts['customer_return'] - $previous_counts['customer_return']) / $current_counts['customer_return']) * 100, 2, '.', ''),
			);

			$dates = array(
				'start_date' => date('M-d', $start_date),
				'end_date' => date('M-d', $end_date),
				'previous_start_date' => date('M-d', $previous_start_date),
				'previous_end_date' => date('M-d', $previous_end_date),
			);

			echo json_encode(array('current' => $current_counts, 'previous' => $previous_counts, 'difference' => $difference, 'dates' => $dates));
			break;

		case 'overview_get_orders_count':

			if (empty($_REQUEST['start_date']))
				$start_date = strtotime('today -1 Month');
			else
				$start_date = strtotime($_REQUEST['start_date']);

			if (empty($_REQUEST['end_date']))
				$end_date = strtotime('tomorrow') - 1;
			else
				$end_date = strtotime($_REQUEST['end_date']);

			$days = (int)(($end_date - $start_date) / 86400) + 1;

			if (!empty($_REQUEST['type']))
				$db->where('o.order_type', $_REQUEST['type']);
			if (!empty($_REQUEST['account_id']))
				$db->where('o.account_id', array('IN' => explode(',', $_REQUEST['account_id'])));

			if (!empty($_REQUEST['brand_id'])) {
				$db->where('pb.brandid', array('IN' => explode(',', $_REQUEST['brand_id'])));
				$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
				$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
				$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=p.account_id', 'LEFT');
				$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
				$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'LEFT');
			}

			// ORDERS
			$output = array();
			$db->where('o.shippedDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
			$db->where('o.status', 'SHIPPED');
			$db->groupBy('SHIPPED_DATE');
			$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'CAST( o.shippedDate AS DATE ) AS SHIPPED_DATE, count( o.orderItemId ) AS ORDERS, SUM(o.replacementOrder) AS REPLACEMENTS');
			$order_dates = $replace_dates = $average_dates = createDatesArray($days, $end_date);
			$average = 0;
			$i = 1;
			foreach ($orders as $order) {
				if (isset($order_dates[strtotime($order['SHIPPED_DATE']) * 1000])) {
					$order_dates[strtotime($order['SHIPPED_DATE']) * 1000] = $order['ORDERS'];
					$replace_dates[strtotime($order['SHIPPED_DATE']) * 1000] = $order['REPLACEMENTS'];
					$average += $order['ORDERS'];
					$average_dates[strtotime($order['SHIPPED_DATE']) * 1000] = (int)($average / $i);
					$i++;
				}
			}

			$previous_value = 0;
			foreach ($average_dates as $key => $value) {
				if ($value == (int)0) {
					$average_dates[$key] = $previous_value;
				}
				$previous_value = $value;
			}

			$output['orders'] = $order_dates;
			$output['replacements'] = $replace_dates;
			$output['average'] = $average_dates;

			if (!empty($_REQUEST['type']))
				$db->where('o.order_type', $_REQUEST['type']);
			if (!empty($_REQUEST['account_id']))
				$db->where('o.account_id', array('IN' => explode(',', $_REQUEST['account_id'])));
			if (!empty($_REQUEST['brand_id']))
				$db->where('pb.brandid', array('IN' => explode(',', $_REQUEST['brand_id'])));

			// RETURNS
			$db->where('r.r_receivedDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
			// $db->where('r.r_source', 'customer_return');
			$db->join(TBL_FK_ORDERS . ' o', 'o.orderItemId=r.orderItemId', 'LEFT');
			$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
			$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=p.account_id', 'LEFT');
			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
			$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
			$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'LEFT');
			if (!empty($_REQUEST['brand_id']))
				$db->where('pb.brandid', array('IN' => explode(',', $_REQUEST['brand_id'])));
			$db->groupBy('RECEIVED_DATE');
			// $db->groupBy('r.r_source');
			$returns = $db->get(TBL_FK_RETURNS . ' r', NULL, 'CAST( r.r_receivedDate AS DATE ) AS RECEIVED_DATE, count( r.r_quantity  ) AS R_ORDERS');
			$return_dates = createDatesArray($days, $end_date);
			foreach ($returns as $return) {
				if (isset($return_dates[strtotime($return['RECEIVED_DATE']) * 1000])) {
					// $return_type[$return['r_source']] = $return['R_ORDERS'];
					// if (is_array($return_dates[strtotime($return['RECEIVED_DATE'])*1000]))
					// 	$return_dates[strtotime($return['RECEIVED_DATE'])*1000]
					// $return_dates[strtotime($return['RECEIVED_DATE'])*1000][$return['r_source']] = $return['R_ORDERS'];
					$return_dates[strtotime($return['RECEIVED_DATE']) * 1000] = $return['R_ORDERS'];
				}
			}

			$output['returns'] = $return_dates;
			$tick_size = round(($days / 14), 0, PHP_ROUND_HALF_DOWN);
			$output['dates'] = array($start_date, $end_date, $tick_size);
			// echo '<pre>';
			// var_dump($output);
			echo json_encode($output);

			break;

		case 'overview_get_region_count':

			if (empty($_REQUEST['start_date']))
				$start_date = strtotime('today -1 Month');
			else
				$start_date = strtotime($_REQUEST['start_date']);

			if (empty($_REQUEST['end_date']))
				$end_date = strtotime('today') - 1;
			else
				$end_date = strtotime($_REQUEST['end_date']);

			$days = (int)(($end_date - $start_date) / 86400);

			if (!empty($_REQUEST['type']))
				$type = strtolower($_REQUEST['type']);
			if (!empty($_REQUEST['account_id']))
				$db->where('o.account_id', explode(',', $_REQUEST['account_id']), 'IN');
			if (!empty($_REQUEST['brand_id']))
				$db->where('pb.brandid', explode(',', $_REQUEST['brand_id']), 'IN');

			// ORDERS
			$output = array();
			if ($type == 'orders') {
				$db->where('o.orderDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
				$db->where('o.status', 'SHIPPED');
				$db->where('o.order_type', 'FBF', '!=');
				$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
				$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
				$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=p.account_id', 'LEFT');
				$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
				$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'LEFT');
				$db->groupBy('stateCode');
				$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'JSON_UNQUOTE( JSON_EXTRACT(`deliveryAddress`, "$.stateCode") ) AS stateCode, count(*) as orders');
			}
			if ($type == 'returns') {
				$db->where('o.insertDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
				$db->where('o.order_type', 'FBF', '!=');
				$db->where('r.r_source', 'customer_return');
				// $db->join(TBL_FK_ORDERS ." o", "r.orderItemId=o.orderItemId", "INNER");
				$db->join(TBL_FK_ORDERS . ' o', 'o.orderItemId=r.orderItemId', 'LEFT');
				$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
				$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
				$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=p.account_id', 'LEFT');
				$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
				$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'LEFT');
				if (!empty($_REQUEST['brand_id']))
					$db->where('pb.brandid', array('IN' => explode(',', $_REQUEST['brand_id'])));
				$db->groupBy('stateCode');
				$orders = $db->get(TBL_FK_RETURNS . ' r', NULL, 'JSON_UNQUOTE( JSON_EXTRACT(`deliveryAddress`, "$.stateCode") ) AS stateCode, count(*) as orders');
			}
			if ($type == 'replacements') {
				$db->where('o.orderDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
				$db->where('o.order_type', 'FBF', '!=');
				$db->where('o.replacementOrder', 1);
				$db->where('o.status', 'SHIPPED');
				$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
				$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
				$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=p.account_id', 'LEFT');
				$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
				$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'LEFT');
				$db->groupBy('stateCode');
				$orders = $db->get(TBL_FK_ORDERS . ' o', NULL, 'JSON_UNQUOTE( JSON_EXTRACT(`deliveryAddress`, "$.stateCode") ) AS stateCode, count(*) as orders');
			}

			foreach ($orders as $order) {
				$output[$order['stateCode']] = $order['orders'];
			}
			echo json_encode($output);
			break;

		case 'overview_get_top_selling_count':

			if (empty($_REQUEST['start_date']))
				$start_date = strtotime('today -1 Month');
			else
				$start_date = strtotime($_REQUEST['start_date']);

			if (empty($_REQUEST['end_date']))
				$end_date = strtotime('today') - 1;
			else
				$end_date = strtotime($_REQUEST['end_date']);

			$page = $_REQUEST['page'];

			$account_ids = array();
			$account_query = "(";
			if (!empty($_REQUEST['account_id'])) {
				$req_account_ids = explode(',', $_REQUEST['account_id']);
				foreach ($accounts as $account) {
					if (in_array($account->account_id, $req_account_ids)) {
						$account_ids[] = $account->account_id;
						$account_query .= "o.account_id = ? OR ";
					}
				}
			} else {
				foreach ($accounts as $account) {
					$account_ids[] = $account->account_id;
					$account_query .= "o.account_id = ? OR ";
				}
			}
			$account_query = substr($account_query, 0, strlen($account_query) - 4) . ")";

			$output = array();

			$db->where('o.orderDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
			$db->where('o.status', 'SHIPPED');
			$db->where($account_query, $account_ids);

			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			$db->joinWhere(TBL_FK_RETURNS . ' r', 'r.r_source', 'courier_return', '!=');
			$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
			$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
			$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.account_id=o.account_id');
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
			$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
			$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'INNER');
			if ($_REQUEST['brand'] != "")
				$db->joinWhere(TBL_PRODUCTS_BRAND . ' pb', 'pm.brand', explode(',', $_REQUEST['brand']), 'IN');

			$db->groupBy('sku');
			$db->groupBy('order_type');
			$db->groupBy('r_source');
			$db->orderBy('qty', 'DESC');
			$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.order_type, o.account_id, fa.account_name, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, SUM( quantity ) AS qty, SUM(r.r_quantity) as r_qty, r_source');
			foreach ($orders as $order) {
				$order->parentSku = trim(strtoupper($order->parentSku));
				if (is_null($order->parentSku))
					continue;
				$output[$order->parentSku][$order->fsn][$order->account_name]['FBF_LITE'] += 0;
				$output[$order->parentSku][$order->fsn][$order->account_name]['NON_FBF'] += 0;
				if ($order->order_type == 'FBF_LITE')
					$output[$order->parentSku][$order->fsn][$order->account_name]['FBF_LITE'] += $order->qty;
				if ($order->order_type == 'NON_FBF')
					$output[$order->parentSku][$order->fsn][$order->account_name]['NON_FBF'] += $order->qty;
				$output[$order->parentSku][$order->fsn][$order->account_name]['sku'] = $order->sku;
				$output[$order->parentSku][$order->fsn]['fsn_quantity'] += $order->qty;
				$output[$order->parentSku]['brand'] = $order->brandName;
				$output[$order->parentSku]['quantity'] += $order->qty;
			}

			$sort = array();
			foreach ($output as $k => $arr) {
				$sort['fsn_quantity'] = array();
				foreach ($arr as $key => $row) {
					$sort['fsn_quantity'][$key] = $row['fsn_quantity'];
				}
				array_multisort($sort['fsn_quantity'], SORT_DESC, $arr);
				$sort['qty'][$k] = $arr['quantity'];
				$output[$k] = $arr; // reset the with new order
			}
			array_multisort($sort['qty'], SORT_DESC, $output);
			// echo '<pre>';
			// print_r($output);
			echo json_encode($output);

			break;

		case 'overview_get_top_returning_count':

			if (empty($_REQUEST['start_date']))
				$start_date = strtotime('today -1 Month');
			else
				$start_date = strtotime($_REQUEST['start_date']);

			if (empty($_REQUEST['end_date']))
				$end_date = strtotime('today') - 1;
			else
				$end_date = strtotime($_REQUEST['end_date']);

			$page = $_REQUEST['page'];

			$account_ids = array();
			$account_query = "(";
			if (!empty($_REQUEST['account_id'])) {
				$req_account_ids = explode(',', $_REQUEST['account_id']);
				foreach ($accounts as $account) {
					if (in_array($account->account_id, $req_account_ids)) {
						$account_ids[] = $account->account_id;
						$account_query .= "o.account_id = ? OR ";
					}
				}
			} else {
				foreach ($accounts as $account) {
					$account_ids[] = $account->account_id;
					$account_query .= "o.account_id = ? OR ";
				}
			}
			$account_query = substr($account_query, 0, strlen($account_query) - 4) . ")";

			$output = array();
			$db->where('r.r_createdDate', array(date('Y-m-d H:i:s', $start_date), date('Y-m-d H:i:s', $end_date)), 'BETWEEN');
			$db->where('r.r_source', 'customer_return');
			$db->where("(r.r_shipmentStatus != ? OR r.r_shipmentStatus != ?)", array("cancelled", "return_cancelled"));
			$db->where($account_query, $account_ids);
			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			$db->join(TBL_PRODUCTS_ALIAS . ' p', 'p.mp_id=o.fsn', 'LEFT');
			$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.sku=o.sku');
			$db->joinWhere(TBL_PRODUCTS_ALIAS . ' p', 'p.account_id=o.account_id');
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=p.pid', 'LEFT');
			$db->join(TBL_PRODUCTS_COMBO . ' pc', 'pc.cid=p.cid', 'LEFT');
			$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=pm.brand', 'INNER');
			if ($_REQUEST['brand'] != "")
				$db->joinWhere(TBL_PRODUCTS_BRAND . ' pb', 'pm.brand', explode(',', $_REQUEST['brand']), 'IN');

			$db->groupBy('sku');
			$db->groupBy('order_type');
			$db->orderBy('qty', 'DESC');
			$orders = $db->objectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, 'o.order_type, o.account_id, fa.account_name, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, SUM( quantity ) AS qty');
			foreach ($orders as $order) {
				$order->parentSku = trim(strtoupper($order->parentSku));
				if (is_null($order->parentSku))
					continue;
				$output[$order->parentSku][$order->fsn][$order->account_name]['FBF'] += 0;
				$output[$order->parentSku][$order->fsn][$order->account_name]['FBF_LITE'] += 0;
				$output[$order->parentSku][$order->fsn][$order->account_name]['NON_FBF'] += 0;
				if ($order->order_type == 'FBF')
					$output[$order->parentSku][$order->fsn][$order->account_name]['FBF'] += $order->qty;
				if ($order->order_type == 'FBF_LITE')
					$output[$order->parentSku][$order->fsn][$order->account_name]['FBF_LITE'] += $order->qty;
				if ($order->order_type == 'NON_FBF')
					$output[$order->parentSku][$order->fsn][$order->account_name]['NON_FBF'] += $order->qty;
				$output[$order->parentSku][$order->fsn][$order->account_name]['sku'] = $order->sku;
				$output[$order->parentSku][$order->fsn]['fsn_quantity'] += $order->qty;
				$output[$order->parentSku]['brand'] = $order->brandName;
				$output[$order->parentSku]['quantity'] += $order->qty;
			}

			$sort = array();
			foreach ($output as $k => $arr) {
				$sort['fsn_quantity'] = array();
				foreach ($arr as $key => $row) {
					$sort['fsn_quantity'][$key] = $row['fsn_quantity'];
				}
				array_multisort($sort['fsn_quantity'], SORT_DESC, $arr);
				$sort['qty'][$k] = $arr['quantity'];
				$output[$k] = $arr; // reset the with new order
			}
			array_multisort($sort['qty'], SORT_DESC, $output);
			echo json_encode($output);
			break;

			// FK DASHBOARD
		case 'send_sms_notification':
			$type = $_REQUEST['type'];

			if (isset($log->logfile))
				$old_logfile = $log->logfile;

			switch ($type) {
				case 'delivered':
					// $log->logfile = TODAYS_LOG_PATH.'/sms-delivered.log';
					$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
					$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
					$db->where('r.returnId', NULL, 'IS');
					// $db->where('o.fk_status', 'DELIVERED');
					$db->where('o.status', 'SHIPPED');
					$db->where('o.shippedDate', date('Y-m-d', strtotime('-31 days')) . '%', 'LIKE');
					$db->where('JSON_UNQUOTE( JSON_EXTRACT(`deliveryAddress`, "$.contactNumber") )', '', '!=');
					$db->where("(o.title LIKE ? OR o.title LIKE ?)", array("Sylvi%", "Style%"));
					// $db->where('o.deliveredDate', array(date('Y-m-d', strtotime('-60 days')), date('Y-m-d', strtotime('-46 days'))), 'BETWEEN');
					// $db->where('o.orderId', 'OD117990823558710000');
					$order_details = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'a.account_name, a.seller_id, o.orderId, o.orderItemId, o.title, o.fsn, o.listingId, JSON_UNQUOTE( JSON_EXTRACT(`deliveryAddress`, "$.contactNumber") ) AS contactNumber, r.returnId');
					if ($order_details) {
						foreach ($order_details as $order_detail) {
							$url = get_short_url_fk('https://www.flipkart.com/rv/orderDetails?order_id=' . $order_detail->orderId . '&item_id=' . $order_detail->orderItemId . '&unit_id=' . $order_detail->orderItemId . '000');
							if (empty($url) || is_null($url))
								continue;

							$msg = str_replace(array('##title##', '##link##'), array(substr($order_detail->title, 0, 14) . '... on Flipkart', $url), get_template('sms', 'seller_feedback'));
							// echo $ssms = $sms->send_sms($msg, array(get_option('sms_notification_number')), 'IMSDAS');
							$ssms = $sms->send_sms($msg, array('91' . $order_detail->contactNumber), 'FPCART');
							$log->write($order_detail->account_name . "\nSMS sent for seller feedback request with orderItemId: " . $order_detail->orderItemId . " \nMobile Number: " . $order_detail->contactNumber . "\nMessage: " . $msg . "\nResponse: " . json_encode($ssms), 'sms-delivered');
						}
					} else {
						echo 'No Orders for SMS';
					}
					header("HTTP/1.1 200 OK");
					break;
			}

			if (isset($old_logfile))
				$log->logfile = $old_logfile;

			break;

		case 'get_wrong_sku':
			$db->where('a.corrected_sku', NULL, 'IS NOT');
			$db->join(TBL_FK_ACCOUNTS . ' ac', "ac.account_id=a.account_id", "LEFT");
			$incorrect_skus = $db->get(TBL_PRODUCTS_ALIAS . ' a', NULL, 'a.alias_id, a.corrected_sku, a.sku, a.mp_id, ac.account_name');

			$i = 0;
			$return['data'] = array();
			foreach ($incorrect_skus as $po) {
				$return['data'][$i] = array_values($po);
				$return['data'][$i][] = '<a class="edit btn btn-default btn-xs purple" href="' . $url . '"><i class="fa fa-edit"></i> Edit</a>';
				$return['data'][$i][] = '<a class="delete btn btn-default btn-xs purple alias_id_' . $po['alias_id'] . '" ><i class="fa fa-trash-alt"></i> Delete</a>';
				$i++;
			}
			echo json_encode($return);
			break;

		case 'get_account_fsn':
			if (!isset($_REQUEST['account_id']))
				return;

			$return = get_all_mp_id('flipkart', $_REQUEST['account_id']);
			echo (json_encode($return));

			break;

		case 'add_wrong_sku':
			$details['corrected_sku'] = $_POST['correct_sku'];
			$db->where('alias_id', $_POST['mp_id']);
			if ($db->update(TBL_PRODUCTS_ALIAS, $details)) {
				$return['type'] = "success";
				$return['message'] = "Successfully updated Correct SKU for SKU " . $_POST['wrong_sku'] . " to " . $_POST['correct_sku'];
			} else {
				$return['type'] = "error";
				$return['message'] = "Error updated new product " . $db->getLastError();
			}
			echo json_encode($return);

			break;

		case 'delete_wrong_sku':
			$details['corrected_sku'] = null;
			$db->where('alias_id', $_POST['alias_id']);
			if ($db->update(TBL_PRODUCTS_ALIAS, $details)) {
				$return['type'] = "success";
				$return['message'] = "Successfully deleted Correct SKU for ID " . $_POST['alias_id'];
			} else {
				$return['type'] = "error";
				$return['message'] = "Error adding new product " . $db->getLastError();
			}
			echo json_encode($return);

			break;

		case 'campaign_status':
			echo '<pre>';
			$cpx_update = isset($_GET['cpx_update']) ? (int)$_GET['cpx_update'] : 0;
			$switch = date('H') == "21" ? 0 : 1;

			foreach ($accounts as $account) {
				$fk = new flipkart_dashboard($account);

				// $active_ads = array("L3U56XE90AZ1", "2IZFAGLG7F5D");
				$active_ads = array();
				$all_ads = $fk->get_ads();
				// var_dump(json_encode($all_ads));
				foreach ($all_ads->campaigns as $ads) {
					if (in_array($ads->id, $active_ads)) {
						$ad = $fk->get_ad_details($ads->id);
						$available_budget = $ads->remainingBudget;

						if ($cpx_update) {
							if (!$switch) {
								$cpx = 2.5;
							} else {
								$adGroup_detail = $fk->get_adgroups_details($ad->adgroups[0]->commodityId, str_replace('Z', '+05:30', $ad->hrsd));
								$winningCPX = (int)$adGroup_detail[0]->winningMaxCpx * 0.75;
								$cpx = ($winningCPX <= 15 ? $winningCPX : 15);
							}
							$update = new stdClass();
							$update->adgroups = $ad->adgroups;
							$update->adgroups[0]->cost = $cpx;
							$update->id = $ad->id;
							$update->name = $ad->name;
							$update->budget = $ad->budget;
							$update->startDate = $ad->startDate;
							$update->hrsd = $ad->hrsd;
							$update->isTillEnd = $ad->isTillEnd;
							$update->paced = $ad->paced;
							$update->status = $ad->status;
							$update->costModel = $ad->costModel;
							unset($update->recommendationInfo);
							unset($update->listingIds);
							unset($update->adgroups[0]->recommendationInfo);
							unset($update->adgroups[0]->status);
							unset($update->adgroups[0]->costModel);
							unset($update->adgroups[0]->progress);

							$payload = json_encode($update);
							$response = $fk->update_cpc($ads->id, $payload);
							// var_dump($response);
							$response = json_decode($response);
							if ($response->adgroups[0]->cost == $cpx)
								echo 'Successfully update cpx of ' . $ad->name . ' to ' . $cpx . '<br />';
							else
								echo 'Unable to update cpx of ' . $ad->name . ' to ' . $cpx . '<br />';
						} else {
							if ($available_budget <= 150) {
								$msg = str_replace(array('##CAMP_NAME##', '##MESSAGE##'), array($ad->name . ' - ' . $account->fk_account_name, 'Low Budget. Current available budget is ' . $available_budget . ' only.'), get_template('sms', 'campaign'));
								$ssms = $sms->send_sms($msg, array(get_option('sms_notification_number')), 'IMSDAS');
								echo $ad->name . ' - LOW BUDGET: Current available budget is ' . $available_budget . '<br />';
							}
						}
					}
				}
			}
			break;

			// SELLER ACCOUNT CREATION
		case 'get_details_fk':
			$selected_account = (int)$_GET['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key], $accounts[$account_key]->sandbox, true, false, true);
			if ($fk->is_login()) {
				$return = array('type' => 'success', 'msg' => 'Successfully fetched Account Details');
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid username/password');
			}

			echo json_encode($return);
			break;

		case 'get_api_details_fk':
			$selected_account = (int)$_GET['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			// $fk = new flipkart_dashboard($accounts[$account_key], $accounts[$account_key]->sandbox, true, true, true);
			// if($fk->is_api_login){
			// 	$return = array('type' => 'success', 'msg' => 'Successfully fetched Account Details');
			// } else {
			// 	$return = array('type' => 'error', 'msg' => 'Invalid username/password');
			// }

			echo json_encode($return);
			break;

		case 'get_api_status':
			$selected_account = (int)$_GET['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new connector_flipkart($accounts[$account_key], $accounts[$account_key]->sandbox);
			$access_token = $fk->get_access_token();
			if ($access_token) {
				$return = array('type' => 'success', 'msg' => 'API Connection Successful.', 'response' => $access_token);
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid API Credentials.', 'response' => $access_token);
			}

			echo json_encode($return);
			break;

		case 'get_login_status':
			$selected_account = (int)$_GET['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $selected_account)
					$account_key = $a_key;
			}

			$fk = new flipkart_dashboard($accounts[$account_key], $accounts[$account_key]->sandbox, true);
			if ($fk->is_login) {
				$return = array('type' => 'success', 'msg' => 'Successfully login to account');
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid username/password');
			}

			echo json_encode($return);
			break;

			// SCAN & SHIP
		case 'scan_ship':
			$tracking_id = $_GET['tracking_id'];

			// MULTI ITEMS + MULTI QTY
			// MULTI ITEMS	[FMPC1448559273]
			// MULTI QTY	[]
			// MULTI ITEMS + MULTI QTY (DIFFERENT SKU SAME PARENT SKU)
			// MULTI ITEMS (DIFFERENT SKU SAME PARENT SKU)	[2703436082]
			// COMBO + MULTI ITEMS + MULTI QTY
			// COMBO + MULTI ITEMS
			// COMBO + MULTI QTY
			// COMBO + MULTI ITEMS + MULTI QTY (DIFFERENT SKU SAME PARENT SKU)
			// COMBO + MULTI ITEMS (DIFFERENT SKU SAME PARENT SKU)

			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			$db->where('o.forwardTrackingId', $tracking_id);
			$db->where('o.status', "RTD");
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', null, "o.orderItemId, o.account_id, o.ordered_quantity, o.quantity, o.uid, o.sku, o.subItems, o.status, o.fk_status, p.corrected_sku, p.alias_id, r.r_shipmentStatus");
			if ($orders) {
				// Process start
				$data  = array(
					"logType" => "Order Process Start",
					"identifier" => $orders[0]->orderId,
					"userId" => $current_user['userID']
				);
				$db->insert(TBL_PROCESS_LOG, $data);

				$return = array();
				$content = array();
				$items = array();
				$multi_items = ($db->count > 1) ? true : false;
				$fulfillable_quantity = 0;
				if (!is_null($orders[0]->r_shipmentStatus)) {
					$account_key = "";
					foreach ($accounts as $a_key => $account) {
						if ($account->account_id == $orders[0]->account_id)
							$account_key = $a_key;
					}

					$fk = new flipkart_dashboard($accounts[$account_key]);
					$return = $fk->update_order_to_cancel($tracking_id);
					if ($return['type'] == "success") {
						$response = array("type" => "info", "msg" => "Order Cancelled<br />DO NOT PROCESS");
					} else {
						$response = array("type" => "info", "msg" => "Unable to mark order Cancelled.<br />DO NOT PROCESS.", "error" => $return);
					}
				} else {
					foreach ($orders as $order) {
						$db->where('marketplace', 'flipkart');
						$db->where('account_id', $order->account_id);
						$db->where('alias_id', $order->alias_id);
						$products = $db->getOne(TBL_PRODUCTS_ALIAS, array('pid, cid'));
						if ($products['cid']) {
							$db->where('cid', $products['cid']);
							$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
							$products = json_decode($products['pid']);
							foreach ($products as $product) {
								$uids = json_decode($order->uid, true);
								$uids_count = is_null($uids) ? 0 : count($uids);
								$db->where('pid', $product);
								$item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
								$item['uids'] = []; //is_null($uids) ? [] : $uids;
								$item['quantity'] = $order->quantity;
								$item['scanned_qty'] = $uids_count;
								$item['scan'] = ($order->quantity == $uids_count) ? true : false;
								$item_index = isset($items[$order->orderItemId]) ? array_search($item['item_id'], array_column($items[$order->orderItemId], 'item_id')) : null;
								$corrected_sku = "";
								if (!is_null($order->corrected_sku)) {
									$corrected_sku = '<div class="form-group item-info corrected_sku">
														<div class="col-md-3">Corrected SKU:</div>
														<div class="col-md-9 sku">' . $order->corrected_sku . '</div>
													</div>';
								}
								$all_uids = "";
								if (!is_null($uids)) {
									$all_uids = implode(', ', $uids);
								}
								$all_success = "hide";
								if ($order->quantity == $uids_count)
									$all_success = "";

								if (is_null($item_index) || $item_index === FALSE) {
									$items[$order->orderItemId][] = $item;
									$fulfillable_quantity += $order->quantity;
									$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '_' . $order->orderItemId . '" data-scanned="false">
												<div class="form-group">
													<div class="col-md-2">
														<div class="product_image">
															<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg"/>
														</div>
													</div>
													<div class="col-md-10">
														<div class="form-group item-info parent_sku">
															<div class="col-md-3">Parent SKU:</div>
															<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
														</div>
														<div class="form-group item-info original_sku">
															<div class="col-md-3">SKU:</div>
															<div class="col-md-9 sku">' . $order->sku . '</div>
														</div>
														' . $corrected_sku . '
														<div class="form-group item-info qty">
															<div class="col-md-3">Quantity:</div>
															<div class="col-md-9">' . ($item['quantity'] > 1 ? "<sapn class='label label-danger'>" . $item['quantity'] . "</span>" : $item['quantity']) . '</div>
														</div>
														<div class="form-group item-info uids hide">
															<div class="col-md-3">UID:</div>
															<div class="col-md-8 uid">' . $all_uids . '</div>
															<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
														</div>
													</div>
												</div>
											</div>';
								} else {
									$fulfillable_quantity += $order->quantity;
									$items[$order->orderItemId][$item_index]['quantity'] += $order->quantity;
									$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '_' . $order->orderItemId . '" data-scanned="false">
												<div class="form-group">
													<div class="col-md-2">
														<div class="product_image">
															<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg"/>
														</div>
													</div>
													<div class="col-md-10">
														<div class="form-group item-info parent_sku">
															<div class="col-md-3">Parent SKU:</div>
															<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
														</div>
														<div class="form-group item-info original_sku">
															<div class="col-md-3">SKU:</div>
															<div class="col-md-9 sku">' . $order->sku . '</div>
														</div>
														' . $corrected_sku . '
														<div class="form-group item-info qty">
															<div class="col-md-3">Quantity:</div>
															<div class="col-md-9">' . ($items[$order->orderItemId][$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$order->orderItemId][$item_index]['quantity'] . "</span>" : $items[$order->orderItemId][$item_index]['quantity']) . '</div>
														</div>
														<div class="form-group item-info uids hide">
															<div class="col-md-3">UID:</div>
															<div class="col-md-8 uid">' . $all_uids . '</div>
															<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
														</div>
													</div>
												</div>
											</div>';
								}
							}
						} else {
							$fulfillable_quantity = count(json_decode($order->subItems)[0]->packages);
							if ($order->ordered_quantity != $order->quantity)
								$fulfillable_quantity = $order->quantity;
							$uids = json_decode($order->uid, true);
							$uids_count = is_null($uids) ? 0 : count($uids);
							$db->where('pid', $products['pid']);
							$item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
							$item['orderItemId'] = $order->orderItemId;
							$item['uids'] = []; //is_null($uids) ? [] : $uids;
							$item['quantity'] = $order->quantity;
							$item['scanned_qty'] = $uids_count;
							$item['scan'] = ($order->quantity == $uids_count) ? true : false;
							// $item_index = array_search($item['item_id'], array_column($items, 'item_id'));
							// $order_index = array_search($item['orderItemId'], array_column($items, 'orderItemId'));
							$item_index = isset($items[$order->orderItemId]) ? array_search($item['item_id'], array_column($items[$order->orderItemId], 'item_id')) : null;

							$corrected_sku = "";
							if (!is_null($order->corrected_sku)) {
								$corrected_sku = '<div class="form-group item-info corrected_sku">
													<div class="col-md-3">Corrected SKU:</div>
													<div class="col-md-9 sku">' . $order->corrected_sku . '</div>
												</div>';
							}
							$all_uids = "";
							if (!is_null($uids)) {
								$all_uids = implode(', ', $uids);
							}
							$all_success = "hide";
							if ($order->quantity == $uids_count)
								$all_success = "";

							// if ($item_index === FALSE || $order_index === FALSE){
							if (is_null($item_index) || $item_index === FALSE) {
								$items[$order->orderItemId][] = $item;
								$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item['item_id'] . '_' . $item['orderItemId'] . '" data-scanned="false">
												<div class="form-group">
													<div class="col-md-2">
														<div class="product_image">
															<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg"/>
														</div>
													</div>
													<div class="col-md-10">
														<div class="form-group item-info parent_sku">
															<div class="col-md-3">Parent SKU:</div>
															<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
														</div>
														<div class="form-group item-info original_sku">
															<div class="col-md-3">SKU:</div>
															<div class="col-md-9 sku">' . $order->sku . '</div>
														</div>
														' . $corrected_sku . '
														<div class="form-group item-info qty">
															<div class="col-md-3">Quantity:</div>
															<div class="col-md-9">' . ($item['quantity'] > 1 ? "<sapn class='label label-danger'>" . $item['quantity'] . "</span>" : $item['quantity']) . '</div>
														</div>
														<div class="form-group item-info uids hide">
															<div class="col-md-3">UID:</div>
															<div class="col-md-8 uid">' . $all_uids . '</div>
															<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
														</div>
													</div>
												</div>
											</div>';
							} else {
								$items[$order->orderItemId][$item_index]['quantity'] += $order->quantity;
								$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item['item_id'] . '_' . $item['orderItemId'] . '" data-scanned="false">
												<div class="form-group">
													<div class="col-md-2">
														<div class="product_image">
															<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg"/>
														</div>
													</div>
													<div class="col-md-10">
														<div class="form-group item-info parent_sku">
															<div class="col-md-3">Parent SKU:</div>
															<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
														</div>
														<div class="form-group item-info original_sku">
															<div class="col-md-3">SKU:</div>
															<div class="col-md-9 sku">' . $order->sku . '</div>
														</div>
														' . $corrected_sku . '
														<div class="form-group item-info qty">
															<div class="col-md-3">Quantity:</div>
															<div class="col-md-9">' . ($items[$order->orderItemId][$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$order->orderItemId][$item_index]['quantity'] . "</span>" : $items[$item_index]['quantity']) . '</div>
														</div>
														<div class="form-group item-info uids hide">
															<div class="col-md-3">UID:</div>
															<div class="col-md-8 uid">' . $all_uids . '</div>
															<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
														</div>
													</div>
												</div>
											</div>';
							}
						}
					}
					foreach ($content as $html) {
						$content_html .= implode('', $html);
					}
					$response = array("type" => "success", "items" => $items, "fulfillable_quantity" => $fulfillable_quantity, "content" => preg_replace('/\>\s+\</m', '><', $content_html));
				}
			} else {
				$db->where('forwardTrackingId', $tracking_id);
				$order = $db->ObjectBuilder()->getOne(TBL_FK_ORDERS, "status, fk_status");
				if ($order) {
					if ($order->fk_status == "RETURN_REQUESTED" || $order->fk_status == "RETURNED" || $order->fk_status == "CANCELLED" || $order->status == "CANCELLED") {
						$response = array("type" => "info", "msg" => "Order Cancelled<br />DO NOT PROCESS");
					} else {
						$response = array("type" => "error", "msg" => "Order status is " . strtoupper($order->status));
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
				echo json_encode(array('type' => 'error', 'msg' => 'Product status is ' . strtoupper($status)));
			}

			break;

		case 'sideline_product':
			$data  = array(
				"logType" => "Order Process Sidelined",
				"identifier" => $_REQUEST["orderId"],
				"userId" => $current_user['userID']
			);
			if ($db->insert(TBL_PROCESS_LOG, $data))
				echo json_encode(array('type' => 'success', 'message' => "Order successfully sidelined"));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Log Error : ' . $db->getLastError()));
			break;

		case 'save_scan_ship':
			$scanned_orders = json_decode($_REQUEST['scanned_items']);
			$update_status = array();
			foreach ($scanned_orders as $orderItemId => $uids) {
				$db->where('orderItemId', $orderItemId);
				$order_detail = array(
					'uid' => json_encode($uids),
					'status' => 'SHIPPED',
					'shippedDate' => date('Y-m-d H:i:s'),
				);
				if ($db->update(TBL_FK_ORDERS, $order_detail)) {
					foreach ($uids as $uid) {
						// CHANGE UID STATUS
						$inv_status = $stockist->update_inventory_status($uid, 'sales');
						// ADD TO LOG
						$log_status = $stockist->add_inventory_log($uid, 'sales', 'Flipkart Sales :: ' . $orderItemId);
						// ADD PROCESS LOG
						$data  = array(
							"logType" => "Product Process End",
							"identifier" => $uid,
							"userId" => $current_user['userID']
						);
						$log_status = $db->insert(TBL_PROCESS_LOG, $data);
						if ($inv_status && $log_status)
							$update_status[$orderItemId] = true;
						else
							$update_status[$orderItemId] = false;
					}
				} else {
					$update_status[$orderItemId] = false;
				}
			}

			if (in_array(false, $update_status))
				$return = array("type" => "error", "msg" => "Error Updating order", "error" => $update_status);
			else
				$return = array("type" => "success", "msg" => "Order successfull shipped");

			echo json_encode($return);
			break;

		default:
			throw new Exception("$action cannot be set");
	}
	exit;
} else {
	header("HTTP/1.1 401 Unauthorised");
	exit('hmmmm... trying to hack in ahh!');
}
function cmp($a, $b)
{
	return strcmp($a->orderItemId, $b->orderItemId);
}
function cmp_s($a, $b)
{
	return strcmp($a->orderItems[0]->id, $b->orderItems[0]->id);
}
function sort_by($a, $b)
{
	return strcmp($a->orderItems[0]->{$key}, $b->orderItems[0]->{$key});
}
function findObjectById($array, $id, $v)
{
	foreach ($array as $struct) {
		if ($v == $struct->$id) {
			return $struct;
		}
	}
	return false;
}
function createDatesArray($days, $date)
{

	#CLEAR OUTPUT FOR USE
	$output = array();

	#SET CURRENT DATE
	list($month, $day, $year) = explode(' ', date('m d Y', $date + 86400));

	#LOOP THROUGH DAYS
	for ($i = 1; $i <= $days; $i++) {
		$output[(mktime(0, 0, 0, $month, ($day - $i), $year)) * 1000] = 0;
	}

	#RETURN DATE ARRAY
	return array_reverse($output, true);
}
function add_new_incident($accountId, $issueType, $incidentId, $subject, $baseIncident, $order_item_ids)
{
	global $db, $current_user;

	$db->where('incidentId', $incidentId);
	$incident_details = $db->getOne(TBL_FK_INCIDENTS);
	if ($incident_details) {
		$db->where('incidentId', $incidentId);
		$added_orders = str_replace('}', ', "' . $order_item_ids . '": false}', $incident_details['referenceId']);
		$details = array(
			'referenceId' => $added_orders
		);

		if ($db->update(TBL_FK_INCIDENTS, $details)) {
			$return["type"] = "success";
			$return["msg"] = "Order successfully added to IncidentID " . $incidentId;
		} else {
			$return["type"] = "error";
			$return["msg"] = "Error adding order to incident. " . $db->getLastError()/*. " :: ".$db->getLastQuery()*/;
			$return["incidentId"] = "";
		}
	} else {
		$details = array(
			'accountId' => $accountId,
			'issueType' => $issueType,
			'subject' => $subject,
			'incidentId' => $incidentId,
			'baseIncident' => $baseIncident,
			'incidentStatus' => 'active',
			'createdDate' => date('Y-m-d H:i:s'),
			'createdBy' => $current_user['userID']
		);

		if (!empty($order_item_ids)) {
			$a = array_flip(array_filter(explode(',', str_replace(' ', '', $order_item_ids)), function ($value) {
				return !is_null($value) && $value !== '';
			}));
			$orderItemIds = array_map(function ($val) {
				return false;
			}, $a);
			$details['referenceId'] = json_encode($orderItemIds);
		}

		$return = array();
		if ($db->insert(TBL_FK_INCIDENTS, $details)) {
			$return["type"] = "success";
			$return["msg"] = "Incident successfully added with id " . $incidentId;
		} else {
			$return["type"] = "error";
			$return["msg"] = "Error adding incident. " . $db->getLastError()/*. " :: ".$db->getLastQuery()*/;
			$return["incidentId"] = "";
		}
	}

	echo json_encode($return);
}
function import_payments($file, $fk, $reportId = "")
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

		$objWorksheet = $objPHPExcel->getSheet(1); // Orders - Payments Sheet

		$types = array();
		foreach ($objPHPExcel->getWorksheetIterator() as $objWorksheet) {
			$worksheetTitle     = strtolower(str_replace(' ', '_', trim($objWorksheet->getTitle())));
			if ($worksheetTitle == "orders" /*|| $worksheetTitle == "tds" || $worksheetTitle == "ads"*/) {
				$highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
				$highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
				$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
				$orders = array();
				$head_line = array();
				for ($row = 2; $row <= $highestRow; ++$row) {
					$line = array();
					$order_item_id = "";
					for ($col = 0; $col < $highestColumnIndex; ++$col) {
						$cell = $objWorksheet->getCellByColumnAndRow($col, $row);
						$val = $cell->getValue();
						if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText)
							$val = $val->getPlainText();

						if ($row == 2) {
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
			}
		}

		$success = array();
		$existing = array();
		$update = array();
		$error = array();
		$error_neft = array();
		$total = count($orders);
		$i = 0;

		foreach ($types as $type => $type_orders) {
			foreach ($type_orders as $order) {
				if (empty($order) || (is_null($order['neft_id']) && is_null($order['order_item_id'])))
					continue;

				$insert = $fk->payments->insert_settlements($order, $type);
				if (strpos($insert, 'INSERT: New settlement for Order Item ID') !== FALSE) {
					$success[] = $order['order_item_id'];
				}
				if (strpos($insert, 'INSERT: Already exists settlement for Order Item ID') !== FALSE) {
					$existing[] = $order['order_item_id'];
				}
				if (strpos($insert, 'UPDATE: Updated settlement for Order Item ID') !== FALSE) {
					$update[] = $order['order_item_id'];
				}
				if (strpos($insert, 'INSERT: Unable to insert settlement for Order Item ID') !== FALSE) {
					$error[] = $order['order_item_id'];
				}
				if (strpos($insert, 'Invalid NEFT_ID') !== FALSE) {
					$error_neft[] = $order['order_item_id'];
				}
				$i++;
			}
		}

		if ($reportId != "") {
			$db->where('reportId', $reportId);
			$db->update(TBL_FK_REPORTS, array('reportStatus' => 'completed'));
		}

		$return = array('type' => 'success', 'total' => $total, 'success' => count($success), 'existing' => count($existing), 'error' => count($error), 'updated' => count($update), 'invalid' => count($error_neft), 'error_data' => $error);
		$log->write($return, 'payments-import');
		return $return;
	} else {
		return array('type' => 'error', 'message' => 'Unable to locate file.');
	}
}
