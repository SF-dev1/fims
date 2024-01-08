<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="model"></div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Inventory <small>Inbound</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN EXAMPLE TABLE PORTLET-->
				<div class="portlet">
					<div class="portlet-body">
						<div role="form" class="form-horizontal form-row-sepe">
							<div class="form-body">
								<div class="form-group">
									<div class="col-md-4 form-inline">
										<div class="checkbox">
											<label class="control-label">Print Label: </label>
											<label class="checkbox-inline">
												<?php $print_ctn_label = (get_option('print_ctn_label') == "true" ? " checked='checked' " : ""); ?>
												<input type="checkbox" <?php echo $print_ctn_label; ?> class="print_label" data-label_for="print_ctn_label"> Cartoon </label>
											<label class="checkbox-inline">
												<?php $print_box_label = (get_option('print_box_label') == "true" ? " checked='checked' " : ""); ?>
												<input type="checkbox" <?php echo $print_box_label; ?> class="print_label" data-label_for="print_box_label"> Box </label>
										</div>
									</div>
								</div>
								<div class="form-group inbound-details">
									<hr />
									<!-- <div class="col-md-2">
											<div class="thumbnail" style="width: 154px; height: 154px;">
												<img src="" onerror="this.onerror=null;this.src='https://via.placeholder.com/150x150/EFEFEF/AAAAAA&amp;text=no+image';" alt="" class="product_image"/>
											</div>
											<span><b>SKU </b><p class="product_sku"></p></span>
										</div> -->
									<div class="col-md-2 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Product Number</label>
												<div class="input-group">
													<input type="text" class="form-control product_number" value="" maxlength="12" minlength="12" tabindex="1">
													<div class="input-group-btn">
														<button type="button" class="btn btn-default add_product">
															<i class="fa fa-barcode"></i>
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
											<div class="form-group">
												<div class="input-group">
													<div class="input-group-btn">
														<button type="button" class="btn btn-info add_box btn-block" tabindex="2">
															<i class="fa fa-plus"></i> Close Box
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
											<div class="form-group">
												<div class="input-group">
													<div class="input-group-btn">
														<button type="button" class="btn btn-success add_ctn btn-block" tabindex="3">
															<i class="fa fa-plus"></i> Close Carton
														</button>
													</div>
												</div>
												<!-- <span class="help-block">This is inline help </span> -->
											</div>
										</div>
									</div>
									<div class="col-md-3 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Items in Current Box</label>&nbsp;<span class="box_count"></span>
												<div class="ms-container">
													<div class="ms-selection">
														<ul class="ms-list current_box_items" tabindex="-1"></ul>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-6 machine_on">
										<div class="col-md-12">
											<div class="form-group">
												<label class="control-label">Boxes in Current Carton</label>&nbsp;<span class="ctn_count"></span>
												<div class="ms-container">
													<div class="ms-selection">
														<ul class="ms-list current_carton_box" tabindex="-1"></ul>
													</div>
												</div>
											</div>
										</div>
									</div>
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