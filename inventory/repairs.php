<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="inventory_history" tabindex="-1" role="dialog" aria-labelledby="InventoryHistory" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Repairs History</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12 product_history">
								<div class="timeline timeline-5"></div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
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
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="get-uid" id="get-uid">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<div class="col-md-12">
												<div class="input-group">
													<span class="input-group-addon">
														<i class="fa fa-barcode"></i>
													</span>
													<input type="text" id="uid" name="uid" class="form-control round-left" placeholder="Scan the product barcode with a scanner" tabindex="1" autocomplete="off" minlength="12" maxlength="12">
													<span class="input-group-btn">
														<button class="btn btn-info" id="sidelineProduct" type="button">
															Sideline Product
														</button>
													</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated product_details hide" name="product-details">
							<div class="row">
								<div class="col-md-12">
									<hr />
									<div class="row">
										<div class="col-md-2">
											<div class="form-group">
												<div class="col-md-12">
													<div class="thumbnail" style="width: 154px; height: 154px; margin-top: 10px;">
														<img loading="lazy" src="" onerror="this.onerror=null;this.src='https://via.placeholder.com/150x150/EFEFEF/AAAAAA&amp;text=no+image';" alt="" class="product_image" />
													</div>
													<span><b>SKU </b>
														<p class="product_sku"></p>
													</span>
												</div>
											</div>
										</div>
										<div class="col-md-5 issue_mapper">
											<div class="form-group">
												<div class="col-md-12 issue_type">
												</div>
												<div class="col-md-12 issues hide">
												</div>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<div class="col-md-12 issue_fixed">
													<label class="control-label">Issues Fixed</label><label class="control-label pull-right previous_history hide"><button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#inventory_history">History</button></label>
													<div class="ms-container">
														<div class="ms-selection">
															<ul class="ms-list fixed_issues" tabindex="-1"></ul>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<div class="col-md-12 parts_request">
													<label class="control-label">Request Component</label>
													<div class="ms-container">
														<div class="ms-selection">
															<ul class="ms-list requested_parts" tabindex="-1"></ul>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<input type="hidden" class="uid" name="uid" value="" />
								<input type="hidden" class="issues_fixed" name="issues_fixed" value="" />
								<input type="hidden" class="components_requested" name="components_requested" value="" />
							</div>
						</form>
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
