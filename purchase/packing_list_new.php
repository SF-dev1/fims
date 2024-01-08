<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_lot" tabindex="-1" role="dialog" aria-labelledby="addLotModel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
					<h4 class="modal-title">Create New Lot</h4>
				</div>
				<div class="modal-body form">
					<form action="#" type="post" class="form-horizontal form-row-seperated add-lot" name="add-lot" id="add-lot">
						<div class="form-body">
							<?php
							$db->where('TABLE_NAME', TBL_PURCHASE_LOT);
							$new_lot_code = sprintf("%02d", $db->getValue('INFORMATION_SCHEMA.TABLES', 'AUTO_INCREMENT'));
							$lot_code = "SF" . $new_lot_code;

							$carriers = get_all_carriers();
							$option = "<option value=''></option>";
							foreach ($carriers as $carrier) {
								$option .= '<option value="' . $carrier->party_id . '">' . $carrier->party_name . '</option>';
							}
							?>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carrier Name<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<select class="form-control select2me input-medium" required id="lot_carrier" name="lot_carrier">
											<?php echo $option; ?>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carriage Type<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<select class="form-control select2me input-medium" required id="carrier_type" name="carrier_type">
											<option value=""></option>
											<option value="percentage">Percentage</option>
											<option value="flat">Per Piece</option>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carriage Value<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="carrier_value" name="carrier_value" class="form-control round-right input-medium" />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Currency Exchange<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="carrier_ex" name="carrier_ex" class="form-control round-right input-medium" />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Lot Code<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="lot_number" name="lot_number" class="form-control round-right input-medium" value="<?php echo $lot_code; ?>" readonly />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carrier Code<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="carrier_code" name="carrier_code" class="form-control round-right input-medium" value="<?php echo $lot_code; ?>" />
									</div>
								</div>
							</div>
							<div class="form-actions fluid">
								<div class="col-md-offset-3 col-md-9">
									<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<!-- /.modal-content -->
		</div>
		<div class="modal fade" id="add_local_lot" tabindex="-1" role="dialog" aria-labelledby="addLotModel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
					<h4 class="modal-title">Create New Local Lot</h4>
				</div>
				<div class="modal-body form">
					<form action="#" type="post" class="form-horizontal form-row-seperated add-lot" name="add-local-lot" id="add-local-lot">
						<div class="form-body">
							<?php
							$db->where('TABLE_NAME', TBL_PURCHASE_LOT_LOCAL);
							$new_lot_code = sprintf("%04d", $db->getValue('INFORMATION_SCHEMA.TABLES', 'AUTO_INCREMENT'));
							$lot_code = "LP" . $new_lot_code;

							$carriers = get_all_suppliers();
							$option = "<option value=''></option>";
							foreach ($carriers as $carrier) {
								$option .= '<option value="' . $carrier->party_id . '">' . $carrier->party_name . '</option>';
							}
							?>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carrier Name<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<select class="form-control select2me input-medium" required id="local_lot_carrier" name="lot_carrier">
											<?php echo $option; ?>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carriage Type<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<select class="form-control select2me input-medium" required id="local_carrier_type" name="carrier_type">
											<option value="fixed">Fixed</option>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carriage Value<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="local_carrier_value" name="carrier_value" class="form-control round-right input-medium" />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Currency Exchange<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="local_carrier_ex" name="carrier_ex" class="form-control round-right input-medium" />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Lot Code<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="local_lot_number" name="lot_number" class="form-control round-right input-medium" value="<?php echo $lot_code; ?>" />
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">Carrier Code<span class="required"> * </span></label>
								<div class="col-sm-8">
									<div class="input-group">
										<input type="text" required id="local_carrier_code" name="carrier_code" class="form-control round-right input-medium" value="<?php echo $lot_code; ?>" />
									</div>
								</div>
							</div>
							<div class="form-actions fluid">
								<div class="col-md-offset-3 col-md-9">
									<input type="hidden" name="is_local" value="1" />
									<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<!-- /.modal-content -->
		</div>
		<div class="modal fade container modal-scroll" id="add_lineItem_model" tabindex="-1" data-replace="true" aria-labelledby="Add Item Modal" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Purchase <small>Create Packing List</small>
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
							<i class="fa fa-reorder"></i>Purchase Packing List
						</div>
					</div>
					<div class="portlet-body form">
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal" id="pack_list">
							<div class="form-body">
								<?php
								$active_lots = get_all_lot_no('created');
								$option = "<option value=''></option>";
								foreach ($active_lots as $active_lot) {
									$option .= '<option value="' . $active_lot->lot_id . '" data-is_local="' . $active_lot->lot_local . '">' . $active_lot->lot_number . '</option>';
								}
								?>
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
												<a class="btn btn-success quick_add" role="button" data-toggle="modal" tabindex="2" disabled><i class="fa fa-plus"></i> Add Product</a>
											</div>
											<div class="col-md-7">
												<button data-target="#add_lot" role="button" class="btn btn-primary pull-right add_lot" tabindex="3" data-toggle="modal"><i class="fa fa-plus"></i> Create LOT</button>
												<button data-target="#add_local_lot" role="button" class="btn btn-primary pull-right add_local_lot" tabindex="3" data-toggle="modal"><i class="fa fa-plus"></i> Create Local LOT</button>
											</div>
										</div>
									</div>
								</div>
								<hr />
								<div class="row packing_products_details hide">
									<div class="col-md-12">
										<div class="packing_products_heading">
											<div class="form-group lineitems lineitem_header">
												<div class="col-md-3">
													<label>SKU</label>
												</div>
												<div class="col-md-2">
													<label>PO</label>
												</div>
												<div class="col-md-2">
													<label>Ordered Quantity</label>
												</div>
												<div class="col-md-2">
													<label>Pending Quantity</label>
												</div>
												<div class="col-md-2">
													<label>Quantity</label>
												</div>
												<div class="col-md-1">
													<label>Action</label>
												</div>
											</div>
										</div>
										<div class="packing_products"></div>
										<div class="form-group packilist_totals">
											<div class="col-md-8">
											</div>
											<div class="col-md-1">
												<label class="col-md-2 control-label">Total</label>
											</div>
											<div class="col-md-2">
												<input class="form-control total_qty" placeholder="Total Qty" readonly="true" type="text">
											</div>
											<div class="col-md-1">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-actions right">
								<input type="hidden" name="update_lot_id" value="<?php echo isset($_GET['lot_id']) ? $_GET['lot_id'] : '0' ?>">
								<input type="hidden" class="lot_is_local" name="is_local" value="">
								<button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save</button>
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