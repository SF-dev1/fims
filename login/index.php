<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once('header.php');

$link_back = "/index.php";
if (isset($_GET['return']) && !empty($_GET['return']))
	$link_back = urldecode($_GET['return']);

if (isset($_POST['username'], $_POST['password'])) {
	$username = htmlspecialchars(strip_tags($_POST['username']));
	$password = $_POST['token']; // The hashed password
	if (isset($_COOKIE["up"]) && $_COOKIE["un"] === $username) {
		$password = $_COOKIE["up"];
	}
	$remember = (isset($_POST['remember']) && $_POST["remember"] != '') ? $_POST['remember'] : 0;
	$userAccess->login($username, $password, $remember);

	if ($userAccess->is_disabled) {
		$userAccess->sec_session_destroy();
		header("Location: " . BASE_URL . "/login/?error=User Disabled");
		exit();
	}

	if ($userAccess->is_loggedIn === true) { // Login success 
		if ($userAccess->is_trustedDevice === true) { // Device Trusted
			header("Location: " . BASE_URL . $link_back);
			exit();
		} else {
			$details['user_otp'] = $userAccess->generateValidationString('mobile');
			$device_info = $userAccess->get_device_details();
			$location_info = $userAccess->get_location_details();
			// $additionalContent = $userAccess->current_user['user_login'].' using '.$device_info['browser'].' via '.$device_info['brand'].' '.$device_info['os'].' v'.$device_info['os_version'] .' from '.$location_info['city'].', '.$location_info['state'].', '.$location_info['country'].' with IP address '.$location_info['ip'];
			$additionalContent = $userAccess->current_user['user_login'];
			$db->where('userId', $userAccess->current_user['userID']);
			if ($db->update(TBL_USERS, $details)) {
				$userAccess->sendVerificationLink($userAccess->current_user['userID'], "device_approval", $additionalContent);
			}
			if ($link_back != "/index.php") {
				header("Location: " . BASE_URL . '/login/two-factor-auth.php?return=' . urlencode($link_back));
				exit();
			} else {
				header("Location: " . BASE_URL . '/login/two-factor-auth.php');
				exit();
			}
		}
	} else {
		// Login failed 
		if ($link_back != "/index.php")
			header("Location: " . BASE_URL . "/login/?error=Login%20Failed&return=" . urlencode($link_back));
		else
			header("Location: " . BASE_URL . "/login/?error=Login%20Failed");
	}
} else {
	// IF WE HAVE AN ACTIVE SESSION
	if ($userAccess->is_loggedIn === true && $userAccess->is_trustedDevice === true && !$userAccess->is_disabled) { // Device Trusted
		header("Location: " . BASE_URL . $link_back);
		exit();
	} else {
		$login_init = "";
?>
		<div class="content">
			<!-- BEGIN LOGIN FORM -->
			<?php $login_url = ($link_back != "/index.php") ? $_SERVER["PHP_SELF"] . "/?return=" . urlencode($link_back) : $_SERVER["PHP_SELF"]; ?>
			<form class="login-form" action="<?php echo $login_url; ?>" method="post">
				<h3 class="form-title">Login to your account</h3>
				<div class="alert alert-danger display-hide">
					<button class="close" data-close="alert"></button>
					<span>Enter any username and password. </span>
				</div>
				<?php
				if (isset($_GET['error'])) {
					echo '<div class="alert alert-danger"><button class="close" data-close="alert"></button><span>Error Logging In! ' . $_GET["error"] . '</span></div>';
				}
				?>
				<?php
				if (isset($_GET['msg']) && $_GET['msg'] == 1) {
					echo '<div class="alert alert-success"><button class="close" data-close="alert"></button><span>Successfully logged out!!</span></div>';
				}
				if (isset($_GET['msg']) && $_GET['msg'] == 2) {
					echo '<div class="alert alert-success"><button class="close" data-close="alert"></button><span>Session timeout. Successfully logged out!!</span></div>';
				}
				if (isset($_GET['msg']) && $_GET['msg'] == 101) {
					echo '<div class="alert alert-success"><button class="close" data-close="alert"></button><span>Password reset link has been sent to your registered mail ID. Please use the link to verify your email address.</span></div>';
				}
				?>
				<div class="form-group">
					<!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
					<label class="control-label visible-ie8 visible-ie9">Username</label>
					<div class="input-icon">
						<i class="fa fa-user"></i>
						<input class="form-control placeholder-no-fix" type="text" autocomplete="off" placeholder="Username" name="username" tabindex="1" value="<?php echo isset($_COOKIE["un"]) ? $_COOKIE["un"] : ''; ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="control-label visible-ie8 visible-ie9">Password</label>
					<div class="input-icon">
						<i class="fa fa-lock"></i>
						<input class="form-control placeholder-no-fix" type="password" autocomplete="off" placeholder="Password" name="password" tabindex="2" value="<?php echo isset($_COOKIE["un"]) ? $_COOKIE["up"] : ''; ?>" />
					</div>
				</div>
				<div class="form-actions">
					<label class="checkbox">
						<input type="checkbox" name="remember" value="1" tabindex="3" <?php echo isset($_COOKIE["un"]) ? 'checked' : ''; ?> /> Remember me </label>
					<button type="submit" class="btn btn-info pull-right" id="login-submit" tabindex="4">
						Login </button>
				</div>
				<div class="forget-password">
					<h4>Forgot your password ?</h4>
					<p>
						no worries, click <a href="javascript:;" id="forget-password">here</a>
						to reset your password.
					</p>
				</div>
			</form>
			<!-- END LOGIN FORM -->
			<!-- BEGIN FORGOT PASSWORD FORM -->
			<form class="forget-form" action="login/" method="post">
				<h3>Forget Password ?</h3>
				<div class="note note-info reset_link hide">
					<h4>Password reset link sent</h4>
					<p>Password reset link has been sent to your registered mail ID. Please use the link to verify your email address.</p>
					<button type="button" id="back-btn" class="btn btn-default"><i class="m-icon-swapleft"></i> Back </button>
				</div>
				<div class="reset_form">
					<p>
						Enter your registered e-mail address below to reset your password.
					</p>
					<div class="form-group">
						<div class="input-icon">
							<i class="fa fa-envelope"></i>
							<input class="form-control placeholder-no-fix" type="text" autocomplete="off" placeholder="Email" name="user_email" />
						</div>
					</div>
					<div class="form-actions">
						<input type="hidden" name="type" value="reset_password" />
						<button type="button" id="back-btn" class="btn btn-default back-login"><i class="m-icon-swapleft"></i> Back </button>
						<button type="submit" class="btn btn-info pull-right"> Submit </button>
					</div>
				</div>
			</form>
			<!-- END FORGOT PASSWORD FORM -->
		</div>
<?php
	}
	include_once('footer.php');
}
?>