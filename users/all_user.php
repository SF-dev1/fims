<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_user" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-user" id="add-user">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Add User</h4>
						</div>
						<div class="modal-body">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Seller<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium" id="party_id" name="party_id">
												<option value="0">Self</option>
												<?php
												$customers = get_all_customers();
												foreach ($customers as $customer) {
													echo '<option value="' . $customer->party_id . '">' . $customer->party_name . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="display_name" name="display_name" class="form-control round-right input-medium" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">User Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="user_login" name="user_login" class="form-control round-right input-medium" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Email<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="user_email" name="user_email" class="form-control round-right input-medium" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Mobile<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="user_mobile" name="user_mobile" class="form-control round-right input-medium" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Role Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium" id="user_role" name="user_role" data-required="1" required>
												<option value=""></option>
												<?php
												foreach (get_all_roles() as $role) {
													echo '<option value="' . $role . '">' . ucwords(str_replace("_", " ", $role)) . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer form-actions fluid">
							<div class="col-md-offset-3 col-md-9">
								<button type="submit" class="btn btn-success btn-submit"><i class="fa fa-check"></i> Submit</button>
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</form>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<div class="modal fade" id="edit_user_role" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form action="#" type="post" class="form-horizontal form-row-seperated" name="update-user-access" id="update-user-access">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Edit User Role & Capability</h4>
						</div>
						<div class="modal-body">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">User Name</label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" class="form-control round-right input-medium user_name" value="" readonly>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Role Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium user_role" id="user_role" name="user_role" data-required="1" required>
												<option value=""></option>
												<?php
												foreach (get_all_roles() as $role) {
													echo '<option value="' . $role . '">' . ucwords(str_replace("_", " ", $role)) . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 offset-md-8 control-label">Capabilities</label>
									<div class="col-sm-12 user_capabilities">

									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer form-actions fluid">
							<div class="col-md-offset-3 col-md-9">
								<input type="hidden" name="action" value="update_user_capabilities">
								<input type="hidden" name="user_id" value="">
								<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</form>
				</div>
				<!-- /.modal-content -->
			</div>
			<!-- /.modal-dialog -->
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Users <small>All</small>
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
							<a data-target="#add_user" role="button" data-toggle="modal" class="btn btn-xs btn-primary add" id="add-user-button" href=""><i class="fa fa-plus"></i> Add</a>
							<button class="btn btn-xs btn-primary reload" id="reload-users">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_users"></table>
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