<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
include_once(ROOT_PATH . '/rma/functions.php');
global $accounts;

if (isset($_GET['rmaId'])) {
	$db->where('rmaId', $_GET['rmaId']);
	$data = $db->objectBuilder()->get(TBL_RMA . ' r', NULL, 'r.*');

	if ($data[0]->marketplace == "amazon") {
		$db->join(TBL_AZ_ORDERS . ' o', 'o.orderId = r.orderId');
		$db->join(TBL_PRODUCTS_ALIAS . " pa", "pa.mp_id=o.asin", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " pa", "pa.account_id=o.account_id");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " pa", "pa.sku=o.sku");
		//		$db->join(TBL_AZ_ACCOUNTS.' az', 'az.account_id=o.account_id', 'INNER');
		$db->join(TBL_PRODUCTS_MASTER . " p", "p.pid=pa.pid", "LEFT");
	} elseif ($data[0]->marketplace == "shopify") {
		$db->join(TBL_SP_ORDERS . ' o', 'o.orderId = r.orderId');
		$db->join(TBL_SP_PRODUCTS . ' p', 'p.productId = o.productId');
		// $db->join(TBL_PRODUCTS_MASTER ." pm", "pm.sku=o.sku", "LEFT");
		// // $db->join(TBL_PRODUCTS_COMBO ." pc", "pc.cid=p.cid", "LEFT");
		// $db->join(TBL_PRODUCTS_CATEGORY." c", "c.catid=pm.category", "LEFT");
	} elseif ($data[0]->marketplace == "flipkart") {
		$db->join(TBL_FK_ORDERS . ' o', 'o.orderId = r.orderId');
		//		$db->join(TBL_SP_PRODUCTS. ' p', 'p.productId = o.productId');
		$db->join(TBL_PRODUCTS_ALIAS . " pa", "pa.mp_id=o.fsn", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " pa", "pa.account_id=o.account_id");
		$db->join(TBL_PRODUCTS_MASTER . " p", "p.pid=pa.pid", "LEFT");
	}
	$db->where('rmaId', $_GET['rmaId']);

	$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');

	if ($data[0]->marketplace != "shopify") {
		$orders = $db->objectBuilder()->get(TBL_RMA . ' r', NULL, 'r.*,f.trackingId,f.shipmentStatus,p.thumb_image_url,o.status as orderStatus,o.uid as orderUIds,o.quantity,o.quantity,o.rtdDate,o.orderDate,o.title,o.sku');
	} else {
		$orders = $db->objectBuilder()->get(TBL_RMA . ' r', NULL, 'r.*,f.trackingId,f.shipmentStatus,p.imageLink,o.status as orderStatus,o.uid as orderUIds,o.quantity,o.quantity,o.rtdDate,o.orderDate,o.title,o.sku');
	}
	// $db->where('rmaId', $_GET['rmaId']);
	// $order = $db->objectBuilder()->get(TBL_RMA.' ra',NULL, 'o.*');
	$db->startTransaction();
	$logistics = $db->objectBuilder()->get('bas_sp_logistic' . ' s', null, 's.*');
	$orderId = $orders[0]->orderId;
	$deliverey_address = json_decode($orders[0]->returnAddress);
	// ccd($deliverey_address);

	$statuses = array();
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Shopify <small>Order</small>
		</h3>
		<div class="page-bar">
			<ul class="page-breadcrumb">
				<li>
					<i class="fa fa-home"></i>
					<a href="<?= BASE_URL; ?>/fims-live">Home</a>
					<i class="fa fa-angle-right"></i>
				</li>
				<li><a href="javascript:;">RMA</a><i class="fa fa-angle-right"></i></li>
				<li><a href="javascript:;">Order</a></li>
			</ul>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row ">
			<div class="col-md-12 order_head">
				<h3 class="inline"><?= isset($orders[0]->orderNumberFormated) ? $orders[0]->orderNumberFormated : ''; ?></h3>&nbsp;&nbsp;<span class="inline"><?= date('Y M d \a\t h:i A', strtotime(isset($orders[0]->orderDate)) ? $orders[0]->orderDate : 1); ?></span>
				<div class="badges inline">
					<span class="badge badge-info"><?= isset($orders[0]->replacementOrder) ? '!! REPLACEMENT !!' : strtoupper(isset($orders[0]->paymentType) ? $orders[0]->paymentType : ''); ?></span>
					<span class="badge badge-warning"><?= strtoupper(isset($orders[0]->status) ? $orders[0]->status : ''); ?></span>
					<?php
					if (isset($orders[0]->isAssuredDelivery)) {
						echo '<span class="badge badge-success">ASSURED DELIVERY</span>';
					}
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-9"> <!-- col-md-offset-2"> -->
				<div class="row">
					<div class="col-md-12">
					</div>
					<div class="col-md-8">
						<div class="portlet">
							<div class="portlet-body">
								<div class="item_details">
									<?php
									if (isset($orders)) {
										$items = 0;
										$sub_total = 0;
										$discount = 0;
										$gift_card = 0;
										$refundAmount = 0;
										$shipping = 0;
										$total = 0;
										$other_discount = 0;
										$net_total = 0;
										$count = count($orders);
										$i = 0;
										$uids = array();
										foreach ($orders as $order) :
											if ($order->status != "cancelled") {
												$items += $order->quantity;
												$sub_total += isset($order->sellingPrice) ? $order->sellingPrice * $order->quantity : 0;
												$discount += isset($order->spDiscount) ? $order->spDiscount : 0;
												$gift_card += isset($order->giftCardAmount) ? $order->giftCardAmount : 0;
												$refundAmount += isset($order->refundAmount) ? $order->refundAmount : 0;
												$shipping += isset($order->shippingCharge) ? $order->shippingCharge : 0;
												$total += isset($order->totalPrice) ? $order->totalPrice * $order->quantity : 0;
												$other_discount += isset($order->paymentGatewayDiscount) ? $order->paymentGatewayDiscount : 0;
											}
											$timeline = array(
												'Order Received' => $orders[0]->orderDate,
												'Confirmed' => isset($order->orderConfirmedDate) ? $order->orderConfirmedDate : '',
												'Packing' => $order->rtdDate,
												'Shipped' => $order->shippedDate,
												'Picked Up' => isset($order->pickedUpDate) ? $order->pickedUpDate : '',
												'Expected Delivery' => isset($order->exDeliveryDate) ? $order->exDeliveryDate : '',
												'Delivered' => isset($order->deliveredDate) ? $order->deliveredDate : '',
												'Canceled' => isset($order->cancelledDate) ? $order->cancelledDate : '',

											);
											if (isset($order->status) && !is_null($order->status))
												$statuses[] = $order->status;
											else if ($order->hold && $order->status != "cancelled")
												$statuses[] = 'HOLD';
											else
												$statuses[] = $order->status;
											if ($order->status == "shipped" && isset($order->pickedUpDate) && is_null($order->pickedUpDate))
												$order->status = 'PICKUP PENDING';
											if ($order->status == "shipped" && isset($order->pickedUpDate) && !is_null($order->pickedUpDate) && $order->status != "undelivered" && is_null($order->deliveredDate))
												$order->status = 'IN-TRANSIT';
											if ($order->status == "shipped" && isset($order->pickedUpDate) && !is_null($order->pickedUpDate) && $order->status == "undelivered" && is_null($order->deliveredDate))
												$order->status = 'UNDELIVERED';

									?>
											<div class="row">
												<div class="col-md-2">
													<?php if (isset($order->thumb_image_url)) : ?>
														<img src=" <?= IMAGE_URL . '/uploads/products/' . $order->thumb_image_url; ?>" />
													<?php endif; ?>
													<?php if (isset($order->imageLink)) : ?>
														<img src=" <?= $order->imageLink; ?>" width="130px" />
													<?php endif; ?>
												</div>
												<div class="col-md-10">
													<?php if (isset($order->hold) && $order->hold && $order->status != "cancelled") : ?>
														<span class="badge badge-warning">HOLD</span>
													<?php else : ?>
														<span class="badge badge-success"><?= strtoupper(isset($order->shipmentStatus) ? $order->shipmentStatus : ''); ?></span>
													<?php endif; ?>
													<?php if (isset($order->status)) : ?>
														&nbsp;<span class="badge badge-warning"><?= strtoupper($order->status); ?></span>
													<?php endif; ?><br />
													<?php if (isset($order->spStatus)) : ?>
														&nbsp;<span class="badge badge-warning"><?= strtoupper($order->spStatus); ?></span>
													<?php endif; ?><br />

													<h4><a href="<?= 'https://' . $order->account_domain . '/products/' . $order->handle; ?>" target="_blank"><?= $order->title; ?></a></h4>

													<h5><b>Marketplace : </b> <?= $order->marketplace; ?></h5>
													<div class="sp_item_details">
														<span><b>SKU:</b> <?= $order->sku; ?></span>
														<span><b>Quantity:</b> <?= $order->quantity; ?></span>
														<span><b>Price:</b> &#8377;<?= isset($order->sellingPrice) ? $order->sellingPrice : '';  ?></span>
														<?php if ($order->uid) :
															$uids = json_decode($order->orderUIds);
															echo '<span><b>UID:</b> ';
															if (isset($uids)) {
																foreach ($uids as $uid) {
																	echo '<a href="' . BASE_URL . '/inventory/history.php?uid=' . $uid . '" target="_blank">' . $uid . '</a> ';
																}
															}

															echo '</span>';
														?>
														<?php endif; ?>
													</div>
													<div class="sp_item_details">
														<span><b>RMA Order ID:</b> <a href="//<?= $order->account_store_url; ?>/admin/orders/<?= $order->orderId; ?>" target="_blank"><?= $order->orderId; ?></a></span>
														<span><b>RMA Order Item ID:</b> <a href="//<?= $order->account_store_url; ?>/admin/orders/<?= $order->orderItemId; ?>" target="_blank"><?= $order->orderItemId; ?></a></span>
														<?php if (isset($order->invoiceNumber)) : ?>
															<span><b>Invoice Date:</b> <?= date('d M, Y', strtotime($order->invoiceDate)); ?></span>
															<span><b>Invoice Number:</b> <?= isset($order->invoiceNumber) ? $order->invoiceNumber : ''; ?></span>
														<?php endif; ?>
													</div>
													<div class="sp_item_details">
														<?php if (isset($order->trackingId)) : ?>
															<span><b>Tracking Id :</b> <?= $order->trackingId; ?></span>
														<?php endif; ?>
													</div>
												</div>
											</div>
									<?php
											$i++;
											if ($count > 1 && $i != $count)
												echo '<hr />';
										endforeach;
									}

									?>
								</div>
							</div>
						</div>
						<div class="portlet">
							<div class="portlet-body">
								<div class="order_value_details">
									<table class="table table-no-border">
										<tbody>
											<tr>
												<td width="20%">Estimate</td>
												<td>
													<p class="text-center">Details</p>
													<table class="table table-no-border">
														<tr>
															<?php
															foreach (json_decode($order->estimate)[0] as  $key => $estimate) {
																echo '<td>' . $key . '</td> ';
															}
															?>
														</tr>
														<?php
														foreach (json_decode($order->estimate) as $estimate) {
															echo '<tr>';
															foreach ($estimate as $key => $es) {
																echo '<td>' . $es . '</td> ';
															}
															echo ' </tr>';
														}
														?>
													</table>
												</td>
											</tr>
											<tr>
												<td>Estimate Status</td>
												<td class="text-right"><?= isset($order->estimateStatus) ? $order->estimateStatus : ' ' ?></td>
											</tr>
											<tr>
												<td>Estimate Total</td>
												<td class="text-right"><?= isset($order->estimateTotal) ? $order->estimateTotal : 0 ?></td>
											</tr>
											<tr>
												<td><b>Estimate Total Approved</b></td>
												<td class="text-right"><b><?= isset($order->estimateTotalApproved) ? $order->estimateTotalApproved : 0 ?></b></td>
											</tr>
											<tr>
												<td>Estimate Shipping Total</td>
												<td class="text-right"><?= isset($order->estimateShippingTotal) ? $order->estimateShippingTotal : 0 ?></td>
											</tr>
										</tbody>
									</table>

									<div>
										<h5>
											<b>Complains :</b>
										</h5>
										<?php

										echo isset($orders[0]->complain) ? $orders[0]->complain : '';
										?>
									</div>
								</div>
							</div>
						</div>
						<div class="portlet">
							<div class="portlet-body">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="order_timeline">
												<div class="timeline-default">
													<?php
													if (isset($timeline)) {
														$timeline = array_filter($timeline);
														if (isset($timeline['Delivered']))
															unset($timeline['Expected Delivery']);
														$time_spans = count($timeline);
														$i = 0;
														foreach ($timeline as $activity => $timestamp) :
															$active = "";
															if ($i == ($time_spans - 1))
																$active = ' active';
															echo '<div class="timeline-container col-md-2' . $active . '">
															<div class="timeline-tooltip-container">
																<div class="title top">
																	<span>' . $activity . '</span>
																</div>
																<div class="timeline-event">
																	<div class="outer-circle">
																		<span class="inner-circle"></span>
																		<span class="connector"></span>
																	</div>
																</div>
															</div>
															<div class="date-container">
																<div class="date">' . date('M d, Y', strtotime($timestamp)) . '</div>
																<div class="day">' . date('H:i', strtotime($timestamp)) . '</div>
															</div>
														</div>';
															$i++;
														endforeach;
													}
													?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="portlet">
							<div class="portlet-body">
								<div class="row">
									<div class="col-md-12">
										<div class="order_comments">
											<?php
											isset($orders[0]->rmaId) ? get_comments($orders[0]->rmaId, true) : '';
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
					if (isset($statuses)) {
						$statuses = array_unique($statuses);

						if (count($statuses) == 1 && $statuses[0] == "cancelled")
							$statuses = "cancelled";
						else {
							$statuses = array_values(array_diff($statuses, array('cancelled')));
						}
						if (count($statuses) == 1 && $statuses[0] == "start" || $statuses[0] == "created") {
							$statuses[0] = 'approved';
						}
						$action_options = json_decode(get_option('order_tags'), 1)['rma'][$statuses[count($statuses) - 1]];

						$action = "";
						if ($action_options) {
							foreach ($action_options as $action_option) {
								$modal = "";
								$modal_class = "";
								$btn_type = 'primary';

								if ($action_option == "General Comment" || $action_option == "Follow Up" || strpos($action_option, 'Escalation') !== FALSE) {
									$modal_class = ' view_comment';
									$modal = 'href="#view_comments" data-toggle="modal" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
									if ($action_option == "General Comment")
										$btn_type = "info";
									else
										$btn_type = "success";
								}

								if ($action_option == "Add Additional Job") {
									$btn_type = "warning create-return-replacement";
									$modal = 'href="#refund_replace" data-type="create_replacement" data-ordernumber="' .  $orders[0]->orderId . '" data-toggle="modal" data-marketplace="' .  $orders[0]->marketplace . '" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
								}
								if ($action_option == "Mark Delivered") {
									$modal = 'href="#order_delivered" data-toggle="modal" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
								}

								if ($action_option == "Update Traking Details" && !($order->trackingId)) {
									$btn_type = "warning create-return-replacement";
									$modal = 'href="#update_tracking_details" data-type="update_tracking_details" data-ordernumber="' .  $orders[0]->orderId . '" data-toggle="modal" data-marketplace="' .  $orders[0]->marketplace . '" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
								} else if ($action_option == "Update Traking Details") {
									continue;
								}

								$action .= '<a role="button" data-name="' . $action_option . '" data-type="' . str_replace(' ', '_', strtolower($action_option)) . '" style="width:100%;float:unset!important;" class=" btn btn-action btn-' . $btn_type . $modal_class . '" ' . $modal . '>' . $action_option . '&nbsp;<i></i></a>';
							}
						}
					}
					?>
					<div class="col-md-4">
						<div class="portlet">
							<div class="portlet-body order-actions">
								<?= isset($action) ? $action : ''; ?>

							</div>
						</div>
						<div class="portlet">
							<div class="portlet-title">
								<div class="caption">
									SHIPPING ADDRESS <?= (strpos(strtolower(isset($orders[0]->tags) ? $orders[0]->tags : 'gokwik'), 'gokwik') !== FALSE) ? "<span class='badge badge-success'>GoKwik</span>" : ""; ?>
								</div>
								<div class="tools">
									<a title='Click to update address' class='view_address pull-right' data-accountid='<?= isset($order->accountId) ? $order->accountId : ''; ?>' data-orderid='<?= isset($order->orderId) ? $order->orderId : ''; ?>'><svg xmlns="http://www.w3.org/2000/svg" height="14px" viewBox="0 0 512 512">
											<path d="M362.7 19.32C387.7-5.678 428.3-5.678 453.3 19.32L492.7 58.75C517.7 83.74 517.7 124.3 492.7 149.3L444.3 197.7L314.3 67.72L362.7 19.32zM421.7 220.3L188.5 453.4C178.1 463.8 165.2 471.5 151.1 475.6L30.77 511C22.35 513.5 13.24 511.2 7.03 504.1C.8198 498.8-1.502 489.7 .976 481.2L36.37 360.9C40.53 346.8 48.16 333.9 58.57 323.5L291.7 90.34L421.7 220.3z" />
										</svg></a>
									<?php
									if (isset($deliverey_address)) {
										// $view_map = "<br /><a title='View Address on Map' href='https://maps.google.com/?q=". isset($deliverey_address->latitude) ? $deliverey_address->latitude : ' '.",".$deliverey_address->longitude."&t=h&z=17' target='_blank'>View Map</a>";
										$google_map = "<a title='View Address on Map' href='https://maps.google.com/?q=" . urlencode(str_replace(array(',', '  '), array('', ' '), $deliverey_address->address1 . ' ' . $deliverey_address->address2 . ' ' . $deliverey_address->city . ' ' . $deliverey_address->province . ' ' . $deliverey_address->zip)) . "&t=h&z=17' target='_blank'><svg xmlns='http://www.w3.org/2000/svg' height='14px' viewBox='0 0 576 512'><path d='M408 120C408 174.6 334.9 271.9 302.8 311.1C295.1 321.6 280.9 321.6 273.2 311.1C241.1 271.9 168 174.6 168 120C168 53.73 221.7 0 288 0C354.3 0 408 53.73 408 120zM288 152C310.1 152 328 134.1 328 112C328 89.91 310.1 72 288 72C265.9 72 248 89.91 248 112C248 134.1 265.9 152 288 152zM425.6 179.8C426.1 178.6 426.6 177.4 427.1 176.1L543.1 129.7C558.9 123.4 576 135 576 152V422.8C576 432.6 570 441.4 560.9 445.1L416 503V200.4C419.5 193.5 422.7 186.7 425.6 179.8zM150.4 179.8C153.3 186.7 156.5 193.5 160 200.4V451.8L32.91 502.7C17.15 508.1 0 497.4 0 480.4V209.6C0 199.8 5.975 190.1 15.09 187.3L137.6 138.3C140 152.5 144.9 166.6 150.4 179.8H150.4zM327.8 331.1C341.7 314.6 363.5 286.3 384 255V504.3L192 449.4V255C212.5 286.3 234.3 314.6 248.2 331.1C268.7 357.6 307.3 357.6 327.8 331.1L327.8 331.1z'/></svg></a>";
										if (empty($deliverey_address->latitude) || empty($deliverey_address->longitude))
											$view_map = "";
										echo $google_map;
									}
									?>
								</div>
							</div>

							<div class="portlet-body">
								<address class="customer-address">
									<div class="customer-address-block">
										<b><?= isset($deliverey_address->name) ? $deliverey_address->name : ''; ?></b><br />
										<?= isset($deliverey_address->address1) ? $deliverey_address->address1 : ''; ?><br />
										<?= isset($deliverey_address->address2) ? $deliverey_address->address2 : ''; ?><br />
										<?= isset($deliverey_address->city) ? $deliverey_address->city : '';; ?><br />
										<?= isset($deliverey_address->province) ? $deliverey_address->province : ''; ?><br />
										<?= isset($deliverey_address->zip) ? $deliverey_address->zip : ''; ?>
										<?= isset($view_map) ? $view_map : ''; ?>
									</div>
								</address>
								<address class="customer-contact">
									<abbr title="Phone">M:</abbr> <button title="Click to Copy Mobile Number" class="copy_text btn btn-xs btn-default" data-clipboard-target="#mobile_number"><i class="fa fa-copy"></i></button> <a id="mobile_number" alt="Click to Copy Mobile Number" class="mobileNumber" href='tel:+91<?= ((int)str_replace(array(" ", "+91"), "", isset($orders[0]->mobileNumber) ? $orders[0]->mobileNumber : '')); ?>' rel='nofollow'><?= ((int)str_replace(array(" ", "+91"), "", isset($orders[0]->mobileNumber) ? $orders[0]->mobileNumber : '')) ?></a> <a class="mobileNumberLink" href="https://app.quickreply.ai/conversations/all?filter=search-by-phone&query=91<?= ((int)str_replace(array(" ", "+91"), "", isset($orders[0]->whatsappNumber)) ? $orders[0]->whatsappNumber : '') ?>" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="14px">
											<path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
										</svg></a><?php echo (isset($orders[0]->isAutoApproved) ? '&nbsp;<span class="badge badge-success">Customer Confirmed</span>' : '') ?><br />
									<abbr title="Email">@:</abbr> <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= strtolower(isset($orders[0]->emailAddress) ? $orders[0]->emailAddress : ''); ?>" target="_blank"><?= strtolower(isset($orders[0]->emailAddress) ? $orders[0]->emailAddress : ''); ?></a>
								</address>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">
							ORDER HISTORY
						</div>
					</div>
					<div class="portlet-body">
						<?php
						/*// $customerId = $db->subQuery();
						if(isset($orderId)){
							$db->where('orderId', $orderId);
							$customerId =$db->getValue(TBL_SP_ORDERS, "customerId");
							$db->where('(customerId = ? OR
											deliveryAddress LIKE ?
											OR billingAddress LIKE ?
											OR deliveryAddress LIKE ?
											OR billingAddress LIKE ?)',
								array(
									$customerId,
									'%'.substr(str_replace(array('+',' '), '', isset($deliverey_address->phone))?$deliverey_address->phone:'', -10).'%',
									'%'.substr(str_replace(array('+',' '), '', isset($deliverey_address->phone))?$deliverey_address->phone:'', -10).'%',
									isset($deliverey_address->email)?$deliverey_address->email:'',
									isset($deliverey_address->email)?$deliverey_address->email:''
								)
							);
							$db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_SP_ACCOUNTS.' sa', 'sa.account_id=o.accountId', 'INNER');
							$db->orderBy('orderDate', 'DESC');
							// ccd($db);
							$orders_history = $db->objectBuilder()->get(TBL_SP_ORDERS.' o', NULL, array('orderId, COALESCE(status, "-") as status, COALESCE(spStatus, "-") as spStatus, COALESCE(shipmentStatus, "-") as shipmentStatus, orderNumberFormated,  replacementOrder, account_store_url'));

							// $orders_history = $db->objectBuilder()->get(TBL_SP_ORDERS.' o', NULL, array('orderId, COALESCE(status, "-") as status, COALESCE(spStatus, "-") as spStatus, COALESCE(shipmentStatus, "-") as shipmentStatus, orderNumberFormated, COALESCE(r_shipmentStatus, "-") as r_shipmentStatus, replacementOrder, account_store_url'));

							// $order_history->r_shipmentStatus

							if ($orders_history) :
								echo '<table class="table"><tr><th>Order#</th><th>Order</th><th>Shipment</th><th>Return</th></tr>';
								foreach ($orders_history as $order_history) :
									$replace = "";
									if ($order_history->replacementOrder)
										$replace = '<span class="badge badge-success">R</span>';
									echo '<tr><td><a href="'.BASE_URL.'/shopify/order.php?orderId='.$order_history->orderId.'" target="_blank">'.$order_history->orderNumberFormated.'</a>'.$replace.'</td><td>'.$order_history->status.'</td><td>'.$order_history->shipmentStatus.'</td><td> - </td></tr>';
								endforeach;
								echo '</table>';
							else :
								echo '<center>Hurray!! Its the first order</center>';
							endif;
					   }*/
						?>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
		<!-- BEGIN MODAL FORM-->
		<div class="modals">
			<div class="modal right fade view_comments" id="view_comments" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-comment'></i> ORDER COMMENTS</h4>
						</div>
						<div class="modal-body">
							<form role="form" class="add-comment">
								<div class="form-body">
									<div class="form-group order_comment">
										<textarea class="form-control" name="comment" placeholder="Add new Comment" rows="3" spellcheck="false"></textarea>
									</div>
									<div class="form-group order_cancellation hide">
										<select id="cancellationReason" class="form-control select2 order_cancellation_reason" name="comment" data-allowClear="true" data-placeholder="Order Cancellation Reason" disabled>
											<option></option>
											<option value="Address not valid">Address not valid</option>
										</select>
									</div>
									<div class="form-group call_attempted hide">
										<select id="callAttepmtReason" class="form-control select2 call_attempt_reason" name="comment" data-allowClear="true" data-placeholder="Call Attempted Reason" disabled>
											<option></option>
											<?php
											$call_options = json_decode(get_option('call_attempt_reason'));
											foreach ($call_options as $call_value) {
												echo '<option value="OBC Attempted - ' . $call_value . '">' . $call_value . '</option>';
											}
											?>
										</select>
									</div>
									<div class="form-group ndr_approval hide">
										<select id="ndrApprovedReason" class="form-control select2 ndr_approved_reason" name="comment" data-allowClear="true" data-placeholder="NDR Approved Reason" disabled>
											<option></option>
											<?php
											$ndr_options = json_decode(get_option('ndr_approved_reason'));
											foreach ($ndr_options as $ndr_key => $ndr_value) {
												echo '<option value="NDR Approved - ' . $ndr_value . '">' . $ndr_value . '</option>';
											}
											?>
										</select>
									</div>
									<div class="row">
										<div class="col-md-6 assured_delivery hide">
											<div class="form-group">
												<div class="checkbox">
													<label><input type="checkbox" name="assured_delivery" value="1" disabled> Assured Delivery?</label>
												</div>
											</div>
										</div>
										<div class="col-md-12 form-action">
											<div class="form-group">
												<input type="hidden" name="comment_for" value="" />
												<input type="hidden" name="rmaId" value="<?= $orders[0]->rmaId; ?>">
												<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>" />
												<input type="hidden" name="action" value="add_comment" />
												<button type="submit" class="btn btn-success btn-block"><i class="fa"></i> Submit</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
			<div class="modal right fade refund_replace" id="refund_replace" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog modal-dialog-large" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-location'></i> REFUND & REPLACEMENT</h4>
						</div>
						<div class="modal-body">
							<span class="loading-block hide">
								<center><i class="fa fa-sync fa-spin spinner"></i></center>
							</span>

							<form role="form" class="form-horizontal data-block return-replace" id="return-replace-form" autocomplete="off" method="POST">
								<div class="form-body">

									<div class="uidSelection">
										<div class="form-group">
											<label class="col-md-3 control-label">Product UID<span class="required" aria-required="true">*</span></label>
											<div class="col-md-9 radio-list">
												<div class="row">
													<?php
													// print_r($orders[0]->orderUIds);
													if (isset($orders[0]->orderUIds)) :
														$uids = json_decode($orders[0]->orderUIds);
														$selectedUId = json_decode($orders[0]->uid);
														foreach ($uids as $key => $uid) {

															$checked = in_array($uid, $selectedUId) ? 'checked' : '';
															echo '<div class="col-md-4">
																		<div class="checkbox-list">
																			<label class="checkbox-inline rma-tooltip" data-original-title="" title="">
																			   <input class="checkbox product-uid"   type="checkbox" name="uids[' . $key . ']"  value="' . $uid . '" ' . $checked . '>
																				' . $uid . '
																			</label>
																		</div>
																	</div>';
														}
													endif; ?>
												</div>
												<span class="help-block" id="product-uid-error"></span>
											</div>
										</div>
									</div>

									<div class="issueSelection form-group" id="fieldset_<?= $uid ?>_div">
										<fieldset class="rma_pid_fieldset fieldset_<?= $uid ?>">
											<legend><?= $uid ?></legend>
											<div class="form-group product_condition_list product_condition_list_group_<?= $uid ?>">
												<label class="col-md-3 control-label">Product Condition<span class="required">*</span></label>
												<div class="col-md-9" data-error-container=".form_product_condition_error_<?= $uid ?>">
													<div class="row">
														<div class="radio-list">
															<div class="col-md-4">
																<label class="radio-inline">
																	<div class="radio"><span class="checked"><input class="radio product_condition_damaged" data-msg="Please select atleast one Product Condition" data-error-container=".form_product_condition_error_<?= $uid ?>" type="radio" name="<?= $uid ?>[product_condition]" data-group="<?= $uid ?>" value="damaged" checked=""></span></div>Product Issue
																</label>
															</div>
														</div>
													</div>
												</div>
												<div class="form_product_condition_error_<?= $uid ?> col-md-12 col-md-offset-2"></div>
											</div>
											<div class="form-group issue_product_list_group_<?= $uid ?> damaged_product_list_group_<?= $uid ?> ">
												<label class="col-md-3 control-label">Issue Type<span class="required">*</span></label>
												<div class="col-md-9 checkbox-list" data-error-container=".form_damage_product_error_<?= $uid ?>">
													<div class="row">
														<?php
														if (isset($orders[0]->productIssues)) :
															$db->where('part_category', 1);
															$damage_issues = $db->get(TBL_PARTS_DETAILS, NULL, 'part_name, part_issue, in_warranty_price, out_warranty_price');
															$estimatePart = [];
															$dyn_issues = "";
															foreach ($damage_issues as $k => $issue) {
																foreach (json_decode($orders[0]->estimate) as $estimate) {
																	array_push($estimatePart, $estimate->part);
																}
																$checked = in_array($issue['part_name'], $estimatePart) ? 'checked' : '';


																$dyn_issues .= '<div class="col-md-4">
																	<label class="checkbox-inline">
																	<input class="checkboxes issue_checkboxes" data-msg="Please select atleast one Damage Type" data-error-container=".form_damage_product_error_' . $uid . '" type="checkbox" name="' . $uid . '[damaged][' . $k . ']" data-group="' . $uid . '" data-partname="' . $issue['part_name'] . '" data-inwarrantyprice="' . $issue['in_warranty_price'] . '" data-outwarrantyprice="' . $issue['out_warranty_price'] . '"  value="' . $issue['part_issue'] . '" ' . $checked . '> ' . $issue['part_issue'] . ' </label>
																</div>';
															}

															$dyn_issues .= '<div class="col-md-4">
																				<label class="checkbox-inline"> 
																				<input class="checkboxes other_issue" data-msg="Please select atleast one Damage Type"  type="checkbox" value="other" name="other_issue">Other</label>
																			</div>
																			<div class="col-md-4" style="margin-top: 4px;">
																				<input type="text" name="' . $orders[0]->uid . '[damaged][' . count($damage_issues) . ']" class="form-control input-medium hide issue_textbox" placeholder="Issue Type" value="" tabindex="1"  />
																			</div>';

															echo $dyn_issues;
														endif
														?>


														<label class="col-md-3 control-label"> </label>
														<div>
															<p class="col-md-9  text-danger form_product_uid_error_' . $uid . '" id="form_product_uid_error"></p>
														</div>

													</div>
												</div>
												<div class="form_damage_product_error_<?= $uid ?> col-md-12 col-md-offset-2"></div>

											</div>
											<div class="form_product_uid_error"></div>
											<label class="col-md-3 control-label"> </label>
											<div>
												<p class="col-md-9  text-danger form_product_uid_error_<?= $uid ?>" id="form_product_uid_error"></p>
											</div>
										</fieldset>

									</div>
									<div class="estimateSection form-group">
										<label class="col-md-3 control-label">Estimate</label>
										<div class="col-md-9 estimateSectionDetails">

											<input name="estimate_data" id="estimate_data" type="hidden" value="<?= $orders[0]->estimate ?>">
											<table class="table table-bordered table-striped">
												<thead>
													<tr>
														<th>UID</th>
														<th>Part Name</th>
														<th>Quantity</th>
														<th>Part Value</th>
														<th>Part Total</th>
													</tr>
												</thead>
												<tbody>
													<?php
													foreach (json_decode($orders[0]->estimate) as $estimate) {
														echo '<tr>
																	<td>' . $uid . '</td>
																	<td>' . $estimate->part . '</td>
																	<td>' . $estimate->approved . '</td>
																	<td>' . $estimate->price . '</td>
																	<td>' . $estimate->price . '</td>
																</tr>';
													}
													?>
													<tr>
														<th colspan="4">Total Estimate</th>
														<th><?php echo $orders[0]->estimateTotal ?></th>
													</tr>
												</tbody>
											</table>
										</div>
									</div>

									<div class="form-group">

										<label class="col-md-3 control-label">Customers Complains<span class="required">*</span></label>
										<div class="col-md-9">
											<textarea class="form-control" name="complains" rows="4" required>
												<?php
												$complain = str_replace("\n", '', $orders[0]->complain);
												echo $complain;
												?>
											</textarea>
										</div>
									</div>

									<div class="form-action">
										<div class="form-group">
											<input type="hidden" name="pincode_servicable">
											<input type="hidden" name="billingAddress" value="<?= isset($orders[0]->billingAddress) ? $orders[0]->billingAddress : ''; ?>">
											<input type="hidden" name="deliveryAddress" value="<?= isset($orders[0]->deliveryAddress) ? $orders[0]->deliveryAddress : ''; ?>">
											<input type="hidden" name="locationId" value="<?= isset($orders[0]->locationId) ? $orders[0]->locationId : ''; ?>">
											<input type="hidden" name="customerId" value="<?= isset($orders[0]->customerId) ? $orders[0]->customerId : ''; ?>">
											<input type="hidden" name="accountId" id="accountId" value="<?= isset($orders[0]->accountId) ? $orders[0]->accountId : ''; ?>">
											<input type="hidden" name="lpOrderId" value="<?= isset($orders[0]->lpOrderId) ? $orders[0]->lpOrderId : ''; ?>">
											<input type="hidden" name="lpShipmentId" value="<?= isset($orders[0]->lpShipmentId) ? $orders[0]->lpShipmentId : ''; ?>">
											<input type="hidden" name="lpManifestId" value="<?= isset($orders[0]->lpManifestId) ? $orders[0]->lpManifestId : ''; ?>">
											<input type="hidden" name="action" value="create_rma">

											<input type="hidden" name="marketplace">
											<input type="hidden" name="orderId">
											<input type="hidden" name="rmaId" value="<?= isset($orders[0]->rmaId) ? $orders[0]->rmaId : ''; ?>">
											<input type="hidden" name="orderItemId">
											<input type="hidden" name="productUId" value='<?= $orders[0]->uid; ?>'>
											<input type="hidden" name="sku">
											<input type="hidden" name="accountId">
											<input type="hidden" name="productCondition">
											<input type="hidden" name="rmaAction">
											<input type="hidden" name="estimate" value='<?= isset($orders[0]->estimate) ? $orders[0]->estimate : ''; ?>'>
											<input type="hidden" name="invoiceDate">
											<input type="hidden" name="warrantyPeriod">
											<input type="hidden" name="forwardTrackingId" value="<?= isset($orders[0]->forwardTrackingId) ? $orders[0]->forwardTrackingId : ''; ?>">

											<button type="submit" class="btn btn-success btn-block"><i class="fa"></i>Save & Generate Pickup</button>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
			<div class="modal right fade update_tracking_details" id="update_tracking_details" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog modal-dialog-large" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-location'></i> UPDATE TRACKING DETAILS</h4>
						</div>
						<div class="modal-body">
							<span class="loading-block hide">
								<center><i class="fa fa-sync fa-spin spinner"></i></center>
							</span>

							<form class="selfship_tracking" id="selfship_order_<?= $order->orderId ?>" method="POST">
								<div class="form-body">
									<div class="form-group">
										<label class="col-md-3 control-label">Tracking Id<span class="required" aria-required="true">*</span></label>
										<input type="text" name="trackingId" class="form-control input-large trackingId" placeholder="Tracking Id" autocomplete="off" required value="<?= $order->trackingId ?>">
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Courier Name<span class="required" aria-required="true">*</span></label>
										<input type="text" name="logistic" class="form-control input-large logistic" placeholder="Logistic (eg: Speed Post)" value="SpeedPost" autocomplete="off" required>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Logistic Provider Name<span class="required" aria-required="true">*</span></label>
										<select id="logisticProvider" class="form-control select2 logisticProvider input-large" name="logisticProvider" data-allowClear="true" data-placeholder="Logistic Provider" required>
											<option></option>
											<?php
											foreach ($logistics as $logistic) {
												echo '<option value="' . $logistic->lp_provider_name . '">' . $logistic->lp_provider_name . '</option>';
											}
											?>
										</select>
									</div>
									<div class="form-group">
										<input type="hidden" name="orderId" value="<?= $order->orderId ?>" />
										<input type="hidden" name="rmaNumber" value="<?= $order->rmaNumber ?>" />
										<input type="hidden" name="orderItemId" value="<?= $order->orderItemId ?>" />
										<input type="hidden" name="accountId" value="<?= $order->accountId ?>" />
										<input type="hidden" name="pickedUpDate" value="<?= date("c") ?>" />
										<!-- <input type="hidden" name="action" value="mark_orders" /> -->
										<input type="hidden" name="type" value="self_shipped" />
									</div>
								</div>

								<div class="form-action">
									<button class="btn btn-success btn-action btn-block selfship" id="tracking_submit_<?= $order->orderId ?>" type="submit">Update to In-Transit</button>
								</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
			<div class="modal right fade order_delivered" id="order_delivered" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-location'></i> MARK ORDER DELIVERED</h4>
						</div>
						<div class="modal-body">
							<span class="loading-block hide">
								<center><i class="fa fa-sync fa-spin spinner"></i></center>
							</span>
							<form role="form" class="form-horizontal data-block order-delivered" id="order-delivered" autocomplete="off" method="POST">
								<div class="form-body">
									<div class="uidSelection">
										<div class="form-group">
											<label class="col-md-3 control-label">Delivery Date<span class="required">*</span></label>
											<div class="col-md-9">
												<input class="form-control date-picker" type="text" value="" name="delivered_date" data-date-format="yyyy-mm-dd" data-date-end-date="0d" required />
											</div>
										</div>
										<div class="form-action">
											<div class="form-group">
												<input type="hidden" name="action" value="mark_orders">
												<input type="hidden" name="type" value="delivered">
												<input type="hidden" name="orderId" value="<?= $orders[0]->orderId; ?>">
												<input type="hidden" name="rmaNumber" value="<?= $orders[0]->rmaNumber; ?>">
												<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>">
												<input type="hidden" name="marketPlace" value="<?= $orders[0]->marketplace; ?>">
												<button type="submit" class="btn btn-success btn-block btn-order-delivered-submit"><i class="fa"></i>Update Delivery Status</button>
											</div>
										</div>
									</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
		</div>
		<!-- END MODAL FORM-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>