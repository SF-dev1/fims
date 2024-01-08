<?php
// https://shopify.dev/docs/admin-api/rest/reference/events/webhook?api%5Bversion%5D=2021-04#events - WEBHOOK LIB
// https://shopify.dev/docs/admin-api/rest/reference/orders/order#close-2021-04
// https://shopify.dev/docs/admin-api/rest/reference/shipping-and-fulfillment/fulfillment#create-2021-04
// https://shopify.dev/docs/admin-api/rest/reference/shipping-and-fulfillment/fulfillment#update_tracking-2021-04
// https://www.skmienterprise.com/fims-sand/fims/shopify/notifications-receiver.php?event=order_creation

// echo '<pre>';
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
include(dirname(dirname(__FILE__)) . '/config.php');
include(ROOT_PATH . '/includes/connectors/shopify/shopify.php');
include(ROOT_PATH . '/includes/connectors/shopify/shopify-listener.php');
global $db, $accounts, $account;

use Razorpay\Api\Api;

// $log = logger::getInstance();
$debug = false;
$test = '';
if (isset($_GET['test']) && $_GET['test'] == "1") {
	$debug = true;
	$test = '-test';
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	echo '<pre>';
}

$headers = apache_request_headers();

$rawData = file_get_contents("php://input");
$jsonData = json_decode($rawData);
if ($headers['Content-Type'] == "application/x-www-form-urlencoded") {
	$rawData = urldecode($rawData);
	parse_str($rawData, $rawData);
	$rawData = json_encode($rawData);
}

