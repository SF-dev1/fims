<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="set_item_price" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Set Item Price</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="set-item-price" id="set-item-price">
							<div class="form-body line_item_price">
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-success update_line_item_prices">Save changes</button>
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
			Purchase <small>Create GRN</small>
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
							<i class="fa fa-reorder"></i>Purchase GRN
						</div>
					</div>
					<div class="portlet-body form">
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal" id="add-grn">
							<div class="form-wizard">
								<div class="form-body">
									<?php
									$active_lots = get_all_lot_no('shipped');
									$option = "<option value=''></option>";
									foreach ($active_lots as $active_lot) {
										$option .= '<option value="' . $active_lot->lot_id . '" data-is_local="' . $active_lot->lot_local . '">' . $active_lot->lot_number . '</option>';
									}
									?>
									<div class="tab-pane" id="main_grn_tab">
										<div class="row">
											<div class="col-md-12">
												<div class="form-group">
													<label class="col-md-1 control-label">Lot No.</label>
													<div class="col-md-2">
														<select class="lot_number select2me form-control" name="lot_id" tabindex="1">
															<?php echo $option; ?>
														</select>
													</div>
													<div class="col-md-2">
													</div>
													<div class="col-md-7">
													</div>
												</div>
											</div>
										</div>
										<hr />
										<div class="row">
											<div class="col-md-12 grn_products_details hide">
												<div class="grn_products"></div>
												<div class="form-group">
													<div class="col-md-5">
													</div>
													<div class="col-md-2">
														<label class="col-md-2 control-label">Total</label>
													</div>
													<div class="col-md-2">
														<input class="form-control total_qty" name="total_qty" placeholder="Total Qty" readonly="true" type="text" value="">
													</div>
													<div class="col-md-2">
														<input class="form-control total_ctn" name="total_ctn" placeholder="Total Cartoons" readonly="true" type="text" value="">
													</div>
													<div class="col-md-1">
														&nbsp;
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="tab-pane hide" id="confirm_grn_tab">
										<div class="row">
											<div class="col-md-12">
												<h3 class="block">Confirm your account</h3>
												<h4 class="form-section">Lot Details</h4>
												<div class="lot_details">
													<div class="form-group preview_lotDetails">
														<div class="col-md-4">
															<label class="control-label col-md-4">Lot Code:</label>
															<div class="col-md-4">
																<input class="form-control" id="lot_number" name="lot_details[lot_number]" readonly />
															</div>
														</div>
														<div class="col-md-4">
															<label class="control-label col-md-4">Carrier:</label>
															<div class="col-md-4">
																<p class="form-control-static" id="lot_carrier">
																</p>
															</div>
														</div>
														<div class="col-md-4">
															<label class="control-label col-md-4">Carrier Code:</label>
															<div class="col-md-4">
																<p class="form-control-static" id="lot_carrier_code">
																</p>
															</div>
														</div>
													</div>
													<div class="form-group preview_lotDetails">
														<div class="col-md-4">
															<label class="control-label col-md-4">Carriage Type:</label>
															<div class="col-md-4">
																<input class="form-control capitalize" id="lot_carriage_type" name="lot_details[lot_carriage_type]" readonly />
															</div>
														</div>
														<div class="col-md-4">
															<label class="control-label col-md-4">Carriage Value:</label>
															<div class="col-md-4">
																<input class="form-control" id="lot_carriage_value" name="lot_details[lot_carriage_value]" readonly />
															</div>
														</div>
														<div class="col-md-4">
															<label class="control-label col-md-4">Exchange Value:</label>
															<div class="col-md-4">
																<input class="form-control" id="lot_exchange" name="lot_details[lot_exchange]" readonly />
																<input type="hidden" id="lot_local" name="lot_details[is_local]" value="" />
															</div>
														</div>
													</div>
												</div>
												<h4 class="form-section">Item Details</h4>
												<div class="preview_items">
													<table class="table table-bordered table-striped">
														<thead>
															<tr>
																<th>SKU</th>
																<th>PO</th>
																<th>Qty</th>
																<th>Boxes</th>
																<th>Price</th>
															</tr>
														</thead>
														<tbody class="preview_item_details">
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="form-actions fluid form-buttons right hide">
									<input type="hidden" id="grn_id" name="grn_id" value="<?php echo isset($_GET['grn_id']) ? $_GET['grn_id'] : '' ?>">
									<input type="hidden" id="lot_id" name="lot_id" value="">
									<button type="sumbit" value="draft" name="type" class="btn btn-info btn-draft" <?php echo $_GET['type'] == 'create' ? 'disabled' : ''; ?>><i class="fa fa-save"></i> Save as Draft</button>
									<button type="button" class="btn btn-warning btn-next">Confirm to Create</button>
									<button type="button" class="btn btn-default btn-back hide">Back</button>
									<button type="submit" value="create" name="type" class="btn btn-success btn-submit hide" disabled><i class="fa fa-save"></i> Create GRN</button>
								</div>
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