<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_product_combo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Combo Product</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products-combo" id="add-products-combo">
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
													<input type="file" id="thumb_image_url" name="thumb_image_url">
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
								<?php
								$products = get_all_parent_sku();
								$options = "";
								foreach ($products as $product) {
									$options .= '<option value="' . $product['key'] . '">' . $product['value'] . '</option>';
								}
								?>
								<div class="form-group">
									<label class="col-sm-4 control-label">Inner SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium inner_sku" id="inner_sku_1" name="inner_sku_1">
												<option value=""></option>
												<?php echo $options; ?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group inner_sku_container">
									<label class="col-sm-4 control-label">Inner SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium inner_sku" id="inner_sku_2" name="inner_sku_2">
												<option value=""></option>
												<?php echo $options; ?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group add_inner_sku">
									<label class="col-sm-4 control-label"></label>
									<div class="col-sm-8">
										<div class="input-group">
											<button type="button" class="btn btn-default" id="add-inner-sku">Add Inner SKU</button>&nbsp;
											<button type="button" class="btn btn-default" id="remove-inner-sku">Remove Last Inner SKU</button>
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
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Products <small> Combo</small>
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
							<i class="fa fa-edit"></i>Products Combo
						</div>
						<div class="tools">
							<button data-target="#add_product_combo" id="add_product_combo" role="button" class="btn btn-xs btn-primary search_order" data-toggle="modal">Add New Combo <i class="fa fa-plus"></i></button>
							<button class="btn btn-xs btn-primary reload" id="reload-products-combo">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_products_combo"></table>
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