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
			QZ <small>Settings</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<?php
		$qz_enable = get_option('qz_status');
		$disabled = "disabled";
		if ($current_user['user_role'] == "super_admin" || $current_user['user_role'] == "administrator")
			$disabled = "";
		// $printer_product_label_size = get_option('printer_product_label_size');
		// $printer_mrp_label_size = get_option('printer_mrp_label_size');
		// $printer_shipping_label_size = get_option('printer_shipping_label_size');
		// $printer_invoice_size = get_option('printer_invoice_size');
		?>
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet" id="porlet_qz_settings">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i>QZ Settings
						</div>
						<div class="actions">
							<input id="qz_status" name="qz_status" type="checkbox" class="qz_status make-switch" data-on-text="&nbsp;Active&nbsp;" data-off-text="&nbsp;Inactive&nbsp;" <?php echo $qz_enable == "1" ? 'checked' : '' ?> data-on-color="success" data-off-color="danger">
						</div>
					</div>
					<div class="portlet-body form qz_settings" <?php echo $qz_enable == "0" ? "style='display: none;'" : "" ?>>
						<!-- BEGIN FORM-->
						<form action="#" class="form-horizontal form-row-sepe" id="qz-settings">
							<div class="form-body">
								<div class="form-group">
									<label class="control-label col-md-3">Product Label Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_product_label" id="printer_product_label"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. 50x20]" name="printer_product_label_size" id="printer_product_label_size" class="form-control" value="<?php echo $printer_product_label_size; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">Weighted Product Label Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_weighted_product_label" id="printer_weighted_product_label"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. 80x12]" name="printer_weighted_product_label_size" id="printer_weighted_product_label_size" class="form-control" value="<?php echo $printer_weighted_product_label_size; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">Carton Box Label Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_ctn_box_label" id="printer_ctn_box_label"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. 50x20]" name="printer_ctn_box_label_size" id="printer_ctn_box_label_size" class="form-control" value="<?php echo $printer_ctn_box_label_size; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">MRP Label Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_mrp_label" id="printer_mrp_label"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. 58x40]" name="printer_mrp_label_size" id="printer_mrp_label_size" class="form-control" value="<?php echo $printer_mrp_label_size; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="control-label col-md-3">Shipping Label Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_shipping_label" id="printer_shipping_label"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. 138x96]" name="printer_shipping_label_size" id="printer_shipping_label_size" class="form-control" value="<?php echo $printer_shipping_label_size; ?>">
									</div>
								</div>
								<div class="form-group last">
									<label class="control-label col-md-3">Invoice Printer</label>
									<div class="col-md-4">
										<select class="form-control printers" name="printer_invoice" id="printer_invoice"></select>
									</div>
									<div class="col-md-2">
										<input type="text" <?php echo $disabled; ?> placeholder="Label Size [eg. A5]" name="printer_invoice_size" id="printer_invoice_size" class="form-control" value="<?php echo $printer_invoice_size; ?>">
									</div>
								</div>
							</div>
							<div class="form-actions">
								<div class="row">
									<div class="col-md-offset-3 col-md-9">
										<button type="submit" class="btn btn-success"><i class=""></i> Submit</button>
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
					<div class="portlet editable_qz_template_wrapper">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-edit"></i>SMS Templates
							</div>
							<div class="tools">
								<button data-target="#add_qz_template" id="add_qz_template_btn" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New <i class="fa fa-plus"></i></button>
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
							<div class="modal fade" id="add_qz_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
							<table class="table table-striped table-hover table-bordered" id="editable_qz_template" width="100%">
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