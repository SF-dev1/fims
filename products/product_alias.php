<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_product_alias" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Alias</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products-alias" id="add-products-alias">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<?php
								$db->orderBy('sku', 'ASC');
								$products = $db->get(TBL_PRODUCTS_MASTER);
								$options = array();
								foreach ($products as $product) {
									$options[] = array('key' => 'pid-' . $product['pid'], 'value' => $product['sku']);
								}

								$products = $db->get(TBL_PRODUCTS_COMBO);
								$options_combo = array();
								foreach ($products as $product) {
									$options[] = array('key' => 'cid-' . $product['cid'], 'value' => $product['sku']);
								}

								usort(
									$options,
									function ($a, $b) {
										if ($a['value'] == $b['value']) {
											return 0;
										}
										return ($a['value'] > $b['value']) ? -1 : 1;
									}
								);

								$option = "";
								foreach (array_reverse($options) as $sku) {
									$option .= '<option value="' . $sku['key'] . '">' . $sku['value'] . '</option>';
								}
								?>
								<div class="form-group">
									<label class="col-sm-4 control-label">Parent SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium" id="parent_sku" name="parent_sku">
												<option value=""></option>
												<?php echo $option; ?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="sku" name="sku" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Marketplace ID<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="mp_id" name="mp_id" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Marketplace<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control input-medium marketplace" name="marketplace">
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control input-medium account_id" name="account_id">
												<option value=""></option>
											</select>
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
			<!-- /.modal-dialog -->
		</div>
		<div class="modal fade" id="add_product_alias_bulk" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Alias Product via CSV</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products-alias-bulk" id="add-products-alias-bulk">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="alert alert-success display-hide">
									<button class="close" data-close="alert"></button>
									Your form validation is successful!
								</div>
								<div class="form-group">
									<label class="control-label col-sm-4">Alias File<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">
													Select file </span>
												<span class="fileinput-exists">
													Change </span>
												<input type="file" name="alias_file" id="alias_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
											</span>
											<span class="fileinput-filename">
											</span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<div id="alias_file_error"></div>
										<p class="help-block">
											<span class="label label-warning label-sm">Note:</span>&nbsp;Only .xlsx or .xls file allowed.
											<br />All data should be filled as in <a href="#" target="_blank" id="create_sample_file">Sample File.</a>
										</p>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Marketplace<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control input-medium marketplace" id="marketplace" name="marketplace">
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control input-medium account_id" id="account_id" name="account_id">
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8">
										<button type="submit" class="btn btn-success"><i class=""></i> Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Products <small>Alias</small>
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
							<i class="fa fa-edit"></i>Products Alias
						</div>
						<div class="tools">
							<button data-target="#add_product_alias_bulk" id="add_product_alias_bulk" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add Bulk Alias <i class="fa fa-plus"></i></button>
							<button data-target="#add_product_alias" id="add_product_alias" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New Alias <i class="fa fa-plus"></i></button>
							<button class="btn btn-xs btn-primary reload" id="reload-products-alias">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_products_alias"></table>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>