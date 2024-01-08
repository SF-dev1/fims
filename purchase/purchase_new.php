<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="portlet-config" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Purchase <small>Create PO</small>
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
							<i class="fa fa-reorder"></i>Create Purchase Order
						</div>
					</div>
					<div class="portlet-body form">
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal" id="add-purchase-order">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="col-md-2 control-label">Supplier Name</label>
											<div class="col-md-3">
												<select class="suppliers_list form-control" name="party_id" required tabindex="1"></select>
											</div>
											<?php
											if (isset($_GET['po_id']) && $_GET['type'] == 'draft') {
											?>
												<label class="col-md-2 control-label">Purchase Order ID</label>
												<div class="col-md-3">
													<input class="form-control" readonly type="text" id="po_id_alias" value="<?php echo sprintf('PO_%06d', $_GET['po_id']); ?>">
												</div>
											<?php
											}
											?>
										</div>
									</div>
								</div>
								<hr />
								<div class="row">
									<div class="col-md-12">
										<div class="form-group lineitems lineitem_0">
											<div class="col-md-4">
												<select class="products_list form-control" name="lineitems[0][sku]" id="sku_0" tabindex="2" required></select>
											</div>
											<div class="col-md-1">
												<select class="form-control currency" name="lineitems[0][currency]" id="currency_0" tabindex="3" required>
													<option value="INR">INR</option>
													<option value="CNY">CNY</option>
												</select>
											</div>
											<div class="col-md-2">
												<input class="form-control price" name="lineitems[0][price]" id="price_0" tabindex="4" placeholder="Price" type="text" required>
											</div>
											<div class="col-md-2">
												<input class="form-control qty" name="lineitems[0][qty]" id="qty_0" tabindex="5" placeholder="Quantity" type="text" required>
											</div>
											<div class="col-md-2">
												<input class="form-control amount" placeholder="Amount" readonly="true" type="text">
											</div>
											<div class="col-md-1">
												<div class="spinner-buttons input-group-btn">
													<button type="button" class="btn add_lineItem btn_add_0" tabindex="6">
														<i class="fa fa-plus"></i>
													</button>
													<button type="button" class="btn remove_lineItem btn_minus_0" tabindex="7">
														<i class="fa fa-trash"></i>
													</button>
												</div>
											</div>
										</div>
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
									</div>
								</div>
							</div>
							<div class="form-actions right">
								<input type="hidden" id="po_id" name="po_id" value="<?php echo isset($_GET['po_id']) ? $_GET['po_id'] : '' ?>">
								<button type="reset" class="btn btn-default" <?php echo isset($_GET['po_id']) ? 'disabled' : ''; ?>>Reset</button>
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