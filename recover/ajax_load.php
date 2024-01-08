<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	global $db, $userAccess, $current_user;

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
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}
