<?php
// error_reporting(-1);
// ini_set('display_errors',  '1');
// echo '<pre>';
include_once(ROOT_PATH . '/init_functions.php');
include_once(ROOT_PATH . '/includes/class-mysqlidb.php');
include_once(ROOT_PATH . '/includes/class-mailer.php');
include_once(ROOT_PATH . '/includes/class-user-access.php');
include_once(ROOT_PATH . '/includes/class-logger.php');
include_once(ROOT_PATH . '/includes/class-notification.php');

$GLOBALS['db'] = new MysqliDb(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$GLOBALS['log'] = logger::getInstance();
// $GLOBALS['mailer'] = mailer::getInstance();
// $GLOBALS['sms'] = sms::getInstance();
// $GLOBALS['whatsApp'] = whatsApp::getInstance();
$GLOBALS['notification'] = notification::getInstance();
$GLOBALS['userAccess'] = userAccess::getInstance();
$GLOBALS['current_url'] = curPageURL();
$userAccess->login_check();

$bypass_pages = array(
	'notifications-receiver',
	// 'test.php',
	'set_min_price.php',
	'get_eligible_listings.php',
	'get_listing_ids.php',
	'send_sms.php',
	'process_fbf',
	'print',
	'verify',
	'reset_password',
	'brand-authorisation',
	'recover/ajax_load.php',
	'flex_process_request.php',
	'monthly_report',
	'rate_card.php',
	// 'two-factor-auth.php',
);

$return_back = 'return=' . str_replace(BASE_URL, "", $current_url);
if (strpos($return_back, '?return=/logout.php') !== FALSE) {
	$return_back = str_replace("return=/login/?return=/logout.php?return=", "", $return_back);
	header("Location: " . BASE_URL . "/login/?msg=2&return=" . urlencode($return_back));
	exit;
}

if (strpos($return_back, 'login%2F') !== FALSE || strpos($return_back, 'login/') !== FALSE) {
	$return_back = "";
}

$is_byPassPage = false;
foreach ($bypass_pages as $page) {
	if (strpos($current_url, $page) !== FALSE) {
		$is_byPassPage = true;
		continue;
	}
}

// if (strpos(curPageURL(), 'login/index.php') === FALSE || strpos(curPageURL(), 'two-factor-auth.php') === FALSE){
if (strpos($current_url, 'login/') === FALSE) {
	if ($is_byPassPage) { // BYPASS LOGIN FOR CRON AND NOTIFICATION PAGES
		$GLOBALS['current_user'] = $userAccess->current_user;
		include_once(ROOT_PATH . '/functions.php');
	} else {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $userAccess->is_loggedIn === false) {
			echo json_encode(array('type' => 'error', 'redirectUrl' => $_SERVER['HTTP_REFERER']));
			exit();
		}
		if ($userAccess->is_loggedIn === true && $userAccess->is_trustedDevice === true && !$userAccess->is_disabled) {
			$GLOBALS['current_user'] = $userAccess->current_user;
			include_once(ROOT_PATH . '/functions.php');
		} else {
			if ($userAccess->is_disabled) {
				$userAccess->sec_session_destroy();
				header("Location: " . BASE_URL . "/login/?error=User Disabled");
			} else {
				header("HTTP/1.1 401 Unauthorized");
				header("Location: " . BASE_URL . "/login/?" . $return_back);
				exit;
			}
		}
	}
}
