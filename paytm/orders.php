<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
global $accounts;
$pt_account_options = '<option value=""></option>';
foreach ($accounts['paytm'] as $account) {
	$pt_account_options .= '<option value="' . $account->account_id . '">' . $account->account_name . '</option>';
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modals">
			<div class="modal fade" id="search-order" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Modal title</h4>
						</div>
						<div class="modal-body">
							Widget settings form goes here
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-success">Save changes</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>
			<div class="modal fade" id="order-import" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Import Orders</h4>
						</div>
						<div class="modal-body">
							<form action="#" class="form-horizontal">
								<div class="form-body">
									<div class="form-group">
										<label class="control-label col-md-3">Orders CSV<span class="required"> * </span></label>
										<div class="col-md-9">
											<div class="fileinput fileinput-new" data-provides="fileinput">
												<div class="input-group input-large">
													<div class="form-control uneditable-input span3" data-trigger="fileinput">
														<i class="fa fa-file fileinput-exists"></i>&nbsp; <span class="fileinput-filename"></span>
													</div>
													<span class="input-group-addon btn default btn-file">
														<span class="fileinput-new">Select file </span>
														<span class="fileinput-exists">Change </span>
														<input type="file" name="orders_csv" accept=".csv">
													</span>
													<a href="#" class="input-group-addon btn btn-danger fileinput-exists" data-dismiss="fileinput">
														Remove </a>
												</div>
											</div>
											<p class="help-block">
												<span class="label label-warning label-sm">Note:</span>&nbsp;Only .csv file allowed.
											</p>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Account<span class="required"> * </span></label>
										<div class="col-sm-9">
											<div class="input-group">
												<select class="form-control select2me input-medium account_id" id="account_id" name="account_id">
													<?php echo $pt_account_options; ?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-success">Save changes</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
							<form method="post" action="" name="mark-orders" id="mark_orders" enctype="multipart/form-data">
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
															<?php echo $pt_account_options; ?>
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
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Paytm <small>Orders</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" data-target="#search-order" id="search_order" role="button" class="btn btn-fit-height default search_order" data-toggle="modal">
						<i class="fa fa-search"></i>
					</button>
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
					</ul>
					<div class="tab-content">
						<div class="tab-pane" id="portlet_pending">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_pending_orders"></table>
						</div>
						<div class="tab-pane active" id="portlet_new">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="javascript:;" role="button" class="btn btn-success btn-select schedule_pickup" disabled><i></i> Schedule Pickup</a>
									<a href="javascript:;" role="button" class="btn btn-success btn-select update_status" disabled><i></i> Update Status</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_new_orders"></table>
						</div>
						<div class="tab-pane" id="portlet_packing">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="javascript:;" role="button" class="btn btn-success btn-select create_labels" disabled><i></i> Create Labels</a>
									<a href="#order_status_update" role="button" class="btn btn-success btn-modal mark_rtd" data-toggle="modal">Mark RTD</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_packing_orders"></table>
						</div>
						<div class="tab-pane" id="portlet_rtd">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="#order_status_update" role="button" class="btn btn-success btn-modal mark_shipped" data-toggle="modal">Mark Shipped</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_rtd_orders"></table>
						</div>
						<div class="tab-pane" id="portlet_shipped">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_shipped_orders"></table>
						</div>
						<div class="tab-pane" id="portlet_cancelled">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_cancelled_orders"></table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>