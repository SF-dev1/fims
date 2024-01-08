<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
include_once(ROOT_PATH . '/shopify/functions.php');
global $accounts;

// $orderId = 4273120346321;
if (isset($_GET['order']))
	$db->where('(orderNumber LIKE ? OR orderNumberFormated LIKE ? )', array($_GET['order'], $_GET['order']));
if (isset($_GET['orderId']))
	$db->where('o.orderId', $_GET['orderId']);

$db->join(TBL_SP_PRODUCTS . ' sp', 'sp.variantId=o.variantId');
$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId');
$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
$db->join(TBL_CLIENTS_WHATSAPP . ' wa', 'wa.whatsappClientId=sa.account_active_whatsapp_id', 'LEFT');
$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider', 'LEFT');
$db->orderBy('insertDate', 'DESC');
$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, ' f.*, o.*, sp.*, sa.*, sl.*, wa.whatsappDashboardUrl, o.status as order_status');
$orderId = $orders[0]->orderId;
$deliverey_address = json_decode($orders[0]->deliveryAddress);
$action_status = $orders[0]->order_status;

// echo '<pre>';
// echo $db->getLastQuery();
// var_dump($orders);
// echo '</pre>';
// exit;

$order_statuses = array();
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
				<li><a href="javascript:;">Shopify</a><i class="fa fa-angle-right"></i></li>
				<li><a href="javascript:;">Order</a></li>
			</ul>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12 order_head">
				<h3 class="inline"><?= $orders[0]->orderNumberFormated; ?></h3>&nbsp;&nbsp;<span class="inline"><?= date('Y M d \a\t h:i A', strtotime($orders[0]->orderDate)); ?></span>
				<div class="badges inline">
					<span class="badge badge-info"><?= ($orders[0]->replacementOrder) ? '!! REPLACEMENT !!' : strtoupper($orders[0]->paymentType); ?></span>
					<span class="badge badge-warning"><?= strtoupper($action_status); ?></span>
					<?php
					if ($orders[0]->isAssuredDelivery) {
						echo '<span class="badge badge-success">ASSURED DELIVERY</span>';
					}
					if ($orders[0]->replacementOrder) {
						$replacementOrder = find_initial_order($orders[0]->orderItemId);
						echo '&nbsp;-&nbsp;<a href="?orderId=' . $replacementOrder->orderId . '" target="_blank">' . $replacementOrder->orderNumberFormated . '</a>';
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
									$discountCodes = "";
									if (!is_null($orders[0]->discountCodes))
										$discountCodes = implode(', ', array_column(json_decode($orders[0]->discountCodes), 'code')) ?? "";
									if (!$discountCodes)
										$discountCodes = implode(', ', array_column(json_decode($orders[0]->discountCodes), 'title')) ?? "";
									foreach ($orders as $order) :
										// echo '<pre>';
										// var_dump($order);
										// echo '</pre>';
										$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=f.lpProvider');
										$db->join(TBL_SP_ORDERS . ' o', 'o.orderId=f.orderId');
										$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId');
										$db->joinWhere(TBL_SP_RETURNS . ' r', 'r.orderItemId', $order->orderItemId);
										$db->where('f.orderId', $order->orderId);
										$db->where('f.fulfillmentType', 'return');
										// $db->where('f.fulfillmentStatus', 'cancelled', '!=');
										$db->orderBy('f.fulfillmentId', 'DESC');
										$return_details = $db->objectBuilder()->getOne(TBL_FULFILLMENT . ' f');

										if ($order->order_status != "cancelled" && $order->shipmentStatus != "cancelled") {
											$items += $order->quantity;
											$sub_total += $order->sellingPrice * $order->quantity;
											$discount += $order->spDiscount;
											$gift_card += $order->giftCardAmount;
											$refundAmount += $order->refundAmount;
											$shipping += $order->shippingCharge;
											$total += $order->totalPrice * $order->quantity;
											$other_discount += $order->paymentGatewayDiscount;
										}
										$timeline = array(
											'Order Received' => $order->orderDate,
											'Confirmed' => $order->orderConfirmedDate,
											'Packing' => $order->rtdDate,
											'Shipped' => $order->shippedDate,
											'Picked Up' => $order->pickedUpDate,
											'Expected Delivery' => $order->expectedDate,
											'Delivered' => $order->deliveredDate,
											'Canceled' => $order->cancelledDate,
											// 'Return Initiated' => $order->deliveredDate,
											// 'Return Picked Up' => $order->deliveredDate,
											// 'Return Delivered' => $order->deliveredDate,
											// 'Return Received' => $order->deliveredDate,

											// 'Refund Initiated' => $order->deliveredDate,
											// 'Return Completed' => $order->deliveredDate
										);


										if ($order->fulfillmentStatus == "start")
											$action_status = $order->order_status;
										else if ($order->hold && $order->order_status != "cancelled")
											$action_status = 'HOLD';
										else if ($order->fulfillmentStatus == "shipped")
											$action_status = $order->shipmentStatus;

										if ($order->fulfillmentStatus == "shipped" && is_null($order->pickedUpDate) && $order->shipmentStatus != "cancelled")
											$action_status = 'pickup_pending';
										if ($order->fulfillmentStatus == "shipped" && !is_null($order->pickedUpDate) && $order->shipmentStatus != "undelivered" && is_null($order->deliveredDate) && $order->shipmentStatus != "cancelled")
											$action_status = 'in_transit';
										if ($order->fulfillmentStatus == "shipped" && !is_null($order->pickedUpDate) && (strtotime($order->pickedUpDate) + 60 * 60 * 24 * 2 < time()) && $order->shipmentStatus != "undelivered" && is_null($order->deliveredDate) && $order->shipmentStatus != "cancelled")
											$action_status = 'out_for_delivery';
										if ($order->fulfillmentStatus == "shipped" && !is_null($order->pickedUpDate) && $order->shipmentStatus == "undelivered" && is_null($order->deliveredDate) && $order->shipmentStatus != "cancelled")
											$action_status = 'undelivered';

										if ($return_details) {
											if ($return_details->shipmentStatus == "cancelled" || $return_details->shipmentStatus == "pickup_exception")
												$action_status = "return_" . $return_details->shipmentStatus;
										}

										// $return_content = "";
										// $db->where('orderItemId', $order->orderItemId);
										// $db->join(TBL_FULFILLMENT.' f', 'f.orderId=o.orderId', 'LEFT');
										// $db->joinWhere(TBL_FULFILLMENT.' fr', 'fr.fulfillmentType', 'return');
										// $returns = $db->get(TBL_SP_RETURNS);
										// if ($returns){
										// 	foreach($returns as $return){
										// 		echo '<pre>';
										// 		var_dump($return);
										// 		echo '</pre>';
										// 	}
										// }
									?>
										<div class="row">
											<div class="col-md-2">
												<img height="150" width="120" src="<?= $order->imageLink; ?>">
											</div>
											<div class="col-md-10">
												<h4>
													<a href="<?= 'https://' . $order->account_domain . '/products/' . $order->handle; ?>" target="_blank"><?= $order->title; ?></a>
													<?php if ($order->hold && $order->order_status != "cancelled") : ?>
														<span class="badge badge-warning">HOLD</span>
													<?php elseif ($order->order_status == "cancelled") : ?>
														<span class="badge badge-danger">CANCELLED</span>
													<?php elseif ($order->fulfillmentStatus) : ?>
														<span class="badge badge-info"><?= strtoupper($order->fulfillmentStatus); ?></span>
													<?php endif; ?>
												</h4>
												<div class="sp_item_details">
													<span><b>SKU:</b> <?= $order->sku; ?></span>
													<span><b>Quantity:</b> <?= $order->quantity; ?></span>
													<span><b>Price:</b> &#8377;<?= $order->sellingPrice; ?></span>
													<?php if ($order->uid) :
														$uids = json_decode($order->uid);
														echo '<span><b>UID:</b> ';
														foreach ($uids as $uid) {
															echo '<a href="' . BASE_URL . '/inventory/history.php?uid=' . $uid . '" target="_blank">' . $uid . '</a> ';
														}
														echo '</span>';
													?>
													<?php endif; ?>
												</div>
												<div class="sp_item_details">
													<span><b>Shopify Order ID:</b> <a href="//<?= $order->account_store_url; ?>/admin/orders/<?= $order->orderId; ?>" target="_blank"><?= $order->orderId; ?></a></span>
													<?php if ($order->invoiceNumber) : ?>
														<span><b>Invoice Date:</b> <?= date('d M, Y', strtotime($order->invoiceDate)); ?></span>
														<span><b>Invoice Number:</b> <?= $order->invoiceNumber; ?> <a class="download_invoice" alt="Click to Download Invoice" href="./ajax_load.php?action=create_invoice&orderId=<?= $order->orderId ?>" target="_blank"><button title="Click to Download Invoice" class="btn btn-xs btn-default" style="font-size: 9px;"><i class="fa fa-download"></i></button></a></span>
													<?php endif; ?>
												</div>
												<?php
												// $db->join(TBL_SP_LOGISTIC.' sl', 'sl.lp_provider_id=f.lpProvider');
												// $db->where('f.orderId', $order->orderId);
												// $db->where('f.fulfillmentType', 'forward');
												// $db->joinWhere('f.fulfillmentStatus', 'cancelled', '!=');
												// $logisticDetails = $db->objectBuilder()->getOne(TBL_FULFILLMENT.' f');
												// if($logisticDetails): 
												if ($order->trackingId) :
												?>
													<div class="sp_item_details">
														<span><b>Logistic Partner:</b> <a href="<?= $order->lp_provider_order_url . $order->lpOrderId; ?>" target="_blank"><?= $order->lp_provider_name; ?></a></span>
														<span><b>Courier:</b> <?= $order->logistic; ?></span>
														<span><b>Tracking:</b> <a id="p2" class="copy_text" alt="Click to Copy Tracking ID" href="<?= $order->lp_provider_tracking_url . $order->trackingId; ?>" target="_blank"><?= $order->trackingId; ?></a> <button title="Click to Copy Tracking ID" class="copy_text btn btn-xs btn-default" data-clipboard-target="#p2"><i class="fa fa-copy"></i></button></span>
														<?php if ($order->shipmentStatus) : ?>
															&nbsp;<span class="badge badge-success"><?= strtoupper($order->shipmentStatus); ?></span>
														<?php endif; ?>

													</div>
												<?php endif;
												if ($return_details) : ?>
													<div class="sp_item_details">
														<span><b>Returns - Logistic Partner:</b> <a href="<?= $return_details->lp_provider_order_url . $return_details->lpOrderId; ?>" target="_blank"><?= $return_details->lp_provider_name; ?></a></span>
														<span><b>Courier:</b> <?= $return_details->logistic; ?></span>
														<span><b>Tracking:</b> <a id="p3" class="copy_text" alt="Click to Copy Tracking ID" href="<?= $return_details->lp_provider_tracking_url . $return_details->trackingId; ?>" target="_blank"><?= $return_details->trackingId; ?></a> <button title="Click to Copy Tracking ID" class="copy_text btn btn-xs btn-default" data-clipboard-target="#p3"><i class="fa fa-copy"></i></button></span>
														<?php if ($return_details->shipmentStatus) : ?>
															&nbsp;<span class="badge badge-warning"><?= $return_details->shipmentStatus == "delivered" ? strtoupper($return_details->r_status) : strtoupper($return_details->shipmentStatus); ?></span>
														<?php endif; ?>
													</div>
												<?php endif; ?>
											</div>
										</div>
									<?php
										$i++;
										if ($count > 1 && $i != $count)
											echo '<hr />';
									endforeach;
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
												<td width="20%">Sub Total</td>
												<td width="60%"><?= $items; ?> Items</td>
												<td class="text-right" width="20%">&#8377;<?= number_format($sub_total, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td>Discount</td>
												<td><?= (($orders[0]->replacementOrder) ? 'REPLACEMENT ORDER' : $discountCodes); ?></td>
												<td class="text-right">&#8377;<?= number_format($discount, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td>Gift Card</td>
												<td></td>
												<td class="text-right">&#8377;<?= number_format($gift_card, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td>Shipping</td>
												<td></td>
												<td class="text-right">&#8377;<?= number_format($shipping, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td><b>Total</b></td>
												<td></td>
												<td class="text-right"><b>&#8377;<?= number_format($total, 2, '.', ','); ?></b></td>
											</tr>
											<tr>
												<td>PG Discount</td>
												<td></td>
												<td class="text-right">&#8377;<?= number_format($other_discount, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td>Net Amount</td>
												<td><?= ucwords(str_replace('_', ' ', $orders[0]->paymentGateway)) ?>
													<?php
													if ($orders[0]->paymentGateway == "Razorpay")
														echo '[<a href="https://dashboard.razorpay.com/app/orders/' . $orders[0]->paymentGatewayOrder . '" target="_blank">View Transaction</a>]';
													?>
												</td>
												<td class="text-right">&#8377;<?= number_format($total - $other_discount, 2, '.', ','); ?></td>
											</tr>
											<tr>
												<td>Refunded Amount</td>
												<td></td>
												<td class="text-right">&#8377;<?= number_format($refundAmount, 2, '.', ','); ?></td>
											</tr>
										</tbody>
									</table>
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
											get_comments($orders[0]->orderId, true);
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
					$order_statuses = array_unique($order_statuses);

					if (count($order_statuses) == 1 && $order_statuses[0] == "cancelled")
						$order_status = "cancelled";
					else {
						$order_statuses = array_values(array_diff($order_statuses, array('cancelled')));
					}

					// $action_options = json_decode(get_option('order_tags'), 1)['shopify'][$order_statuses[count($order_statuses)-1]];
					$action_options = json_decode(get_option('order_tags'), 1)['shopify'][strtolower($action_status)];

					$action = "";
					foreach ($action_options as $action_option) {
						$modal = "";
						$modal_class = "";
						$btn_type = 'primary';
						if ($action_option == "Cancellation Request" && $orders[0]->replacementOrder)
							continue;
						else if ($action_option == "Cancellation Request") {
							$btn_type = "danger";
							$modal_class = ' view_comment';
							$modal = 'href="#view_comments" data-toggle="modal" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						}

						if ($action_option == "Convert to Self Pickup" && strtolower($deliverey_address->city) == "surat") {
							$btn_type = "warning";
							// $modal_class = ' view_comment';
							// $modal = 'href="#view_comments" data-toggle="modal" data-comment="Self Pickup from warehouse" data-commentfor="self_pickup"';
							$modal = ' data-comment="Self Pickup from warehouse" data-commentfor="self_pickup" data-accountid="' . $orders[0]->accountId . '" data-orderid="' . $orders[0]->orderId . '"';
						} else if ($action_option == "Convert to Self Pickup") {
							continue;
						}

						if ($action_option == "Follow Up" || strpos($action_option, 'Escalation') !== FALSE) {
							$modal = 'href="#view_comments" data-toggle="modal" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
							if ($action_option == "General Comment")
								$btn_type = "info";
							else
								$btn_type = "success";
						}

						if ($action_option == "Confirmation Call" || $action_option == "Auto Confirmed") {
							$btn_type = "success";
						}

						if ($action_option == "Confirmation Call") {
							$modal_class = ' view_comment';
							$modal = 'href="#view_comments" data-toggle="modal" data-comment="OBC - Order Approved." data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						}

						if ($action_option == "Auto Confirmed") {
							$modal_class = ' view_comment';
							$modal = 'href="#view_comments" data-toggle="modal" data-comment="Address Traceable. Order Approved." data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						}

						if ($action_option == "Call Attempted") {
							$modal_class = ' view_comment';
							$modal = 'href="#view_comments" data-toggle="modal" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '" data-identifiervalue="' . $orders[0]->orderId . '" data-identifiertype="shopify" data-type="call_attempted" data-accountid="' . $orders[0]->accountId . '"';
						}

						if ($action_option == "Request Address") {
							$modal = ' data-comment="Address requested" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '" data-identifiervalue="' . $orders[0]->orderId . '" data-identifiertype="shopify" data-type="request_address" data-accountid="' . $orders[0]->accountId . '"';
						}

						if ($action_option == "Assured Delivery") {
							$btn_type = "success";
							$modal_class = ' add_comment';
							$modal = 'data-comment="Assured Delivery Confirmed" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						}

						if ($action_option == "NDR Approved" && $orders[0]->isReturnApproved) {
							continue;
						} else if ($action_option == "NDR Approved") {
							$btn_type = "warning";
							$modal_class = ' view_comment';
							$modal = 'href="#view_comments" data-toggle="modal" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						}

						if ($orders[0]->paymentType == "prepaid" && $action_option == "Convert to Prepaid")
							continue;

						if ($action_option == "Mark Delivered" && is_null($timeline['Delivered'])) {
							$btn_type = "success";
							$modal = 'href="#order_delivered" data-toggle="modal" data-ordernumber="' .  $orders[0]->orderNumber . '" data-accountid="' .  $orders[0]->accountId . '" data-comment="Order Marked Delivered Manually" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						} else if ($action_option == "Mark Delivered") {
							continue;
						}

						if ($action_option == "Mark Undelivered" && is_null($timeline['Delivered']) && (!$return_details || $return_details->shipmentStatus == "cancelled")) {
							$btn_type = "warning";
							$modal = 'data-orderId="' .  $orders[0]->orderId . '" data-accountid="' .  $orders[0]->accountId . '" data-comment="Order Marked RTO Manually" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						} else if ($action_option == "Mark Undelivered") {
							continue;
						}

						if ($action_option == "Reassign Courier") {
							$modal = 'href="#reassign_courier" data-type="reassign_courier" data-toggle="modal"';
							$btn_type = "warning";
						}

						if ($action_option == "Convert To Self Return") {
							$modal = ' data-comment="Convert To Self Return" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '" data-accountid="' . $orders[0]->accountId . '" data-orderid="' . $orders[0]->orderId . '" data-fulfillmentid="' . $return_details->fulfillmentId . '" data-returnid="' . $return_details->returnId . '"';
							$btn_type = "warning";
						}

						$returnableDays = strtotime((isset($timeline['Delivered']) ? ($timeline['Delivered']) : date("Y-m-d")) . ' +10 days') + ($order->returnGrace * 86400);
						if (($action_option == "Create Refund/Replacement") && !is_null($timeline['Delivered']) && (time() < $returnableDays) && (!$return_details || $return_details->shipmentStatus == "cancelled")) {
							$btn_type = "warning create-return-replacement";
							$modal = 'href="#refund_replace" data-type="create_replacement" data-ordernumber="' .  $orders[0]->orderNumber . '" data-accountid="' .  $orders[0]->accountId . '" data-toggle="modal" data-comment="" data-commentfor="' . str_replace(' ', '_', strtolower($action_option)) . '"';
						} else if ($action_option == "Create Refund/Replacement") {
							continue;
						}

						if ($action_option == "Create RMA" && !is_null($timeline['Delivered']) && (time() > $returnableDays)) {
							$btn_type = "danger";
							$modal = 'href="' . BASE_URL . '/rma/create.php?order_id=' . $orders[0]->orderNumber . '&marketplace=shopify" target="_blank"';
						} else if ($action_option == "Create RMA") {
							continue;
						}

						$action .= '<a role="button" data-name="' . $action_option . '" data-type="' . str_replace(' ', '_', strtolower($action_option)) . '" class="btn-action btn btn-' . $btn_type . $modal_class . '" ' . $modal . '>' . $action_option . '&nbsp;<i></i></a>';
					}
					?>
					<div class="col-md-4">
						<div class="portlet">
							<div class="portlet-body">
								<div class="order-actions">
									<?= $action; ?>
									<a role="button" data-name="General Comment" data-type="general_comment" class="btn-action btn btn-info" href="#view_comments" data-toggle="modal" data-commentfor="general_comment">General Comment&nbsp;<i></i></a>
								</div>
							</div>
						</div>
						<div class="portlet">
							<div class="portlet-title">
								<div class="caption">
									SHIPPING ADDRESS <?= ((strpos(strtolower($orders[0]->tags), 'gokwik') !== FALSE) ? "<span class='badge badge-success'>GoKwik</span>" : ((strpos(strtolower($orders[0]->tags), 'magic') !== FALSE) ? "<span class='badge badge-success'>Magic Checkout</span>" : "")); ?>
								</div>
								<div class="tools">
									<a title='Click to update address' class='view_address pull-right' data-accountid='<?= $order->accountId; ?>' data-orderid='<?= $order->orderId; ?>'><svg xmlns="http://www.w3.org/2000/svg" height="14px" viewBox="0 0 512 512">
											<path d="M362.7 19.32C387.7-5.678 428.3-5.678 453.3 19.32L492.7 58.75C517.7 83.74 517.7 124.3 492.7 149.3L444.3 197.7L314.3 67.72L362.7 19.32zM421.7 220.3L188.5 453.4C178.1 463.8 165.2 471.5 151.1 475.6L30.77 511C22.35 513.5 13.24 511.2 7.03 504.1C.8198 498.8-1.502 489.7 .976 481.2L36.37 360.9C40.53 346.8 48.16 333.9 58.57 323.5L291.7 90.34L421.7 220.3z" />
										</svg></a>
									<?php
									$view_map = "<br /><a title='View Address on Map' href='https://maps.google.com/?q=" . $deliverey_address->latitude . "," . $deliverey_address->longitude . "&t=h&z=17' target='_blank'>View Map</a>";
									$google_map = "<a title='View Address on Map' href='https://maps.google.com/?q=" . urlencode(str_replace(array(',', '  '), array('', ' '), $deliverey_address->address1 . ' ' . $deliverey_address->address2 . ' ' . $deliverey_address->city . ' ' . $deliverey_address->province . ' ' . $deliverey_address->zip)) . "&t=h&z=17' target='_blank'><svg xmlns='http://www.w3.org/2000/svg' height='14px' viewBox='0 0 576 512'><path d='M408 120C408 174.6 334.9 271.9 302.8 311.1C295.1 321.6 280.9 321.6 273.2 311.1C241.1 271.9 168 174.6 168 120C168 53.73 221.7 0 288 0C354.3 0 408 53.73 408 120zM288 152C310.1 152 328 134.1 328 112C328 89.91 310.1 72 288 72C265.9 72 248 89.91 248 112C248 134.1 265.9 152 288 152zM425.6 179.8C426.1 178.6 426.6 177.4 427.1 176.1L543.1 129.7C558.9 123.4 576 135 576 152V422.8C576 432.6 570 441.4 560.9 445.1L416 503V200.4C419.5 193.5 422.7 186.7 425.6 179.8zM150.4 179.8C153.3 186.7 156.5 193.5 160 200.4V451.8L32.91 502.7C17.15 508.1 0 497.4 0 480.4V209.6C0 199.8 5.975 190.1 15.09 187.3L137.6 138.3C140 152.5 144.9 166.6 150.4 179.8H150.4zM327.8 331.1C341.7 314.6 363.5 286.3 384 255V504.3L192 449.4V255C212.5 286.3 234.3 314.6 248.2 331.1C268.7 357.6 307.3 357.6 327.8 331.1L327.8 331.1z'/></svg></a>";
									if (empty($deliverey_address->latitude) || empty($deliverey_address->longitude))
										$view_map = "";
									echo $google_map;
									?>
								</div>
							</div>
							<div class="portlet-body">
								<address class="customer-address">
									<div class="customer-address-block">
										<b><?= $deliverey_address->name; ?></b><br />
										<?= $deliverey_address->address1; ?><br />
										<?= $deliverey_address->address2; ?><br />
										<?= $deliverey_address->city; ?><br />
										<?= $deliverey_address->province; ?><br />
										<?= $deliverey_address->zip; ?>
										<?= $view_map; ?>
									</div>
								</address>
								<address class="customer-contact">
									<abbr title="Phone">M:</abbr> <button title="Click to Copy Mobile Number" class="copy_text btn btn-xs btn-default" data-clipboard-target="#mobile_number"><i class="fa fa-copy"></i></button> <a id="mobile_number" alt="Click to Copy Mobile Number" class="mobileNumber" href='tel:+91<?= ((int)str_replace(array(" ", "+91"), "", $deliverey_address->phone)); ?>' rel='nofollow'><?= ((int)str_replace(array(" ", "+91"), "", $deliverey_address->phone)) ?></a> <a class="mobileNumberLink" href="<?= $orders[0]->whatsappDashboardUrl ?>91<?= ((int)str_replace(array(" ", "+91"), "", $deliverey_address->phone)) ?>" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" height="14px">
											<path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
										</svg></a><?php echo ($orders[0]->isAutoApproved ? '&nbsp;<span class="badge badge-success">Customer Confirmed</span>' : '') ?><br />
									<abbr title="Email">@:</abbr> <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= strtolower($deliverey_address->email); ?>" target="_blank"><?= strtolower($deliverey_address->email); ?></a>
								</address>
							</div>
						</div>
						<div class="portlet">
							<div class="portlet-title">
								<div class="caption">
									RISK ASSESMENT
								</div>
							</div>
							<div class="portlet-body">
								<?php
								$risk_profile = "No Assessment Available";
								if (strpos(strtolower($orders[0]->tags), 'rto risk') !== FALSE) {
									if (strpos(strtolower($orders[0]->tags), 'low') !== FALSE)
										$risk_profile = '<span class="badge badge-success">RTO Risk - Low</span>';

									if (strpos(strtolower($orders[0]->tags), 'medium') !== FALSE)
										$risk_profile = '<span class="badge badge-warning">RTO Risk - Medium</span>';

									if (strpos(strtolower($orders[0]->tags), 'high') !== FALSE)
										$risk_profile = '<span class="badge badge-danger">RTO Risk - High</span>';
								}
								?>
								<?= $risk_profile; ?>
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
						$db->where('orderId', $orderId);
						$customerId = $db->getValue(TBL_SP_ORDERS, "customerId");

						if ($customerId) {
							$db->where(
								'(customerId = ? OR
													deliveryAddress LIKE ?
													OR billingAddress LIKE ?
													OR deliveryAddress LIKE ?
													OR billingAddress LIKE ?)',
								array(
									$customerId,
									'%' . substr(str_replace(array('+', ' '), '', $deliverey_address->phone), -10) . '%',
									'%' . substr(str_replace(array('+', ' '), '', $deliverey_address->phone), -10) . '%',
									$deliverey_address->email,
									$deliverey_address->email
								)
							);
							// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
							$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
							$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
							$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');

							$db->join(TBL_FULFILLMENT . ' fr', 'fr.orderId=o.orderId', 'LEFT');
							$db->joinWhere(TBL_FULFILLMENT . ' fr', 'fr.fulfillmentType', 'return');
							// $db->groupBy('o.orderItemId');
							$db->orderBy('orderDate', 'DESC');
							$db->orderBy('f.fulfillmentId', 'DESC');
							$db->orderBy('fr.fulfillmentId', 'DESC');
							$count = 10;
							$orders_history = $db->withTotalCount()->objectBuilder()->get(TBL_SP_ORDERS . ' o', array('0', $count), array('o.orderId, o.orderItemId, COALESCE(status, "-") as status, COALESCE(spStatus, "-") as spStatus, COALESCE(f.shipmentStatus, "-") as shipmentStatus, COALESCE(fr.shipmentStatus, "-") as returnShipmentStatus, orderNumberFormated, replacementOrder, account_store_url'));
							if ($count > $db->totalCount)
								$count = $db->totalCount;
							// $orders_history = $db->objectBuilder()->get(TBL_SP_ORDERS.' o', NULL, array('orderId, COALESCE(status, "-") as status, COALESCE(spStatus, "-") as spStatus, COALESCE(shipmentStatus, "-") as shipmentStatus, orderNumberFormated, COALESCE(r_shipmentStatus, "-") as r_shipmentStatus, replacementOrder, account_store_url'));

							$included_orders = array();
							if ($orders_history) :
								echo '<table class="table"><tr><th>Order#</th><th>Order</th><th>Shipment</th><th>Return</th></tr>';
								foreach ($orders_history as $order_history) :
									if (in_array($order_history->orderId, $included_orders)) {
										$count--;
										$db->totalCount--;
										continue;
									}

									$replace = "";
									if ($order_history->replacementOrder)
										$replace = '<span class="badge badge-success">R</span>';

									echo '<tr><td><a href="' . BASE_URL . '/shopify/order.php?orderId=' . $order_history->orderId . '" target="_blank">' . $order_history->orderNumberFormated . '</a>' . $replace . '</td><td>' . ucwords(str_replace('_', ' ', $order_history->status)) . '</td><td>' . ucwords(str_replace('_', ' ', $order_history->shipmentStatus)) . '</td><td>' . ucwords(str_replace('_', ' ', $order_history->returnShipmentStatus)) . '</td></tr>';
									$included_orders[] = $order_history->orderId;
								endforeach;
								echo '</table>';
								echo "<center>Showing {$count} of {$db->totalCount} orders</center>";
							else :
								echo '<center>Hurray!! Its the first order</center>';
							endif;
						} else {
							echo '<center>Unable to find order history</center>';
						}
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
												<input type="hidden" name="orderId" value="<?= $orders[0]->orderId; ?>">
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
			<div class="modal right fade update_address" id="update_address" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-location'></i> UPDATE ADDRESS</h4>
						</div>
						<div class="modal-body">
							<span class="loading-block hide">
								<center><i class="fa fa-sync fa-spin spinner"></i></center>
							</span>
							<form role="form" class="form-horizontal data-block update-address" autocomplete="off">
								<div class="form-body">
									<div class="form-group">
										<label class="col-md-3 control-label">Name: </label>
										<div class="col-md-9">
											<input type="text" class="form-control name" name="name" placeholder="Address 1" value="<?= $deliverey_address->name; ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Address 1: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address1" name="address1" placeholder="Address 1" value="<?= $deliverey_address->address1; ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Address 2: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address2" name="address2" placeholder="Address 2" value="<?= $deliverey_address->address2; ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Pincode: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control zip" name="zip" placeholder="Pincode" value="<?= $deliverey_address->zip; ?>">
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">City: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control city" name="city" placeholder="City" value="<?= $deliverey_address->city; ?>">
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">State: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control province" name="province" placeholder="State" value="<?= $deliverey_address->province; ?>">
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Phone: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control phone" name="phone" placeholder="Phone" value="<?= str_replace('+91', '', $deliverey_address->phone); ?>">
											</div>
										</div>
									</div>
									<div class="form-action">
										<div class="form-group">
											<input type="hidden" name="orderId" value="<?= $orders[0]->orderId; ?>">
											<input type="hidden" name="customerId" value="<?= $orders[0]->customerId; ?>">
											<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>">
											<input type="hidden" name="oldMobileNumber" class="oldMobileNumber" value="<?= ((int)str_replace(array(" ", "+91"), "", $deliverey_address->phone)); ?>">
											<input type="hidden" name="action" value="update_address">
											<button type="submit" class="btn btn-success btn-block btn-address-submit"><i class="fa"></i> Update Address</button>
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
							<form role="form" class="form-horizontal data-block return-replace" autocomplete="off">
								<div class="form-body">
									<div class="uidSelection">
										<div class="form-group">
											<label class="col-md-3 control-label">Product UID<span class="required" aria-required="true">*</span></label>
											<div class="col-md-9 radio-list">
												<div class="row">
													<?php
													$uids = array();
													foreach ($orders as $key => $order) {
														$item_uids = json_decode($order->uid);
														foreach ($item_uids as $item_uid) {
															echo '<input class="item_details_' . $item_uid . '" disabled type="hidden" name="' . $item_uid . '[orderItemId]" value="' . $order->orderItemId . '">';
															echo '<input class="item_details_' . $item_uid . '" disabled type="hidden" name="' . $item_uid . '[sku]" value="' . $order->sku . '">';
															echo '<input class="item_details_' . $item_uid . '" disabled type="hidden" name="' . $item_uid . '[productId]" value="' . $order->productId . '">';
															echo '<input class="item_details_' . $item_uid . '" disabled type="hidden" name="' . $item_uid . '[variantId]" value="' . $order->variantId . '">';
														}
														$uids = is_array($item_uids) ? (array_merge($uids, $item_uids)) : $uids;
													}

													foreach ($uids as $key => $uid) {
														echo '<div class="col-md-4">
																	<div class="checkbox-list">
																		<label class="checkbox-inline rma-tooltip" data-original-title="" title="">
																			<input class="checkbox product-uid" type="checkbox" name="uids[]" value="' . $uid . '" data-msg="Please select atleast one Product UID" data-error-container=".product-uid-error" required>
																			' . $uid . '
																		</label>
																	</div>
																</div>';
													}
													?>
												</div>
												<div class="product-uid-error"></div>
											</div>
										</div>
									</div>
									<?php
									foreach ($uids as $key => $uid) { ?>
										<div class="row hide" id="fieldset_<?= $uid ?>_div">
											<fieldset class="rma_pid_fieldset fieldset_<?= $uid ?>">
												<legend><?= $uid ?></legend>
												<div class="form-group">
													<label class="col-md-3 control-label">Return Type</label>
													<div class="col-md-9">
														<div class="row">
															<div class="col-md-4">
																<div class="radio-list">
																	<label class="radio-inline">
																		<input class="radio radio-btn" disabled type="radio" name="<?= $uid ?>[return_replacement]" value="refund" data-msg="Please select Return Type" data-error-container=".return-type-error-<?= $uid ?>" />Refund
																	</label>
																</div>
															</div>
															<div class="col-md-4 <?php echo ($orders[0]->replacementOrder ? "hide" : ""); ?>">
																<div class="radio-list">
																	<label class="radio-inline">
																		<input class="radio radio-btn" disabled type="radio" name="<?= $uid ?>[return_replacement]" value="replace" data-msg="Please select Return Type" data-error-container=".return-type-error-<?= $uid ?>" />Replace
																	</label>
																</div>
															</div>
														</div>
														<div class="return-type-error-<?= $uid ?>"></div>
													</div>
												</div>
												<div class="form-group">
													<label class="col-md-3 control-label">Reason: </label>
													<div class="col-md-9 replacement_reason_type_div">
														<div class="row">
															<div class="col-md-4">
																<div class="radio-list">
																	<label class="radio-inline">
																		<input class="radio replacement_reason replacement_reason_type_<?= $uid ?>" disabled type="radio" name="<?= $uid ?>[replacement_reason_type]" data-uid="<?= $uid ?>" value="product_issue" data-msg="Please select Reason" data-error-container=".return-reason-error-<?= $uid ?>" />Product Issue
																	</label>
																</div>
															</div>
															<div class="col-md-4">
																<div class="radio-list">
																	<label class="radio-inline">
																		<input class="radio replacement_reason replacement_reason_type_<?= $uid ?>" disabled type="radio" name="<?= $uid ?>[replacement_reason_type]" data-uid="<?= $uid ?>" value="wrong_reason" data-msg="Please select Reason" data-error-container=".return-reason-error-<?= $uid ?>" />Wrong Reasons
																	</label>
																</div>
															</div>
														</div>
														<div class="return-reason-error-<?= $uid ?>"></div>
													</div>
												</div>
												<div class="replacement-reason-div">
													<div class="form-group product_issue_<?= $uid ?> hide">
														<label class="col-md-3 control-label"></label>
														<div class="col-md-9">
															<div class="radio-list">
																<select class="form-control select2me input-medium replacement_reason_dd" disabled data-placeholder="Select Product Issue Reason" data-allow-clear="true" id="select2_<?= $uid ?> " name="<?= $uid ?>[replacement_reason]" data-msg="Please select Product Issue Reason" data-error-container=".replacement-reason-error-<?= $uid ?>">
																	<option value=""></option>
																	<option value="size_too_big_small">Size Too Big/Small</option>
																	<option value="damaged_received">Damange Item Received</option>
																	<option value="bought_by_mistake">Bought by Mistake</option>
																	<option value="better_price_available">Better price available</option>
																	<option value="performance_or_quality_not_adequate">Performance or quality not adequate</option>
																	<option value="incompatible_or_not_useful">Incompatible or not useful</option>
																	<option value="item_arrived_too_late">Item arrived too late</option>
																	<option value="no_longer_needed">No longer needed</option>
																	<option value="didnt_approve_purchase">Didn\t approve purchase</option>
																	<option value="inaccurate_website_description">Inaccurate website description</option>
																</select>
															</div>
															<div class="replacement-reason-error-<?= $uid ?>"></div>
														</div>
													</div>
													<div class="form-group wrong_reason_<?= $uid ?> hide">
														<label class="col-md-3 control-label"></label>
														<div class="col-md-9">
															<div class="radio-list">
																<select class="form-control select2me input-medium replacement_reason_dd" disabled data-placeholder="Select Wrong Reasons Reason" data-allow-clear="true" id="select2_<?= $uid ?>" name="<?= $uid ?>[replacement_reason]" data-msg="Please select Wrong Reason" data-error-container=".replacement-wrong-reason-error-<?= $uid ?>">
																	<option value=""></option>
																	<option value="empty_tempered">Empty/Tempered</option>
																	<option value="wrong">Wrong</option>
																</select>
															</div>
															<div class="replacement-wrong-reason-error-<?= $uid ?>"></div>
														</div>
													</div>
												</div>
												<div class="replacement-comment-div">
													<div class="form-group">
														<label class="col-md-3 control-label">Complain:<span class="required">*</span></label>
														<div class="col-md-9">
															<textarea class="form-control replacement_subreason" disabled name="<?= $uid ?>[replacement_subreason]" rows="4" data-msg="Please enter Complain details" data-error-container=".replacement_subreason-error-<?= $uid ?>"></textarea>
															<div class="replacement_subreason-error-<?= $uid ?>"></div>
														</div>
													</div>
												</div>
											</fieldset>
										</div>
									<?php } ?>
									<div class="form-group qc_enabled">
										<label class="col-md-3 control-label">Qc Enable Return</label>
										<div class="col-md-9">
											<div class="checkbox-list">
												<label class="checkbox-inline">
													<input type="checkbox" class="has-checkbox qc_enable" name="qc_enable" checked /> Yes
												</label>
											</div>
										</div>
									</div>
									<div class="form-group pickup_type">
										<label class="col-md-3 control-label">Pickup<span class="required">*</span></label>
										<div class="col-md-9">
											<div class="radio-list">
												<label class="radio-inline">
													<input type="radio" name="pickup_type" value="free_pickup" checked> Free Pickup </label>
												<label class="radio-inline">
													<input type="radio" name="pickup_type" value="self_ship"> Self Ship </label>
											</div>
										</div>
									</div>
									<div class="form-group customer_address">
										<label class="col-md-3 control-label label-address">Return Address</label>
										<div class="col-md-9">
											<div class="checkbox-list">
												<label class="checkbox-inline">
													<input type="checkbox" class="has-checkbox" id="return_address" name="default_address" checked>Same as below Address? </label>
											</div>
										</div>
									</div>
									<div class="form-group customer_address">
										<label class="col-md-3 control-label">Address<span class="required">*</span></label>
										<div class="col-md-9">

											<p class="form-control-static current_address">
												<label>Name</label> <?= $deliverey_address->name; ?><br />
												<label>Address Line 1:</label> <?= $deliverey_address->address1; ?><br />
												<label>Address Line 2:</label> <?= $deliverey_address->address2; ?><br />
												<label>Landmark:</label> <?= isset($deliverey_address->landmark) ? $deliverey_address->landmark : ''; ?><br />
												<label>City:</label> <?= isset($deliverey_address->city) ? $deliverey_address->city : ''; ?><br />
												<label>State:</label> <?= isset($deliverey_address->province) ? $deliverey_address->province : ''; ?><br />
												<label>Pincode:</label> <?= isset($deliverey_address->zip) ? $deliverey_address->zip : ''; ?> <button class='btn btn-info btn-xs btn-pincode-check primary-pincode-check' data-accountid="<?= $orders[0]->accountId; ?>" type='button'><i class='fa'></i> Check Servicability!</button><br />
											</p>

										</div>
									</div>
									<div class="form-group confirmed_address">
										<label class="col-md-3 control-label"></label>
										<div class="col-md-9">
											<div class="checkbox-list">
												<label class="checkbox-inline">
													<input type="checkbox" class="has-checkbox" name="return_address_confirm" data-msg="Please confirm Return Address" data-error-container=".confirm-return-address" required /> Return Address Confirmed
												</label>
											</div>
											<div class="confirm-return-address"></div>
										</div>
									</div>
									<div class="return_address_details hide">
										<div class="form-group">
											<label class="col-md-3 control-label">Name<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[name]" class="form-control pickup-address-form return_address_name" data-msg="Please enter Name" placeholder="Name" readonly value="<?= $deliverey_address->name; ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">Address #1<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[address1]" class="form-control pickup-address-form return_address_1" data-msg="Please enter Address Line 1" placeholder="Address Line #1" readonly value="<?= $deliverey_address->address1; ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">Address #2<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[address2]" class="form-control pickup-address-form return_address_2" data-msg="Please enter Address Line 2" placeholder="Address Line #2" readonly value="<?= $deliverey_address->address2; ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">Landmark<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[landmark]" class="form-control pickup-address-form return_landmark" data-msg="Please enter Landmark" placeholder="Landmark" readonly value="<?= isset($deliverey_address->city) ? $deliverey_address->city : ''; ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">Pincode<span class="required">*</span></label>
											<div class="col-md-5">
												<div class="input-icon right pincode_servicability_group">
													<i class="fa"></i>
													<input type="text" name="return[zip]" class="form-control pickup-address-form return_pincode" data-msg="Please enter Pincode" data-error-container=".pincode-error" placeholder="Pincode" readonly value="<?= isset($deliverey_address->zip) ? $deliverey_address->zip : ''; ?>">
												</div>
												<div class="pincode-error"></div>
											</div>
											<div class="col-md-4 pincode_servicable">
												<button class='btn btn-info btn-pincode-check alt-pincode-check' data-lporderid="<?= $orders[0]->lpOrderId; ?>" data-accountid="<?= $orders[0]->accountId; ?>" type='button'><i class='fa'></i> Check!</button>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">City<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[city]" class="form-control pickup-address-form return_city" data-msg="Please enter City" placeholder="City" readonly value="<?= isset($deliverey_address->city) ? $deliverey_address->city : ''; ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label">State<span class="required">*</span></label>
											<div class="col-md-9">
												<input type="text" name="return[province]" class="form-control pickup-address-form return_state" data-msg="Please enter State" placeholder="State" readonly value="<?= isset($deliverey_address->province) ? $deliverey_address->province : ''; ?>">
												<input type="hidden" name="return[country]" value="India">
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Contact Number<span class="required">*</span></label>
										<div class="col-md-9">
											<input type="text" name="mobile_number" class="form-control input-medium" data-msg="Please enter Mobile Number" placeholder="Mobile Number" value="<?= str_replace('+91', '', $deliverey_address->phone); ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">WhatsApp Number<span class="required">*</span></label>
										<div class="col-md-4">
											<div class="input-group">
												<input type="text" name="whatsapp_number" class="form-control input-medium has-whatsapp-number" data-msg="Please enter WhatsApp Number" placeholder="WhatsApp Number" readonly value="<?= str_replace('+91', '', $deliverey_address->phone); ?>">
												<span class="input-group-addon">
													<input type="checkbox" id="default_to_wa" name="default_to_wa" checked><label for="default_to_wa">Same as Contact Number?</label>
												</span>
											</div>
										</div>
										<div class="col-md-5"></div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Email Address<span class="required">*</span></label>
										<div class="col-md-9">
											<input type="text" name="email_address" class="form-control" data-msg="Please enter Email Address" placeholder="Email Address" value="<?= $deliverey_address->email; ?>" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Share updates on<span class="required">*</span></label>
										<div class="col-md-9">
											<div class="checkbox-list">
												<label class="checkbox-inline">
													<input type="checkbox" name="share_update[whatsapp]" value="true" checked> WhatsApp </label>
												<label class="checkbox-inline">
													<input type="checkbox" name="share_update[email]" value="true" checked> Email </label>
											</div>
										</div>
									</div>
									<div class="form-action">
										<div class="form-group">
											<label class="col-md-3"></label>
											<div class="col-md-9">
												<input type="hidden" name="action" value="create_refund_replace">
												<input type="hidden" name="pincode_servicable" data-msg="Pincode is not entered/serviceable" required>
												<input type="hidden" name="orderId" value="<?= $orders[0]->orderId; ?>">
												<input type="hidden" name="customerId" value="<?= $orders[0]->customerId; ?>">
												<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>">
												<!-- <input type="hidden" name="productCondition">
													<input type="hidden" name="rmaAction">
													<input type="hidden" name="estimate"> -->
												<input type="hidden" name="invoiceDate">
												<input type="hidden" name="warrantyPeriod">
											</div>
											<button type="submit" class="btn btn-success btn-block btn-submit"><i class="fa"></i> Save & Generate Pickup</button>
										</div>
									</div>
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
												<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>">
												<input type="hidden" name="trackingId" value="<?= $order->trackingId; ?>">
												<button type="submit" class="btn btn-success btn-block btn-order-delivered-submit"><i class="fa"></i>Update Delivery Status</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
			<div class="modal right fade reassign_courier" id="reassign_courier" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-location'></i> REASSIGN COURIER</h4>
						</div>
						<div class="modal-body">
							<span class="loading-block hide">
								<center><i class="fa fa-sync fa-spin spinner"></i></center>
							</span>
							<form role="form" class="form-horizontal data-block reassign-courier" autocomplete="off">
								<div class="form-body">
									<div class="form-group">
										<label class="col-md-3 control-label">Pickup Address: </label>
										<div class="col-md-9">
											<div class="checkbox-list">
												<label class="checkbox-inline">
													<input type="checkbox" class="same_pickup_address" name="same_pickup_address" value="true" checked> Same Pickup Address?</label>
												</label>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Name: </label>
										<div class="col-md-9">
											<input type="text" class="form-control name" name="name" placeholder="Address 1" value="<?= $deliverey_address->name; ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Address 1: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address1" name="address1" placeholder="Address 1" value="<?= $deliverey_address->address1; ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Address 2: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address2" name="address2" placeholder="Address 2" value="<?= $deliverey_address->address2; ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Pincode: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control zip" name="zip" placeholder="Pincode" value="<?= $deliverey_address->zip; ?>" readonly>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">City: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control city" name="city" placeholder="City" value="<?= $deliverey_address->city; ?>" readonly>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">State: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control province" name="province" placeholder="State" value="<?= $deliverey_address->province; ?>" readonly>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Phone: </label>
										<div class="col-md-9">
											<div class="input-icon right">
												<i></i>
												<input type="text" class="form-control phone" name="phone" placeholder="Phone" value="<?= str_replace('+91', '', $deliverey_address->phone); ?>" readonly>
											</div>
										</div>
									</div>
									<div class="form-action">
										<div class="form-group">
											<input type="hidden" name="orderId" value="<?= $orders[0]->orderId; ?>">
											<input type="hidden" name="accountId" value="<?= $orders[0]->accountId; ?>">
											<input type="hidden" name="fulfillmentId" value="<?= $return_details->fulfillmentId; ?>">
											<input type="hidden" name="trackingId" value="<?= $return_details->trackingId; ?>">
											<input type="hidden" name="lporderId" value="<?= $return_details->lpOrderId; ?>">
											<input type="hidden" name="shipmentId" value="<?= $return_details->lpShipmentId; ?>">
											<input type="hidden" name="returnId" value="<?= $return_details->returnId; ?>">
											<input type="hidden" name="action" value="reassign_courier">
											<button type="submit" class="btn btn-success btn-block btn-address-submit"><i class="fa"></i> Reassign Courier</button>
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