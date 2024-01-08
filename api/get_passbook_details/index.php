<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once('./functions.php');
include_once('../../.env.db_tables.php');
// global $log, $db;

// if(isset($_GET['account_id'])){

// } else {
//     $db->join(TBL_SP_ACCOUNTS.' a', 'a.account_active_logistic_id=l.lp_provider_id' );
//     // $db->joinWhere(TBL_SP_ACCOUNTS.' a', 'a.account_status', '1');
//     $accounts = $db->get(TBL_SP_LOGISTIC.' l', NULL, 'a.account_id, l.');
// }
// var_dump($accounts);
// exit;

$account_id = "57162236113";
$fromDate = $_GET["fromDate"];
$toDate = $_GET["toDate"];

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://apiv2.shiprocket.in/v1/auth/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => '{"email":"support@stylefeathers.com","password":"Ishan576!"}',
    CURLOPT_HTTPHEADER => array(
        'authority: apiv2.shiprocket.in',
        'accept: application/json',
        'content-type: application/json',
        'no-auth: True',
        'origin: https://app.shiprocket.in',
        'pragma: no-cache',
        'referer: https://app.shiprocket.in/'
    ),
));

$response = curl_exec($curl);
$token = json_decode($response)->token;
if ($token) {
    $page = 1;
    $res = array();
    do {
        $res[] = getPassbookData($curl, $token, $page, $fromDate, $toDate);
        $page++;
    } while ($page <= $res[0]->meta->pagination->total_pages);
    curl_close($curl);
    insert_remittance_charge($res);
}
