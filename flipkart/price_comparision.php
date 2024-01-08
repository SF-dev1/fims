<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
echo '<pre>';

$update = true;
$debug = false;
if ($_SERVER['HTTP_HOST'] != 'www.skmienterprise.com') {
	$update = false;
	$debug = true;
	echo '<pre>';
}
$update = false;
$debug = true;

include_once(dirname(dirname(__FILE__)) . '/config.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
ini_set('max_execution_time', 1800);

global $db, $accounts;

$data = array();

$account_key = "";
foreach ($accounts['flipkart'] as $a_key => $account) {
	// Declare global scopes
	$GLOBALS['current_account'] = $accounts['flipkart'][$a_key]; // Shivansh - 2, Sylvi - 1, Shreehan - 0
	$GLOBALS['other_accounts'] = $other_accounts = array();
	$GLOBALS['comm_rate'] = 0;
	$GLOBALS['product'] = "";
	$GLOBALS['fk'] = "";

	// Initiate all dependencies
	$fk = new connector_flipkart((object)$current_account);
	$fk->payments->order_date = date('Y-m-d', time()); // Current Date
	$log = logger::getInstance();
	$start = time();

	include_once(ROOT_PATH . '/flipkart/functions.php'); // will add this file after $current_account is found and $fk is initiated
	$log->logfile = TODAYS_LOG_PATH . '/pricing-comparision.log';

	$limit = '';

	$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
	$db->join(TBL_FK_ACCOUNTS . " a", "p.account_id=a.account_id", "LEFT");
	$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
	$db->joinWhere(TBL_PRODUCTS_MASTER . " pm", "pm.is_active", 1);
	$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
	$db->joinWhere(TBL_PRODUCTS_COMBO . " pc", "pc.is_active", 1);
	$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
	// $db->where('p.mp_id', 'WATEMV67JHSTSH3Y');
	$db->where('p.marketplace', 'flipkart');
	$db->where('p.account_id', $current_account->account_id);
	// $db->where('p.auto_update', 1);
	$db->orderBy('p.sku', 'ASC');
	$listings = $db->get(TBL_PRODUCTS_ALIAS . " p", NULL, array('p.*', 'c.categoryName as category', 'pb.brandName', 'COALESCE(pc.is_active, pm.is_active, 0) as is_active'));
	$listings_count = array_count_values(array_column($listings, 'is_active'))[1];
	echo $db->getLastQuery();
	var_dump($listings);
	exit;

	if ($listings) {
		$c = 1;
		foreach ($listings as $listing) {
			if (!$listing['is_active']) {
				// $data[$fsn] = array(
				// 	'shreehan' => array('price' => 'INACTIVE LISTING'),
				// 	'sylvi' => array('price' => 'INACTIVE LISTING'),
				// 	'shivansh' => array('price' => 'INACTIVE LISTING'),
				// );
				continue;
			}

			$fsn = trim($listing['mp_id']);
			$sku = trim($listing['sku']);
			$gst = 5; // set to global on each instance

			if (isset($data[$fsn]))
				continue;

			$stock_notice = "";

			if (empty($fsn) || empty($sku)) {
				$log->write('Alias ID ' . $listing['alias_id'] . ' :: FSN or SKU missing!!');
				continue;
			}

			$log->write($c . '] Initiated FSN: ' . $fsn . ' SKU:' . $sku);
			$c++;

			$delivery_charges = get_max_shipping_charges();

			// Define GLOBAL $product after the conformation of fulfilled stock status.
			$product = $listing;
			$item_details = $fk->get_products_details($sku);
			var_dump($item_details);
			$item_details = $item_details->available->{$sku};
			if (isset($item_details->listing_status) && ($item_details->listing_status == 'INACTIVE' || $item_details->listing_status == 'ARCHIVED')) {
				// echo $item_details->listing_status.' listing with FSN '.$fsn .' with SKU '.$sku.' skipped';
				$log->write($item_details->listing_status . ' listing with FSN ' . $fsn . ' with SKU ' . $sku . ' skipped');
				continue;
			}

			try {
				$min_prices = get_minimum_comp_price($fsn);
			} catch (Exception $e) {
				$ex = 'Unable to process request for FSN ' . $fsn . ' with SKU ' . $sku;
				$log->write($ex);
				throw new Exception($ex, 1);
			}

			// var_dump($min_prices);

			// print_r(array_column($min_prices, 'seller')); 
			// if(in_array('ShreehanEnterprise', array_column($min_prices, 'seller'))) { // search value in the array
			$shreehan_key = array_search('ShreehanEnterprise', array_column($min_prices['all_data'], 'seller'));
			$sylvi_key = array_search('Sylvi', array_column($min_prices['all_data'], 'seller'));
			$shivansh_key = array_search('ShivanshEntp', array_column($min_prices['all_data'], 'seller'));

			$data[$fsn] = array(
				'shreehan' => $min_prices['all_data'][$shreehan_key],
				'sylvi' => $min_prices['all_data'][$sylvi_key],
				'shivansh' => $min_prices['all_data'][$shivansh_key],
			);

			// var_dump($data);
		}
	}
}

// var_dump($data);

$echo = "FSN \tShreehan \t\t\tSylvi \t\t\tShivansh \t\t\t\n";
$echo .= "\tPrice\tFulfilled\tOffer\tPrice\tFulfilled\tOffer\tPrice\tFulfilled\tOffer\t\n";
foreach ($data as $data_key => $data_value) {
	// var_dump($data_key);
	// var_dump($data_value);
	$echo .= $data_key . "\t " . $data_value['shreehan']['price'] . "\t" . $data_value['shreehan']['fulfilled'] . "\t" . $data_value['shreehan']['offer'] . "\t";
	$echo .= $data_value['sylvi']['price'] . "\t" . $data_value['sylvi']['fulfilled'] . "\t" . $data_value['sylvi']['offer'] . "\t";
	$echo .= $data_value['shivansh']['price'] . "\t" . $data_value['shivansh']['fulfilled'] . "\t" . $data_value['shivansh']['offer'] . "\n";
}
echo $echo;
