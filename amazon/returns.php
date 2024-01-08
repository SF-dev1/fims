<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
global $accounts;
$az_account_options = '<option value=""></option>';
foreach ($accounts['amazon'] as $account) {
	$az_account_options .= '<option value="' . $account->account_id . '">' . $account->account_name . '</option>';
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
			<div class="modal fade" id="return-import" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<form action="#" name="import-returns" id="import-returns" class="form-horizontal">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
								<h4 class="modal-title">Import Returns</h4>
							</div>
							<div class="modal-body">
								<div class="form-body">
									<div class="form-group">
										<label class="control-label col-md-3">Returns CSV<span class="required"> * </span></label>
										<div class="col-md-9">
											<div class="fileinput fileinput-new" data-provides="fileinput">
												<div class="input-group input-large">
													<div class="form-control uneditable-input span3" data-trigger="fileinput">
														<i class="fa fa-file fileinput-exists"></i>&nbsp; <span class="fileinput-filename"></span>
													</div>
													<span class="input-group-addon btn default btn-file">
														<span class="fileinput-new">Select file </span>
														<span class="fileinput-exists">Change </span>
														<input type="file" name="returns_csv" class="returns_csv" accept=".csv">
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
													<?php echo $az_account_options; ?>
												</select>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Is Flex?<span class="required"> * </span></label>
										<div class="col-sm-9">
											<div class="radio-list">
												<label class="radio-inline">
													<input type="radio" name="is_flex" value="0" checked> No </label>
												<label class="radio-inline">
													<input type="radio" name="is_flex" value="1"> Yes </label>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="modal-footer form-actions">
								<input type="hidden" name="action" value="import-returns" />
								<button type="submit" class="btn btn-success"><i></i> Submit</button>
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</form>
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
			Amazon <small>Returns</small>
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
							<a href="#" data-target="#return-import" id="return_import" role="menu" data-toggle="modal">Import Returns</a>
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
						<li class="active"><a href="#portlet_start" data-toggle="tab">Approved <span class="portlet_start count"></span></a></li>
						<li><a href="#portlet_in_transit" data-toggle="tab">In Transit <span class="portlet_in_transit count"></span></a></li>
						<li><a href="#portlet_out_for_delivery" data-toggle="tab">Out for Delivery <span class="portlet_out_for_delivery count"></span></a></li>
						<li><a href="#portlet_delivered" data-toggle="tab">Delivered <span class="portlet_delivered count"></span></a></li>
						<li><a href="#portlet_received" data-toggle="tab">Received <span class="portlet_received count"></span></a></li>
						<li><a href="#portlet_claimed" data-toggle="tab">Claimed <span class="portlet_claimed count"></span></a></li>
						<li><a href="#portlet_completed" data-toggle="tab">Completed <span class="portlet_completed count"></span></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="portlet_start">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_start_return"></table>
						</div>
						<div class="tab-pane" id="portlet_in_transit">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_in_transit_return"></table>
						</div>
						<div class="tab-pane" id="portlet_out_for_delivery">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_out_for_delivery_return"></table>
						</div>
						<div class="tab-pane" id="portlet_delivered">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="#order_status_update" role="button" class="btn btn-success btn-modal" data-updatetype="return_received" data-toggle="modal">Mark Received</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_delivered_return"></table>
						</div>
						<div class="tab-pane" id="portlet_received">
							<div class="clearfix margin-bottom-10 grp-process">
								<div class="btn-group">
									<a href="#order_status_update" role="button" class="btn btn-success btn-modal" data-updatetype="return_completed" data-toggle="modal">Mark Completed</a>
								</div>
							</div>
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_received_return"></table>
						</div>
						<div class="tab-pane" id="portlet_claimed">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_claimed_return"></table>
						</div>
						<div class="tab-pane" id="portlet_completed">
							<table class="table table-striped table-bordered table-hover" id="selectable_amazon_completed_return"></table>
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