<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Inventory <small>Audit</small>
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
							<i class="fa fa-hockey-puck"></i>Audit
						</div>
						<div class="tools">
							<!-- <button class="btn btn-xs btn-primary reload" id="reload-stock">Reload</button> -->
						</div>
					</div>
					<div class="portlet-body">
						<form role="form" class="form-horizontal form-row-sepe" id="audit-uid">
							<div class="form-body">
								<div class="form-group hide">
									<label class="control-label col-md-1">Audit Group</label>
									<div class="col-md-3 input-group">
										<input type="hidden" name="audit_id" value="<?php echo get_option('current_audit_tag'); ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-1">UID</label>
									<div class="col-md-3 input-group">
										<input type="text" name="uid" class="form-control round-left uid" placeholder="Scan the product barcode with a scanner" tabindex="1" autocomplete="off" minlength="12" maxlength="12" required>
										<span class="input-group-btn">
											<input type="hidden" name="action" value="audit_uid">
											<button class="btn btn-success" type="submit" tabindex="2"><i></i> Submit</button>
										</span>
									</div>
								</div>
						</form>
					</div>
					<div class="portlet-body hide audit_response">
						<hr />
					</div>
				</div>
			</div>
			<audio id="chatAudio">
				<source src="<?php echo BASE_URL; ?>/assets/beep.mp3" type="audio/mpeg">
			</audio>
			<!-- END EXAMPLE TABLE PORTLET-->
		</div>
	</div>
	<!-- END PAGE CONTENT-->
</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>