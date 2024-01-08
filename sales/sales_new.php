<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade modal_1100 quick_load" id="quick_load" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Quick Add</h4>
					</div>
					<div class="modal-body form">
						<form method="post" action="" name="quick-add" id="quick-add">
							<div class="form-body">
								<div class="form-group">
									<div class="input-group">
										<span class="input-group-addon">
											<i class="fa fa-barcode"></i>
										</span>
										<input type="text" id="uid" name="uid" class="form-control" placeholder="Use a barcode scanner or enter UID manually" tabindex="1" minlength="12" maxlength="12" />
									</div>
								</div>
							</div>
						</form>
						<div class="row">
							<div class="col-md-12">
								<div class="scanner-container hide">
									<div class="list-container">
										<div class="success-container"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-success btn-add-items">Save changes</button>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Sales <small>New Sales</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i>Create Sales Order
						</div>
					</div>
					<div class="portlet-body form">
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal" id="add-sales-order">
							<div class="form-body">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-md-4 control-label">Customer</label>
											<div class="col-md-8">
												<select class="customers_list form-control" name="party_id" required tabindex="1"></select>
											</div>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group customers_name hide">
											<div class="col-md-12">
												<input class="customer_name form-control" placeholder="Customer Name" name="so_notes[customer_name]" tabindex="2">
												<input type="hidden" class="payment_status form-control" name="so_notes[payment_status]" value="pending" tabindex="2">
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<?php
										if (isset($_GET['so_id']) && $_GET['type'] == 'draft') {
										?>
											<div class="form-group hide">
												<div class="col-md-12">
													<input class="form-control" readonly type="text" id="so_id_alias" value="<?php echo sprintf('so_%06d', $_GET['so_id']); ?>">
												</div>
											</div>
										<?php
										}
										?>
									</div>
									<div class="col-md-2">
										<div class="form-group current_due hide">
											<label class="col-md-6 control-label">Due Amount</label>
											<div class="col-md-6">
												<p class="form-control-static due_amount"></p>
											</div>
										</div>
									</div>
									<div class="col-md-1">
										<a href="" data-target="#quick_load" class="btn scan_to_add" data-toggle="modal" disabled><i class="fa fa-plus"></i> Scan</a>
									</div>
								</div>
								<div class="row sales_product hide">
									<hr />
									<div class="col-md-12 sales_product_items">
										<div class="form-group">
											<div class="col-md-5">
											</div>
											<div class="col-md-2">
												<label class="col-md-2 control-label">Total</label>
											</div>
											<div class="col-md-2">
												<input class="form-control total_qty" placeholder="Total Qty" readonly="true" type="text">
											</div>
											<div class="col-md-2">
												<input class="form-control total_amount" placeholder="Total Amount" readonly="true" type="text">
											</div>
											<div class="col-md-1">
											</div>
										</div>
										<div class="form-group">
											<div class="col-md-12">
												<textarea class="form-control so_notes" id="so_notes" rows="3" name="so_notes[notes]" placeholder="Additional Notes or Remarks"><?php echo isset($_GET['so_notes']) ? $_GET['so_notes'] : '' ?></textarea>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-actions right sales_action hide">
								<input type="hidden" id="so_id" name="so_id" value="<?php echo isset($_GET['so_id']) ? $_GET['so_id'] : '' ?>">
								<input type="hidden" id="so_edit" name="so_edit" value="<?php echo isset($_GET['so_edit']) ? $_GET['so_edit'] : '' ?>">
								<input type="hidden" id="client_id" name="client_id" value="<?php echo $client_id; ?>">
								<button type="reset" class="btn btn-default" <?php echo isset($_GET['so_id']) ? 'disabled' : ''; ?>>Reset</button>
								<button type="sumbit" value="draft" name="type" class="btn btn-info" <?php echo $_GET['type'] == 'create' ? 'disabled' : ''; ?>><i class="fa fa-save"></i> Save as Draft</button>
								<button type="submit" value="create" name="type" class="btn btn-success"><i class="fa fa-check"></i> Create PO</button>
							</div>
						</form>
						<!-- END FORM-->
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>