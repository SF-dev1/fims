<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))).'/config.php');
include_once(dirname(dirname(__FILE__)).'/header-option.php');
ignore_user_abort(true); // optional
global $log, $db, $accounts, $account, $logisticPartner, $notification;

$accountId = isset($_REQUEST['accountId']) ? $_REQUEST['accountId'] : NULL;
if ($accountId){
	foreach ($accounts['shopify'] as $sp_account) {
		if ($sp_account->account_id == $accountId){
			$db->where('lp_provider_id', $sp_account->account_active_logistic_id);
			$account_logistics = $db->objectBuilder()->getOne(TBL_SP_LOGISTIC);
			$account = (object) array_merge((array) $sp_account, (array) $account_logistics);
		}
	}
	$active_lp = strtolower($account->lp_provider_name);
}

if ($active_lp && $account){
	include_once(ROOT_PATH.'/includes/class-'.$active_lp.'-client.php');
} else {
	exit('account not set');
}

$locations = $db->objectBuilder()->get(TBL_PINCODE_SERVICEABILITY, NULL);

foreach($locations as $location){
	$pincode = $location->pincode;
	$response = $logisticPartner->check_courier_serviceability('394105', $pincode, '0.2', true, '', null, false, true);
	$r_response = $logisticPartner->check_courier_serviceability($pincode, '394105', '0.2', true, '', null, true, true);
	$log->write(json_encode($response)."\n".json_encode($r_response), '/api/pincode_updater');
	$details = array();
	$r_details = array();
	if (isset($response['error']['status']) && $response['error']['status'] == 404){ // No Service
		$details['hasCOD'] = false;
		$details['hasPrepaid'] = false;

		$db->where('pincode', $pincode);
		if ($db->update(TBL_PINCODE_SERVICEABILITY, $details)){
			echo "Pincode not serviceable: ".$pincode."\n";
		}
	} elseif (isset($response['courier']['cod']) && $response['courier']['cod'] == 1) {
		$details['hasCOD'] = true;
		$details['hasPrepaid'] = true;

		$db->where('pincode', $pincode);
		if ($db->update(TBL_PINCODE_SERVICEABILITY, $details)){
			echo "Pincode COD available: ".$pincode."\n";
		}
	} elseif (isset($response['courier']['cod']) && $response['courier']['cod'] == 0) {
		$details['hasCOD'] = false;
		$details['hasPrepaid'] = true;

		$db->where('pincode', $pincode);
		if ($db->update(TBL_PINCODE_SERVICEABILITY, $details)){
			echo "Pincode COD not available but serviceable: ".$pincode."\n";
		}
	}

	if (isset($r_response['error']['status']) && $r_response['error']['status'] == 404){ // No Service
		$r_details['hasReverse'] = false;

		$db->where('pincode', $pincode);
		if ($db->update(TBL_PINCODE_SERVICEABILITY, $r_details)){
			echo "Pincode Returns not serviceable: ".$pincode."\n";
		}
	} elseif (isset($r_response['courier'])) {
		$r_details['hasReverse'] = true;

		$db->where('pincode', $pincode);
		if ($db->update(TBL_PINCODE_SERVICEABILITY, $r_details)){
			echo "Pincode Returns available: ".$pincode."\n";
		}
	}
}

$db->where('hasCOD', 1);
$codPincodes = $db->getValue(TBL_PINCODE_SERVICEABILITY, 'pincode', NULL);

$file = UPLOAD_PATH.'/cod_pincodes/cod_pincodes-'.date('Y-m-d').'.csv';
$fp = fopen($file, 'wb');
fputcsv($fp, array('Postal code'));
foreach ($codPincodes as $pincode) {
	fputcsv($fp, array($pincode));
}
fclose($fp);

// SEND EMAIL FOR UPLOADING UPDATE FILE TO SHOPIFY
$data = new stdClass();
$data->body = 'Hi, <br /><br />Please find attached COD servicable pincode attached to the mail. Update the same on shopify platform.<br /><br/>Regards,<br />FIMS Bot';
$data->subject = 'DAILY UPDATE: PINCODE SERVICABILITY | SYLVI';
$data->medium = 'email';
$to_emails = array(
	"email" => 'stylefeathers.marketing.1@gmail.com',
	'name' => 'Style Feathers Marketing'
);
$data->to[0] = (object)$to_emails;
$data->to_others->cc = 'stylefeathers.management.1@gmail.com';
$data->to_others->bcc = 'stylefeathers@gmail.com';
$data->attachments = array($file);

$mail = $notification->send($data);
if ($mail)
	echo 'Mail Sent';
else
	echo 'Unable to send Mail';


$codPincodes = $db->get(TBL_PINCODE_SERVICEABILITY, NULL, 'pincode, hasCOD');
$file = UPLOAD_PATH.'/cod_pincodes/go_kwik_pincodes-'.date('Y-m-d').'.csv';
$fp = fopen($file, 'wb');
fputcsv($fp, array('pincode', 'cod', 'upi'));
foreach ($codPincodes as $pincode) {
	fputcsv($fp, array($pincode['pincode'], ($pincode['hasCOD'] ? "true" : "false"), "true"));
}
fclose($fp);

// SEND EMAIL FOR UPLOADING UPDATE FILE TO SHOPIFY
$data->subject = 'GOKWIK DAILY UPDATE: PINCODE SERVICABILITY | SYLVI';
$data->attachments = array($file);

$mail = $notification->send($data);
if ($mail)
	echo 'Mail Sent';
else
	echo 'Unable to send Mail';

/*
// INSERT PINCODE DATE TO DB
$locations = csv_to_array('/Volumes/Data/Office/o-76f1d4f0042c7e0675ed0d8492f54227.csv');
foreach($locations as $location){
	$multiInsert[] = array(
		'pincode' => $location['pincode'],
		'city' => $location['city'],
		'state' => $location['state'],
		'hasCOD' => filter_var( $location['hasCOD'], FILTER_VALIDATE_BOOLEAN ),
		'hasPrepaid' => filter_var( $location['hasPrepaid'], FILTER_VALIDATE_BOOLEAN ),
		'hasReverse' => filter_var( $location['hasReverse'], FILTER_VALIDATE_BOOLEAN ),
		'insertDate' => date('Y-m-d H:i:s', time())
	);
}
if ($db->insertMulti(TBL_PINCODE_SERVICEABILITY, $multiInsert)){
	echo 'Successfully inserted '.count($multiInsert).' locations';
} else {
	echo 'Error adding orders '.$db->getLastError();
}*/

?>