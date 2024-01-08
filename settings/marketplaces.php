<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="portlet-config-flipkart" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Flipkart Account</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-fk-marketplace" id="add-fk-marketplace" data-modal="portlet-config-flipkart">
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
									<label class="col-sm-4 control-label">Login E-Mail Id.<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_email" class="form-control input-medium round-right" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Login Password<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="password" required name="account_password" class="form-control input-medium round-right" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is AMS?</label>
									<div class="col-sm-8">
										<input name="is_ams" type="checkbox" class="is_ams make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group if_ams">
									<label class="col-sm-4 control-label">AMS For?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select name="party_id" class="form-control input-medium select2me ams_vendor round-right" required disabled />
											<option></option>
											<?php
											$ams_vendors = get_all_vendors();
											foreach ($ams_vendors as $ams_vendor) {
												echo '<option value=' . $ams_vendor->party_id . '>' . $ams_vendor->party_name . '</option>';
											}

											?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Get API ACCESS From</label>
									<div class="col-sm-8">
										<p class="help-block">
											<a target="_blank" href="https://api.flipkart.net/oauth-register/">
												https://api.flipkart.net/oauth-register/ </a>
										</p>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<!-- <input type="action" name="add_fk"> -->
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
		<div class="modal fade" id="portlet-edit-account-flipkart" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Update Account</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal" name="update-account-flipkart" id="update-account-flipkart" data-modal="portlet-edit-account-flipkart">
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
									<label class="col-sm-4 control-label">Login E-Mail Id.<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_email" class="form-control input-medium account_email" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Login Password<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="password" required name="account_password" class="form-control input-medium account_password" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Account Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="fk_account_name" class="form-control input-medium fk_account_name" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Display Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_name" class="form-control input-medium account_name" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Mobile No.<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_mobile" class="form-control input-medium account_mobile" />
										</div>
									</div>
								</div>
								<hr />
								<h4>Fulfilment Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is SellerSmart?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_sellerSmart" type="checkbox" class="is_sellerSmart make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is Flipkart Fulfilled?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_marketplaceFulfilled" type="checkbox" class="is_marketplaceFulfilled make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group if_fa_warehouses hide">
									<label class="col-sm-4 control-label">FA Warehouses<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="checkbox-list">
											<label>East</label>
											<label><input type="checkbox" class="fa_warehouses kol_dan_01" name="mp_warehouses[]" value="kol_dan_01" disabled> Kolkata Dankuni, West Bengal </label>
											<label><input type="checkbox" class="fa_warehouses kol_sank_01" name="mp_warehouses[]" value="kol_sank_01" disabled> Kolkata Sankrail 01, West Bengal </label>
											<label><input type="checkbox" class="fa_warehouses ulub_bts" name="mp_warehouses[]" value="ulub_bts" disabled> Kolkata Uluberia BTS, West Bengal </label>
											<label>West</label>
											<label><input type="checkbox" class="fa_warehouses bhi_vas_wh_nl_01nl" name="mp_warehouses[]" value="bhi_vas_wh_nl_01nl" disabled> Bhiwandi BTS, Maharashtra </label>
											<label>North</label>
											<label><input type="checkbox" class="fa_warehouses bil" name="mp_warehouses[]" value="bil" disabled> Bilaspur, Haryana </label>
											<label><input type="checkbox" class="fa_warehouses binola" name="mp_warehouses[]" value="binola" disabled> Binola, Haryana </label>
											<label><input type="checkbox" class="fa_warehouses frk_bts" name="mp_warehouses[]" value="frk_bts" disabled> Farrukhnagar BTS, Haryana </label>
											<label>South</label>
											<label><input type="checkbox" class="fa_warehouses blr_mal_01" name="mp_warehouses[]" value="blr_mal_01" disabled> Bangalore Malur, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses blr_wfld" name="mp_warehouses[]" value="blr_wfld" disabled> Bangalore Whitefiled, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses malur_bts" name="mp_warehouses[]" value="malur_bts" disabled> Malur BTS, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses hyderabad_medchal_01" name="mp_warehouses[]" value="hyderabad_medchal_01" disabled> Hyderabad Medchal, Telangana </label>
											<!-- 
													patna_01 - 800020
													mum_bndi - 421302
													kol_dan_02 - 712250
													chn_tvr_03 - 601102
													mah_rsyni - 410220
												-->
										</div>
									</div>
								</div>
								<hr />
								<h4>API Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">App Id<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="app_id" class="form-control input-medium round-right app_id" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">App Secret<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="app_secret" class="form-control input-medium round-right app_secret" required />
										</div>
									</div>
								</div>
								<hr />
								<h4>AMS Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is AMS?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_ams" type="checkbox" class="is_ams make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group if_ams hide">
									<label class="col-sm-4 control-label">AMS For?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select name="party_id" class="form-control input-medium select2me ams_vendor round-right" required disabled />
											<option></option>
											<?php
											$ams_vendors = get_all_vendors();
											foreach ($ams_vendors as $ams_vendor) {
												echo '<option value=' . $ams_vendor->party_id . '>' . $ams_vendor->party_name . '</option>';
											}

											?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" name="account_id" class="account_id" value="" />
										<input type="hidden" name="marketplaces" class="marketplaces" value="" />
										<input type="hidden" name="action" value="update_account_details" />
										<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="portlet-edit-account-amazon" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Update Account</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal" name="update-account-amazon" id="update-account-amazon" data-modal="portlet-edit-account-amazon">
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
									<label class="col-sm-4 control-label">Account Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="fk_account_name" class="form-control input-medium fk_account_name" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Display Name<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_name" class="form-control input-medium account_name" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Mobile No.<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="account_mobile" class="form-control input-medium account_mobile" />
										</div>
									</div>
								</div>
								<hr />
								<h4>Fulfilment Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is Flex?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_sellerSmart" type="checkbox" class="is_sellerSmart make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is Amazon Fulfilled?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_marketplaceFulfilled" type="checkbox" class="is_marketplaceFulfilled make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group if_fa_warehouses hide">
									<label class="col-sm-4 control-label">FVA Warehouses<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="checkbox-list">
											<label>East</label>
											<label><input type="checkbox" class="fa_warehouses kol_dan_01" name="mp_warehouses[]" value="kol_dan_01" disabled> Kolkata Dankuni, West Bengal </label>
											<label><input type="checkbox" class="fa_warehouses kol_sank_01" name="mp_warehouses[]" value="kol_sank_01" disabled> Kolkata Sankrail 01, West Bengal </label>
											<label><input type="checkbox" class="fa_warehouses ulub_bts" name="mp_warehouses[]" value="ulub_bts" disabled> Kolkata Uluberia BTS, West Bengal </label>
											<label>West</label>
											<label><input type="checkbox" class="fa_warehouses bhi_vas_wh_nl_01nl" name="mp_warehouses[]" value="bhi_vas_wh_nl_01nl" disabled> Bhiwandi BTS, Maharashtra </label>
											<label>North</label>
											<label><input type="checkbox" class="fa_warehouses bil" name="mp_warehouses[]" value="bil" disabled> Bilaspur, Haryana </label>
											<label><input type="checkbox" class="fa_warehouses binola" name="mp_warehouses[]" value="binola" disabled> Binola, Haryana </label>
											<label><input type="checkbox" class="fa_warehouses frk_bts" name="mp_warehouses[]" value="frk_bts" disabled> Farrukhnagar BTS, Haryana </label>
											<label>South</label>
											<label><input type="checkbox" class="fa_warehouses blr_mal_01" name="mp_warehouses[]" value="blr_mal_01" disabled> Bangalore Malur, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses blr_wfld" name="mp_warehouses[]" value="blr_wfld" disabled> Bangalore Whitefiled, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses malur_bts" name="mp_warehouses[]" value="malur_bts" disabled> Malur BTS, Karnataka </label>
											<label><input type="checkbox" class="fa_warehouses hyderabad_medchal_01" name="mp_warehouses[]" value="hyderabad_medchal_01" disabled> Hyderabad Medchal, Telangana </label>
											<!-- 
													patna_01 - 800020
													mum_bndi - 421302
													kol_dan_02 - 712250
													chn_tvr_03 - 601102
													mah_rsyni - 410220
												-->
										</div>
									</div>
								</div>
								<hr />
								<h4>API Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">Seller Id<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="app_id" class="form-control input-medium round-right app_id" required />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">MWS Authorisation Token<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" required name="app_secret" class="form-control input-medium round-right app_secret" required />
										</div>
									</div>
								</div>
								<hr />
								<h4>AMS Details</h4>
								<div class="form-group">
									<label class="col-sm-4 control-label">Is AMS?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<input name="is_ams" type="checkbox" class="is_ams make-switch" data-size="medium" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;" data-on-color="success" data-off-color="danger">
									</div>
								</div>
								<div class="form-group if_ams hide">
									<label class="col-sm-4 control-label">AMS For?<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select name="party_id" class="form-control input-medium select2me ams_vendor round-right" required disabled />
											<option></option>
											<?php
											$ams_vendors = get_all_vendors();
											foreach ($ams_vendors as $ams_vendor) {
												echo '<option value=' . $ams_vendor->party_id . '>' . $ams_vendor->party_name . '</option>';
											}

											?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-3 col-md-9">
										<input type="hidden" name="account_id" class="account_id" value="" />
										<input type="hidden" name="marketplaces" class="marketplaces" value="" />
										<input type="hidden" name="action" value="update_account_details" />
										<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Marketplaces <small>Account Manager</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN EXAMPLE TABLE PORTLET-->
				<div class="portlet editable_flipkart_wrapper">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-edit"></i>Flipkart
						</div>
						<div class="tools">
							<a href="#portlet-config-flipkart" data-toggle="modal" class="btn btn-xs btn-primary add"><i class="fa fa-plus"></i> Add</a>
							<button class="btn btn-xs btn-primary reload" id="reload-mp-fk">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_flipkart"></table>
					</div>
				</div>
				<div class="portlet editable_amazon_wrapper">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-edit"></i>Amazon
						</div>
						<div class="tools">
							<a href="#portlet-config-amazon" data-toggle="modal" class="btn btn-xs btn-primary add"><i class="fa fa-plus"></i> Add</a>
							<button class="btn btn-xs btn-primary reload" id="reload-mp-az">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<div class="table-toolbar">
							<div class="row">
								<div class="col-md-6">
									<div class="btn-group">
										<a href="#portlet-config-amazon" data-toggle="modal" class="config btn btn-success">
											Add New <i class="fa fa-plus"></i>
										</a>
									</div>
								</div>
							</div>
						</div>
						<table class="table table-striped table-hover table-bordered" id="editable_amazon"></table>
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