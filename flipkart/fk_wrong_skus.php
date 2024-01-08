<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN MODAL FORM-->
		<div class="modal fade" id="add_wrong_sku" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Add Alias</h4>
					</div>
					<div class="modal-body form">
						<form action="#" type="post" class="form-horizontal form-row-seperated" name="add-wrong-sku" id="add-wrong-sku">
							<div class="form-body">
								<div class="alert alert-danger display-hide">
									<button class="close" data-close="alert"></button>
									You have some form errors. Please check below.
								</div>
								<?php

								$db->setQueryOption('DISTINCT');
								$db->where('marketplace', 'flipkart');
								$db->orderBy('sku', 'ASC');
								$products = $db->get(TBL_PRODUCTS_ALIAS);
								$options_mp_id = array();
								foreach ($products as $product) {
									$options_mp_id[] = array('key' => $product['mp_id'], 'value' => $product['mp_id']);
								}

								usort(
									$options,
									function ($a, $b) {
										if ($a['value'] == $b['value']) {
											return 0;
										}
										return ($a['value'] > $b['value']) ? -1 : 1;
									}
								);

								// $option_mp_id = "";
								// foreach ($options_mp_id as $sku) {
								// 	$option_mp_id .= '<option value="'.$sku['key'].'">'.$sku['value'].'</option>';
								// }
								?>
								<div class="form-group">
									<label class="col-sm-4 control-label">Account<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control input-medium account_id" id="account_id" name="account_id">
												<option value=""></option>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">FSN<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<select class="form-control select2me input-medium mp_id" id="mp_id" name="mp_id">
												<?php echo $option_mp_id; ?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Wrong SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="wrong_sku" name="wrong_sku" class="form-control round-right input-medium" disabled="true" />
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">Correct SKU<span class="required"> * </span></label>
									<div class="col-sm-8">
										<div class="input-group">
											<input type="text" data-required="1" id="correct_sku" name="correct_sku" class="form-control round-right input-medium" />
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
		<!-- END MODAL FORM-->
		<!-- BEGIN PAGE HEADER-->
		<h3 class="page-title">
			Wrong SKU <small>Details</small>
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
							<i class="fa fa-ban"></i>Wrong SKU's
						</div>
						<div class="tools">
							<!-- <button data-target="#add_product_alias_bulk" id="add_product_alias_bulk" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add Bulk Alias <i class="fa fa-plus"></i></button> -->
							<button data-target="#add_wrong_sku" id="add_wrong_alias" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add Wrong SKU <i class="fa fa-plus"></i></button>
							<button class="btn btn-xs btn-primary reload" id="reload-wrongSKU">Reload</button>
						</div>
					</div>
					<div class="portlet-body">
						<table class="table table-striped table-hover table-bordered" id="editable_wrong_sku">
							<thead>
								<tr>
									<th class="return_hide_column">
										alias_id
									</th>
									<th>
										Correct SKU
									</th>
									<th>
										Wrong SKU
									</th>
									<th>
										FSN
									</th>
									<th>
										Account
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
			</div>
		</div>
		<!-- END PAGE CONTENT-->
	</div>
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>