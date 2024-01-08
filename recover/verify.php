<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
$verified = false;
$case = $_GET['case'];
$title = ucwords(str_replace('_', ' ', $case));

$db->where('user_activation_key', $_GET['token']);
if ($case == "account_activation")
	$db->where('user_email', $_GET['email']);

if ($user = $db->has(TBL_USERS)) {
	$details = array('is_email_verified' => 1);

	if ($case == "email_verification")
		$details["user_activation_key"] = "";

	$db->where('user_activation_key', $_GET['token']);
	if ($case == "account_activation") {
		$db->where('user_email', $_GET['email']);
		$details['user_status'] = 0; // ENABLE USER ACCESS
	}

	if ($db->update(TBL_USERS, $details))
		$verified = true;

	// GET USER DETAILS
	$db->where('user_activation_key', $_GET['token']);
	if ($case == "account_activation")
		$db->where('user_email', $_GET['email']);
	$current_user = $db->getOne(TBL_USERS);
}
?>
<!-- BEGIN CONTENT -->
<!-- BEGIN LOGO -->
<div class="logo">
	<a href="index-2.html">
		<img src="assets/img/logo.png" alt="" />
	</a>
</div>
<!-- END LOGO -->
<!-- BEGIN CONTENT -->
<div class="content">
	<div class="page-body">
		<div class="row">
			<div class="col-md-12 page-lock-info">
				<h3>
					<center><?php echo $title; ?></center>
				</h3><br />

				<?php if ($case == "email_verification" && $verified) : ?>
					<div class="note note-success">
						<h4>Email Verified successfully!</h4>
						<p>You will be redirected to login page automatically in 5 seconds. If not click <a href="../login">here</a></p>
					</div>
					<script type="text/javascript">
						window.setTimeout(function() {
							window.location.href = '../login';
						}, 5000);
					</script>
				<?php elseif ($case == "account_activation" && $verified) : ?>
					<div class="note note-success">
						<h4>Email Verified successfully!</h4>
						<p>Please verify your mobile number below.</p>
					</div>
					<form action="#" type="post" class="login-form" name="verify-mobile" id="verify-mobile">
						<h4 class="form-title">Verify Mobile</h4>
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-sms"></i>
								</span>
								<input type="text" data-required="1" id="user_otp" minlength="6" maxlength="6" autocomplete="off" name="user_otp" class="form-control round-right" required placeholder="Enter OTP" />
								<span class="input-group-btn resend_otp hide">
									<a href="javascript:;" class="btn btn-success" id="resend_otp"><i class="fa fa-sync"></i> Resend OTP</a>
								</span>
							</div>
						</div>
						<div class="form-actions">
							<input type="hidden" name="user_mobile" value="<?php echo $current_user['user_mobile']; ?>">
							<input type="hidden" id="token" value="<?php echo $_GET['token']; ?>">
							<button type="submit" class="btn btn-success btn-submit"><i class="fa"></i> Verify</button>
						</div>
					</form>
					<div class="note note-success mobile_verified hide">
						<h4>Mobile Verified successfully!</h4>
						<p>You will be redirected to password reset page automatically in 5 seconds. If not click <a href="recover/reset_password.php?token=<?php echo $_GET["token"]; ?>">here</a></p>
					</div>
				<?php else : ?>
					<div class="note note-error link_expired">
						<h4>Email Verification link expired!</h4>
						<p><a class="btn btn-warning btn-xs resend_verification_link" data-case="<?php echo $_GET['case']; ?>" data-email="<?php echo $_GET['email']; ?>"><i></i> Request New</a> email verification link</p>
					</div>
					<div class="note note-info reset_link hide">
						<h4>New link sent</h4>
						<p>New email verification link has been sent to your registered mail ID. Please use the link to verify your email address.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<!-- END CONTENT -->
<!-- BEGIN COPYRIGHT -->
<div class="copyright">
	<?php echo date('Y', time()); ?> &copy; FIMS
</div>
<!-- END COPYRIGHT -->
<?php include_once(ROOT_PATH . '/footer.php');
?>