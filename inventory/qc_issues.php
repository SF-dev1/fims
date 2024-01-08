<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
		<div class="modal fade" id="add_issue" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Issue</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-issue" id="add-issue">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Issue<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="issue" name="issue" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Issue Fix<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="issue_fix" name="issue_fix" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Key Stroke<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="issue_key" name="issue_key" class="form-control round-right input-medium" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Category<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2 input-medium" id="category" name="issue_category">
												<option value=""></option>
												<?php
												$categories = get_all_categories();
												foreach ($categories['opt'] as $category) {
													echo '<option value="' . $category['catid'] . '">' . $category['categoryName'] . '</option>';
												}
												?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Group</label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2 input-medium" id="group" name="issue_group">
											</select>
										</div>
									</div>
								</div>
								<div class="form-actions fluid">
									<div class="col-md-offset-4 col-md-8">
										<input type="hidden" id="update_issue" name="update" value="0" />
										<input type="hidden" id="issue_id" name="issue_id" value="" />
										<button type="submit" class="btn btn-success"><i class="fa"></i> Submit</button>
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
			Inventory <small>QC Issues</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
			<div class="page-toolbar">
				<div class="btn-group pull-right">
					<button type="button" class="btn btn-fit-height default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="1000" data-close-others="true">Actions <i class="fa fa-angle-down"></i></button>
					<ul class="dropdown-menu pull-right" role="menu">
						<li>
							<a data-target="#add_issue" id="add_issue" role="button" data-toggle="modal" href="#">Add Issue</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN EXAMPLE TABLE PORTLET-->
				<div class="portlet">
					<div class="portlet-body">
						<div class="dd">
							<ol class="dd-list">
								<?php
								$qc_issues = get_qc_issues();
								// echo '<pre>';
								// var_dump($qc_issues);
								// echo '</pre>';
								$dd = "";
								foreach ($qc_issues as $category => $issues) {
									$dd .= '<li class="dd-item dd-collapsed">
														<div class="dd-nodrag">
															 ' . $category . '
														</div>';

									foreach ($issues as $issue) {
										$dd .= '<ol class="dd-list">';
										$dd .= '<li class="dd-item dd-collapsed issue-' . $issue['issue_id'] . '" data-id="' . $issue['issue_id'] . '" data-issue="' . $issue['issue'] . '" data-keystroke="' . $issue['issue_key'] . '" data-group="" data-category="' . $issue['issue_category'] . '">';
										if (isset($issue['issues'])) {
											$dd .= '<div class="dd-nodrag">' . $issue['issue'] . '
																<div class="issue_attributes">
																	<span>[Key: ' . $issue['issue_key'] . ']</span>
																	<button class="btn btn-xs edit"><i class="fa fa-edit"></i></button>
																	<button class="btn btn-xs delete"><i class="fa fa-trash"></i></button>
																</div>
															</div>';
											$dd .= '<ol class="dd-list">';
											foreach ($issue['issues'] as $sub_issue) {
												$dd .= '<li class="dd-item dd-collapsed issue-' . $sub_issue['issue_id'] . '" data-id="' . $sub_issue['issue_id'] . '" data-issue="' . $sub_issue['issue'] . '" data-keystroke="' . $sub_issue['issue_key'] . '" data-group="' . $sub_issue['issue_group'] . '" data-category="' . $issue['issue_category'] . '">';
												$dd .= '<div class="dd-nodrag">' . $sub_issue['issue'] . ' 
																	<div class="issue_attributes">
																		<span>[Subkey: ' . $sub_issue['issue_key'] . ']</span>
																		<button class="btn btn-xs edit"><i class="fa fa-edit"></i></button>
																		<button class="btn btn-xs delete"><i class="fa fa-trash"></i></button>
																	</div>
																</div>';
												$dd .= '</li>';
											}
											$dd .= '</ol>';
										} else {
											$dd .= '<div class="dd-nodrag">' . $issue['issue'] . '</div>';
										}
										$dd .= '</li>';
										$dd .= '</ol>';
									}
									$dd .= '</li>';
								}
								// echo '</pre>';
								echo $dd;
								?>
							</ol>
						</div>
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