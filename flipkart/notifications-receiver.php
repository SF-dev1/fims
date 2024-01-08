<?php
// https://www.skmienterprise.com/fims/flipkart/notifications-receiver.php?account=sandbox
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
include(dirname(dirname(__FILE__)) . '/config.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart-listener.php');
global $accounts;

// $log = logger::getInstance();
$debug = false;
if (isset($_GET['test']) && $_GET['test'] == "1")
	$debug = true;

$accounts['flipkart'] = $db->ObjectBuilder()->get('bas_fk_account');

$account_key = (int)$_GET['account'];
if ($_GET['account'] == "sandbox") {
	$account_key = 999;
}
foreach ($accounts['flipkart'] as $a_key => $account) {
	if ($account_key == $account->account_id)
		$account_key = $a_key;
}

$account = $accounts['flipkart'][$account_key];
$fk = new flipkart_listner($account, $account->sandbox, true);
if ($_GET['test'] == "1" && $account->sandbox) {
	$sandbox = $account->sandbox;
	$token = $fk->check_access_token(true);
	var_dump($token);
	exit;
}
include(ROOT_PATH . '/flipkart/functions.php');
$fk->payments->order_date = date('Y-m-d', time()); // Current Date

$test = '';
if ($debug)
	$test = '-test';
// $log->logfile = TODAYS_NOTIFICATION_PATH.'/notification-'.$account->account_name.$test.'.log';
// $log->write(json_encode($account));

$headers = apache_request_headers();
if ($debug)
	$headers = json_decode('{"Cookie":"ci_session_dropship_development=gQn9tvNOblmsn7T2z5XnG21%2FLNbpOHDchG5v90iR%2FjCMz05vkzRl5wx4Y%2Fsar8w8KbGrGraBiWA%2BnWE%2BUmmzoRpyc3hFoLpzWiN%2B0rGbwCT65DxwezVIweu4Z67%2FexPQ%2Frp31ASOde3NaykXne6BUpnyYlLLqJ4RiwgLse7r3B9nsS0uAP4F8CddROc%2FqgUsbyweQLDDSBKm9wQ03mD0ic4GtXhJQWvxFPMEt33m67jBA%2BCLCIZky6EcL5qzgU6XhpZZvie0%2BKXMlZbjGytZxiMrUPjx%2FzanHkdn73c7tzVAgoXnjje0Qc9Ft%2BPza4qjrgOS0u0ZatJIxh1flmDRbRxQ9Ui6yRqfyo%2FAmjkIY1FsU2aix7IAbApBya%2FoLGxUvLSMs9gx91UxJJW8EVpOUPY7PcFSDFNZ6Hfu7yMizeUFEealIlwJ7v7eFX1DS8fp6PdlKqCzDfn88XCVphODLCuawOrk7p286ZEjVlyGBYORPCf%2BaQArHaBHPvHTRA8t6LugMoATGfJzr4BX7hYKRieAG0I4LYWEEf48LZRaNtUrslLkmHJ7Vme7zzt8F2vO3QSsWq4oTuAF%2Bc5SYuDItw%3D%3D; ci_session=1df04a32a90647df340a5707b53e684e9007ccd4, fims_sid=peh4eqb3g8g3dhijdgnbihaia8","Accept":"*\/*","X-Forwarded-Proto":"http","X-REQUEST-ID":"3a57a017-4c97-4ab3-a2da-57da584e9770","FK-Client-IP":"10.50.2.138","Via":"elb_2.0","Authorization":"Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJodHRwOi8vMTAuMjQuMC4yMDk6ODAiLCJhdWQiOiJodHRwOi8vMTAuMjQuMS4xMzg6ODAiLCJncmFudF90eXBlIjoiY2xpZW50X2NyZWRlbnRpYWxzIiwic2NvcGUiOlsidXNlci5wcm9maWxlIl0sImlzcyI6Imh0dHA6Ly8xMC4yNC4wLjE2NSIsImV4cCI6MTYyNzk3Mzk1NCwiaWF0IjoxNjI3OTcwMzU0LCJqdGkiOiJhOTM1NWZmNC1lZDlmLTRmZTAtODYwZS0wZWIyZjMwMzgzN2MiLCJjbGllbnRfaWQiOiJodHRwOi8vMTAuMjQuMC4yMDk6ODAifQ.o2AgLXRJ1gaVoCadqkO9M0yG22QRJoJvLXmEuVVkMG7Ud5AiQJg6AFzuU0_ul6kvD1BHw3xAp9HO8t04MWH_sd_3Vg50Yi8OqZem4u0sxD-vUfo2_PIBfgdrK0QcBjZqpcp9_njO5bLNDe9YYHoqX_wmBTHaNKQtKtJ-uOeQJ1Xvf4jM0ofMDgtdpnG0x5bF_R83zxl493WpZ2XU43PMwmA3Q7Ec3w-cvo7K1a9bfXWmHoyzxDaGFRrqDET57qsVhV_gvuujG3543XjaFFdKahJqHk8IheDVdenjV62B7X8EQdVog1FNqHLhBo4xmblopQffPT4Zj1y8HOw-3mtrLQ","X_Date":"Tue, 03 Aug 2021 11:51:47 GMT+05:30","X_Authorization":"FKLOGIN YjQyOTUxNjk1NjMyMTcxYjdiMzI2ODQ4MjA0ODgzNjBhMTgwOmJjNmJlY2MyMDkwZjMyMTVjYWYzMzgwZjEwYTM3YzgyODM2ZDE2MDE=","x-e-id":"10.52.98.37","X-Forwarded-For":"10.50.2.138, 10.50.3.121","X-PROXY-USER":"sfs_order_notifications","x-elb-id":"10.52.98.37","X-E-CLIENT":"10.50.3.121","Content-Type":"application\/json","Content-Length":"1332","Host":"www.skmienterprise.com","Connection":"Keep-Alive","User-Agent":"ManagerApplication (HTTP_CLIENT)","Cookie2":"$Version=1","Accept-Encoding":"gzip,deflate"}', true);
