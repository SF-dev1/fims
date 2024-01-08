<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Sales <small>Return</small>
		</h3>
		<div class="page-bar">
			<?php echo $breadcrumps; ?>
		</div>
		<!-- END PAGE HEADER-->
		<!-- BEGIN PAGE CONTENT-->
		<div class="row">
			<div class="col-md-12">
				<div class="portlet">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-reorder"></i>Search Sales Order
						</div>
					</div>
					<div class="portlet-body form">
						<div class="form-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="control-label col-md-4">Sales Order</label>
										<div class="input-group col-md-8">
											<input class="form-control" type="text" id="so_id" placeholder="SO_000000"> <!-- INPUT MASK -->
											<span class="input-group-btn">
												<button class="btn btn-success search_so"><i class="fa fa-search"></i></button>
											</span>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="control-label col-md-4">Product SKU</label>
										<div class="input-group col-md-8">
											<select class="form-control select2me select_so_items" data-placeholder="Select SKU" data-allowclear="true" disabled>
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="control-label col-md-4">Search UID</label>
										<div class="input-group col-md-8">
											<select multiple="multiple" class="form-control multi-select product_uids">
											</select>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<form id="sales_return">
										<div class="row">
											<div class="col-md-6">
												<div class="form-body">
													<div class="form-group selected_return_uids">
														<label class="control-label col-md-4">Return UID's <p class="help-block selected_uids_count"></p></label>
														<div class="input-group col-md-8">
															<select multiple class="form-control return_uids" name="return_uids" required readonly>
															</select>
															<input type="hidden" name="so_id" class="so_id" required>
															<input type="hidden" name="party_id" class="party_id" required>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="form-actions">
											<div class="row">
												<div class="col-md-offset-2 col-md-6">
													<button type="submit" class="btn btn-success btn-sales-return" disabled><i></i> Submit</button>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>