<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

date_default_timezone_set("Asia/Kolkata");

ini_set('memory_limit', '1024M');
ini_set('max_input_time', 300);
ini_set('max_execution_time', 0);
// ini_set ( 'max_execution_time', 100);

$test = "";
if (isset($_GET['test']) && $_GET['test'] == 1)
	$test = '/debug';

$definer = array(
	// GLOBAL
	'ROOT_PATH' => dirname(__FILE__),
	'BASE_URL' => 'http://localhost/fims-live',
	'IMAGE_URL' => 'https://www.skmienterprise.com/fims',
	'LOG_PATH' => dirname(__FILE__) . '/log/',
	'TODAYS_LOG_PATH' => dirname(__FILE__) . '/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test,
	'TODAYS_NOTIFICATION_PATH' => dirname(__FILE__) . '/log/notifications/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test,
	'TODAYS_PICKLIST_PATH' => dirname(__FILE__) . '/labels/picklists/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test,
	'TODAYS_STOCKIST_PATH' => dirname(__FILE__) . '/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . '/stockist' . $test,
	'UPLOAD_PATH' => dirname(__FILE__) . '/uploads',
	// DATABASE
	'DB_PORT' => 3306,
	'DB_HOST' => 'localhost',
	'DB_USER' => 'root-local',
	'DB_PASS' => 'Ishan576!',
	'DB_NAME' => 'bas',

	// API
	'G_CLIENT_ID' => '575394445772-0h42hkcfohe1ickdvioef1utn9f9sjdl.apps.googleusercontent.com',
	'G_CLIENT_SECRET' => 'st0OG4owOueMDSm3jKXncDln',

	// AMAZON MWS
	'MWS_DEV_ID' => '968266485260',
	'MWS_ACCESS_KEY' => 'AKIAI5LLEHRWM6KE72OA',
	'MWS_ACCESS_SECRET' => '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',
);

if ($_SERVER['SERVER_NAME'] == "fims-live.com") { // LINUX DEV
	$definer['BASE_URL'] = 'http://fims-live.com';
	$definer['DB_HOST'] = 'localhost';
	$definer['DB_USER'] = 'root';
	$definer['DB_PASS'] = 'root';
}

// LIVE
if ($_SERVER['SERVER_NAME'] == "www.skmienterprise.com" || $_SERVER['SERVER_NAME'] == "skmienterprise.com") {
	$definer['LOG_PATH'] = '/var/www/skmienterprise/fims/log/';
	$definer['TODAYS_LOG_PATH'] = '/var/www/skmienterprise/fims/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_NOTIFICATION_PATH'] = '/var/www/skmienterprise/fims/log/notifications/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_PICKLIST_PATH'] = '/var/www/skmienterprise/fims/labels/picklists/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_STOCKIST_PATH'] = '/var/www/skmienterprise/fims/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . '/stockist' . $test;
	$definer['UPLOAD_PATH'] = '/var/www/skmienterprise/fims/uploads';
}

if ($_SERVER['SERVER_NAME'] == "www.skmienterprise.com" || $_SERVER['SERVER_NAME'] == "skmienterprise.com" || strpos($_SERVER['PHP_SELF'], 'fims-sand')) { // PRODUCTION
	$definer['BASE_URL'] = 'https://www.skmienterprise.com/fims';
	$definer['DB_HOST'] = 'fims.cy9ut4zgz1ip.us-east-1.rds.amazonaws.com';
	$definer['DB_USER'] = 'root';
}

if ($_SERVER['SERVER_NAME'] == "fims-live.com") { // PRODUCTION
	$definer['BASE_URL'] = 'http://fims-live.com';

	$definer['DB_HOST'] = 'localhost';
	$definer['DB_USER'] = 'root';
	$definer['DB_PASS'] = 'root';

	$definer['LOG_PATH'] = '/var/www/html/fims/log/';
	$definer['TODAYS_LOG_PATH'] = '/var/www/html/fims/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_NOTIFICATION_PATH'] = '/var/www/html/fims/log/notifications/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_PICKLIST_PATH'] = '/var/www/html/fims/labels/picklists/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . $test;
	$definer['TODAYS_STOCKIST_PATH'] = '/var/www/html/fims/log/' . date("Y", time()) . '/' . date("M", time()) . '/' . date("d", time()) . '/stockist' . $test;
	$definer['UPLOAD_PATH'] = '/var/www/html/fims/uploads';
}


if (strpos($_SERVER['PHP_SELF'], 'fims-sand')) // STAGING
	$definer['BASE_URL'] = 'https://www.skmienterprise.com/fims-sand/fims';

foreach ($definer as $key => $value) {
	if (!defined($key))
		define($key, $value);
}

include_once('.env.db_tables.php');
include_once('.env.folders.php');
include_once(ROOT_PATH . '/login-checker.php');