<?php
include_once('header.php');

$link_back = "/index.php";
if (isset($_GET['return']) && !empty($_GET['return']))
	$link_back = urldecode($_GET['return']);

if (isset($_POST['trust_device']) && ($_POST['trust_device'] == "Always Trust this Device" || $_POST['trust_device'] == "Allow Once")) {
	$expiry = NULL;
	if ($_POST['trust_device'] == "Allow Once")
		$expiry = time() + 7200;
	$userAccess->is_trustedDevice = $userAccess->addTrustedDevice($expiry);
	if ($userAccess->is_trustedDevice && $userAccess->is_loggedIn) {
		$db->where('userID', $userAccess->current_user['userID']);
		$db->update(TBL_USERS, array('user_otp' => NULL));
		if ($link_back != "/index.php") {
			header("Location: " . BASE_URL . urldecode($_GET['return']));
			exit();
		}

		header("Location: " . BASE_URL . '/index.php');
		exit();
	}
}
$device_info = $userAccess->get_device_details();
$location_info = $userAccess->get_location_details();
$login_init = '"tfa"';
?>
<div class="content">
	<div class="page-body">
		<div class="row">
			<div class="col-md-12 page-lock-info">
				<h3>
					<center>Two Factor Authentication</center>
				</h3><br />
				<div class="alert alert-block alert-info fade in">
					<h4 class="alert-heading">New Device Detected</h4>
					<p>You are logging through an new device.</p>
					<p><b>Device:</b> <?php echo $device_info['brand'] . ' ' . $device_info['os'] . ' v' . $device_info['os_version']; ?></p>
					<p><b>Browser:</b> <?php echo $device_info['browser']; ?></p>
					<p><b>IP:</b> <?php echo $location_info['ip']; ?></p>
					<p><b>Location:</b> <?php echo $location_info['city'] . ', ' . $location_info['state'] . ', ' . $location_info['country']; ?></p>
				</div>
				<?php $form_url = ($link_back != "/index.php") ? "two-factor-auth.php?return=" . urlencode($link_back) : 'two-factor-auth.php'; ?>
				<form action="<?php echo $form_url; ?>" method="post" class="login-form" name="verify-device" id="verify-device">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-sms"></i>
							</span>
							<input type="text" data-required="1" id="access_otp" minlength="6" maxlength="6" autocomplete="off" name="access_otp" class="form-control round-right" required placeholder="Enter OTP" />
							<span class="input-group-btn resend_otp">
								<a href="javascript:;" class="btn btn-success" disabled id="resend_otp"><i class=""></i> Resend OTP</a>
							</span>
						</div>
					</div>
					<div class="form-actions text-center">
						<input type="hidden" name="uid" id="uid" value="<?php echo $userAccess->current_user['userID']; ?>">
						<input type="submit" name="trust_device" class="btn btn-success btn-submit hide" value="Always Trust this Device" disabled />
						<input type="submit" name="trust_device" class="btn btn-success btn-submit hide" value="Allow Once" disabled />
					</div>
				</form>
				<!-- <div class="note note-success mobile_verified hide">
							<h4>Mobile Verified successfully!</h4>
							<p>You will be redirected to password reset page automatically in 5 seconds. If not click <a href="reset_password.php?token=<?php echo $_GET["token"]; ?>">here</a></p>
						</div> -->
			</div>
		</div>
	</div>
</div>
<?php include_once('footer.php');
?>