<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Inventory <small>Reconciliation</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12 table-responsive">
				<!-- BEGIN EXAMPLE TABLE PORTLET-->
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-hockey-puck"></i>Reconciliation
						</div>
						<div class="tools">
							<!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
						</div>
					</div>
					<div class="portlet-body">
						<form role="form" class="form-horizontal form-row-seperated" id="reconciliation-sku">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-1">SKU</label>
									<div class="col-md-3 input-group">
										<input type="text" class="form-control sku" tabindex="1" name="sku" value="<?php echo (isset($_GET['sku']) ? $_GET['sku'] : "") ?>">
										<span class="input-group-btn">
											<button class="btn btn-success" type="submit" tabindex="2"><i class="fa fa-search"></i></button>
										</span>
									</div>
								</div>
						</form>
						<div class="form-group reconciliation_details hide">
							<div class="row">
								<div class="col-md-12 reconciliation_overview">
									Inbound QC <span class="inbound_count"></span> <span class="spacer">|</span>
									Pending <span class="qc_pending_count"></span> <span class="spacer">|</span>
									QC Cooling <span class="qc_cooling_count"></span> <span class="spacer">|</span>
									QC Verified <span class="qc_verified_count"></span> <span class="spacer">|</span>
									QC Failed <span class="qc_failed_count"></span> <span class="spacer">|</span>
									Components Requested <span class="components_requested_count"></span> <span class="spacer">|</span>
									Dead Stock <span class="dead_stock_count"></span> <span class="spacer">|</span>
									<a href="./ajax_load.php?action=export_reconciliation_details&sku=##SKU##" class="export_reconciliation_details" target="_blank">Export</a>
								</div><br /><br />
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">Inbound <span class="inbound_count"></span></div>
								<div class="panel-body inbound_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">QC Pending <span class="qc_pending_count"></span></div>
								<div class="panel-body qc_pending_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">QC Cooling <span class="qc_cooling_count"></span></div>
								<div class="panel-body qc_cooling_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">QC Verified <span class="qc_verified_count"></span></div>
								<div class="panel-body qc_verified_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">QC Failed <span class="qc_failed_count"></span></div>
								<div class="panel-body qc_failed_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">Components Requested <span class="components_requested_count"></span></div>
								<div class="panel-body components_requested_uids"> - </div>
							</div>
							<div class="panel panel-default">
								<div class="panel-heading">Dead Stock <span class="dead_stock_count"></span></div>
								<div class="panel-body dead_stock_uids"> - </div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- END EXAMPLE TABLE PORTLET-->
		</div>
	</div>
	<!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>