<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once(dirname(dirname(__FILE__)) . '/header-option.php');
global $log, $sms, $whatsApp;

$log->write(json_encode($_REQUEST), '/api/ivr');

$site = $_REQUEST['site'];
$type = $_REQUEST['type'];
$to = $_REQUEST['to'];
$name = $_REQUEST['name'];

switch ($type) {
	case 'shopping':
		// $sms->
		// Hi {{1}}, Please visit {{2}} to shop for our products. Let us know if you need any assistance to shop. Regards Sylvi.in
		$post_params = array(
			'to' => trim($to), // SHOULD INCLUDE ISD CODE and +
			'name' => $name,
			'params' => array(
				$name,
				"0.00",
				"SFOD000TEST",
				"30 January 2022",
				"REFUND REFERENCE TEST"
			)
		);
		$whatsApp->setSite($site);
		$response = $whatsApp->sendMessage('refund', $post_params);
		var_dump($response);
		break;

	case 'tracking':
		// $sms->
		// $whastapp->
		break;

	case 'warranty':
		// $sms->
		// $whastapp->sendMessage('warranty', $data, $to);
		break;
}
