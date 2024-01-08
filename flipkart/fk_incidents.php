<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add-incident" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add New Incident</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add_new_incident" id="add_new_incident">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Account<span class="required"> * </span></label>
									<div class="col-sm-9">
										<select class="form-control account_id" id="account_id" name="account_id"></select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Incident Type<span class="required"> * </span></label>
									<div class="col-sm-9">
										<select class="form-control incident_type select2me" id="incident_type" name="incident_type">
											<?php
											$incident_type = json_decode(get_option('fk_incident_types'));
											$options = "<option value=''></option>";
											foreach ($incident_type->items as $incident_value) {
												$options .= '<optgroup label="' . $incident_value->name . '">';
												foreach ($incident_value->sub_items as $incident) {
													$options .= '<option value="' . $incident->name . '">' . $incident->name . '</option>';
												}
												$options .= '</optgroup>';
											}
											echo $options;
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Incident ID<span class="required"> * </span></label>
									<div class="col-sm-9">
										<input type="text" data-required="1" id="incident_id" name="incident_id" class="form-control" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Subject<span class="required"> * </span></label>
									<div class="col-sm-9">
										<input type="text" data-required="1" id="subject" name="subject" class="form-control" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Base Incident</label>
									<div class="col-sm-9">
										<input type="text" data-required="1" id="base_incident" name="base_incident" class="form-control" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">Order Item ID</label>
									<div class="col-sm-9">
										<input placeholder="comma seperated value" type="text" data-required="1" id="order_item_ids" name="order_item_ids" class="form-control" />
									</div>
								</div>
								<!-- <div class="form-group">
										<label class="col-sm-3 control-label">Details<span class="required"> * </span></label>
										<div class="col-sm-9">
											<textarea rows="5" data-required="1" id="incident_details" name="incident_details" class="form-control"></textarea>
										</div>
									</div> -->
								<div class="form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Add Incident</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										<span class="re_error"></span>
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
		<div class="modal fade" id="confirm_merge_to_incident" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Merged to Incident</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="request-report" id="request-report">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="alert alert-success display-hide">
									<button class="close" data-close="alert"></button>
									Your form validation is successful!
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Merged to Incident ID<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" class="form-control input-medium" name="merge_to_incident" id="merge_to_incident" maxlength=21 maxlength=21>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8 text-right">
										<button type="button" class="btn btn-default close_merge_incident_modal" data-dismiss="modal"> Cancel</button>
										<button type="button" class="btn btn-success btn-submit confirm_merge_incident_modal"><i class=""></i> Confirm</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- /.modal-content -->
			</div>
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Flipkart <small>Incidents</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<!-- <div class="btn-group pull-right">
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
					</div> -->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="span12">
					<!-- BEGIN TAB PORTLET-->
					<div class="tabbable tabbable-custom boxless">
						<ul class="nav nav-tabs incident_status">
							<li class="active"><a href="#portlet_active" data-toggle="tab">Active </a></li>
							<li><a href="#portlet_closed" data-toggle="tab">Closed </a></li>
						</ul>
						<div class="tab-content table-scrollable-x">
							<div class="tab-pane active" id="portlet_active">
								<table class="table table-striped table-bordered table-hover incidents" id="incidents_active">
									<thead>
										<tr>
											<th>Account</th>
											<th>Incident ID</th>
											<th>Type</th>
											<th>Subject</th>
											<th>Reference ID</th>
											<th>Base Incident</th>
											<th>Created Date</th>
											<th>Updated Date</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div class="tab-pane" id="portlet_closed">
								<table class="table table-striped table-bordered table-hover incidents" id="incidents_closed">
									<thead>
										<tr>
											<th>Account</th>
											<th>Incident ID</th>
											<th>Type</th>
											<th>Subject</th>
											<th>OrderItemIds</th>
											<th>Base Incident</th>
											<th>Merged To Incident</th>
											<th>Created Date</th>
											<th>Updated Date</th>
											<th>Closed Date</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<!-- <div class="tab-pane" id="portlet_unsettled">
									<table class="table table-striped table-bordered table-hover" id="dashboard_unsettled">
										<thead>
											<tr>
												<th>Account Name</th>
												<th>Order Item ID</th>
												<th>Order ID</th>
												<th>Shipped Date</th>
												<th>Due Date</th>
												<th>Expected Payout</th>
												<th>Settlement</th>
												<th>Difference</th>
												<th class="return_hide_column">Account ID</th>
											</tr>
										</thead>
										<tbody>
										</tbody>
									</table>
								</div>
								<div class="tab-pane" id="portlet_disputed">
									<table class="table table-striped table-bordered table-hover" id="dashboard_disputed">
										<thead>
											<tr>
												<th>Account Name</th>
												<th>Order Item ID</th>
												<th>Order ID</th>
												<th>Shipped Date</th>
												<th>Due Date</th>
												<th>Expected Payout</th>
												<th>Settlement</th>
												<th>Difference</th>
												<th class="return_hide_column">Account ID</th>
											</tr>
										</thead>
										<tbody>
										</tbody>
									</table>
								</div> -->
						</div>
					</div>
					<!-- END TAB PORTLET-->
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>