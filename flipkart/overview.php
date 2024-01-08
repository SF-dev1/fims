<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper fk-overview">
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Flipkart <small>overview statistics and more</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div id="dashboard-report-range" class="pull-right tooltips btn btn-fit-height btn-primary" data-container="body" data-placement="bottom" data-original-title="Change dashboard date range">
					<i class="icon-calendar"></i>&nbsp; <span class="thin uppercase visible-lg-inline-block"></span>&nbsp; <i class="fa fa-angle-down"></i>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN OVERVIEW STATISTIC BARS-->
		<div class="row stats-overview-cont">
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_non_fbf">
					<div class="display stat huge">
						<span class="line-chart">
							5, 6, 7, 11, 14, 10, 15, 19, 15, 2 </span>
						<div class="percent">
							+66%
						</div>
					</div>
					<div class="details">
						<div class="title">
							NON-FBF
						</div>
						<div class="numbers">
							1360
						</div>
					</div>
					<div class="previous">
						<span class="previous_dates">Nov 10 - Dec 09 : </span>
						<span class="previous_value">1234</span>
					</div>
					<!-- <div class="progress">
							<span style="width: 40%;" class="progress-bar" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100">
							<span class="sr-only">
							66% Complete </span>
							</span>
						</div> -->
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_fbf_lite">
					<div class="display stat good huge">
						<span class="line-chart">
							2,6,8,12, 11, 15, 16, 11, 16, 11, 10, 3, 7, 8, 12, 19 </span>
						<div class="percent">
							+16%
						</div>
					</div>
					<div class="details">
						<div class="title">
							FBF-LITE
						</div>
						<div class="numbers">
							1800
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
								<span style="width: 16%;" class="progress-bar progress-bar-warning" aria-valuenow="16" aria-valuemin="0" aria-valuemax="100">
								<span class="sr-only">
								16% Complete </span>
								</span>
							</div> -->
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_courier_return">
					<div class="display stat bad huge">
						<span class="line-chart">
							2,6,8,11, 14, 11, 12, 13, 15, 12, 9, 5, 11, 12, 15, 9,3 </span>
						<div class="percent">
							+6%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Courier Returns
						</div>
						<div class="numbers">
							509
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
								<span style="width: 16%;" class="progress-bar progress-bar-success" aria-valuenow="16" aria-valuemin="0" aria-valuemax="100">
								<span class="sr-only">
								16% Complete </span>
								</span>
							</div> -->
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_customer_return">
					<div class="display stat good huge">
						<span class="bar-chart">
							1,4,9,12, 10, 11, 12, 15, 12, 11, 9, 12, 15, 19, 14, 13, 15 </span>
						<div class="percent">
							+86%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Customer Returns
						</div>
						<div class="numbers">
							1550
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
								<span style="width: 56%;" class="progress-bar progress-bar-warning" aria-valuenow="56" aria-valuemin="0" aria-valuemax="100">
								<span class="sr-only">
								56% Complete </span>
								</span>
							</div> -->
					</div>
				</div>
			</div>
			<!-- <div class="overview_view_more"> -->
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_average_cp">
					<div class="display stat ok huge">
						<span class="line-chart">
							2,6,8,12, 11, 15, 16, 17, 14, 12, 10, 8, 10, 2, 4, 12, 19 </span>
						<div class="percent">
							+72%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Avg. Cost Price
						</div>
						<div class="numbers">
							9600
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
									<span style="width: 72%;" class="progress-bar progress-bar-danger" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
									<span class="sr-only">
									72% Complete </span>
									</span>
								</div> -->
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_average_sp">
					<div class="display stat ok huge">
						<span class="line-chart">
							2,6,8,12, 11, 15, 16, 17, 14, 12, 10, 8, 10, 2, 4, 12, 19 </span>
						<div class="percent">
							+72%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Avg. Selling Price
						</div>
						<div class="numbers">
							9600
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
									<span style="width: 72%;" class="progress-bar progress-bar-danger" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
									<span class="sr-only">
									72% Complete </span>
									</span>
								</div> -->
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_sales">
					<div class="display stat bad huge">
						<span class="line-chart">
							1,7,9,11, 14, 12, 6, 7, 4, 2, 9, 8, 11, 12, 14, 12, 10 </span>
						<div class="percent">
							+15%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Sales
						</div>
						<div class="numbers">
							2090
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
									<span style="width: 15%;" class="progress-bar progress-bar-success" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">
									<span class="sr-only">
									15% Complete </span>
									</span>
								</div> -->
					</div>
				</div>
			</div>
			<div class="col-sm-6 col-md-3">
				<div class="stats-overview stat-block overview_loading">
					<center><i class="fa fa-sync fa-spin"></i></center>
				</div>
				<div class="stats-overview stat-block overview_details overview_replacements">
					<div class="display stat ok huge">
						<span class="line-chart">
							2,6,8,12, 11, 15, 16, 17, 14, 12, 10, 8, 10, 2, 4, 12, 19 </span>
						<div class="percent">
							+72%
						</div>
					</div>
					<div class="details">
						<div class="title">
							Replacements
						</div>
						<div class="numbers">
							9600
						</div>
						<div class="previous">
							<span class="previous_dates">Nov 10 - Dec 09 : </span>
							<span class="previous_value">1234</span>
						</div>
						<!-- <div class="progress">
									<span style="width: 72%;" class="progress-bar progress-bar-danger" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
									<span class="sr-only">
									72% Complete </span>
									</span>
								</div> -->
					</div>
				</div>
			</div>
			<!-- <div class="col-sm-6 col-md-3">
						<div class="stats-overview stat-block overview_loading"><center><i class="fa fa-sync fa-spin"></i></center></div>
						<div class="stats-overview stat-block overview_details overview_claims">
							<div class="display stat bad huge">
								<span class="line-chart">
								1,7,9,11, 14, 12, 6, 7, 4, 2, 9, 8, 11, 12, 14, 12, 10 </span>
								<div class="percent">
									+15%
								</div>
							</div>
							<div class="details">
								<div class="title">
									Claims
								</div>
								<div class="numbers">
									2090
								</div>
								<div class="previous">
									<span class="previous_dates">Nov 10 - Dec 09 : </span>
									<span class="previous_value">1234</span>
								</div>
								<div class="progress">
									<span style="width: 15%;" class="progress-bar progress-bar-success" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">
									<span class="sr-only">
									15% Complete </span>
									</span>
								</div>
							</div>
						</div>
					</div> -->
			<!-- </div> -->
		</div>
		<!-- END OVERVIEW STATISTIC BARS-->
		<div class="clearfix">
		</div>
		<div class="row">
			<div class="col-md-6 col-sm-12">
				<!-- BEGIN PORTLET-->
				<div class="portlet orders">
					<div class="portlet-title">
						<div class="caption">
							<i class="icon-bar-chart"></i>Orders
						</div>
						<div class="tools">
							<div class="btn-group flipkart_accounts_group orders_statistics">
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Accounts <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right flipkart_accounts" role="menu"></div>
							</div>
							<div class="btn-group flipkart_brands_group orders_statistics">
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Brands <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right product_brands" role="menu"></div>
							</div>
						</div>
					</div>
					<div class="portlet-body">
						<div id="orders_statistics_loading" class="loading">
							<img src="<?php echo BASE_URL; ?>/assets/img/loading.gif" alt="Loading..." />
						</div>
						<div id="orders_statistics_content" class="display-none">
							<div class="btn-toolbar margin-bottom-10" id="overview_order_type">
								<div class="btn-group" data-toggle="buttons">
									<label class="btn btn-info btn-sm active">
										<input type="radio" name="options" class="toggle" value="">All </label>
									<label class="btn btn-info btn-sm">
										<input type="radio" name="options" class="toggle" value="non_fbf">NON FBF </label>
									<label class="btn btn-info btn-sm">
										<input type="radio" name="options" class="toggle" value="fbf_lite">FBF LITE </label>
								</div>
							</div>
							<div id="fk_orders_statistics" class="chart">
							</div>
						</div>
					</div>
				</div>
				<!-- END PORTLET-->
			</div>
			<div class="col-md-6 col-sm-12">
				<!-- BEGIN REGIONAL STATS PORTLET-->
				<div class="portlet regions">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-globe"></i>Regional
						</div>
						<div class="tools">
							<div class="btn-group flipkart_accounts_group region_statistics">
								<!-- <button type="button" class="btn btn-default btn-xs">Accounts</button> -->
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Accounts <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right flipkart_accounts" role="menu"></div>
							</div>
							<div class="btn-group flipkart_brands_group region_statistics">
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Brands <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right product_brands" role="menu"></div>
							</div>
						</div>
					</div>
					<div class="portlet-body">
						<div id="regions_statistics_loading" class="loading">
							<img src="<?php echo BASE_URL; ?>/assets/img/loading.gif" alt="Loading..." />
						</div>
						<div id="regions_statistics_content" class="display-none">
							<div class="btn-toolbar margin-bottom-10">
								<div class="btn-group" data-toggle="buttons">
									<label class="btn btn-info btn-sm active">
										<input type="radio" name="options" class="toggle" value="orders">Orders </label>
									<label class="btn btn-info btn-sm">
										<input type="radio" name="options" class="toggle" value="returns">Returns </label>
									<label class="btn btn-info btn-sm">
										<input type="radio" name="options" class="toggle" value="replacements">Replacements </label>
								</div>
							</div>
							<div id="vmap_orders" class="vmaps display-none">
							</div>
							<div id="vmap_returns" class="vmaps display-none">
							</div>
							<div id="vmap_replacements" class="vmaps display-none">
							</div>
						</div>
					</div>
				</div>
				<!-- END REGIONAL STATS PORTLET-->
			</div>
		</div>
		<div class="clearfix">
		</div>
		<div class="row ">
			<div class="col-md-6 col-sm-6">
				<!-- BEGIN PORTLET-->
				<div class="portlet sales">
					<div class="portlet-title">
						<div class="caption">
							<i class="fab fa-dropbox"></i>Sales
						</div>
						<div class="tools">
							<div class="btn-group flipkart_accounts_group sales_statistics">
								<!-- <button type="button" class="btn btn-default btn-xs">Accounts</button> -->
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Accounts <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right flipkart_accounts" role="menu"></div>
							</div>
							<div class="btn-group flipkart_brands_group sales_statistics">
								<!-- <button type="button" class="btn btn-default btn-xs">Accounts</button> -->
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Brands <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right product_brands" role="menu"></div>
							</div>
						</div>
					</div>
					<div class="portlet-body">
						<div id="sales_statistics_loading" class="loading">
							<img src="<?php echo BASE_URL; ?>/assets/img/loading.gif" alt="Loading..." />
						</div>
						<div id="sales_statistics_content" class="display-none">
							<div class="overview-scroller">
								<ul class="feeds sales-feeds">
								</ul>
							</div>
						</div>
					</div>
				</div>
				<!-- END PORTLET-->
			</div>
			<div class="col-md-6 col-sm-6">
				<!-- BEGIN PORTLET-->
				<div class="portlet returns">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reply-all"></i>Returns
						</div>
						<div class="tools">
							<div class="btn-group flipkart_accounts_group returns_statistics">
								<!-- <button type="button" class="btn btn-default btn-xs">Accounts</button> -->
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Accounts <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right flipkart_accounts" role="menu"></div>
							</div>
							<div class="btn-group flipkart_brands_group returns_statistics">
								<!-- <button type="button" class="btn btn-default btn-xs">Accounts</button> -->
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">Brands <i class="fa fa-angle-down"></i></button>
								<div class="dropdown-menu hold-on-click dropdown-checkboxes pull-right product_brands" role="menu"></div>
							</div>
						</div>
					</div>
					<div class="portlet-body">
						<div id="returns_statistics_loading" class="loading">
							<img src="<?php echo BASE_URL; ?>/assets/img/loading.gif" alt="Loading..." />
						</div>
						<div id="returns_statistics_content" class="display-none">
							<div class="overview-scroller">
								<ul class="feeds returns-feeds">
								</ul>
							</div>
						</div>
					</div>
				</div>
				<!-- END PORTLET-->
			</div>
		</div>
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>