<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="upload_docs" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Upload GST and Signed & Stamped T&C</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="upload-docs" id="upload-docs">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Seller Name</label>
									<div class="col-sm-8">
										<p id="party_name_gst" class="form-control-static" />
									</div>
								</div>
								<div class="form-group party_gst_certificate ">
									<label class="control-label col-sm-4">GST Certificate<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="party_gst_certificate" id="party_gst_certificate" accept="application/pdf" required>
											</span>
											<span class="fileinput-filename"></span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<span class="help-block">Only .pdf files allowed. <br />Please updoad the GST Certifcate of the above GST Number</span>
									</div>
								</div>
								<div class="form-group party_signed_tnc">
									<label class="control-label col-sm-4">Signed & Stamped T&C<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="party_signed_tnc" id="party_signed_tnc" accept="application/pdf" required>
											</span>
											<span class="fileinput-filename"></span>
											&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<span class="help-block">Only .pdf files allowed. <br />Please updoad the <b>signed and stamped</b> T&C</span>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" id="party_id" name="party_id" value="">
										<input type="hidden" id="party_name" name="party_name" value="">
										<input type="hidden" id="is_reupload" name="is_reupload" value="">
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
			Sellers <small>All</small>
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
					<div class="portlet-title">
						<div class="tools">
							<button class="btn btn-xs btn-primary reload" id="reload-sellers">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="view_sellers"></table>
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