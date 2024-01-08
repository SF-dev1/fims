<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
// var_dump($current_user['party']);
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="mobile_verify" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<form action="#" type="post" class="form-horizontal form-row-seperated" name="verify-mobile" id="verify-mobile">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Verify Mobile</h4>
						</div>
						<div class="modal-body">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">OTP<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="user_otp" minlength="6" maxlength="6" name="user_otp" class="form-control round-right input-medium" required />
											<span class="input-group-btn hide resend_otp">
												<a href="javascript:;" class="btn btn-success" id="resend_otp"><i class="fa fa-sync"></i> Resend OTP</a>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer form-actions fluid">
							<div class="col-md-offset-3 col-md-9">
								<input type="hidden" name="user_mobile" value="<?php echo $current_user['user_mobile']; ?>">
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
			Profile <small>My Profile</small>
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
		<div class="row profile">
			<div class="col-md-12">
				<!--BEGIN TABS-->
				<div class="tabbable tabbable-custom">
					<ul class="nav nav-tabs">
						<!-- <li>
								<a href="#tab_1_1" data-toggle="tab">Overview</a>
							</li> -->
						<li class="active">
							<a href="#profile_account" data-toggle="tab">Account</a>
						</li>
						<!-- <li>
								<a href="#tab_1_6" data-toggle="tab">Help</a>
							</li> -->
					</ul>
					<div class="tab-content">
						<?php /* ?>
							<div class="tab-pane active" id="tab_1_1">
								<div class="row">
									<div class="col-md-3">
										<ul class="list-unstyled profile-nav">
											<li>
												<img src="assets/img/profile/profile-img.png" class="img-responsive" alt=""/>
												<a href="#" class="profile-edit">edit</a>
											</li>
											<li>
												<a href="#">Projects</a>
											</li>
											<li>
												<a href="#">Messages <span>
												3 </span>
												</a>
											</li>
											<li>
												<a href="#">Friends</a>
											</li>
											<li>
												<a href="#">Settings</a>
											</li>
										</ul>
									</div>
									<div class="col-md-9">
										<div class="row">
											<div class="col-md-8 profile-info">
												<h1>John Doe</h1>
												<p>
													 Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt laoreet dolore magna aliquam tincidunt erat volutpat laoreet dolore magna aliquam tincidunt erat volutpat.
												</p>
												<p>
													<a href="#">www.mywebsite.com</a>
												</p>
												<ul class="list-inline">
													<li>
														<i class="fa fa-map-marker"></i> Spain
													</li>
													<li>
														<i class="fa fa-calendar"></i> 18 Jan 1982
													</li>
													<li>
														<i class="fa fa-briefcase"></i> Design
													</li>
													<li>
														<i class="fa fa-star"></i> Top Seller
													</li>
													<li>
														<i class="fa fa-heart"></i> BASE Jumping
													</li>
												</ul>
											</div>
											<!--end col-md-8-->
											<div class="col-md-4">
												<div class="portlet sale-summary">
													<div class="portlet-title">
														<div class="caption">
															 Sales Summary
														</div>
														<div class="tools">
															<a class="reload" href="javascript:;"></a>
														</div>
													</div>
													<div class="portlet-body">
														<ul class="list-unstyled">
															<li>
																<span class="sale-info">
																TODAY SOLD <i class="fa fa-img-up"></i>
																</span>
																<span class="sale-num">
																23 </span>
															</li>
															<li>
																<span class="sale-info">
																WEEKLY SALES <i class="fa fa-img-down"></i>
																</span>
																<span class="sale-num">
																87 </span>
															</li>
															<li>
																<span class="sale-info">
																TOTAL SOLD </span>
																<span class="sale-num">
																2377 </span>
															</li>
															<li>
																<span class="sale-info">
																EARNS </span>
																<span class="sale-num">
																$37.990 </span>
															</li>
														</ul>
													</div>
												</div>
											</div>
											<!--end col-md-4-->
										</div>
										<!--end row-->
										<div class="tabbable tabbable-custom tabbable-custom-profile">
											<ul class="nav nav-tabs">
												<li class="active">
													<a href="#tab_1_11" data-toggle="tab">Latest Customers</a>
												</li>
												<li>
													<a href="#tab_1_22" data-toggle="tab">Feeds</a>
												</li>
											</ul>
											<div class="tab-content">
												<div class="tab-pane active" id="tab_1_11">
													<div class="portlet-body">
														<table class="table table-striped table-bordered table-advance table-hover">
														<thead>
														<tr>
															<th>
																<i class="fa fa-briefcase"></i> Company
															</th>
															<th class="hidden-xs">
																<i class="fa fa-question-sign"></i> Descrition
															</th>
															<th>
																<i class="fa fa-bookmark"></i> Amount
															</th>
															<th>
															</th>
														</tr>
														</thead>
														<tbody>
														<tr>
															<td>
																<a href="#">Pixel Ltd</a>
															</td>
															<td class="hidden-xs">
																 Server hardware purchase
															</td>
															<td>
																 52560.10$ <span class="label label-success label-sm">
																Paid </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs green-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">
																Smart House </a>
															</td>
															<td class="hidden-xs">
																 Office furniture purchase
															</td>
															<td>
																 5760.00$ <span class="label label-warning label-sm">
																Pending </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs blue-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">
																FoodMaster Ltd </a>
															</td>
															<td class="hidden-xs">
																 Company Anual Dinner Catering
															</td>
															<td>
																 12400.00$ <span class="label label-success label-sm">
																Paid </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs blue-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">
																WaterPure Ltd </a>
															</td>
															<td class="hidden-xs">
																 Payment for Jan 2013
															</td>
															<td>
																 610.50$ <span class="label label-danger label-sm">
																Overdue </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs red-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">Pixel Ltd</a>
															</td>
															<td class="hidden-xs">
																 Server hardware purchase
															</td>
															<td>
																 52560.10$ <span class="label label-success label-sm">
																Paid </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs green-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">
																Smart House </a>
															</td>
															<td class="hidden-xs">
																 Office furniture purchase
															</td>
															<td>
																 5760.00$ <span class="label label-warning label-sm">
																Pending </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs blue-stripe" href="#">View</a>
															</td>
														</tr>
														<tr>
															<td>
																<a href="#">
																FoodMaster Ltd </a>
															</td>
															<td class="hidden-xs">
																 Company Anual Dinner Catering
															</td>
															<td>
																 12400.00$ <span class="label label-success label-sm">
																Paid </span>
															</td>
															<td>
																<a class="btn btn-default btn-xs blue-stripe" href="#">View</a>
															</td>
														</tr>
														</tbody>
														</table>
													</div>
												</div>
												<!--tab-pane-->
												<div class="tab-pane" id="tab_1_22">
													<div class="tab-pane active" id="tab_1_1_1">
														<div class="scroller" data-height="290px" data-always-visible="1" data-rail-visible1="1">
															<ul class="feeds">
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-success">
																					<i class="fa fa-bell"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 You have 4 pending tasks. <span class="label label-danger label-sm">
																					Take action <i class="fa fa-share-alt"></i>
																					</span>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 Just now
																		</div>
																	</div>
																</li>
																<li>
																	<a href="#">
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-success">
																					<i class="fa fa-bell"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New version v1.4 just lunched!
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 20 mins
																		</div>
																	</div>
																	</a>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-danger">
																					<i class="fa fa-bolt"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 Database server #12 overloaded. Please fix the issue.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 24 mins
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 30 mins
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-success">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 40 mins
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-warning">
																					<i class="fa fa-plus"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New user registered.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 1.5 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-success">
																					<i class="fa fa-cogs"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 Web server hardware needs to be upgraded. <span class="label label-inverse label-sm">
																					Overdue </span>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 2 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-default">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 3 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-warning">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 5 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 18 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-default">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 21 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 22 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-default">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 21 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 22 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-default">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 21 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 22 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-default">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 21 hours
																		</div>
																	</div>
																</li>
																<li>
																	<div class="col1">
																		<div class="cont">
																			<div class="cont-col1">
																				<div class="label label-info">
																					<i class="fa fa-bullhorn"></i>
																				</div>
																			</div>
																			<div class="cont-col2">
																				<div class="desc">
																					 New order received. Please take care of it.
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="col2">
																		<div class="date">
																			 22 hours
																		</div>
																	</div>
																</li>
															</ul>
														</div>
													</div>
												</div>
												<!--tab-pane-->
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php  */
						?>
						<!--tab_1_2-->
						<div class="tab-pane active" id="profile_account">
							<div class="row profile-account">
								<div class="col-md-3">
									<ul class="ver-inline-menu tabbable margin-bottom-10">
										<li class="active">
											<a data-toggle="tab" href="#personal"><i class="fa fa-cog"></i> Personal Info </a>
										</li>
										<?php if (isset($current_user['party'])) : ?>
											<li>
												<a data-toggle="tab" href="#company"><i class="fa fa-building"></i> Company Info</a>
											</li>
										<?php endif; ?>
										<li>
											<a data-toggle="tab" href="#password"><i class="fa fa-lock"></i> Change Password</a>
										</li>
										<li>
											<a data-toggle="tab" href="#display"><i class="fa fa-eye"></i> Display Settings</a>
										</li>
									</ul>
								</div>
								<div class="col-md-9">
									<div class="tab-content">
										<div id="personal" class="tab-pane active">
											<form role="form" id="update-profile" action="#">
												<div class="alert alert-danger display-hide">
													<button class="close hide" data-close="alert"></button>
													You have some errors. Please check Order Item ID's entered and retry.
												</div>
												<div class="form-group">
													<label class="control-label">User Name</label>
													<input type="text" placeholder="John" class="form-control" value="<?php echo $current_user['user_login']; ?>" readonly />
												</div>
												<div class="form-group">
													<label class="control-label">Display Name</label>
													<input type="text" placeholder="John Doe" class="form-control" name="display_name" value="<?php echo $current_user['display_name']; ?>" tabindex="1" required />
												</div>
												<div class="form-group">
													<label class="control-label">Nick Name</label>
													<input type="text" placeholder="John" class="form-control" name="user_nickname" value="<?php echo $current_user['user_nickname']; ?>" tabindex="1" required />
												</div>
												<div class="form-group">
													<label class="control-label">Mobile Number</label>
													<div class="input-group">
														<input type="text" placeholder="+91 968 766 0234" class="form-control" name="user_mobile" value="<?php echo $current_user['user_mobile']; ?>" tabindex="2" required readonly />
														<?php if ($current_user['is_mobile_verified']) : ?>
															<span class="input-group-btn">
																<a href="javascript:;" class="btn btn-success" disabled><i class="fa fa-check-double"></i></a>
															</span>
														<?php else : ?>
															<span class="input-group-btn">
																<a href="javascript:;" class="btn btn-warning" id="mobile_verify" data-target="#mobile_verify" data-toggle="modal">Verify Now</a>
															</span>
														<?php endif; ?>
													</div>
												</div>
												<div class="form-group">
													<label class="control-label">Email</label>
													<div class="input-group">
														<input type="text" placeholder="john@duo.com" class="form-control" name="user_email" value="<?php echo $current_user['user_email']; ?>" tabindex="3" required readonly />
														<?php if ($current_user['is_email_verified']) : ?>
															<span class="input-group-btn">
																<a href="javascript:;" class="btn btn-success" disabled><i class="fa fa-check-double"></i></a>
															</span>
														<?php else : ?>
															<span class="input-group-btn">
																<a href="javascript:;" class="btn btn-warning" id="mobile_verify">Verify Now</a>
															</span>
														<?php endif; ?>
													</div>

												</div>
												<div class="margiv-top-10">
													<input type="hidden" name="userID" value="<?php echo $current_user['userID']; ?>">
													<input type="hidden" name="current_user_display_name" id="current_user_display_name" value="<?php echo $current_user['display_name']; ?>">
													<input type="hidden" name="current_user_nickname" id="current_user_nickname" value="<?php echo $current_user['user_nickname']; ?>">
													<input type="hidden" name="current_user_mobile" id="current_user_mobile" value="<?php echo $current_user['user_mobile']; ?>">
													<input type="hidden" name="current_user_email" id="current_user_email" value="<?php echo $current_user['user_email']; ?>">
													<input type="submit" class="btn btn-success" value="Save Changes" tabindex="4" />
													<input type="reset" class="btn btn-default" value="Cancel" tabindex="5" />
												</div>
											</form>
										</div>
										<?php if (isset($current_user['party'])) : ?>
											<div id="company" class="tab-pane">
												<form role="form" id="update-company" action="#">
													<div class="form-group">
														<label class="control-label">Company Role:</label>
														<?php if ($current_user['party']['party_distributor'] == 1) : ?>
															<span class="label label-warning">Distributor</span>
														<?php endif; ?>
														<?php if ($current_user['party']['party_customer'] == 1) : ?>
															<span class="label label-warning">Seller</span>
														<?php endif; ?>
														<?php if ($current_user['party']['party_reseller'] == 1) : //Only used for distributors seller 
														?>
															<span class="label label-warning">Reseller</span>
														<?php endif; ?>
													</div>
													<div class="form-group">
														<label class="control-label">Company Name:</label>
														<input type="text" placeholder="John" class="form-control" value="<?php echo $current_user['party']['party_name']; ?>" readonly />
													</div>
													<div class="form-group">
														<label class="control-label">GSTIN:</label>
														<input type="text" placeholder="John" class="form-control" value="<?php echo $current_user['party']['party_gst']; ?>" tabindex="1" readonly />
													</div>
													<div class="form-group">
														<label class="control-label">Address:</label>
														<textarea class="form-control col-md-12" rows="4" readonly><?php echo $current_user['party']['party_address']; ?></textarea>
													</div>
												</form>
											</div>
										<?php endif; ?>
										<div id="password" class="tab-pane">
											<form role="form" id="update-password" action="#">
												<div class="form-group">
													<label class="control-label">Current Password</label>
													<input type="password" class="form-control" name="current_pass" tabindex="1" />
												</div>
												<div class="form-group password-strength">
													<label class="control-label">New Password</label>
													<input type="password" class="form-control" id="user_pass" name="user_pass" tabindex="2" />
												</div>
												<div class="form-group">
													<label class="control-label">Re-type New Password</label>
													<input type="password" class="form-control" name="ruser_pass" tabindex="3" />
												</div>
												<div class="margin-top-10">
													<input type="hidden" name="userID" value="<?php echo $current_user['userID']; ?>">
													<button type="submit" class="btn btn-success btn-submit" tabindex="4"><i class=""></i> Change Password</button>
													<input type="reset" class="btn btn-default" value="Cancel" tabindex="5" />
												</div>
											</form>
										</div>
										<div id="display" class="tab-pane">
											<form role="form" id="update-settings" action="#">
												<table class="table table-bordered table-striped">
													<?php
													$settings = json_decode($current_user['user_settings'], true);
													$side_bar_checked = $settings['view_sidebar'] == 1 ? "checked" : "";
													?>
													<tr>
														<td>
															Always Open Sidebar
														</td>
														<td>
															<label class="uniform-inline"><input type="radio" name="view_sidebar" value="1" tabindex="1" <?php echo $side_bar_checked; ?> />Yes </label>
															<label class="uniform-inline"><input type="radio" name="view_sidebar" value="0" tabindex="2" <?php echo ($side_bar_checked == "") ? "checked" : ""; ?> />No </label>
														</td>
													</tr>
												</table>
												<!--end profile-settings-->
												<div class="margin-top-10">
													<input type="hidden" name="userID" value="<?php echo $current_user['userID']; ?>">
													<button type="submit" class="btn btn-success btn-submit" tabindex="4"><i class=""></i> Save</button>
													<input type="reset" class="btn btn-default" value="Cancel" tabindex="5" />
												</div>
											</form>
										</div>
									</div>
								</div>
								<!--end col-md-9-->
							</div>
						</div>
						<!--end tab-pane-->
						<div class="tab-pane" id="tab_1_6">
							<div class="row">
								<div class="col-md-3">
									<ul class="ver-inline-menu tabbable margin-bottom-10">
										<li class="active">
											<a data-toggle="tab" href="#tab_1">
												<i class="fa fa-briefcase"></i> General Questions </a>
											<span class="after">
											</span>
										</li>
										<li>
											<a data-toggle="tab" href="#tab_2"><i class="fa fa-users"></i> Membership</a>
										</li>
										<li>
											<a data-toggle="tab" href="#tab_3"><i class="fa fa-leaf"></i> Terms Of Service</a>
										</li>
										<li>
											<a data-toggle="tab" href="#tab_1"><i class="fa fa-info-circle"></i> License Terms</a>
										</li>
										<li>
											<a data-toggle="tab" href="#tab_2"><i class="fa fa-tint"></i> Payment Rules</a>
										</li>
										<li>
											<a data-toggle="tab" href="#tab_3"><i class="fa fa-plus"></i> Other Questions</a>
										</li>
									</ul>
								</div>
								<div class="col-md-9">
									<div class="tab-content">
										<div id="tab_1" class="tab-pane active">
											<div id="accordion1" class="panel-group">
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_1">
																1. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion1_1" class="panel-collapse collapse in">
														<div class="panel-body">
															Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_2">
																2. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion1_2" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-success">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_3">
																3. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor ? </a>
														</h4>
													</div>
													<div id="accordion1_3" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-warning">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_4">
																4. Wolf moon officia aute, non cupidatat skateboard dolor brunch ? </a>
														</h4>
													</div>
													<div id="accordion1_4" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-danger">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_5">
																5. Leggings occaecat craft beer farm-to-table, raw denim aesthetic ? </a>
														</h4>
													</div>
													<div id="accordion1_5" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_6">
																6. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth ? </a>
														</h4>
													</div>
													<div id="accordion1_6" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#accordion1_7">
																7. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft ? </a>
														</h4>
													</div>
													<div id="accordion1_7" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
											</div>
										</div>
										<div id="tab_2" class="tab-pane">
											<div id="accordion2" class="panel-group">
												<div class="panel panel-warning">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_1">
																1. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion2_1" class="panel-collapse collapse in">
														<div class="panel-body">
															<p>
																Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
															</p>
															<p>
																Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
															</p>
														</div>
													</div>
												</div>
												<div class="panel panel-danger">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_2">
																2. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion2_2" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-success">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_3">
																3. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor ? </a>
														</h4>
													</div>
													<div id="accordion2_3" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_4">
																4. Wolf moon officia aute, non cupidatat skateboard dolor brunch ? </a>
														</h4>
													</div>
													<div id="accordion2_4" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_5">
																5. Leggings occaecat craft beer farm-to-table, raw denim aesthetic ? </a>
														</h4>
													</div>
													<div id="accordion2_5" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_6">
																6. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth ? </a>
														</h4>
													</div>
													<div id="accordion2_6" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#accordion2_7">
																7. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft ? </a>
														</h4>
													</div>
													<div id="accordion2_7" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
											</div>
										</div>
										<div id="tab_3" class="tab-pane">
											<div id="accordion3" class="panel-group">
												<div class="panel panel-danger">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_1">
																1. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion3_1" class="panel-collapse collapse in">
														<div class="panel-body">
															<p>
																Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et.
															</p>
															<p>
																Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et.
															</p>
															<p>
																Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
															</p>
														</div>
													</div>
												</div>
												<div class="panel panel-success">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_2">
																2. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry ? </a>
														</h4>
													</div>
													<div id="accordion3_2" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_3">
																3. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor ? </a>
														</h4>
													</div>
													<div id="accordion3_3" class="panel-collapse collapse">
														<div class="panel-body">
															Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_4">
																4. Wolf moon officia aute, non cupidatat skateboard dolor brunch ? </a>
														</h4>
													</div>
													<div id="accordion3_4" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_5">
																5. Leggings occaecat craft beer farm-to-table, raw denim aesthetic ? </a>
														</h4>
													</div>
													<div id="accordion3_5" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_6">
																6. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth ? </a>
														</h4>
													</div>
													<div id="accordion3_6" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#accordion3_7">
																7. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft ? </a>
														</h4>
													</div>
													<div id="accordion3_7" class="panel-collapse collapse">
														<div class="panel-body">
															3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!--end tab-pane-->
					</div>
				</div>
				<!--END TABS-->
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>