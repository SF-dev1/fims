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
			Inventory <small>Manage</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet">
					<div class="portlet-title">Manage</div>
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="update-inventory" id="update-inventory">
							<div class="form-body">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="text" required name="uid" class="form-control round-right uid" minlength="12" maxlength="12" placeholder="Scan the product barcode with a scanner" tabindex="1" autocomplete="off" />
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<select class="form-control select2me manage_type" required name="manage_type" tabindex="2" data-placeholder="Select Type">
												<option></option>
												<option value="reprint">Reprint Label</option>
												<option value="reprint_ctn_box">Reprint Ctn/Box Label</option>
												<option value="move_to">Move</option>
												<option value="status">Status</option>
											</select>
										</div>
									</div>
									<div class="col-md-4 type_manage hide">
										<div class="form-group reprint hide">
											<div class="input-group">
												<div class="row">
													<div class="col-md-6">
														<input type="number" required name="units" class="form-control units" placeholder="Qty" tabindex="3" autocomplete="off" disabled />
													</div>
													<div class="col-md-6">
														<input type="number" required name="qty" class="form-control qty" placeholder="Copies" tabindex="4" autocomplete="off" disabled />
													</div>
												</div>
											</div>
										</div>

										<div class="form-group move_to hide">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="text" required name="update_uid" class="form-control round-right update_uid" minlength="12" maxlength="12" placeholder="Scan the product barcode with a scanner" tabindex="3" autocomplete="off" disabled />
											</div>
										</div>
										<div class="form-group status hide">
											<select class="form-control select2me status_select" required tabindex="3" name="status" disabled>
												<option></option>
												<option value="lost">Lost</option>
												<option value="dead">Dead</option>
												<option value="liquidated">Liquidated</option>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<button type="submit" class="btn btn-success" tabindex="5" disabled><i></i> Update</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="portlet">
					<div class="portlet-title">Reprint UID Label</div>
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="reprint-inventory" id="reprint-inventory">
							<div class="form-body">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="text" required name="uid" class="form-control round-right uid" minlength="12" maxlength="12" placeholder="Scan the product barcode with a scanner" tabindex="6" autocomplete="off" />
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<input type="number" class="form-control product_weight" name="product_weight" value="" min="1" max="25" step="1" tabindex="7" placeholder="Product Weight">
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<button type="submit" class="btn btn-success" tabindex="8"><i></i> Print</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="portlet">
					<div class="portlet-title">Swap GRN SKU</div>
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="swap-inventory" id="swap-inventory">
							<div class="form-body">
								<div class="row">
									<div class="col-md-2">
										<div class="form-group">
											<select class="form-control select2me grn_id" required name="grn_id" tabindex="9" data-placeholder="Select GRN">
												<option></option>
												<?php
												$grns = get_grn(array('created', 'receiving'));
												foreach ($grns as $grn) {
													echo '<option value="' . $grn->grn_id . '">GRN_' . $grn->grn_id . '</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<select class="form-control select2me grn_sku" required name="grn_sku" tabindex="10" data-placeholder="Select SKU" disabled></select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group swap_details">
											<select class="form-control select2me new_sku" required name="new_sku" tabindex="11" data-placeholder="Select SKU" disabled></select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group swap_details">
											<input type="number" required name="qty" class="form-control qty" placeholder="Qty" tabindex="12" autocomplete="off" min="0" max="" disabled />
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group swap_details">
											<button type="submit" class="btn btn-success btn-submit" tabindex="13" disabled><i></i> Update</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="portlet">
					<div class="portlet-title">Alter Master SKU</div>
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="alter-inventory" id="alter-inventory">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12 ">
										<div class="form-group">
											<textarea class="form-control product_ids" rows="5" name="product_ids" tabindex="14" spellcheck="false" placeholder="Product IDs [One Product UID per line]" required></textarea>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<select class="form-control select2me alter_from" required name="alter_from" tabindex="15" data-placeholder="From SKU"></select>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<select class="form-control select2me alter_to" required name="alter_to" tabindex="16" data-placeholder="To SKU"></select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<select class="form-control select2me alter_reason" required name="alter_reason" tabindex="17" data-allow-clear="true" data-placeholder="Reason">
												<option></option>
												<option value="Belt Alteration">Belt Alteration</option>
												<option value="Ring Alteration">Ring Alteration</option>
												<option value="Wrong Inward">Wrong Inward</option>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<button type="submit" class="btn btn-success btn-submit" tabindex="18"><i></i> Update</button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>