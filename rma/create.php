<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			RMA <small>Creation</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar"></div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN PORTLET-->
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">RMA Creation</div>
					</div>
					<div class="portlet-body">
						<form role="form" action="#" type="post" class="form-inline" name="get_order_details" id="get_order_details">
							<div class="form-group">
								<input type="text" name="order_key" class="form-control input-medium order_key" placeholder="Order Id" value="<?php echo isset($_GET['order_id']) ? $_GET['order_id'] : ''; ?>" tabindex="1" required />
							</div>
							<div class="form-group">
								<select class="form-control select2me input-medium" data-placeholder="Select Marketplace" data-allow-clear="true" name="marketplace" tabindex="2" required>
									<option></option>
									<?php
									$selected_mp = isset($_GET['marketplace']) ? strtolower($_GET['marketplace']) : '';
									foreach ($accounts as $marketplace => $account) {
										$selected = '';
										if ($selected_mp == $marketplace)
											$selected = 'selected';
										echo '<option value="' . $marketplace . '" ' . $selected . '>' . ucfirst($marketplace) . '</option>';
									}
									?>
								</select>
							</div>
							<div class="form-group">
								<input type="hidden" name="action" value="get_orders_details">
								<button type="submit" class="btn btn-success search_order" tabindex="3"><i class="fa fa-search"></i></button>
							</div>
							<div class="form-group lookup_error has-error hide">
								<span class="help-block">Please correct the error </span>
							</div>
						</form>
						<div class="multi_order_selection hide"></div>
						<div class="rma_order_details hide">
							<hr />
							<div class="row">
								<div class="col-md-8">
									<div class="portlet">
										<div class="portlet-body form" id="rmaCreation">
											<form role="form" action="#" type="post" name="raise_rma_request" id="raise_rma_request" class="form-horizontal">
												<div class="form-body">
													<div class="uidSelection"></div>
													<div class="issueSelection form-group"></div>
													<div class="estimateSection form-group hide">
														<label class="col-md-3 control-label">Estimate</label>
														<div class="col-md-9 estimateSectionDetails">
														</div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">Customers Complains<span class="required">*</span></label>
														<div class="col-md-9">
															<textarea class="form-control" name="complains" rows="4" data-msg="Please enter the customers complain" required></textarea>
														</div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">Pickup<span class="required">*</span></label>
														<div class="col-md-9">
															<div class="radio-list">
																<label class="radio-inline">
																	<input type="radio" name="pickup_type" id="self_ship" value="self_ship" checked> Self Ship </label>
																<label class="radio-inline">
																	<input type="radio" name="pickup_type" id="paid_pickup" value="paid_pickup"> Paid Pickup </label>
																<label class="radio-inline free_pickup_check ">
																	<input type="radio" name="pickup_type" id="free_pickup" value="free_pickup"> Free Pickup </label>
															</div>
														</div>
													</div>
													<div class="form-group qc_enabled">
														<label class="col-md-3 control-label">Qc Enable Return</label>
														<div class="col-md-9">
															<div class="checkbox-list">
																<label class="checkbox-inline">
																	<input type="checkbox" class="has-checkbox qc_enable" name="qc_enable" checked /> Yes
																</label>
															</div>
														</div>
													</div>

													<div class="form-group customer_address">
														<label class="col-md-3 control-label label-address">Return Address</label>
														<div class="col-md-9">
															<div class="checkbox-list">
																<label class="checkbox-inline">
																	<input type="checkbox" class="has-checkbox" id="return_address" name="default_address" checked>Same as below Address? </label>
															</div>
														</div>
													</div>

													<div class="form-group customer_address">
														<label class="col-md-3 control-label">Address<span class="required">*</span></label>
														<div class="col-md-9">
															<p class="form-control-static current_address"></p>
														</div>
													</div>
													<div class="form-group confirmed_address">
														<label class="col-md-3 control-label"></label>
														<div class="col-md-9">
															<div class="checkbox-list">
																<label class="checkbox-inline">
																	<input type="checkbox" class="has-checkbox" name="return_address_confirm" data-msg="Please confirm Return Address" data-error-container=".confirm-return-address" required /> Return Address Confirmed
																</label>
															</div>
															<div class="confirm-return-address"></div>
														</div>
													</div>
													<div class="return_address_details hide">
														<div class="form-group">
															<label class="col-md-3 control-label">Name<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[name]" class="form-control pickup-address-form return_address_name" data-msg="Please enter Name" placeholder="Name" readonly value="">
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">Address #1<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[address1]" class="form-control pickup-address-form return_address_1" data-msg="Please enter Address Line 1" placeholder="Address Line #1" readonly value="">
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">Address #2<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[address2]" class="form-control pickup-address-form return_address_2" data-msg="Please enter Address Line 2" placeholder="Address Line #2" readonly value="">
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">Landmark<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[landmark]" class="form-control pickup-address-form return_landmark" data-msg="Please enter Landmark" placeholder="Landmark" readonly value="">
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">Pincode<span class="required">*</span></label>
															<div class="col-md-5">
																<div class="input-icon right pincode_servicability_group">
																	<i class="fa"></i>
																	<input type="text" name="return[zip]" class="form-control pickup-address-form return_pincode" data-msg="Please enter Pincode" data-error-container=".pincode-error" placeholder="Pincode" readonly value="">
																</div>
																<div class="pincode-error"></div>
															</div>
															<div class="col-md-4 pincode_servicable">
																<button class='btn btn-info btn-pincode-check alt-pincode-check' data-lporderid="" data-accountid="" type='button'><i class='fa'></i> Check!</button>
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">City<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[city]" class="form-control pickup-address-form return_city" data-msg="Please enter City" placeholder="City" readonly value="">
															</div>
														</div>
														<div class="form-group">
															<label class="col-md-3 control-label">State<span class="required">*</span></label>
															<div class="col-md-9">
																<input type="text" name="return[province]" class="form-control pickup-address-form return_state" data-msg="Please enter State" placeholder="State" readonly value="">
																<input type="hidden" name="return[country]" value="India">
															</div>
														</div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">Contact Number<span class="required">*</span></label>
														<div class="col-md-9">
															<input type="text" name="mobile_number" class="form-control input-small" data-msg="Please enter Mobile Number" placeholder="Mobile Number" value="" required>
														</div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">WhatsApp Number<span class="required">*</span></label>
														<div class="col-md-4">
															<div class="input-group">
																<input type="text" name="whatsapp_number" class="form-control input-small has-whatsapp-number" data-msg="Please enter WhatsApp Number" placeholder="WhatsApp Number" readonly value="">
																<span class="input-group-addon">
																	<input type="checkbox" id="default_to_wa" name="default_to_wa" checked><label for="default_to_wa">Same as Contact Number?</label>
																</span>
															</div>
														</div>
														<div class="col-md-5"></div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">Email Address<span class="required">*</span></label>
														<div class="col-md-9">
															<input type="text" name="email_address" class="form-control" data-msg="Please enter Email Address" placeholder="Email Address" value="" required>
														</div>
													</div>
													<div class="form-group">
														<label class="col-md-3 control-label">Share updates on<span class="required">*</span></label>
														<div class="col-md-9">
															<div class="checkbox-list">
																<label class="checkbox-inline">
																	<input type="checkbox" name="share_update[sms]" value="true" checked> SMS </label>
																<label class="checkbox-inline">
																	<input type="checkbox" name="share_update[whatsapp]" value="true" checked> WhatsApp </label>
																<label class="checkbox-inline">
																	<input type="checkbox" name="share_update[email]" value="true" checked> Email </label>
															</div>
														</div>
													</div>
												</div>
												<div class="form-actions fluid">
													<div class="row">
														<div class="col-md-12">
															<div class="col-md-offset-3 col-md-9">
																<input type="hidden" name="pincode_servicable" data-msg="Pincode is not entered/serviceable" required>
																<input type="hidden" name="marketplace">
																<input type="hidden" name="orderId">
																<input type="hidden" name="orderItemId">
																<input type="hidden" name="accountId">
																<input type="hidden" name="productCondition">
																<input type="hidden" name="estimate">
																<input type="hidden" name="invoiceDate">
																<input type="hidden" name="warrantyPeriod">
																<button type="submit" class="btn btn-success btn-submit"><i></i> Submit</button>
															</div>
														</div>
													</div>
												</div>
												<!-- <div class="form-actions">
														<button type="submit" class="btn btn-info">Submit</button>
													</div> -->
											</form>
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="well">
										<p class="help-block-static">Invoice Number: <span class="help-block-content invoiceNumber"></span></p>
										<p class="help-block-static">Invoice Date: <span class="help-block-content invoiceDate"></span></p>
										<p class="help-block-static">Warranty Period: <span class="help-block-content warrantyPeriod"></span></p>
										<p class="help-block-static">Warranty Expiry: <span class="help-block-content warrantyExpiry"></span></p>
										<p class="help-block-static">Delivery Date: <span class="help-block-content deliveredDate"></span></p>
										<p class="help-block-static">In Warranty: <span class="help-block-content inWarranty"></span></p>
										<p class="help-block-static is-returnable-block hide">Is Returnable: <span class="help-block-content label"></span></p>
										<hr />
										<p class="help-block-static">Marketplace: <span class="help-block-content marketplace"></span></p>
										<p class="help-block-static">Order Id: <span class="help-block-content orderId">OD107391161526537000</span></p>
										<p class="help-block-static">Order Item Id: <span class="help-block-content orderItemId"></span></p>
										<p class="help-block-static">Customer Name: <span class="help-block-content customerName"></span></p>
										<p class="help-block-static">Sold By: <span class="help-block-content accountName"></span></p>
										<p class="help-block-static">Location Id: <span class="help-block-content locationId"></span></p>
										<p class="help-block-static">Order Date: <span class="help-block-content orderDate"></span></p>
										<p class="help-block-static">Quantity: <span class="help-block-content quantity"></span></p>
										<p class="help-block-static">Title: <span class="help-block-content productTitle"></span></p>
										<p class="help-block-static">MP Id: <span class="help-block-content mpId"></span></p>
										<p class="help-block-static">SKU: <span class="help-block-content sku"></span></p>
										<p class="help-block-static">Order Amount: <span class="help-block-content invoiceAmount"></span></p>
									</div>
								</div>
							</div>
						</div>
						<div class="confirm_rma_order_details hide">
							<hr />
							<div class="row">
								<div class="col-md-12 text-center">
									<svg class="loadingmark rmaCreateLoading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
										<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
										<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
										<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
									</svg>
									<h2>RMA #<span class="rmaId"></span></h2>
									<div class="payment_status hide">
										<svg class="loadingmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
											<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
											<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
											<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
										</svg>
										<p>Generating Payments Link...</p>
									</div>
									<div class="pickup_status hide">
										<svg class="loadingmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
											<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
											<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
											<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
										</svg>
										<p>Requesting Pickup...</p>
									</div>
									<div class="notification_status hide">
										<div class="sms_notification_status ">
											<svg class="loadingmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
												<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
												<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
												<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
											</svg>
											<p>Sending SMS...</p>
										</div>
										<div class="whatsapp_notification_status ">
											<svg class="loadingmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
												<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
												<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
												<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
											</svg>
											<p>Sending WhatsApp...</p>
										</div>
										<div class="email_notification_status ">
											<svg class="loadingmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="animation-iteration-count: infinite">
												<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
												<path class="checkmark__check hide" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
												<path class="checkmark__cross hide" fill="none" d="M16 16 36 36 M36 16 16 36" />
											</svg>
											<p>Sending Email...</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- END PORTLET-->
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>