$log->write(json_encode($headers), 'notification-' . $account->account_name . $test);

$rawData = file_get_contents("php://input");
if ($debug) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	echo '<pre>';
	echo 'Test Mode <br />';
	if ($fk->fk_verify($headers)) {
		echo "HTTP/1.1 200 Authorised";
		$rawData = '{"eventType":"shipment_created","source":"flipkart","locationId":"LOC914d90fdeade4be8a84a7894c19d70e3","version":"v3","timestamp":"2021-08-03T11:51:44+05:30","attributes":{"dispatchByDate":"2021-08-03T16:00:00.000+05:30","dispatchAfterDate":"2021-08-03T11:56:34.000+05:30","updatedAt":"2021-08-03T11:51:45.000+05:30","hold":false,"mps":false,"subShipments":[{"subShipmentId":"SS-1","packages":[{"packageId":"PKGWATF2DCK8GH5NZPPU5TH3H","packageTitle":null,"packageSku":"SKMEI-1213-GRN-ORG-101","dimensions":{"length":25,"breadth":5,"height":15,"weight":0.15}}]}],"packagingPolicy":"DEFAULT","orderItems":[{"orderItemId":"12248267400772201","orderId":"OD122482674007722000","cancellationGroupId":"grp12248267400772201","orderDate":"2021-08-03T11:51:34.000+05:30","paymentType":"COD","status":"APPROVED","quantity":1,"fsn":"WATF2DCK8GH5NZPP","sku":"SKMEI-1213-GRN-ORG-101","listingId":"LSTWATF2DCK8GH5NZPPU5TH3H","hsn":"6104","title":"SKMEI 1213 Army Green Chronograph Digital Digital Watch  - For Men","packageIds":["PKGWATF2DCK8GH5NZPPU5TH3H"],"priceComponents":{"sellingPrice":452.0,"totalPrice":501.0,"shippingCharge":49.0,"customerPrice":501.0,"flipkartDiscount":0.0},"serviceProfile":"Seller_Fulfilment","is_replacement":false}],"forms":[],"dispatchLocation":"SURAT : 395006"},"shipmentId":"32813d5e-527f-4244-a32c-992a179bc11d"}';
		// $rawData = '{"eventType":"return_expected_date_changed","source":"flipkart","locationId":"LOCa3361d2b3342484a8b144bc2c34b8e20","timestamp":"2019-04-10T20:09:41+05:30","attributes":{"returnItems":[{"orderItemId":"11517445665925700","expectedDate":"2019-05-10T20:09:39+05:30"}]},"returnId":"10201517624615360867"}';
		// $rawData = '{"eventType":"shipment_cancelled","source":"flipkart","locationId":"LOCa3361d2b3342484a8b144bc2c34b8e20","version":"v3","timestamp":"2019-04-22T11:06:34+05:30","attributes":{"status":"CANCELLED","orderItems":[{"orderItemId":"11527659580943500","quantity":1,"reason":"cancellation_requested","subReason":"purchased_mistake"}]},"shipmentId":"b27c442c-8dec-46a4-a2d7-01dc2b1284ab"}';
		$log->write("HTTP/1.1 200 Authorised \n\n" . $rawData, 'notification-' . $account->account_name . $test);
	} else {
		echo "HTTP/1.1 401 Unauthorised";
	}

	$fk->process_notification($rawData);
	return;
}

if ($fk->fk_verify($headers)) {
	header("HTTP/1.1 200 OK");
	$log->write("HTTP/1.1 200 Authorised\t" . $rawData, 'notification-' . $account->account_name . $test);

	$fk->process_notification($rawData);
} else {
	header("HTTP/1.1 401 Unauthorised");
	$log->write("HTTP/1.1 401 Unauthorised\t" . $rawData, 'notification-' . $account->account_name . $test);
}
