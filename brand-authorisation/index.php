<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN LOGO -->
<div class="logo">
	<img src="assets/img/logo.png" alt="" />
</div>
<!-- END LOGO -->
<!-- BEGIN CONTENT -->
<div class="content">
	<!-- BEGIN LOGIN FORM -->
	<form id="authorisation-verify" class="form-horizontal" role="form" method="post">
		<h3 class="form-title">Certificate Verification</h3>
		<div class="alert alert-danger display-hide">
			<button class="close" data-close="alert"></button>
			<span>Please enter authorisation certificate number. </span>
		</div>
		<div class="form-group">
			<!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
			<label for="auth_code" class="control-label visible-ie8 visible-ie9">Certificate Code</label>
			<div class="input-icon">
				<i class="fa fa-key"></i>
				<input class="form-control placeholder-no-fix" type="text" autocomplete="off" placeholder="XX-XXXX-XXXXXX" name="auth_code" tabindex="1" required />
			</div>
		</div>
		<div class="form-actions">
			<button type="submit" class="col-md-12 btn btn-info btn-submit pull-right" id="login-submit" tabindex="2"><i></i> Check</button>
		</div>
	</form>
	<form id="authorisation-verify-response" class="form-horizontal hide">
		<div class="row">
			<div class="col-md-12">
				<h3 class="form-title">Certificate Details</h3>
				<div class="certificate_details_valid hide">
					<div class="form-group">
						<label class="control-label col-md-5">Status</label>
						<div class="col-md-7">
							<p class="form-control-static"><span class="label badge-danger badge-danger-dot hide">Expired</span><span class="label badge-success-dot hide">Active</span></p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Serial #</label>
						<div class="col-md-7">
							<p class="form-control-static auth_code">SF-2019-ASDASD</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Brand Name</label>
						<div class="col-md-7">
							<p class="form-control-static brand_name">Style Feathers</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Seller Name</label>
						<div class="col-md-7">
							<p class="form-control-static seller_name">Talgo</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Seller GST</label>
						<div class="col-md-7">
							<p class="form-control-static seller_gst">24ASDASDASDASD12</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Valid From</label>
						<div class="col-md-7">
							<p class="form-control-static valid_from">30th September, 2019</p>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-5">Valid Till</label>
						<div class="col-md-7">
							<p class="form-control-static valid_till">30th September, 2019</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
	<!-- END LOGIN FORM -->
</div>
<!-- END CONTENT -->
<?php include_once(ROOT_PATH . '/footer.php'); ?>