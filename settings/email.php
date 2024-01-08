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
			Email <small>API</small>
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
		$email_enable = get_option('email_enable');
		$email_url = get_option('email_host');
		$email_username = get_option('email_username');
		$email_password = get_option('email_password');
		// $email_templates = get_option('email_template%');
		// var_dump($email_templates);
		?>
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet" id="porlet_email_api_settings">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i>E-mail API Settings
						</div>
						<div class="actions">
							<input id="email_api_status" name="email_api_status" type="checkbox" class="email_api_status make-switch" data-on-text="&nbsp;Active&nbsp;" data-off-text="&nbsp;Inactive&nbsp;" <?php echo $email_enable == "1" ? 'checked' : '' ?> data-on-color="success" data-off-color="danger">
						</div>
					</div>
					<div class="portlet-body form email_api_settings" <?php echo $email_enable == "0" ? "style='display: none;'" : "" ?>>
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal form-row-sepe">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-3">API Host</label>
									<div class="col-md-4">
										<input type="text" name="email_url" id="email_url" class="form-control" value="<?php echo $email_url; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">API Username</label>
									<div class="col-md-4">
										<input type="text" name="email_username" id="email_username" class="form-control" value="<?php echo $email_username; ?>">
									</div>
								</div>
								<div class="form-group last">
									<label class="control-label col-md-3">API Password</label>
									<div class="col-md-4">
										<input type="password" name="email_password" id="email_password" class="form-control" value="<?php echo $email_password; ?>">
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
		<div class="row hide">
			<div class="col-md-12">
				<!-- BEGIN TEMPLATE TABLE PORTLET-->
				<div class="portlet editable_email_template_wrapper">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-edit"></i>E-mail Templates
						</div>
						<div class="tools">
							<button data-target="#add_email_template" id="add_email_template_btn" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New <i class="fa fa-plus"></i></button>
							<button class="btn btn-xs btn-primary reload">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<div class="modal fade" id="add_email_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
										<h4 class="modal-title">Add EMAIL Template</h4>
									</div>
									<div class="modal-body form">
										<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-products" id="add-products">
											<div class="form-body">
												<div class="alert alert-danger display-hide">
													<button class="close" data-close="alert"></button>
													You have some form errors. Please check below.
												</div>
												<div class="form-group">
													<label class="col-sm-4 control-label">Template Name<span class="required"> * </span></label>
													<div class="col-sm-8">
														<div class="input-group">
															<input type="text" data-required="1" id="sku" name="template_name" class="form-control round-right input-medium" />
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
						<table class="table table-striped table-hover table-bordered" id="editable_email_template" width="100%">
							<thead>
								<tr>
									<th class="return_hide_column">
										Template ID
									</th>
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
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>