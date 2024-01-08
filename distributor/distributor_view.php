<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="view-docs" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Distributor Documents</h4>
					</div>
					<div class="modal-body">
						<iframe id="view_pdf" src="" frameborder="0" width="100%" height="450px"></iframe>
					</div>
					<div class="modal-footer">
						<div class="form-action">
							<button class="btn btn-success approve" data-partyid="" data-partydoc=""><i></i>Approve</button>
							<button class="btn btn-warning disapprove" href="#confirm-disapprove" role="button" data-toggle="modal" data-partyid="" data-partydoc=""><i></i>Disapprove</button>
						</div>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
		<div class="modal fade" id="confirm-disapprove" tabindex="-1" data-width="600" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
					<h4 class="modal-title">Confirm Header</h4>
				</div>
				<div class="modal-body">
					<div class="form-horizontal form-row-seperated">
						<div class="form-body">
							<div class="form-group">
								<label class="control-label col-sm-4">Disapprove Reason<span class="required"> * </span></label>
								<div class="col-sm-8">
									<input type="text" class="form-control" id="disapprove_reason" required>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Close</button>
					<button class="btn btn-info confirmed-disapprove">Confirm</button>
				</div>
			</div>
		</div>
		<div class="modal fade" id="upload-docs" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Sign & Stamped Terms & Condition</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="upload-signed-docs" id="upload-signed-docs">
							<div class="form-body">
								<div class="form-group party_signed_certificate ">
									<label class="control-label col-sm-4">Signed & Stamped T&C<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="dist_signed_tnc" id="dist_signed_tnc" accept="application/pdf" required>
											</span>
											<span class="fileinput-filename"></span>&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<span class="help-block"></span>
										<span class="help-block-static">Only .pdf files allowed. <br />Please updoad the GST Certifcate of the above GST Number</span>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" id="party_id" name="party_id" value="">
										<input type="hidden" id="party_name" name="party_name" value="">
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
		<div class="modal fade" id="upload-certificate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Generate & Send Certificate</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="upload-signed-certificate" id="upload-signed-certificate">
							<div class="form-body">
								<div class="party_signed_certificate_sample"></div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" name="party_id" value="">
										<input type="hidden" name="is_renewal" value="">
										<input type="hidden" name="is_distributor" value="1">
										<button type="submit" class="btn btn-success btn-submit"><i class="fa fa-check"></i> Submit</button>
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
			Distributor <small>All</small>
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
						<div class="tools">
							<button class="btn btn-xs btn-primary reload">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="view_distributors"></table>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>