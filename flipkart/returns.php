<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
global $accounts;
$fk_account_options = '<option value=""></option>';
foreach ($accounts['flipkart'] as $account) {
	$fk_account_options .= '<option value="' . $account->account_id . '">' . $account->account_name . '</option>';
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<!-- BEGIN PAGE CONTAINER-->
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Flipkart <small>Returns</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-delay="1000" data-close-others="true">Actions <i class="fa fa-angle-down"></i></button>
					<ul class="dropdown-menu pull-right" role="menu">
						<li>
							<a href="#return_delivery_breached" class="return_delivery_breached_list" data-toggle="modal">Return Delivery Breached&nbsp;&nbsp;<span class="badge badge-important return_breach_count"> 5 </span></a>
						</li>
						<li>
							<a href="#return_reconciliation" class="return_reconciliation" data-toggle="modal">Return Reconciliation</a>
						</li>
						<li>
							<a href="#return_pod" class="return_pod" data-toggle="modal">Return POD</a>
						</li>
						<li>
							<a href="#search_claim_return" class="return_pod" data-toggle="modal">Search & Claim</a>
						</li>
					</ul>
				</div>
				<div class="model-content-group">
					<div class="modal fade" id="return_delivery_breached" tabindex="-1" role="dialog" aria-labelledby="DeliveryBreached" aria-hidden="true" data-backdrop="static" data-keyboard="true">
						<div class="modal-dialog modal-full">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4 id="myModalLabel1">Return Delivery Breached</h4>
								</div>
								<div class="modal-body">
								</div>
								<div class='modal-footer' id="return_delivery_breached_template_content">
								</div>
							</div>
						</div>
					</div>
					<div class="modal fade" id="return_reconciliation" tabindex="-1" role="dialog" aria-labelledby="ReturnReconciliation" aria-hidden="true" data-backdrop="static" data-keyboard="true">
						<div class="modal-dialog modal-full">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4>Return Reconciliation</h4>
								</div>
								<div class="modal-body">
									<div class="portlet-body form">
										<form class="form-inline search_returns">
											<div class="row">
												<div class="col-md-12">
													<div class="form-group">
														<select class="form-control select2me input-large" required name="account_id" placeholder="Select Account">
															<?php echo $fk_account_options; ?>
														</select>
														<select class="form-control select2me input-large search_by" name="search_by" required>
															<option></option>
															<option value="r_deliveredDate">Return by Delivered Date</option>
															<option value="r_receivedDate">Return by Received Date</option>
															<option value="r_trackingId">Return by Tracking ID</option>
														</select>
														<input type="text" class="form-control input-xlarge input-text search_by_value" name="search_value" autocomplete="off" placeholder="Comma seperated value" required>
														<input type="text" class="form-control input-xlarge input-date date-picker hide search_by_date" disabled autocomplete="off" name="search_value" data-date-format="dd-mm-yyyy" data-date-end-date="-1d" placeholder="Select date" required>
														<input type="hidden" name="action" value="return_reconciliation">
														<button class="btn btn-success" type="submit"><i></i> Search</button>
													</div>
												</div>
											</div>
										</form>
										<div class="report_data hide"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal fade" id="return_pod" tabindex="-1" role="dialog" aria-labelledby="ReturnPOD" aria-hidden="true" data-backdrop="static" data-keyboard="true">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4>Download Flipkart POD</h4>
								</div>
								<div class="modal-body">
									<div class="portlet-body form">
										<form class="form-inline returns_pod">
											<div class="row">
												<div class="col-md-12">
													<div class="form-group">
														<select class="form-control select2me input-medium" name="account_id" placeholder="Select Account" required>
															<?php echo $fk_account_options; ?>
														</select>
														<select class="form-control select2me input-small logistic_name" name="logistic_name" required>
															<option value=""></option>
															<option value="Ekart Logistics">Ekart Logistics</option>
															<option value="Ecom Express">Ecom Express</option>
															<option value="Delhivery">Delhivery</option>
														</select>
														<input type="text" class="form-control input-small input-date date-picker" autocomplete="off" name="pod_date" data-date-format="yyyy-mm-dd" data-date-end-date="-1d" placeholder="Select date" required>
														<input type="hidden" name="action" value="get_return_pod">
														<button class="btn btn-success" type="submit"><i></i> Search</button>
													</div>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row margin-bottom-30">
			<div class="row-fluid">
				<div class="span12">
					<!-- BEGIN TAB PORTLET-->
					<div class="tabbable tabbable-custom boxless">
						<ul class="nav nav-tabs order_type">
							<li class="active"><a href="#portlet_start" data-toggle="tab">Approved <span class="portlet_start count"></span></a></li>
							<li><a href="#portlet_in_transit" data-toggle="tab">Picked Up <span class="portlet_in_transit count"></span></a></li>
							<li><a href="#portlet_out_for_delivery" data-toggle="tab">Out for Delivery <span class="portlet_out_for_delivery count"></span></a></li>
							<li><a href="#portlet_delivered" data-toggle="tab">Delivered <span class="portlet_delivered count"></span></a></li>
							<li><a href="#portlet_return_received" data-toggle="tab">Received <span class="portlet_received count"></span></a></li>
							<li><a href="#portlet_return_claimed" data-toggle="tab">Claimed <span class="portlet_claimed count"></span></a></li>
							<li><a href="#portlet_return_claimed_undelivered" data-toggle="tab">Claimed Undelivered <span class="portlet_claimed_undelivered count"></span></a></li>
							<li><a href="#portlet_return_completed" data-toggle="tab">Completed <span class="portlet_return_completed count"></span></a></li>
							<li><a href="#portlet_return_unexpected" data-toggle="tab">Unexpected <span class="portlet_return_unexpected count"></span></a></li>
						</ul>
						<div class="tab-content">
							<div class="modal_div">
								<div class="modal fade" id="lost_claim" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
									<div class="modal-dialog modal-wide">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Undelivered Order Claim Details</h4>
											</div>
											<div class="modal-body">
												<div id="blinx-wrapper-410" class="returnOrdersCard">
													<article class="return-order-card clearfix detail-view">
														<header class="col-md-7 return-card-header font-rlight">
															<div>
																<h4>
																	<div class="article-title font-rblue font-rreguler"></div>
																</h4>
															</div>
														</header>
														<div class="col-md-12 sub-header">
															<span class="card-label">SKU:&nbsp;</span>
															<span class="sku"></span>
															<span class="card-label">Order Item ID:&nbsp;</span>
															<span class="order_item_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">FSN:&nbsp;</span>
															<span class="fsn"></span>
															<span class="card-label">Order ID:&nbsp;</span>
															<span class="order_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">Order Date:&nbsp;</span>
															<span class="order_date"></span>
														</div>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-2 product-image"><img src="" onerror="this.src='https://via.placeholder.com/100x100'"></div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Price</dt>
																	<dd class="amount"></dd>
																</dl>
																<dl class="dl-horizontal quantity">
																	<dt class="font-rlight font-dark-rgrey fs-14">QTY:&nbsp;</dt>
																	<dd class="qty"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Type</dt>
																	<dd class="type"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="reason">Order Cancelled</dd>
																	<dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="sub_reason">Order Cancelled</dd>
																	<dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="customer_comment">No comments from buyer</dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Tracking Detail</dt>
																</dl>
																<dl class="dl-horizontal tracking-details">
																	<dd class="font-rlight font-dark-rgrey">
																		ID:&nbsp;
																		<span class="font-rblue shipping-details tracking_id" data-placement="left"></span>
																	</dd>
																</dl>
															</div>
														</section>
														<form method="post" action="" name="lost-claim" id="lost-claim" enctype="multipart/form-data" class="form-claim form-horizontal">
															<div class="form-body">
																<div class="alert alert-danger hide">
																	You have some form errors. Please check below.
																</div>
																<div class="skus">
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Return ID</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="return_id" data-required="1" class="span6 return_id form-control" disabled />
																	</div>
																</div>
																<div class="form-group hide products_condition">
																</div>
																<div class="form-group claim_id">
																	<label class="col-md-3 control-label">Claim Ref No.<span class="required">*</span></label>
																	<div class="col-md-9 controls control-group">
																		<input type="text" name="claim_id" data-required="1" class="span6 form-control" />
																	</div>
																</div>
																<div class="form-group hide sku_pids">
																	<label class="col-md-3 control-label">SKU<span class="required">*</span></label>
																	<div class="col-md-9 controls control-group">
																		<input type="text" class="pids" name="pids" data-required="1" class="span6 form-control" />
																	</div>
																</div>
																<div class="form-actions">
																	<button type="submit" class="btn btn-success">Save</button>
																	<i class='icon icon-spin'></i><span class="re_error"></span>
																</div>
															</div>
														</form>
													</article>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal fade" id="mark_acknowledge_return_single" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
									<div class="modal-dialog modal-wide">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Returned Order Claim Details</h4>
											</div>
											<div class="modal-body">
												<div id="blinx-wrapper-410" class="returnOrdersCard">
													<article class="return-order-card clearfix detail-view">
														<header class="col-md-7 return-card-header font-rlight">
															<div>
																<h4>
																	<div class="article-title font-rblue font-rreguler"></div>
																</h4>
															</div>
														</header>
														<div class="col-md-12 sub-header">
															<span class="card-label">SKU:&nbsp;</span>
															<span class="sku"></span>
															<span class="card-label">Order Item ID:&nbsp;</span>
															<span class="order_item_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">FSN:&nbsp;</span>
															<span class="fsn"></span>
															<span class="card-label">Order ID:&nbsp;</span>
															<span class="order_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">Order Date:&nbsp;</span>
															<span class="order_date"></span>
														</div>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-2 product-image"><img src="" onerror="this.src='https://via.placeholder.com/100x100'"></div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Price</dt>
																	<dd class="amount"></dd>
																</dl>
																<dl class="dl-horizontal quantity">
																	<dt class="font-rlight font-dark-rgrey fs-14">QTY:&nbsp;</dt>
																	<dd class="qty"></dd>
																</dl>
																<dl class="dl-horizontal quantity">
																	<dt class="font-rlight font-dark-rgrey fs-14">Return QTY:&nbsp;</dt>
																	<dd class="return_qty"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Type</dt>
																	<dd class="type"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="reason">Order Cancelled</dd>
																	<dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="sub_reason">Order Cancelled</dd>
																	<dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="customer_comment">No comments from buyer</dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Tracking Detail</dt>
																</dl>
																<dl class="dl-horizontal tracking-details">
																	<dd class="font-rlight font-dark-rgrey">
																		ID:&nbsp;
																		<span class="font-rblue shipping-details tracking_id" data-placement="left"></span>
																	</dd>
																</dl>
															</div>
														</section>
														<form method="post" action="" name="mark-acknowledge-return-single" id="mark-acknowledge-return-single" enctype="multipart/form-data" class="form-claim form-horizontal">
															<div class="form-body">
																<div class="alert alert-danger hide">
																	You have some form errors. Please check below.
																</div>
																<div class="skus">
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Return ID</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="return_id" data-required="1" class="span6 return_id form-control" disabled />
																	</div>
																</div>
																<div class="products_condition">
																</div>
																<div class="products_uid">
																</div>
																<div class="form-group to_claim">
																	<label class="col-md-3 control-label">Claim?<span class="required">*</span></label>
																	<div class="col-md-9 radio-list">
																		<label class="radio-inline">
																			<input type="radio" name="claim" value="yes" />Yes
																		</label>
																		<label class="radio-inline">
																			<input type="radio" name="claim" value="no" />No
																		</label>
																		<label class="radio-inline">
																			<input type="radio" name="claim" value="re-claim" />Re-Claim
																		</label>
																		<div class="form_claim_error"></div>
																	</div>
																</div>
																<div class="form-group hide claim_id">
																	<label class="col-md-3 control-label">Claim Ref No.<span class="required">*</span></label>
																	<div class="col-md-9 controls control-group">
																		<input type="text" name="claim_id" data-required="1" class="span6 form-control" />
																	</div>
																</div>
																<div class="form-group hide sku_pids">
																	<label class="col-md-3 control-label">Claim Ref No.<span class="required">*</span></label>
																	<div class="col-md-9 controls control-group">
																		<input type="text" class="pids" name="pids" data-required="1" class="span6 form-control" />
																	</div>
																</div>
																<div class="form-actions">
																	<button type="submit" class="btn btn-success">Save</button>
																	<i class='icon icon-spin'></i><span class="re_error"></span>
																</div>
															</div>
														</form>
													</article>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal fade" id="mark_return_received" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Mark Return Shipments Received by Scanner</h4>
											</div>
											<div class="modal-body form">
												<form method="post" action="" name="mark-return-received" id="mark-return-received" enctype="multipart/form-data">
													<div class="form-group">
														<div class="input-group">
															<span class="input-group-addon">
																<i class="fa fa-barcode"></i>
															</span>
															<input type="text" id="return_trackin_id" name="return_trackin_id" class="form-control round-left" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
															<input type="submit" name="submit" value="Mark RTD" style="display:none;" />
														</div>
													</div>
												</form>
												<div class="scanner-container">
													<div class="list-container">
														<div class="list-count-container">
															<div class="success-list-count">
																<div class="scan-successful">SCANNED SUCCESSFUL</div>
																<div class="scan-passed">0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span></div>
															</div>
														</div>
														<div class="list-count-container">
															<div class="failed-list-count">
																<div class="scan-failed">UNABLE TO SCAN</div>
																<div class="scan-failed-count">0<span class="cancel-icon"><i class="icon icon-times-circle" aria-hidden="true"></i></span></div>
															</div>
														</div>
													</div>
													<div class="list-container">
														<div class="success-container">
															<ul class="success-list">
															</ul>
														</div>
														<div class="failed-container">
															<ul class="failed-list">
															</ul>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal fade" id="mark_acknowledge_return" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Mark Return Shipments Acknoledged by Scanner</h4>
											</div>
											<div class="modal-body form">
												<form method="post" action="" name="mark-acknowledge-return" id="mark-acknowledge-return" enctype="multipart/form-data">
													<div class="form-group">
														<div class="input-group">
															<span class="input-group-addon">
																<i class="fa fa-barcode"></i>
															</span>
															<input type="text" id="return_tracking_id" name="return_tracking_id" class="form-control round-left" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
															<input type="submit" name="submit" value="Mark RTD" style="display:none;" />
														</div>
													</div>
												</form>
												<div class="scanner-container">
													<div class="list-container">
														<div class="list-count-container">
															<div class="success-list-count">
																<div class="scan-successful">SCANNED SUCCESSFUL</div>
																<div class="scan-passed">0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span></div>
															</div>
														</div>
														<div class="list-count-container">
															<div class="failed-list-count">
																<div class="scan-failed">UNABLE TO SCAN</div>
																<div class="scan-failed-count">0<span class="cancel-icon"><i class="icon icon-times-circle" aria-hidden="true"></i></span></div>
															</div>
														</div>
													</div>
													<div class="list-container">
														<div class="success-container">
															<ul class="success-list">
															</ul>
														</div>
														<div class="failed-container">
															<ul class="failed-list">
															</ul>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal fade" id="search_claim_return" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="true">
									<div class="modal-dialog modal-wide">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Search & Claim Returns</h4>
											</div>
											<div class="modal-body">
												<div class="alert alert-danger alert-dismissable fade in">
													<button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
													<strong>Error!</strong> <span class="alert-text"></span>
												</div>
												<form method="post" action="" name="search-cliam-returns" id="search-cliam-returns" enctype="multipart/form-data">
													<div class="form-group">
														<div class="input-group">
															<span class="input-group-addon">
																<i class="fa fa-barcode"></i>
															</span>
															<input type="text" id="search_return_trackin_id" name="search_return_trackin_id" class="form-control round-left" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
															<input type="submit" name="submit" value="Search" class="hide" />
														</div>
													</div>
												</form>
												<div class="modal-body-loading text-center hide"><i class="fa fa-sync fa-spin fa-3x fa-fw"></i></div>
												<div class="modal-body-nothing-found text-center hide"></div>
												<div class="returnOrdersCard modal-body-claim-details">
													<article class="return-order-card clearfix detail-view form">
														<header class="col-md-7 return-card-header font-rlight">
															<h4>
																<div class="article-title font-rblue font-rreguler"></div>
															</h4>
														</header>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-2 product-image"><img src="" onerror="this.src='https://via.placeholder.com/100x100'"></div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Order ID</dt>
																	<dd class="order_id"><a class="font-rblue" href="" target="_blank"></a></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Order Item ID</dt>
																	<dd class="order_item_id"><a class="font-rblue" href="" target="_blank"></a></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Order Date</dt>
																	<dd class="order_date"><a class="font-rblue" href="" target="_blank"></a></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Order QTY</dt>
																	<dd class="qty"><a class="font-rblue" href="" target="_blank"></a></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">FSN</dt>
																	<dd class="fsn"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">SKU</dt>
																	<dd class="sku"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Price</dt>
																	<dd class="amount"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-grey">Return QTY</dt>
																	<dd class="r_qty"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Return Type</dt>
																	<dd class="type"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="reason"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="sub_reason"></dd>
																</dl>
																<dl>
																	<dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="customer_comment">No comments from buyer</dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-grey">Return Type</dt>
																	<dd class="type"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-grey">Tracking ID</dt>
																	<dd class="tracking_id"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-grey">Return Delivered Date</dt>
																	<dd class="delivered_date"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-grey">Return Received Date</dt>
																	<dd class="received_date"></dd>
																</dl>
															</div>
														</section>
														<form method="post" action="#" name="claim-spf" id="claim-spf" enctype="multipart/form-data" class="form-claim form-horizontal" role="form">
															<div class="form-body">
																<div class="alert alert-error hide">
																	You have some form errors. Please check below.
																</div>
																<div class="form-group">
																	<label class="col-md-2 control-label">Return ID</label>
																	<div class="col-md-10 controls">
																		<input type="text" name="return_id" data-required="1" class="return_id form-control" readonly />
																	</div>
																</div>
																<div class="product_uids"></div>
																<div class="product_claim_details"></div>
																<div class="product_rto_claim_details hide">
																	<div class="form-group">
																		<label class="col-md-2 control-label">Forward Video URL<span class="required">*</span></label>
																		<div class="col-md-10 controls">
																			<input type="url" placeholder="https://www.dropbox.com/" name="forward_url" class="rto_urls forward_url form-control" />
																		</div>
																	</div>
																	<div class="form-group">
																		<label class="col-md-2 control-label">Return Video URL<span class="required">*</span></label>
																		<div class="col-md-10 controls">
																			<input type="url" name="return_url" class="rto_urls return_url form-control" />
																		</div>
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-2 control-label">Claim?<span class="required">*</span></label>
																	<div class="col-md-10 radio-list">
																		<div class="row">
																			<div class="col-md-3">
																				<label class="radio-inline">
																					<input class="radio" data-error-container=".form_claim_error" type="radio" name="claim" value="yes" required />Yes
																				</label>
																			</div>
																			<div class="col-md-3">
																				<label class="radio-inline">
																					<input class="radio" data-error-container=".form_claim_error" type="radio" name="claim" value="no" disabled required />No
																				</label>
																			</div>
																		</div>
																		<div class="form_claim_error"></div>
																	</div>
																</div>
																<div class="form-group claim_images hide">
																	<label class="col-md-2 control-label">Claim Images<span class="required">*</span></label>
																	<div class="col-md-10">
																		<div class="dropzone-multi" id="claim_images_dropzone">
																			<div class="dropzone-panel">
																				<a class="dropzone-select btn btn-primary btn-xs">Attach files</a>
																				<a class="dropzone-upload btn btn-success btn-xs"><i class=""></i> Upload All</a>
																				<a class="dropzone-remove-all btn btn-danger btn-xs">Remove All</a>
																			</div>
																			<div class="dropzone-items row">
																			</div>
																		</div>
																		<span class="form-text text-muted">Max file size is 10MB and max number of files is 10.</span>
																	</div>
																</div>
																<div class="form-actions">
																	<input type="hidden" class="order_id" name="order_id" value="" />
																	<input type="hidden" class="order_item_id" name="order_item_id" value="" />
																	<input type="hidden" class="tracking_id" name="tracking_id" value="" />
																	<input type="hidden" class="account_id" name="account_id" value="" />
																	<input type="hidden" class="spf_files" name="spf_files" value="" />
																	<input type="hidden" class="is_combo" name="is_combo" value="" />
																	<input type="hidden" class="return_type" name="return_type" value="" />
																	<button type="submit" class="btn btn-success"><i class=''></i> Raise Claim</button>
																	<span class="re_error"></span>
																</div>
															</div>
														</form>
													</article>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal fade" id="update_claim" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="true">
									<div class="modal-dialog modal-wide">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
												<h4 id="myModalLabel1">Update Order Claim Details</h4>
											</div>
											<div class="modal-body">
												<div id="blinx-wrapper-410" class="returnOrdersCard play-arena">
													<article class="return-order-card clearfix detail-view">
														<header class="col-md-7 return-card-header font-rlight">
															<div>
																<h4>
																	<div class="article-title font-rblue font-rreguler"></div>
																</h4>
															</div>
														</header>
														<div class="col-md-12 sub-header">
															<span class="card-label">SKU:&nbsp;</span>
															<span class="sku"></span>
															<span class="card-label">Order Item ID:&nbsp;</span>
															<span class="order_item_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">FSN:&nbsp;</span>
															<span class="fsn"></span>
															<span class="card-label">Order ID:&nbsp;</span>
															<span class="order_id"><a class="font-rblue" href="" target="_blank"></a></span>
															<span class="card-label">Order Date:&nbsp;</span>
															<span class="order_date"></span>
														</div>
														<section class="col-md-12 row return-card-details">
															<div class="col-md-2 product-image"><img src="" onerror="this.src='https://via.placeholder.com/100x100'"></div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Item Price</dt>
																	<dd class="item_amount"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Shipping</dt>
																	<dd class="shipping_amount"></dd>
																</dl>
																<dl>
																	<dt class="font-rlight font-rgrey">Order Total</dt>
																	<dd class="amount"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Type</dt>
																	<dd class="type"></dd>
																</dl>
																<dl class="dl-horizontal quantity">
																	<dt class="font-rlight font-dark-rgrey fs-14">QTY:&nbsp;</dt>
																	<dd class="qty"></dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="reason">Order Cancelled</dd>
																	<dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="sub_reason">Order Cancelled</dd>
																	<dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="customer_comment">No comments from buyer</dd>
																</dl>
															</div>
															<div class="col-md-2">
																<dl>
																	<dt class="font-rlight font-rgrey">Tracking Detail</dt>
																</dl>
																<dl class="dl-horizontal tracking-details">
																	<dd class="font-rlight font-dark-rgrey">
																		ID:&nbsp;
																		<span class="font-rblue shipping-details tracking_id" data-placement="left"></span>
																	</dd>
																</dl>
															</div>
														</section>
														<form method="post" action="" name="update-claim" id="update-claim" enctype="multipart/form-data" class="form-claim form-horizontal">
															<div class="form-body">
																<div class="alert alert-error hide">
																	<button class="close" data-dismiss="alert">×</button>
																	You have some form errors. Please check below.
																</div>
																<div class="alert alert-success hide">
																	<button class="close" data-dismiss="alert">×</button>
																	Details validatated successfully!
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Return ID</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="return_id" data-required="1" class="span6 return_id form-control" disabled />
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Claim Ref No.</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="claim_id" data-required="1" class="span6 claim_id form-control" disabled />
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Claim Date</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="claim_date" data-required="1" class="span6 claim_date form-control" disabled />
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Product Condition</label>
																	<div class="col-md-9 controls">
																		<input type="text" name="product_condition" data-required="1" class="span6 product_condition form-control" disabled />
																	</div>
																</div>
																<div class="form-group receive_type">
																	<label class="col-md-3 control-label">Received Status<span class="required receive_type">*</span></label></label>
																	<div class="col-md-9 controls radio-list">
																		<label class="radio-inline">
																			<input type="radio" name="receive_type" value="pod" />POD
																		</label>
																		<label class="radio-inline">
																			<input type="radio" name="receive_type" value="lost" />Lost
																		</label>
																		<div id="form_receive_type_error"></div>
																	</div>
																</div>
																<div class="form-group hide received_on">
																	<label class="col-md-3 control-label">Received On</label>
																	<div class="col-md-9 controls">
																		<div class="input-group date">
																			<input type="text" name="received_on" size="16" readonly class="form-control form_datetime" data-date-start-date="-180d" data-date-end-date="+0d">
																			<span class="input-group-btn">
																				<button class="btn btn-success date-set" type="button"><i class="fa fa-calendar"></i></button>
																			</span>
																		</div>
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Claim Status<span class="required claim_staus">*</span></label>
																	<div class="col-md-9 controls radio-list">
																		<label class="radio-inline">
																			<input type="radio" name="claim_staus" value="resolved" />No Reimbursement
																		</label>
																		<label class="radio-inline">
																			<input type="radio" name="claim_staus" value="reimbursed" />Reimbursed
																		</label>
																		<div id="form_product_condition_error"></div>
																	</div>
																</div>
																<div class="form-group">
																	<label class="col-md-3 control-label">Claim Comments<span class="required">*</span></label>
																	<div class="col-md-9 controls">
																		<textarea class="span6 m-wrap form-control" name="claim_comments" rows="3"></textarea>
																	</div>
																</div>
																<div class="form-group claim_reimburse hide">
																	<label class="col-md-3 control-label">Claim Reimbursement<span class="required">*</span></label>
																	<div class="col-md-9 controls">
																		<div class="input-group">
																			<span class="input-group-addon">
																				<i class="fa fa-rupee-sign"></i>
																			</span>
																			<input class="span6 m-wrap claim_reimbursment form-control" name="claim_reimbursment" id="mask_currency2" type="text" readonly="true" />
																			<span class="input-group-addon sellement_percentage hide"></span>
																			<span class="input-group-addon">
																				<button class='btn btn-xs btn-warning spf_reclaim hide' data-subject='Incorrect SPF settlement' data-issueType='I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT'><i></i> Reclaim SPF</a> &nbsp;
																					<button type="button" class="get_spf btn btn-xs btn-success"><i></i> Get SPF Amount</button>
																			</span>
																		</div>
																	</div>
																</div>
																<div class="form-actions">
																	<button type="submit" class="btn btn-success">Save</button>
																	<i class='icon icon-spin'></i><span class="re_error"></span>
																</div>
															</div>
														</form>
													</article>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="tab-pane active" id="portlet_start">
								<table class="table table-striped table-bordered table-hover" id="returns_approved">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#returns_approved .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_in_transit">
								<table class="table table-striped table-bordered table-hover" id="returns_in_transit">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#returns_in_transit .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_out_for_delivery">
								<table class="table table-striped table-bordered table-hover" id="returns_delivered">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#returns_delivered .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_delivered">
								<div class="clearfix margin-bottom-10">
									<div class="btn-group">
										<button data-target="#mark_return_received" id="mark_return_received_link" role="button" class="btn btn-success mark_return_received" data-toggle="modal">Mark Return Received</button>
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_delivered">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#orders_delivered .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_return_received">
								<div class="clearfix margin-bottom-10">
									<div class="btn-group">
										<button data-target="#mark_acknowledge_return" id="mark_acknowledge_return_link" role="button" class="btn btn-success mark_acknowledge_return" data-toggle="modal">Mark Acknowledge Returns</button>
										<button data-target="#search_claim_return" id="search_claim_return_link" role="button" class="btn btn-success search_claim_return" data-toggle="modal">Search & Claim</button>
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="return_received">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#return_received .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_return_claimed">
								<table class="table table-striped table-bordered table-hover" id="return_claimed">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#return_claimed .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_return_claimed_undelivered">
								<table class="table table-striped table-bordered table-hover" id="return_claimed_undelivered">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#return_claimed_undelivered .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_return_completed">
								<table class="table table-striped table-bordered table-hover" id="return_completed">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#return_completed .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_return_unexpected">
								<table class="table table-striped table-bordered table-hover" id="return_unexpected">
									<thead>
										<tr>
											<th style="width:8px;"><input type="checkbox" class="group-checkable" data-set="#return_unexpected .checkboxes" /></th>
											<th>Order Details</th>
											<th class="return_hide_column">Date</th>
											<th class="return_hide_column">Account</th>
											<th class="return_hide_column">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<!-- END TAB PORTLET-->
				</div>
			</div>
		</div>
	</div>
	<!-- END PAGE CONTAINER-->
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>