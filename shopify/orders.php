<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
global $accounts;
$sp_account_options = '<option value=""></option>';
foreach ($accounts['shopify'] as $account) {
	$sp_account_options .= '<option value="' . $account->account_id . '">' . $account->account_name . '</option>';
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modals">
			<div class="modal fade search_orders" id="search-order" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog modal-wide">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Search Order</h4>
						</div>
						<div class="modal-body">
							<form method="post" action="" name="search_orders" id="search_orders">
								<div class="form-body">
									<div class="form-group">
										<div class="input-group-fluid margin-top-10">
											<input type="search" id="search_order_key" name="search_order_key" class="form-control round-left" placeholder="Use a bar scanner or enter search term manually" tabindex="1" required />
											<select class="form-control select2me input-large round-right" id="search_order_by" name="search_order_by" data-placeholder="Search By" tabindex="2" required>
												<option value=""></option>
												<option value="uid">Product ID</option>
												<option value="orderNumber">Shopify Order ID</option>
												<option value="orderId">Order ID</option>
												<option value="orderItemId">Item ID</option>
												<option value="trackingId">Tracking ID</option>
												<option value="mobileNumber">Mobile Number</option>
											</select>
											<input type="hidden" name="action" value="search_order" />
										</div>
									</div>
								</div>
								<div class="hide"><input type="submit" /></div>
							</form>
							<div class="modal-body-loading text-center hide"><i class="fa fa-sync fa-spin"></i></div>
							<div class="modal-body-details"></div>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>
			<div class="modal fade modal_770" id="order_status_update" tabindex="-1" role="basic" aria-hidden="true" data-backdrop="static" data-keyboard="false">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Mark Shipments by Scanner</h4>
						</div>
						<div class="modal-body form">
							<form method="post" action="" name="mark-orders" id="mark_orders">
								<div class="form-body">
									<div class="form-group">
										<div class="row">
											<div class="col-md-12">
												<div class="input-group">
													<span class="input-group-addon">
														<i class="fa fa-barcode"></i>
													</span>
													<input type="text" id="trackin_id" name="trackin_id" class="form-control" placeholder="Use a bar scanner or enter courier AWB No. manually" tabindex="1" />
													<div class="input-group-btn">
														<select class="form-control input-medium select2me" id="account_id" name="account_id" data-placeholder="Choose a account" tabindex="2">
															<?php echo $sp_account_options; ?>
														</select>
													</div>
												</div>
											</div>
											<div class="modal-footer hide">
												<input type="hidden" id="update_type" name="type" value="" />
												<input type="submit" name="submit" value="" />
											</div>
										</div>
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
			<div class="modal right fade view_comments" id="view_comments" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"><i class='fa fa-comment'></i> ORDER COMMENTS</h4>
						</div>
						<div class="modal-body">
							<!-- <form role="form" class="add-comment">
									<div class="form-body">
										<div class="form-group">
											<textarea class="form-control" name="comment" placeholder="Add new Comment" rows="3" spellcheck="false" required></textarea>
										</div>
										<div class="form-group">
											<select id="commentsFor" class="form-control select2 comment_for" name="comment_for" data-allowClear="true" data-placeholder="Comment For" required>
											</select>
										</div>
										<div class="form-group ndr_approval hide">
											<select id="ndrApprovedReason" class="form-control select2 ndr_approved_reason" name="ndr_approved_reason" data-allowClear="true" data-placeholder="NDR Approved Reason">
												<option></option>
												<?php
												$ndr_options = json_decode(get_option('ndr_approved_reason'));
												foreach ($ndr_options as $ndr_key => $ndr_value) {
													echo '<option value="' . $ndr_key . '">' . $ndr_value . '</option>';
												}
												?>
											</select>
										</div>
										<div class="row">
											<div class="col-md-6 assured_delivery hide">
												<div class="form-group">
													<div class="checkbox">
														<label><input type="checkbox" name="assured_delivery" value="1"> Assured Delivery?</label>
													</div>
												</div>
											</div>
											<div class="col-md-12 form-action">
												<div class="form-group">
													<input type="hidden" name="orderId" value="">
													<input type="hidden" name="orderItemId" value="">
													<input type="hidden" name="accountId" value="" />
													<input type="hidden" name="action" value="add_comment">
													<button type="submit" class="btn btn-success btn-block"><i class="fa"></i> Submit</button>
												</div>
											</div>
										</div>
									</div>
								</form> -->
							<div class="customer-body"></div>
							<div class="comments-body"></div>
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
							<span class="address-loading-block">
								<center><i class="fa fa-sync fa-spin"></i></center>
							</span>
							<form role="form" class="form-horizontal update-address hide" autocomplete="off">
								<div class="form-body">
									<div class="form-group">
										<label class="col-md-3 control-label">Address 1: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address1" name="address1" placeholder="Address 1" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Address 2: </label>
										<div class="col-md-9">
											<input type="text" class="form-control address2" name="address2" placeholder="Address 2" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Pincode: </label>
										<div class="col-md-9">
											<input type="text" class="form-control zip" name="zip" placeholder="Pincode" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">City: </label>
										<div class="col-md-9">
											<input type="text" class="form-control city" name="city" placeholder="City" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">State: </label>
										<div class="col-md-9">
											<input type="text" class="form-control province" name="province" placeholder="State" required>
										</div>
									</div>
									<div class="form-action">
										<div class="form-group">
											<input type="hidden" name="orderId" value="">
											<input type="hidden" name="orderItemId" value="">
											<input type="hidden" name="customerId" value="">
											<input type="hidden" name="accountId" value="" />
											<input type="hidden" name="action" value="update_address">
											<button type="submit" class="btn btn-success btn-block"><i class="fa"></i> Update Address</button>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div><!-- modal-content -->
				</div><!-- modal-dialog -->
			</div>
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Shopify <small>Orders</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" data-target="#search-order" id="search_order" role="button" class="btn btn-fit-height default search_order" data-toggle="modal">
						<i class="fa fa-search"></i>
					</button>
					<div class="hide">
						<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
							Actions <i class="fa fa-angle-down"></i>
						</button>
						<ul class="dropdown-menu pull-right" role="menu">
							<!-- <li>
									<a href="#">Export</a>
								</li> -->
							<li>
								<a href="#" data-target="#order-import" id="order_import" role="menu" data-toggle="modal">Import Orders</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN TAB PORTLET-->
				<div class="tabbable tabbable-custom boxless">
					<ul class="nav nav-tabs order_type">
						<li><a href="#portlet_pending" data-toggle="tab">Pending <span class="portlet_pending count"></span></a></li>
						<li class="active"><a href="#portlet_new" data-toggle="tab">New <span class="portlet_new count"></span></a></li>
						<li><a href="#portlet_packing" data-toggle="tab">To Pack <span class="portlet_packing count"></span></a></li>
						<li><a href="#portlet_rtd" data-toggle="tab">Handovered <span class="portlet_rtd count"></span></a></li>
						<li><a href="#portlet_shipped" data-toggle="tab">Shipped <span class="portlet_shipped count"></span></a></li>
						<li><a href="#portlet_cancelled" data-toggle="tab">Cancelled <span class="portlet_cancelled count"></span></a></li>
						<li><a href="#portlet_pickup_pending" data-toggle="tab">Pickup Pending <span class="portlet_pickup_pending count"></span></a></li>
						<li><a href="#portlet_in_transit" data-toggle="tab">In Transit <span class="portlet_in_transit count"></span></a></li>
						<li><a href="#portlet_ndr" data-toggle="tab">NDR <span class="portlet_ndr count"></span></a></li>
						<!-- <li><a href="#portlet_follow_up" data-toggle="tab">Follow Up <span class="portlet_follow_up count"></span></a></li> -->
					</ul>
					<div class="tab-content">
						<div class="tab-pane" id="portlet_pending">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_pending_order"></table>
						</div>
						<div class="tab-pane active" id="portlet_new">
							<!-- <div class="clearfix margin-bottom-10 grp-process">
									<div class="btn-group">
										<a href="javascript:;" role="button" class="btn btn-success btn-select schedule_pickup" disabled><i></i> Schedule Pickup</a>
										<a href="javascript:;" role="button" class="btn btn-success btn-select update_status" disabled><i></i> Update Status</a>												
									</div>
								</div> -->
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_new_order"></table>
						</div>
						<div class="tab-pane" id="portlet_packing">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="javascript:;" role="button" class="btn btn-success btn-select get_labels" disabled><i></i> Get Labels</a>
									<a href="javascript:;" role="button" class="btn btn-success btn-select generate_packing_list"><i></i> Generate Packlist</a>
									<a href="#order_status_update" role="button" class="btn btn-success btn-modal" data-updatetype="rtd" data-toggle="modal">Mark RTD</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_packing_order"></table>
						</div>
						<div class="tab-pane" id="portlet_rtd">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_rtd_order"></table>
						</div>
						<div class="tab-pane" id="portlet_shipped">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<!-- <a href="javascript:;" role="button" class="btn btn-success btn-select schedule_pickup" disabled><i></i> Schedule Pickup</a> -->
									<a href="javascript:;" role="button" class="btn btn-success btn-select get_manifest" disabled><i></i> Get Manifest</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_shipped_order"></table>
						</div>
						<div class="tab-pane" id="portlet_cancelled">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_cancelled_order"></table>
						</div>
						<div class="tab-pane" id="portlet_pickup_pending">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_pickup_pending_order"></table>
						</div>
						<div class="tab-pane" id="portlet_in_transit">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_in_transit_order"></table>
						</div>
						<div class="tab-pane" id="portlet_ndr">
							<table class="table table-striped table-bordered table-hover" id="selectable_shopify_ndr_order"></table>
						</div>
						<!-- <div class="tab-pane" id="portlet_follow_up">
								<table class="table table-striped table-bordered table-hover" id="selectable_shopify_follow_up_order"></table>
							</div> -->
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<!-- BEGIN SIDEBAR-->
<!-- <section class="animate-menu animate-menu-right">
		<div class="sidebar-menu-rtl">
			<span class="sidebar-header"><i class='fa fa-comment'></i> ORDER COMMENTS</span>
			<div class="margin-top-10 portlet-body form">
				<form role="form" class="add-comment">
					<div class="form-body">
						<div class="form-group">
							<textarea class="form-control" name="comment" placeholder="Add new Comment" rows="3" spellcheck="false" required></textarea>
						</div>
						<div class="form-group">
							<select id="commentsFor" class="form-control select2me" name="comment_for" data-allowClear="true" data-placeholder="Comment For" required>
							</select>
						</div>
						<div class="form-group ndr_approval">
							<select id="ndrApprovedReason" class="form-control select2me" name="ndr_approved_reason" data-allowClear="true" data-placeholder="NDR Approved Reason" required>
								<option></option>
								<option value="customer_refusal">Customer Refused</option>
								<option value="out_of_delivery_area">Out of Delivery Area</option>
								<option value="fake_attempt">Fake Attempt</option>
							</select>
						</div>
						<div class="row">
							<div class="col-md-6 assured_delivery hide">
								<div class="form-group">
									<div class="checkbox">
										<label><input type="checkbox" name="assured_delivery" value="1"> Assured Delivery?</label>
									</div>
								</div>
							</div>
							<div class="col-md-12 form-action">
								<div class="form-group">
									<input type="hidden" name="orderId" value="">
									<input type="hidden" name="orderItemId" value="">
									<input type="hidden" name="accountId" value="" />
									<input type="hidden" name="action" value="add_comment">
									<button type="submit" class="btn btn-success btn-block"><i class="fa"></i> Submit</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="comments-body"></div>
		</div>
	</section> -->
<!-- END SIDEBAR-->
<?php include_once(ROOT_PATH . '/footer.php'); ?>