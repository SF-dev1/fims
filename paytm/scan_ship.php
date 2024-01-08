<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Paytm <small>Scan & Ship</small>
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
						<form role="form" action="#" type="post" class="form-horizontal form-row-seperated" name="get-product" id="get-product">
							<div class="form-body">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<div class="input-group">
												<span class="input-group-addon">
													<i class="fa fa-barcode"></i>
												</span>
												<input type="text" id="tracking_id" name="tracking_id" class="form-control round-left" placeholder="Scan the tracking barcode with a scanner" tabindex="1" autocomplete="off" required>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
						<div class="product_details text-muted hide">
							<hr />
							<form role="form" action="#" type="post" class="form uid-scanship" name="uid-scanship" id="uid-scanship">
								<div class="form-body">
									<div class="row">
										<div class="col-md-9">
											<div class="row">
												<div class="col-md-12">
													<div class="item_groups">
													</div>
												</div>
												<!--/span-->
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<div class="input-group">
													<span class="input-group-addon">
														<i class="fa fa-barcode"></i>
													</span>
													<input type="text" id="uid" name="uid" class="form-control round-left" placeholder="Scan the uid barcode with a scanner" tabindex="2" autocomplete="off" minlength="12" maxlength="12" required>
												</div>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<audio id="chatAudio">
					<source src="<?php echo BASE_URL; ?>/assets/beep.mp3" type="audio/mpeg">
				</audio>
				<!-- END PORTLET-->
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<?php include_once(ROOT_PATH . '/footer.php'); ?>