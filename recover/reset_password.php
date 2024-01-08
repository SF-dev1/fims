<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
$reset_success = false;
$is_valid_token = false;
if (isset($_POST['token'], $_POST['p'])) {
	$db->where('user_activation_key', $_POST['token']);
	if ($db->update(TBL_USERS, array('user_pass' => $_POST['p'], 'user_activation_key' => "", "user_status" => 0))) {
		$reset_success = true;
		$is_valid_token = true;
	}
}
if (isset($_GET['token'])) {
	$db->where('user_activation_key', $_GET['token']);
	if ($db->has(TBL_USERS))
		$is_valid_token = true;
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
	<?php if (!$is_valid_token) : ?>
		<h3>Reset Password</h3>
		<div class="note note-error">
			<h4>Invalid password reset request</h4>
			<p>Please create a new request from Forget Password page.</p>
		</div>
	<?php elseif (!$reset_success && $is_valid_token) : ?>
		<form class="reset-password" action="reset_password.php" method="post">
			<h3>Reset Password</h3>
			<div class="form-group password-strength">
				<label class="control-label visible-ie8 visible-ie9">Password</label>
				<div class="input-icon">
					<i class="fa fa-lock"></i>
					<input class="form-control placeholder-no-fix" type="password" autocomplete="off" id="register_password" placeholder="Password" name="password" />
				</div>
			</div>
			<div class="form-group">
				<label class="control-label visible-ie8 visible-ie9">Re-type Your Password</label>
				<div class="controls">
					<div class="input-icon">
						<i class="fa fa-check"></i>
						<input class="form-control placeholder-no-fix" type="password" autocomplete="off" placeholder="Re-type Your Password" name="rpassword" />
					</div>
				</div>
			</div>
			<div class="form-actions">
				<input type="hidden" name="token" value="<?php echo $_GET['token']; ?>" />
				<button type="submit" id="register-submit-btn" class="btn btn-info pull-right"> Submit <i class="m-icon-swapright m-icon-white"></i>
				</button>
			</div>
		</form>
	<?php else : ?>
		<h3>Reset Password</h3>
		<div class="note note-success">
			<h4>Password Updated successfully!</h4>
			<p>You will be redirected to login page automatically in 5 seconds. If not click <a href="../login">here</a></p>
		</div>
		<script type="text/javascript">
			window.setTimeout(function() {
				window.location.href = '../login';
			}, 5000);
		</script>
	<?php endif; ?>
</div>
<!-- END CONTENT -->
<!-- BEGIN COPYRIGHT -->
<div class="copyright">
	<?php echo date('Y', time()); ?> &copy; FIMS
</div>
<!-- END COPYRIGHT -->
<?php include_once(ROOT_PATH . '/footer.php');
?>