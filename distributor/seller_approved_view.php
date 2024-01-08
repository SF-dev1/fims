<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
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
						<h4 class="modal-title">Seller Documents</h4>
					</div>
					<div class="modal-body">
						<iframe id="view_pdf" src="" frameborder="0" width="100%" height="450px"></iframe>
					</div>
					<div class="modal-footer">
						<div class="form-action">
							<button class="btn btn-success approve" data-partyid="" data-partydoc="" data-checkedgst="" data-checkedtnc=""><i></i>Approve</button>
							<button class="btn btn-warning disapprove" href="#confirm-disapprove" role="button" data-toggle="modal" data-partyid="" data-partydoc="" data-checkedgst="" data-checkedtnc=""><i></i>Disapprove</button>
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
		<div class="modal fade" id="upload-certificate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Generate & Sign Certificate</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="upload-signed-certificate" id="upload-signed-certificate">
							<div class="form-body">
								<div class="form-group party_signed_certificate ">
									<label class="control-label col-sm-4">Signed Certificate Sylvi<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="party_signed_certificate_sy" id="party_signed_certificate_sy" accept="application/pdf" required>
											</span>
											<span class="fileinput-filename"></span>&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<span class="help-block"></span>
										<span class="help-block-static">Only .pdf files allowed. <br />Please updoad the GST Certifcate of the above GST Number</span>
										<button class="btn btn-default btn-small generate_certificate" data-partyid="" data-brandid="2" data-brandname="Sylvi" data-partyname="" role="button">Generate & Download Certificate</button>
									</div>
								</div>
								<div class="form-group party_signed_certificate">
									<label class="control-label col-sm-4">Signed Certificate Style Feathers<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="fileinput fileinput-new" data-provides="fileinput">
											<span class="btn btn-default btn-file">
												<span class="fileinput-new">Select file </span>
												<span class="fileinput-exists">Change </span>
												<input type="file" name="party_signed_certificate_sf" id="party_signed_certificate_sf" accept="application/pdf" required>
											</span>
											<span class="fileinput-filename"></span>&nbsp; <a href="#" class="close fileinput-exists" data-dismiss="fileinput">
											</a>
										</div>
										<span class="help-block"></span>
										<span class="help-block-static">Only .pdf files allowed. <br />Please updoad the GST Certifcate of the above GST Number</span>
										<button class="btn btn-default btn-small generate_certificate" data-partyid="" data-brandid="1" data-brandname="Style Feathers" data-partyname="" role="button">Generate & Download Certificate</button>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" name="party_id" value="">
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
			Sellers <small>Approved</small>
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
							<button class="btn btn-xs btn-primary reload" id="reload-unapproved-sellers">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="view_approved_sellers">
							<thead>
								<tr>
									<th class="return_hide_column">
										Seller ID
									</th>
									<th class="return_hide_column">
										Distributor ID
									</th>
									<th>
										Seller Name
									</th>
									<th>
										Seller POC
									</th>
									<th>
										Seller Email
									</th>
									<th>
										Seller Mobile
									</th>
									<th>
										Distributor Email
									</th>
									<th>
										Distributor Mobile
									</th>
									<th>
										Certificate ID
									</th>
									<th>
										Brand Name
									</th>
									<th>
										Expiry Date
									</th>
									<th>
										Status
									</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
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