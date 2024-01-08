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
		<h3 class="page-title">
			Flipkart<small>Orders</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-delay="1000" data-close-others="true">Actions <i class="fa fa-angle-down"></i></button>
					<ul class="dropdown-menu pull-right" role="menu">
						<!-- <li>
									<a href="#" id="order_sync"><i class=""></i> Sync Orders</a>
								</li> -->
						<li>
							<a href="#order_import_fbf" class="order_import_fbf" data-toggle="modal">Import FBF Orders</a>
						</li>
						<li>
							<a href="#order_import" class="order_import" data-toggle="modal">Import Orders</a>
						</li>
						<li>
							<a href="#order_replacements" class="order_replacements" data-toggle="modal">Replacement & Quanity Orders</a>
						</li>
						<li>
							<a href="#order_duplicate" class="order_duplicate" data-toggle="modal">Duplicate Orders</a>
						</li>
						<li>
							<a href="#order_export" class="order_export" data-toggle="modal">Export Orders</a>
						</li>
					</ul>
				</div>
				<div class="model-content-group">
					<div class="modal fade" id="order_import" tabindex="-1" role="dialog" aria-labelledby="ImportSellerOrders" aria-hidden="true" data-backdrop="static" data-keyboard="false">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
									<h4 class="modal-title">Import Orders Manually</h4>
								</div>
								<div class="modal-body form">
									<form action="#" type="post" class="form-horizontal form-row-seperated" name="order-import" id="order-import">
										<div class="form-body">
											<div class="alert alert-danger display-hide">
												<button class="close hide" data-close="alert"></button>
												You have some errors. Please check Order Item ID's entered and retry.
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label">Order Item IDs<span class="required"> * </span></label>
												<div class="col-sm-8">
													<div class="input-group">
														<input type="text" data-required="1" id="order_item_id" name="order_item_id" class="form-control round-right input-medium" />
													</div>
													<span class="help-block">Use comma (,) seperated Order Item ID for multiple orders</span>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
												<div class="col-sm-8">
													<div class="input-group">
														<select class="form-control select2me input-medium account_id" id="account_id" name="account_id">
															<option value=""></option>
														</select>
													</div>
												</div>
											</div>
											<div class="form-group">
												<div class="form-actions fluid">
													<div class="col-md-offset-4 col-md-8">
														<button type="submit" class="btn btn-success btn-submit"><i class=""></i> Submit</button>
														<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
													</div>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
							<!-- /.modal-content -->
						</div>
					</div>
					<div class="modal fade" id="order_import_fbf" tabindex="-1" role="dialog" aria-labelledby="ImportSellerSmartOrders" aria-hidden="true" data-backdrop="static" data-keyboard="false">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
									<h4 class="modal-title">Import FBF Orders</h4>
								</div>
								<div class="modal-body form">
									<form action="#" type="post" class="form-horizontal form-row-seperated" name="order-import-fbf" id="order-import-fbf">
										<div class="form-body">
											<div class="alert alert-danger display-hide">
												<button class="close" data-close="alert"></button>
												You have some form errors. Please check below.
											</div>
											<div class="alert alert-success display-hide">
												<button class="close" data-close="alert"></button>
												Your form validation is successful!
											</div>
											<div class="form-group">
												<label class="control-label col-sm-4">CSV File<span class="required"> * </span></label>
												<div class="col-sm-8">
													<div class="fileinput fileinput-new" data-provides="fileinput">
														<span class="btn btn-default btn-file">
															<span class="fileinput-new">
																Select file </span>
															<span class="fileinput-exists">
																Change </span>
															<input type="file" name="orders_csv" accept=".csv" id="orders_csv">
														</span>
														<span class="fileinput-filename">
														</span>
														&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
														</a>
													</div>
													<div id="orders_csv_error"></div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
												<div class="col-sm-8">
													<div class="input-group">
														<select class="form-control input-medium account_id" id="account_id" name="account_id">
															<option value=""></option>
														</select>
													</div>
												</div>
											</div>
											<div class="form-actions fluid">
												<div class="col-md-offset-4 col-md-8">
													<button type="submit" class="btn btn-success btn-submit"><i class=""></i> Submit</button>
													<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
							<!-- /.modal-content -->
						</div>
					</div>
					<div class="modal fade" id="order_replacements" tabindex="-1" role="dialog" aria-labelledby="ReplacementOrders" aria-hidden="true" data-backdrop="static" data-keyboard="true">
						<div class="modal-dialog modal-full">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4 id="myModalLabel1">Replacement & Multi Quanity Orders Details</h4>
								</div>
								<div class="modal-body">
								</div>
								<div class='modal-footer' id="order_replacements_template_content">
								</div>
							</div>
						</div>
					</div>
					<div class="modal fade" id="order_duplicate" tabindex="-1" role="dialog" aria-labelledby="DuplicateOrders" aria-hidden="true" data-backdrop="static" data-keyboard="true">
						<div class="modal-dialog modal-full">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4 id="myModalLabel1">Duplicate Orders Details</h4>
								</div>
								<div class="modal-body">
								</div>
								<div class='modal-footer' id="order_duplicate_template_content">
								</div>
							</div>
						</div>
					</div>
					<div class="modal fade" id="order_export" tabindex="-1" role="dialog" aria-labelledby="ExportOrders" aria-hidden="true" data-backdrop="static" data-keyboard="false">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4 id="myModalLabel1">Export Orders</h4>
								</div>
								<div class="modal-body form">
									<form action="#" type="post" class="form-horizontal form-row-seperated" name="order-export" id="order-export">
										<div class="form-body">
											<div class="alert alert-danger display-hide">
												<button class="close" data-close="alert"></button>
												You have some form errors. Please check below.
											</div>
											<div class="alert alert-success display-hide">
												<button class="close" data-close="alert"></button>
												Your form validation is successful!
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label">Type<span class="required"> * </span></label>
												<div class="col-sm-8">
													<div class="input-group">
														<select class="form-control input-medium select2me order_type" name="order_type">
															<option value=""></option>
															<option value="new">New</option>
															<option value="packing">To Pack</option>
															<option value="rtd">Handovered</option>
															<option value="shipped">Shipped</option>
															<option value="cancel">Cancelled</option>
														</select>
													</div>
												</div>
											</div>
											<div class="form-actions fluid">
												<div class="col-md-offset-4 col-md-8">
													<button type="submit" class="btn btn-success btn-submit"><i class=""></i> Submit</button>
													<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
					<div class="modal fade" id="search_order" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h4 id="myModalLabel1">Search Order</h4>
								</div>
								<div class="modal-body">
									<div class="alert alert-danger alert-dismissable fade in">
										<button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
										<strong>Error!</strong> <span class="alert-text"></span>
									</div>
									<form method="post" action="" name="search-orders" id="search-orders" enctype="multipart/form-data">
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="search" id="search_order_key" name="search_order_key" class="form-control round-left" placeholder="Use a bar scanner or enter courier search term manually" tabindex="1" />
												<select class="form-control input-medium select2me " id="search_order_by" name="search_order_by" data-placeholder="Search By" tabindex="2">
													<option value=""></option>
													<option value="uid">Product ID</option>
													<option value="shipmentId">Shipment ID</option>
													<option value="orderId">Order ID</option>
													<option value="orderItemId">Item ID</option>
													<option value="trackingId">Tracking ID</option>
													<option value="returnId">Return ID</option>
													<option value="claimID">Claim ID</option>
												</select>
												<input type="submit" name="submit" value="Search" style="display:none;" />
											</div>
										</div>
									</form>
									<div class="modal-body-loading text-center hide"><i class="fa fa-sync fa-spin fa-3x fa-fw"></i></div>
									<div id="blinx-wrapper-410" class="returnOrdersCard modal-body-details">
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- BEGIN PAGE CONTENT-->
		<div class="row margin-bottom-30">
			<!-- BEGIN ORDERS PAGE -->
			<div class="row-fluid">
				<div class="span12">
					<div class="clearfix margin-bottom-10">
						<div class="btn-group pull-right">
							<button class="btn btn-inverse" id="update_status" disabled="disabled" data-activetab="portlet_new"><i class=""></i> Update Orders</button>
							<button data-target="#search_order" id="search_order" role="button" class="btn btn-inverse search_order" data-toggle="modal" data-keyboard="true">Search Order</button>
						</div>
					</div>
					<!-- BEGIN TAB PORTLET-->
					<div class="tabbable tabbable-custom boxless all_orders">
						<ul class="nav nav-tabs order_type">
							<li><a href="#portlet_upcoming" data-toggle="tab">Upcoming <span class="portlet_upcoming count"></span></a></li>
							<li class="active"><a href="#portlet_new" data-toggle="tab">New <span class="portlet_new count"></span></a></li>
							<li><a href="#portlet_packing" data-toggle="tab">To Pack <span class="portlet_packing count"></span></a></li>
							<li><a href="#portlet_rtd" data-toggle="tab">Handovered <span class="portlet_rtd count"></span></a></li>
							<li><a href="#portlet_shipped" data-toggle="tab">Shipped <span class="portlet_shipped count"></span></a></li>
							<li><a href="#portlet_cancelled" data-toggle="tab">Cancelled <span class="portlet_cancelled count"></span></a></li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane" id="portlet_upcoming">
								<div class="clearfix margin-bottom-10">
									<div class="btn-group">
										<button class="btn btn-success dropdown-toggle" type="button" data-toggle="dropdown">Generate NON_FBF Picklists <i class="fa fa-angle-down"></i></button>
										<ul class="dropdown-menu" role="menu" id="non_fbf_dates"></ul>
									</div>
									<div class="btn-group">
										<button class="btn btn-success dropdown-toggle" type="button" data-toggle="dropdown">Generate FBF_LITE Picklists <i class="fa fa-angle-down"></i></button>
										<ul class="dropdown-menu" role="menu" id="fbf_lite_dates"></ul>
									</div>
									<div class="btn-group">
										<button class="btn btn-success upcoming_picklist" type="button" data-toggle="dropdown">List Upcoming Quantities</button>
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_upcoming">
									<thead>
										<tr>
											<th style="width:8px;">
												<input type="checkbox" class="group-checkable" data-set="#orders_upcoming .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane active" id="portlet_new">
								<!-- <div class="alert alert-success alert-dismissable">
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
											<strong>Success!</strong>
										</div>
										<div class="alert alert-danger alert-dismissable">
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
											<strong>Error!</strong>
										</div> -->
								<div class="clearfix margin-bottom-10 btn-process">
									<div class="btn-group">
										<a href="javascript:;" id="mark_create_labels" role="button" class="btn btn-success btn-select" disabled="disabled"><i></i> Create Labels</a>
										<a href="javascript:;" id="mark_to_pack" role="button" class="btn btn-success btn-select" disabled="disabled"><i></i> Mark To Pack</a>
										<a href="javascript:;" id="mark_to_cancel" role="button" class="btn btn-success btn-select" disabled="disabled"><i></i> Mark Cancel</a>
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_new">
									<thead>
										<tr>
											<th style="width:8px !important;">
												<input type="checkbox" class="group-checkable" data-set="#orders_new .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_packing">
								<div class="clearfix margin-bottom-10 btn-process">
									<div class="btn-group">
										<a href="javascript:;" id="gen_label" role="button" class="btn btn-success btn-select" disabled="disabled">Get Labels & Invoices</a>
										<!-- <a href="javascript:;" id="gen_label" role="button" class="btn btn-success btn-select">Get Labels</a> -->
										<!-- <a href="javascript:;" id="gen_invoice" role="button" class="btn btn-success btn-select">Get Invoices</a> -->
										<!-- <a href="javascript:;" id="gen_form" role="button" class="btn btn-success btn-select">Get Forms</a> -->
										<a href="javascript:;" id="get_tracking" role="button" class="btn btn-success btn-select" disabled="disabled"><i></i> Get Tracking ID</a>
										<a href="javascript:;" id="get_packlist" role="button" class="btn btn-success">Get Packlist</a>
										<a href="javascript:;" id="get_packlist-fbf" role="button" class="btn btn-success">Get Packlist FBF</a>
										<a href="#mark_rtd" id="mark_rtd_single" role="button" class="btn btn-success mark_rtd" data-toggle="modal">Mark Ready To Dispatch</a>
									</div>
									<div class="modal fade" id="mark_rtd" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
													<h4 class="modal-title">Mark Shipments Ready to Dispatch by Scanner</h4>
												</div>
												<div class="modal-body form">
													<form method="post" action="" name="mark-rtd" id="mark-rtd" enctype="multipart/form-data">
														<div class="form-group">
															<div class="input-group">
																<span class="input-group-addon">
																	<i class="fa fa-barcode"></i>
																</span>
																<input type="text" id="trackin_id" name="trackin_id" class="form-control round-left" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
																<select class="form-control input-medium " id="rtd_account_name" name="rtd_account_name" data-placeholder="Choose a account" tabindex="2">
																	<?php echo $fk_account_options; ?>
																</select>
															</div>
															<div class="modal-footer">
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
											<!-- /.modal-content -->
										</div>
										<!-- /.modal-dialog -->
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_pack">
									<thead>
										<tr>
											<th style="width:8px;">
												<input type="checkbox" class="group-checkable" data-set="#orders_pack .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_rtd">
								<div class="clearfix margin-bottom-10 btn-process">
									<div class="btn-group">
										<a href="javascript:;" id="regen_label" role="button" class="btn btn-success btn-select" disabled="disabled">Regenerate Labels & Invoices</a>
										<!-- <a href="javascript:;" id="regen_label" role="button" class="btn btn-success btn-select">Regenerate Labels</a>
												<a href="javascript:;" id="regen_invoice" role="button" class="btn btn-success btn-select">Regenerate Invoices</a> -->
										<!-- <a href="javascript:;" id="regen_form" role="button" class="btn btn-success">Regenerate Forms</a> -->
										<a href="#mark_shipped" id="mark_shipped_single" role="button" class="btn btn-success mark_shipped" data-toggle="modal">Mark Shipped</a>
									</div>
									<div class="modal fade" id="mark_shipped" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
													<h4 class="modal-title">Mark Shipments Shipped by Scanner</h4>
												</div>
												<div class="modal-body form">
													<form method="post" action="" name="mark-shipped" id="mark-shipped" enctype="multipart/form-data">
														<div class="form-group">
															<div class="input-group">
																<span class="input-group-addon">
																	<i class="fa fa-barcode"></i>
																</span>
																<input type="text" id="ship_trackin_id" name="ship_trackin_id" class="form-control round-right" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
																<select class="form-control input-medium select2 " id="ship_account_name" name="ship_account_name" data-placeholder="Choose a account" tabindex="2">
																	<?php echo $fk_account_options; ?>
																</select>
															</div>
															<div class="modal-footer">
																<input type="submit" name="submit" value="Mark Shipped" style="display:none;" />
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
											<!-- /.modal-content -->
										</div>
										<!-- /.modal-dialog -->
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_handover">
									<thead>
										<tr>
											<th style="width:8px;">
												<input type="checkbox" class="group-checkable" data-set="#orders_handover .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_shipped">
								<div class="clearfix margin-bottom-10">
									<div class="btn-group">
										<a href="#mark_cancelled" id="mark_cancelled_single" role="button" class="btn btn-success mark_cancelled" data-toggle="modal">Mark Cancelled</a>
									</div>
									<div class="logistic_details btn-group btn-group-solid text-right">
									</div>
									<div class="modal fade" id="mark_cancelled" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
													<h4 class="modal-title">Mark Shipments Cancelled by Scanner</h4>
												</div>
												<div class="modal-body form">
													<form method="post" action="" name="mark-cancelled" id="mark-cancelled" enctype="multipart/form-data">
														<div class="form-group">
															<div class="input-group">
																<span class="input-group-addon">
																	<i class="fa fa-barcode"></i>
																</span>
																<input type="text" id="cancel_trackin_id" name="cancel_trackin_id" class="form-control round-right" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
																<select class="form-control input-medium select2 " id="cancel_account_name" name="cancel_account_name" data-placeholder="Choose a account" tabindex="2">
																	<?php echo $fk_account_options; ?>
																</select>
															</div>
															<div class="modal-footer">
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
											<!-- /.modal-content -->
										</div>
										<!-- /.modal-dialog -->
									</div>
								</div>
								<table class="table table-striped table-bordered table-hover" id="orders_rtd">
									<thead>
										<tr>
											<th style="width:8px;">
												<input type="checkbox" class="group-checkable" data-set="#orders_rtd .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_cancelled">
								<div class="clearfix margin-bottom-10"></div>
								<table class="table table-striped table-bordered table-hover" id="orders_cancelled">
									<thead>
										<tr>
											<th style="width:8px;">
												<input type="checkbox" class="group-checkable" data-set="#orders_cancelled .checkboxes" />
											</th>
											<th>Order Details</th>
											<th style="display:none;">Status</th>
											<th style="display:none;">Account</th>
											<th style="display:none;">Order Type</th>
										</tr>
									</thead>
									<tbody class="order_content">
									</tbody>
								</table>
							</div>
						</div>
						<!-- END TAB PORTLET-->
					</div>
				</div>
			</div>
			<!-- END PAGE CONTENT-->
		</div>
		<!-- END PAGE CONTAINER-->
	</div>
	<!-- END PAGE CONTAINER-->
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>