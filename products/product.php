<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_product" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Product</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products" id="add-products">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Image<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput" data-required="1" data-error-container="#form_2_services_error">
											<div class="fileinput-new thumbnail" style="width: 100px; height: 100px;">
												<img src="https://via.placeholder.com/100x100/EFEFEF/AAAAAA&amp;text=no+image" alt="" />
											</div>
											<div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 100px; max-height: 100px;">
											</div>
											<div>
												<span class="btn btn-default btn-file">
													<span class="fileinput-new">
														Select image </span>
													<span class="fileinput-exists">
														Change </span>
													<input type="file" id="thumb_image_url" name="thumb_image_url" accept="image/*">
												</span>
												<a href="#" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">
													Remove </a>
											</div>
											<div id="thumb_image_url_error"></div>
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
									<label class="col-sm-4 control-label">Selling Price<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="text" value="55" minlength="0" data-required="1" id="selling_price" name="selling_price" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Retail Price<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="text" value="55" minlength="0" data-required="1" id="retail_price" name="retail_price" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Family Price<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-inline input-medium">
											<input type="text" value="55" minlength="0" data-required="1" id="family_price" name="family_price" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Category<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium" id="category" name="category">
												<option value=""></option>
												<?php
												$categories = get_all_categories();
												foreach ($categories['opt'] as $category) {
													echo '<option value="' . $category['catid'] . '">' . $category['categoryName'] . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Brand<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium" id="brand" name="brand">
												<option value=""></option>
												<?php
												$brands = get_all_brands();
												foreach ($brands['opt'] as $brand) {
													echo '<option value="' . $brand['brandid'] . '">' . $brand['brandName'] . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">In Stock?</label>
									<div class="col-sm-8">
										<input id="in_stock" name="in_stock" type="checkbox" class="in_stock make-switch" data-on-text="&nbsp;Yes&nbsp;" data-off-text="&nbsp;No&nbsp;" checked data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group <?php echo ($client_id == 0 ? '' : 'hide') ?>">
									<label class="col-sm-4 control-label">Sold Offline?</label>
									<div class="col-sm-8">
										<input id="is_stock_offline" name="is_stock_offline" type="checkbox" class="is_stock_offline make-switch" data-on-text="&nbsp;Yes&nbsp;" data-off-text="&nbsp;No&nbsp;" <?php echo ($client_id == 0 ? 'checked' : '') ?> data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group <?php echo ($client_id == 0 ? '' : 'hide') ?>">
									<label class="col-sm-4 control-label">In Stock Offline?</label>
									<div class="col-sm-8">
										<input id="in_stock_rtl" name="in_stock_rtl" type="checkbox" class="in_stock_rtl make-switch" data-on-text="&nbsp;Yes&nbsp;" data-off-text="&nbsp;No&nbsp;" <?php echo ($client_id == 0 ? 'checked' : '') ?> data-on-color="success" data-off-color="danger">
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
		<div class="modal fade" id="view_inacitive" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Inactive Products</h4>
					</div>
					<div class="modal-body">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="inactive-products" id="inactive-products">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-2">SKU</label>
									<div class="col-md-10">
										<select class="inactive_skus form-control" name="inactive_sku" required></select>
									</div>
								</div>
							</div>
							<div class="form-actions">
								<div class="row">
									<div class="col-md-offset-5 col-md-8">
										<button type="submit" class="btn btn-submit btn-success"><i></i> Activate</button>
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
			Products <small>All Products</small>
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
							<i class="fa fa-edit"></i>Products
						</div>
						<div class="tools">
							<button data-target="#add_product" id="add_product" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New <i class="fa fa-plus"></i></button>
							<button data-target="#view_inacitive" id="view_inacitive" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Inactive Products <i class="fa fa-eye-slash"></i></button>
							<button class="btn btn-xs btn-primary reload" id="reload-products">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_products">
							<!-- <thead>
									<tr>
										<th class="return_hide_column">
											ProductID
										</th>
										<th class="return_hide_column">
											CategoryID
										</th>
										<th class="return_hide_column">
											BrandID
										</th>
										<th>
											Image
										</th>
										<th>
											SKU
										</th>
										<th>
											Selling Price
										</th>
										<th>
											Category
										</th>
										<th>
											Brand
										</th>
										<?php if ($client_id == 0) : ?>
										<th>
											Current Stock
										</th>
										<th>
											Saleable Stock
										</th>
										<th>
											Reserved Stock
										</th>
										<th>
											Returning Stock
										</th>
										<th>
											QC Reject Stock
										</th>
										<th>
											Damaged Stock
										</th>
										<th>
											Manage Stock?
										</th>
										<?php endif; ?>
										<th>
											In Stock?
										</th>
										<th>
											Is Active?
										</th>
										<th>
											Edit
										</th>
										<th>
											Delete
										</th>
									</tr>
								</thead>
								<tbody>
								</tbody> -->
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