switch ($_REQUEST['event']) {
	case 'logistic_update':
		$awb = json_decode($rawData)->awb;
		$order_id = json_decode($rawData)->order_id;

		// OLD WITHOUT FULFILMENT TABLE
		// $db->join(TBL_SP_LOGISTIC.' l', 'l.account_id=a.account_id');
		// $db->where('lp_provider_id', $_REQUEST['lp_provider_id']);

		// WITHOUT FULFILMENT TABLE
		// $db->join(TBL_SP_ORDERS.' o', 'a.account_id=o.accountId');
		// $db->joinWhere(TBL_SP_ORDERS.' o', 'o.forwardTrackingId', $awb);
		// $db->join(TBL_SP_LOGISTIC.' l', 'l.lp_provider_id=o.lpProvider');

		$is_rma = (substr($order_id, 0, 4) === "RMA-" ? true : false);
		// WITH FULFILMENT TABLE
		if ($is_rma) {
			$db->join(TBL_RMA . ' r', 'a.account_id=r.accountId');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=r.rmaNumber');
		} else {
			$db->join(TBL_SP_ORDERS . ' o', 'a.account_id=o.accountId');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
		}
		$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.trackingId', $awb);
		$db->join(TBL_SP_LOGISTIC . ' l', 'l.lp_provider_id=f.lpProvider');
		$account = $db->objectBuilder()->getOne(TBL_SP_ACCOUNTS . ' a', 'a.*, l.*');
		// if ($debug){
		// 	var_dump($is_rma);
		// 	echo $db->getLastQuery();
		// 	var_dump($account);
		// }
		$sp = new shopify_listner($account);
		$account_name = str_replace(' ', '-', strtolower($account->account_name));
		$log->write(json_encode($headers), 'notification-' . strtolower($account->lp_provider_name) . '-' . $account_name . $test);

		if ($account_name) {
			// if ($debug){
			// 	error_reporting(E_ALL);
			// 	ini_set('display_errors', '1');
			// 	echo '<pre>';
			// 	echo 'Test Mode <br />';
			// 	$rawDatas = array(
			// 		'{"awb":"19041323362066","courier_name":"Delhivery Surface","current_status":"RTO INITIATED","current_status_id":15,"shipment_status":"RTO INITIATED","shipment_status_id":9,"current_timestamp":"02 06 2022 08:59:14","order_id":"SY-WA-2223-4022","sr_order_id":219058400,"etd":"2022-05-28 09:56:20","scans":[{"date":"2022-05-24 11:58:17","status":"X-UCI","activity":"Manifested - Consignment Manifested","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"5","sr-status-label":"MANIFEST GENERATED"},{"date":"2022-05-24 11:58:24","status":"FMPUR-101","activity":"Manifested - Pickup scheduled","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-24 13:06:14","status":"FMOFP-101","activity":"Manifested - Out for Pickup","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"19","sr-status-label":"OUT FOR PICKUP"},{"date":"2022-05-24 14:43:02","status":"FMEOD-152","activity":"Manifested - Shipment not ready for pickup","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"20","sr-status-label":"PICKUP EXCEPTION"},{"date":"2022-05-24 14:43:04","status":"FMPUR-101","activity":"Manifested - Pickup scheduled","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-25 10:58:00","status":"FMOFP-101","activity":"Manifested - Out for Pickup","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"19","sr-status-label":"OUT FOR PICKUP"},{"date":"2022-05-25 14:45:04","status":"X-PPOM","activity":"In Transit - Shipment picked up","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"42","sr-status-label":"PICKED UP"},{"date":"2022-05-26 00:56:20","status":"X-PIOM","activity":"In Transit - Shipment Recieved at Origin Center","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"6","sr-status-label":"SHIPPED"},{"date":"2022-05-26 00:57:03","status":"X-DWS","activity":"In Transit - System weight captured","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-26 04:25:24","status":"X-DBL1F","activity":"In Transit - Added to Bag","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-26 05:11:23","status":"X-UNEX","activity":"In Transit - Unexpected scan","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-26 07:00:50","status":"X-DBL1F","activity":"In Transit - Added to Bag","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-26 22:28:01","status":"X-DLL2F","activity":"In Transit - Added to Trip","location":"Surat_Kacholi_GW (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-27 06:59:16","status":"X-ILL2F","activity":"In Transit - Vehicle Arrived","location":"Ahmedabad_Matoda_H (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-27 08:18:14","status":"X-ILL1F","activity":"In Transit - Trip received","location":"Ahmedabad_Matoda_H (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-28 00:40:31","status":"X-DLL2F","activity":"In Transit - Added to Trip","location":"Ahmedabad_Matoda_H (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-28 06:03:52","status":"DLYLH-105","activity":"In Transit - Vehicle delayed","location":"Ahmedabad_Matoda_H (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-05-28 09:57:55","status":"X-ILL2F","activity":"In Transit - Vehicle Arrived","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-28 10:04:15","status":"X-ILL1F","activity":"In Transit - Trip received","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-28 10:31:31","status":"X-IBD3F","activity":"Pending - Shipment Received at Facility","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"18","sr-status-label":"IN TRANSIT"},{"date":"2022-05-28 13:35:57","status":"X-SC","activity":"Pending - Consignee to collect from branch","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"21","sr-status-label":"UNDELIVERED"},{"date":"2022-06-01 07:52:35","status":"ST-120","activity":"Returned - Maximum attempts reached for self collect","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-06-01 10:43:26","status":"DTUP-209","activity":"Returned - Return Center Changed","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"date":"2022-06-01 10:58:22","status":"DTUP-205","activity":"Returned - Package details changed by Delhivery","location":"Dhrangadhra_NvygRDPP_D (Gujarat)","sr-status":"NA","sr-status-label":"NA"},{"location":"Dhrangadhra_NvygRDPP_D (Gujarat)","date":"2022-06-02 08:59:02","activity":"In Transit - Added to Bag","status":"X-DBL1F","sr-status":"NA","sr-status-label":"NA"}],"is_return":0,"channel_id":1902481}'
			// 	);
			// 	foreach ($rawDatas as $rawData) {
			// 		$log->write("HTTP/1.1 200 Authorised\t".$rawData, 'notification-shiprocket-'.$account_name.$test);
			// 		$sp->process_notification('logistic/update', $rawData, 'ShipRocket');
			// 	}
			// 	return;
			// }
			header("HTTP/1.1 200 OK");
			$log->write("HTTP/1.1 200 Authorised\t" . $rawData, 'notification-' . strtolower($account->lp_provider_name) . '-' . $account_name . $test);
			$sp->process_notification('logistic/update', $rawData, strtolower($account->lp_provider_name));
		} else {
			$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, 'notification-' . strtolower($account->lp_provider_name) . '-' . $account_name . $test);
			header("HTTP/1.1 401 Unauthorised");
		}
		break;

	case 'refund_updates': // X.RAZORPAY
		echo '<pre>';
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		$db->join(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.account_id=ac.account_active_pg_id');
		$db->joinWhere(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.pg_provider_type', 'payment');
		$db->where('pg.pg_provider_accountId', $jsonData->account_id);
		$account = $db->objectBuilder()->getOne(TBL_SP_ACCOUNTS . ' ac');

		$sp = new shopify_listner($account);
		$account_name = str_replace(' ', '-', strtolower($account->account_name));
		$payment_api = new Api($account->pg_provider_key, $account->pg_provider_secret);
		$hmac_header = $headers['X-Razorpay-Signature'];

		$log_file = 'notification-razorpayx' . $account_name . $test;
		$log->write(json_encode($headers), $log_file);

		$api = $payment_api->utility->verifyWebhookSignature($rawData, $hmac_header, $account->pg_provider_hash);
		var_dump($api);
		try {
			$payment_api->utility->verifyWebhookSignature($rawData, $hmac_header, $account->pg_provider_hash);
			echo 'Authorised';
		} catch (Exception $e) {
			var_dump($e);
			echo 'Unauthorised';
		}
		exit;
		if ($sp->verify_payment_webhook($rawData, $hmac_header)) {
			$log->write("HTTP/1.1 200 Authorised\t" . $rawData, $log_file);
			header("HTTP/1.1 200 OK");
			echo 'Authorised';
		} else {
			$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, $log_file);
			header("HTTP/1.1 401 Unauthorised");
			echo 'Unauthorised';
		}
		break;

		// case 'order_creation':
	case 'order_update':
		$db->where('account_store_url', $headers['X-Shopify-Shop-Domain']);
		$account = $db->objectBuilder()->getOne(TBL_SP_ACCOUNTS);
		$sp = new shopify_listner($account);
		$account_name = str_replace(' ', '-', strtolower($account->account_name));

		$log->write(json_encode($headers), 'notification-shopify-' . $account_name . $test);
		$hmac_header = $headers['X-Shopify-Hmac-Sha256'];
		$event = $headers['X-Shopify-Topic'];

		// if ($headers['X-Shopify-Order-Id'] == "4363747819729"){
		// 	header("HTTP/1.1 200 OK");
		// 	$sp->process_notification($event, $rawData);
		// 	break;
		// }

		if ($sp->verify_shopify_webhook($rawData, $hmac_header) || $debug) {
			header("HTTP/1.1 200 OK");
			$log->write("HTTP/1.1 200 Authorised\t" . $rawData, 'notification-shopify-' . $account_name . $test);

			$sp->process_notification($event, $rawData);
		} else {
			$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, 'notification-shopify-' . $account_name . $test);
			header("HTTP/1.1 401 Unauthorised");
		}
		break;

	case 'product_update':
		$db->where('account_store_url', $headers['X-Shopify-Shop-Domain']);
		$account = $db->objectBuilder()->getOne(TBL_SP_ACCOUNTS);
		$sp = new shopify_listner($account);
		$account_name = str_replace(' ', '-', strtolower($account->account_name));

		$log->write(json_encode($headers), 'notification-shopify-products-' . $account_name . $test);
		$hmac_header = $headers['X-Shopify-Hmac-Sha256'];
		$event = $headers['X-Shopify-Topic'];

		if ($sp->verify_shopify_webhook($rawData, $hmac_header)) {
			header("HTTP/1.1 200 OK");
			$log->write("HTTP/1.1 200 Authorised\t" . $rawData, 'notification-shopify-products-' . $account_name . $test);

			$sp->process_notification($event, $rawData);
		} else {
			$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, 'notification-shopify-products-' . $account_name . $test);
			header("HTTP/1.1 401 Unauthorised");
		}
		break;

	case 'razorpay_payments':
		echo '<pre>';
		error_reporting(E_ALL);
		ini_set('display_errors', '1');

		// var_dump($jsonData);
		$db->join(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.account_id=ac.account_active_pg_id');
		$db->joinWhere(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.pg_provider_type', 'payment');
		$db->where('pg.pg_provider_accountId', $jsonData->account_id);
		$account = $db->objectBuilder()->getOne(TBL_SP_ACCOUNTS . ' ac');

		$sp = new shopify_listner($account);
		$account_name = str_replace(' ', '-', strtolower($account->account_name));
		$payment_api = new Api($account->pg_provider_key, $account->pg_provider_secret);
		$hmac_header = $headers['X-Razorpay-Signature'];

		$log_file = 'notification-shopify-rp_payments-' . $account_name . $test;
		$log->write(json_encode($headers), $log_file);

		if ($sp->verify_payment_webhook($rawData, $hmac_header)) {
			header("HTTP/1.1 200 OK");
			$log->write("HTTP/1.1 200 Authorised\t" . $rawData, $log_file);
		} else {
			$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, $log_file);
			header("HTTP/1.1 401 Unauthorised");
		}
		break;

	case 'gokwik_refund':
		echo '<pre>';
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		$log->write(json_encode($headers), 'notification-gokwik' . $test);
		$log->write($rawData, 'notification-gokwik' . $test);
		header("HTTP/1.1 200 OK");
		break;
}
