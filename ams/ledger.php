<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			AMS <small>Ledger</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet" id="portlet_sales_ledger">
					<div class="portlet-body">
						<form class="horizontal-form" id="sales_ledger" role="form">
							<div class="row">
								<div class="col-md-3">
									<div class="form-group">
										<select class="form-control customers_list" name="ledger_party" required>
										</select>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<div class="input-group" id="defaultrange">
											<input type="text" class="form-control" name="ledger_date_range" autocomplete="off" required>
											<span class="input-group-btn">
												<button class="btn btn-default date-range-toggle" type="button"><i class="fa fa-calendar"></i></button>
											</span>
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<button class="btn btn-success" type="submit"><i></i> Search</button>
										<input class="btn btn-default" type="reset" value="Reset" />
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<div class="col-md-12">
				<div class="portlet hide" id="portlet_sales_ledger_details">
					<div class="portlet-title">
						<div class="caption">
							Transaction Details
						</div>
						<div class="tools">
							<a href="javascript:;" target="_blank" class="ledger_print"><i class="fa fa-print"></i></a>
							<!-- <a href="javascript:;" class="customer_ledger_download"><i class="fa fa-download"></i></a> -->
						</div>
					</div>
					<div class="portlet-body">
						<div class="table-responsive">
							<table class="table table-striped table-bordered table-advance table-hover table-sales-ledger">
								<thead>
									<tr>
										<th>
											Date
										</th>
										<th class="hidden-xs">
											Particular
										</th>
										<th>
											DR
										</th>
										<th>
											CR
										</th>
										<th>
											Balance
										</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td colspan="5">
											<center><i class="fa fa-sync fa-spin"></i></center>
										</td>
									</tr>
								</tbody>
							</table>
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