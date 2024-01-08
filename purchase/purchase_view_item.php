<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal container fade" id="modal-po-print-preview" tabindex="-1" role="document" aria-labelledby="printPreviewModel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
					<h4 class="modal-title">PO Details</h4>
				</div>
				<div class="modal-body">
					<iframe id="view_pdf" src="" frameborder="0" width="100%" height="550px"></iframe>
				</div>
			</div>
			<!-- /.modal-content -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Purchase <small>View PO</small>
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
						<div class="tools">
							<button class="btn btn-xs btn-primary reload" id="reload-dataTable">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_purchase_order_items">
							<thead>
								<tr>
									<th>
										PO ID
									</th>
									<th>
										Supplier
									</th>
									<th>
										SKU
									</th>
									<th>
										Quantity
									</th>
									<th>
										Received Quantity
									</th>
									<th>
										Rate
									</th>
									<th>
										Total
									</th>
									<th>
										Status
									</th>
									<th>
										Action
									</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
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