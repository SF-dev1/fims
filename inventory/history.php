<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN PORTLET MODAL FORM-->
		<div class="model"></div>
		<!-- END PORTLET MODAL FORM-->
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
				<!-- BEGIN PORTLET-->
				<div class="portlet">
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="get-uid" id="get-uid">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="text" id="uid" name="uid" class="form-control round-left" placeholder="Scan the product barcode with a scanner" tabindex="1" autocomplete="off" minlength="12" maxlength="12" value="<?php echo $_GET['uid']; ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
						<div class="product_history text-muted"></div>
					</div>
				</div>
				<!-- END PORTLET-->
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>