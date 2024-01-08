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
		<h3 class="page-title">
			Shopify <small>Overview</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" data-target="#search-order" id="search_order" role="button" class="btn btn-fit-height default search_order" data-toggle="modal">
						<i class="fa fa-search"></i>
					</button>
					<div id="dashboard-report-range" class="pull-right tooltips btn btn-fit-height btn-primary" data-container="body" data-placement="bottom" data-original-title="Change dashboard date range">
						<i class="icon-calendar"></i>&nbsp; <span class="thin uppercase visible-lg-inline-block"></span>&nbsp; <i class="fa fa-angle-down"></i>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row stats-overview-cont order_mx">
			<div class="col-md-12">
				<h4>Order Metrics</h4>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block total_orders">
					<div class="display stat ok huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Orders
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-info" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block cancelled_orders">
					<div class="display stat bad huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Cancellation
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-danger" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block shipped_orders">
					<div class="display stat good huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Shipped
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-success" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block delivered_orders">
					<div class="display stat good huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Delivered
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-success" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block courier_return">
					<div class="display stat bad huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							RTO
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-danger" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block customer_return">
					<div class="display stat bad huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							RVP
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-danger" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="row stats-overview-cont shipment_mx">
			<div class="col-md-12">
				<h4>Shipment Metrics</h4>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block total_orders">
					<div class="display stat ok huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Orders
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-info" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block cancelled_orders">
					<div class="display stat bad huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Cancelled
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-danger" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block new_orders">
					<div class="display stat good huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							New
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-success" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block in_transit_orders">
					<div class="display stat ok huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							In-Transit <div class="pull-right"><button type="button" data-status="overview_shipment_in_transit" class="btn btn-xs btn-default generate_overview_report"><i class="fa fa-download"></i></button></div>
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-info" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block delivered_orders">
					<div class="display stat good huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							Delivered
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-success" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="col-md-2 col-sm-4">
				<div class="stats-overview stat-block courier_return">
					<div class="display stat bad huge">
						<div class="percent"></div>
					</div>
					<div class="details">
						<div class="title">
							RTO
						</div>
						<div class="numbers"></div>
					</div>
					<div class="progress">
						<span class="progress-bar progress-bar-danger" aria-valuenow="" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only"></span>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include_once(ROOT_PATH . '/footer.php'); ?>