<?php

// ------------------------------------------------------------------------------------------------
// uncomment the following line if you are getting 500 error messages
// 
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
// ------------------------------------------------------------------------------------------------

include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');

global $accounts;
$ms_account_options = "";
foreach ($accounts['meesho'] as $account) {
	$ms_account_options .= '<option value="' . $account->accountId . '">' . $account->accountName . '</option>';
}
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modals">

			<div class="modal fade" id="order-import" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Modal title</h4>
						</div>
						<div class="modal-body">
							<form action="#" type="post" class="form-horizontal form-row-seperated" name="orders-import" id="orders-import">
								<div class="form-body">
									<div class="form-group">
										<label class="control-label col-sm-4">Orders Sheet<span class="required"> * </span></label>
										<div class="col-sm-8">
											<div class="fileinput fileinput-new" data-provides="fileinput">
												<span class="btn btn-default btn-file">
													<span class="fileinput-new"> Select file </span>
													<span class="fileinput-exists"> Change </span>
													<input type="file" name="orders_csv" accept=".xlsx, .xls, .csv" id="orders_csv">
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
										<label class="col-sm-4 control-label">Meesho Account<span class="required"> * </span></label>
										<div class="col-sm-8">
											<div class="input-group">
												<select class="form-control accounts_select2me select2 input-medium shopify_account" id="shopify_account" name="shopify_account">
													<option value="">Please select shopify account</option>
													<?= $ms_account_options ?>
												</select>
											</div>
										</div>
									</div>
									<div class="form-actions fluid">
										<div class="col-md-offset-4 col-md-8">
											<button type="submit" class="btn btn-success btn-submit"><i class=""></i>Submit</button>
											<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										</div>
									</div>
								</div>
							</form>
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
			Meesho <small>Orders</small>
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
						<li><a href="#portlet_panding" data-toggle="tab">panding <span class="portlet_panding count"></span></a></li>
						<li class="active"><a href="#portlet_new" data-toggle="tab">New <span class="portlet_new count"></span></a></li>
						<li><a href="#portlet_packing" data-toggle="tab">To Pack <span class="portlet_packing count"></span></a></li>
						<li><a href="#portlet_handovered" data-toggle="tab">Handovered <span class="portlet_handovered count"></span></a></li>
						<li><a href="#portlet_shipped" data-toggle="tab">Shipped <span class="portlet_shipped count"></span></a></li>
						<li><a href="#portlet_cancelled" data-toggle="tab">Cancelled <span class="portlet_cancelled count"></span></a></li>
						<li><a href="#portlet_delivered" data-toggle="tab">Delivered <span class="portlet_delivered count"></span></a></li>
						<li><a href="#portlet_rto" data-toggle="tab">RTO <span class="portlet_rto count"></span></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane" id="portlet_pending">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_pending_order"></table>
						</div>
						<div class="tab-pane active" id="portlet_new">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_new_order"></table>
						</div>
						<div class="tab-pane" id="portlet_packing">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_packing_order"></table>
						</div>
						<div class="tab-pane" id="portlet_handovered">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_handovered_order"></table>
						</div>
						<div class="tab-pane" id="portlet_shipped">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_shipped_order"></table>
						</div>
						<div class="tab-pane" id="portlet_cancelled">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_cancelled_order"></table>
						</div>
						<div class="tab-pane" id="portlet_delivered">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_delivered_order"></table>
						</div>
						<div class="tab-pane" id="portlet_rto">
							<table class="table table-striped table-bordered table-hover" id="selectable_meesho_rto_order"></table>
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