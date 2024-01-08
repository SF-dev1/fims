<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="portlet-config" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Modal title</h4>
					</div>
					<div class="modal-body">
						Widget settings form goes here
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-success">Save changes</button>
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			SMS Settings <small>API</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">
						Actions <i class="fa fa-angle-down"></i>
					</button>
					<ul class="dropdown-menu pull-right" role="menu">
						<li>
							<a href="#">Action</a>
						</li>
						<li>
							<a href="#">Another action</a>
						</li>
						<li>
							<a href="#">Something else here</a>
						</li>
						<li class="divider">
						</li>
						<li>
							<a href="#">Separated link</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<?php
		$sms_enable = get_option('sms_enable');
		$sms_url = get_option('sms_url');
		$sms_username = get_option('sms_username');
		$sms_password = get_option('sms_password');
		// $sms_templates = get_option('sms_template%');
		// var_dump($sms_templates);
		?>
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet" id="porlet_sms_api_settings">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i>SMS API Settings
						</div>
						<div class="actions">
							<input id="sms_api_status" name="sms_api_status" type="checkbox" class="sms_api_status make-switch" data-on-text="&nbsp;Active&nbsp;" data-off-text="&nbsp;Inactive&nbsp;" <?php echo $sms_enable == "1" ? 'checked' : '' ?> data-on-color="success" data-off-color="danger">
						</div>
					</div>
					<div class="portlet-body form sms_api_settings" <?php echo $sms_enable == "0" ? "style='display: none;'" : "" ?>>
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal form-row-sepe">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-3">API URL</label>
									<div class="col-md-4">
										<input type="text" name="sms_url" id="sms_url" class="form-control" value="<?php echo $sms_url; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">API Username</label>
									<div class="col-md-4">
										<input type="text" name="sms_username" id="sms_username" class="form-control" value="<?php echo $sms_username; ?>">
									</div>
								</div>
								<div class="form-group last">
									<label class="control-label col-md-3">API Password</label>
									<div class="col-md-4">
										<input type="password" name="sms_password" id="sms_password" class="form-control" value="<?php echo $sms_password; ?>">
									</div>
								</div>
							</div>
							<div class="form-actions">
								<div class="row">
									<div class="col-md-offset-3 col-md-9">
										<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
										<button type="button" class="btn btn-default">Cancel</button>
									</div>
								</div>
							</div>
						</form>
						<!-- END FORM-->
					</div>
				</div>
			</div>
		</div>
		<?php
		// $ssms = $sms->send_sms('Hello IMS', array('919099925025'));
		?>
		<?php /*
			<div class="row">
				<div class="col-md-12">
					 <!-- BEGIN TEMPLATE TABLE PORTLET-->
					<div class="portlet editable_sms_template_wrapper">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-edit"></i>SMS Templates
							</div>
							<div class="tools">
								<button data-target="#add_sms_template" id="add_sms_template_btn" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New <i class="fa fa-plus"></i></button>
								<button class="btn btn-xs btn-primary reload" id="reload-sms-template">Reload</button>
							</div>
						</div>
						<div class="portlet-body">
							<div class="table-toolbar">
								<div class="row">
									<div class="col-md-6">
										<div class="btn-group">
											<a href="#portlet-sms-template" data-toggle="modal" class="config btn btn-success">
												Add New <i class="fa fa-plus"></i>
											</a>
										</div>
									</div>
								</div>
							</div>
							<div class="modal fade" id="add_sms_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
											<h4 class="modal-title">Add SMS Template</h4>
										</div>
										<div class="modal-body form">
											<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products" id="add-products" >
												<div class="form-body">
													<div class="alert alert-danger display-hide">
														<button class="close" data-close="alert"></button>
														You have some form errors. Please check below.
													</div>
												 	<div class="form-group">
														<label class="col-sm-4 control-label">Template Name<span class="required"> * </span></label>
														<div class="col-sm-8">
															<div class="input-group">
																<input type="text" data-required="1" id="sku" name="template_name" class="form-control round-right input-medium"/>
															</div>
														</div>
													</div>
													<div class="form-group last">
														<label class="col-sm-4 control-label">Template Content<span class="required"> * </span></label>
														<div class="col-sm-8">
															<div class="input-inline input-medium">
																<textarea id="template_content" class="form-control" maxlength="1024" rows="5" placeholder="This template has a limit of 1024 chars."></textarea>
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
							<table class="table table-striped table-hover table-bordered" id="editable_sms_template" width="100%">
								<thead>
									<tr>
										<th>
											Template Name
										</th>
										<th>
											Template Content
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
							</tbody>
							</table>
						</div>
					</div>
					<!-- END TEMPLATE TABLE PORTLET-->
				</div>
			</div>
			*/ ?>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>