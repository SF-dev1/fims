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
			Distributor <small>New Seller</small>
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
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet" id="form_wizard_1">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i> Seller Sign Up - <span class="step-title">
								Step 1 of 4 </span>
						</div>
						<div class="tools hidden-xs">
							<a href="javascript:;" class="reload"></a>
						</div>
					</div>
					<div class="portlet-body form">
						<form action="#" class="form-horizontal" id="new-seller-registration">
							<div class="form-wizard">
								<div class="form-body">
									<ul class="nav nav-pills nav-justified steps">
										<li>
											<a href="#tab1" data-toggle="tab" class="step">
												<span class="number">1 </span>
												<span class="desc"><i class="fa fa-check"></i> Firm Details </span>
											</a>
										</li>
										<li>
											<a href="#tab2" data-toggle="tab" class="step">
												<span class="number">2 </span>
												<span class="desc"><i class="fa fa-check"></i> Marketplace Details </span>
											</a>
										</li>
										<li>
											<a href="#tab3" data-toggle="tab" class="step">
												<span class="number">3 </span>
												<span class="desc"><i class="fa fa-check"></i> Personal Details </span>
											</a>
										</li>
										<!-- <li>
												<a href="#tabx" data-toggle="tab" class="step active">
													<span class="number">3 </span>
													<span class="desc"><i class="fa fa-check"></i> Account Details </span>
												</a>
											</li> -->
										<li>
											<a href="#tab4" data-toggle="tab" class="step">
												<span class="number">4 </span>
												<span class="desc"><i class="fa fa-check"></i> Confirm </span>
											</a>
										</li>
									</ul>
									<div id="bar" class="progress progress-striped" role="progressbar">
										<div class="progress-bar progress-bar-success">
										</div>
									</div>
									<div class="tab-content">
										<div class="alert alert-danger display-none hide">
											<button class="close" data-close="alert"></button>
											You have some form errors. Please check below.
										</div>
										<div class="alert alert-success display-none">
											<button class="close" data-close="alert"></button>
											Your form validation is successful!
										</div>
										<div class="tab-pane" id="tab1">
											<h3 class="block">Provide your firm details</h3>
											<div class="form-group">
												<label class="control-label col-md-3">Firm Name <span class="required">* </span></label>
												<div class="col-md-4">
													<input type="text" class="form-control" name="party_name" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Firm GST <span class="required">* </span></label>
												<div class="col-md-4">
													<input type="text" class="form-control" name="party_gst" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Address <span class="required">* </span></label>
												<div class="col-md-4">
													<textarea rows="4" class="form-control" minlength="30" name="party_address"></textarea>
												</div>
											</div>
										</div>
										<div class="tab-pane" id="tab2">
											<h3 class="block">Provide your categories, brands & marketplace details</h3>
											<div class="form-group">
												<label class="control-label col-md-3">Current Selling Categories: <span class="required">* </span></label>
												<div class="col-md-4">
													<input id="category_tags" type="text" class="form-control party_categories" data-role="tagsinput" name="party_categories" value="" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Your Brand Name: <span class="required">* </span></label>
												<div class="col-md-4">
													<input id="brand_tags" type="text" class="form-control party_brands" data-role="tagsinput" name="party_brands" value="" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Other Brands currently selling: <span class="required">* </span></label>
												<div class="col-md-4">
													<input id="brand_tags_others" type="text" class="form-control party_brands" data-role="tagsinput" name="party_brands_others" value="" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Currenlty Selling on? <span class="required">* </span></label>
												<div class="col-md-4">
													<div class="checkbox-list marketplaces">
														<div class="row">
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="flipkart" /> Flipkart </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="amazon" /> Amazon </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="paytm" /> Paytm </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="snapdeal" /> Snapdeal </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="limeRoad" /> LimeRoad </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="shopClues" /> ShopClues </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="mirraw" /> Mirraw </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="meesho" /> Meesho </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="shop_101" /> Shop 101 </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="facebook" /> Facebook </label>
															</div>
															<div class="col-md-4">
																<label><input type="checkbox" class="marketplace" name="marketplace_checkbox[]" value="instagram" /> Instagram </label>
															</div>
														</div>
														<div class="row">
															<div class="col-md-12">
																<label><input type="checkbox" class="marketplace other_marketplace" name="marketplace_checkbox[]" value="other" /> Other </label>
																<label><input type="text" name="other_marketplace_input" class="form-control input-large other_marketplace_input hide" placeholder="Other Marketplaces (comma seperated)" value="" disabled /> </label>
																<span class="help-block"></span>
															</div>
														</div>
													</div>
													<div id="form_marketplace_error"></div>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Brand Approval for? <span class="required">* </span></label>
												<div class="col-md-4">
													<div class="checkbox-list brands">
														<div class="row">
															<?php
															$checkbox = "";
															if ($client_id != (int)0) {
																$approved_brands = json_decode($current_user['party']['party_approved_brands'], true);
																foreach ($approved_brands as $brand) {
																	$brand = get_brand_details_by_name($brand, 0);
																	$checkbox .= '<div class="col-md-4">
																				<label><input type="checkbox" class="brand" name="party_approved_brands[]" data-title="' . $brand['brandName'] . '" value="' . $brand['brandName'] . '" /> ' . $brand['brandName'] . ' </label>
																			</div>';
																}
															} else {
																$brands = get_all_brands();
																foreach ($brands['opt'] as $brand) {
																	$checkbox .= '<div class="col-md-4">
																				<label><input type="checkbox" class="brand" name="party_approved_brands[]" data-title="' . $brand['brandName'] . '" value="' . $brand['brandName'] . '" /> ' . $brand['brandName'] . ' </label>
																			</div>';
																}
															}
															echo $checkbox;
															?>
														</div>
													</div>
													<div id="form_brand_error"></div>
												</div>
											</div>
											<div class="form-group marketplaces_additional_info"></div>
										</div>
										<div class="tab-pane" id="tab3">
											<h3 class="block">Provide your personal details</h3>
											<div class="form-group">
												<label class="control-label col-md-3">POC/Owners Name <span class="required">* </span></label>
												<div class="col-md-4">
													<input type="text" class="form-control" name="party_poc" />
													<span class="help-block"></span>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Email Address <span class="required">* </span></label>
												<div class="col-md-4">
													<input type="text" class="form-control" name="party_email" />
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Mobile Number <span class="required">* </span></label>
												<div class="col-md-4">
													<input type="text" class="form-control" name="party_mobile" />
												</div>
											</div>
											<!-- <div class="form-group">
													<label class="control-label col-md-3">Payment Options <span class="required">* </span></label>
													<div class="col-md-4">
														<div class="checkbox-list">
															<label><input type="checkbox" name="payment[]" value="1" data-title="Auto-Pay with this Credit Card."/> Auto-Pay with this Credit Card </label>
															<label><input type="checkbox" name="payment[]" value="2" data-title="Email me monthly billing."/> Email me monthly billing </label>
														</div>
														<div id="form_payment_error">
														</div>
													</div>
												</div> -->
										</div>
										<div class="tab-pane" id="tab4">
											<h3 class="block">Confirm your account</h3>
											<h4 class="form-section">Firm</h4>
											<div class="form-group">
												<label class="control-label col-md-3">Firm Name:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_name">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Firm GST:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_gst">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Address:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_address">
													</p>
												</div>
											</div>
											<h4 class="form-section">Category, Brand & Marketplace Details</h4>
											<div class="form-group">
												<label class="control-label col-md-3">Your Categories:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_categories">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Your Brand Name:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_brands">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Other Brands currently selling:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_brands_others">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Currently selling on:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="marketplace">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Brand Approval For?</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="brand_checkbox">
													</p>
												</div>
											</div>
											<h4 class="form-section">Personal Details</h4>
											<div class="form-group">
												<label class="control-label col-md-3">POC/Owners Name:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_poc">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Email Address:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_email">
													</p>
												</div>
											</div>
											<div class="form-group">
												<label class="control-label col-md-3">Mobile Number:</label>
												<div class="col-md-4">
													<p class="form-control-static" data-display="party_mobile">
													</p>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="row">
										<div class="col-md-12">
											<div class="col-md-offset-3 col-md-9">
												<input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
												<?php
												if (isset($_GET['seller_id']) && $_GET['seller_id'] != "") {
													echo '<input type="hidden" name="party_id" value="' . $_GET["seller_id"] . '">';
												}
												?>
												<input type="hidden" name="action" value="add_seller">
												<a href="javascript:;" class="btn btn-default button-previous"><i class="m-icon-swapleft"></i> Back </a>
												<a href="javascript:;" class="btn btn-info button-next">Continue <i class="m-icon-swapright m-icon-white"></i></a>
												<a href="javascript:;" class="btn btn-success button-submit">Submit <i class="m-icon-swapright m-icon-white"></i></a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>