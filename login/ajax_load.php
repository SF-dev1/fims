<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	global $db, $userAccess, $current_user, $sms;

	switch ($_REQUEST['action']) {
			// FORGET PASSWORD
		case 'resend_verification':
			$keys = array_keys($_POST);
			$db->where($keys[1], $_POST[$keys[1]]); // IDENTIFIERS SHOULD BE ALWAYS AT POSITION 1
			$userID = $db->getValue(TBL_USERS, 'userID');

			$type = isset($_POST['type']) ? $_POST['type'] : "";

			if (array_key_exists("user_email", $_POST))
				$details['user_activation_key'] = $userAccess->generateValidationString('email', $type);
			else
				$details['user_otp'] = $userAccess->generateValidationString('mobile');

			$db->where('userID', $userID);
			if ($db->update(TBL_USERS, $details)) {
				$userAccess->sendVerificationLink($userID, $type);
				$return = array('type' => 'success');
			} else {
				$return = array('type' => 'error', 'error' => $db->getLastError());
			}

			echo json_encode($return);
			break;

			// FORGET PASSWORD
		case 'check_email_registered':
			$db->where('user_email', $_POST['user_email']);
			if ($db->has(TBL_USERS))
				echo 'true';
			else
				echo 'false';
			break;

			// VERIFY MOBILE
		case 'verify_mobile':
			$user_mobile = $_POST['user_mobile'];
			$db->where('user_mobile', $user_mobile);
			$db->where('user_otp', $_POST['user_otp']);
			if ($db->has(TBL_USERS)) {
				$details = array(
					'is_mobile_verified' => 1,
					'user_otp' => NULL
				);
				$db->where('user_mobile', $user_mobile);
				if ($db->update(TBL_USERS, $details))
					$return = array('type' => 'success', 'msg' => 'Mobile verified successfully');
				else
					$return = array('type' => 'error', 'msg' => 'Error verifying mobile. Please retry', 'error' => $db->getLastError());
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid OTP');
			}

			echo json_encode($return);
			break;

		case 'update_profile':
			if (($_POST['display_name'] === $_POST['current_display_name']) && ($_POST['user_email'] === $_POST['current_user_email']) && ($_POST['user_mobile'] === $_POST['current_user_mobile'])) {
				return;
			} else {
				$db->where('userID', $_POST['userID']);
				if ($db->update(TBL_USERS, array('display_name' => $_POST['display_name'])))
					$return = array('type' => "success", "msg" => "Profile Updated Successfully");
				else
					$return = array('type' => "success", "msg" => "Unable to update profile");

				echo json_encode($return);
			}
			break;

		case 'update_password':
			$userID = $_POST['userID'];
			$user = $db->getOne(TBL_USERS);

			if ($user['user_pass'] != $_POST['current_pass']) {
				$return = array('type' => 'error', 'msg' => 'Current Password Mismatch. Please enter correct current password');
			} else {
				$db->where('userID', $userID);
				if ($db->update(TBL_USERS, array('user_pass' => $_POST['user_pass'])))
					$return = array('type' => 'success', 'msg' => 'Password successfully updated');
				else
					$return = array('type' => 'success', 'msg' => 'Error updating password');
			}

			echo json_encode($return);
			break;

		case 'update_settings':
			$details = $_POST;
			$userID = $_POST['userID'];

			unset($details['action']);
			unset($details['userID']);

			$db->where('userID', $userID);
			if ($db->update(TBL_USERS, array("user_settings" => json_encode($details))))
				$return = array('type' => 'success', 'msg' => 'Settings successfully updated');
			else
				$return = array('type' => 'success', 'msg' => 'Error updating settings');

			echo json_encode($return);
			break;

			// LOGIN - 2FA
		case 'resend_device_otp':
			$details['user_otp'] = $userAccess->generateValidationString('mobile');
			$db->where('userId', $_POST['uid']);
			if ($db->update(TBL_USERS, $details)) {
				$userAccess->sendVerificationLink(1, "device_approval", "Sagar"); // ONLY TO ME!!!
				$return = array('type' => 'success');
			} else {
				$return = array('type' => 'error', 'error' => $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'verify_device_otp':
			$token = $_POST['access_otp'];
			$userId = $_POST['uid'];
			$db->where('userID', $userId);
			$db->where('user_otp', $token);
			if ($db->has(TBL_USERS)) {
				$return = array('type' => 'success', 'msg' => 'Successfully verified OTP');
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid OTP');
			}
			echo json_encode($return);
			break;

			// EXTEND SESSION
		case 'keepalive':
			var_dump('keepalive');
			break;
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}
