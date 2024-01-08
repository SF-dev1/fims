<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
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


if (!isset($_GET['account_id']) || (int)$_GET['account_id'] == 0)
	exit('No account requested');

$account_id = (int)$_GET['account_id'];
$account_key = "";
foreach ($accounts['flipkart'] as $a_key => $account) {
	if ($account_id == $account->account_id)
		$account_key = $a_key;
	else
		$other_accounts[] = $account->account_name;
}

// Declare global scopes
$GLOBALS['current_account'] = $accounts['flipkart'][$account_key]; // Shivansh - 2, Sylvi - 1, Shreehan - 0

// Initiate all dependencies
$fk = new connector_flipkart((object)$current_account);

$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
$db->join(TBL_FK_ACCOUNTS . " a", "p.account_id=a.account_id", "LEFT");
$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
$db->joinWhere(TBL_PRODUCTS_MASTER . " pm", "pm.is_active", 1);
$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
$db->joinWhere(TBL_PRODUCTS_COMBO . " pc", "pc.is_active", 1);
$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
// $db->where('(p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ? OR p.mp_id = ?)', array("WATFF82GHVRTGBMA", "WATF2FDU5N3BRRDZ", "WATEJ5Z3WCDBFAH7", "WATEH9RK9USHTHCX", "WATF4SG9XJ3W6MEN", "WATEXPAZVYBH4CWR", "WATEH97ENGZB5HFQ", "WATF5KGUBGWHGBZB", "WATF3HFBBP7ATBP2", "WATFYTTR5K3HHPGF", "WATEX2WEMGBYNWED", "WATEHW85FMRRDEYN", "WATFFHBZAA2QMJ72", "WATF4VRGJWQ9TCY9", "WATEHW859MVCKYER", "WATEY6HK2GQ5DXN2", "WATF3BGYKNYWRKWA", "WATF3H9Z2NQVZFG2", "WATFYHSN94JBGDYT", "WATES62ZGT8CWJTH", "WATFFHBZEZZXPZRY", "WATENHF7PF3JTTHM", "WATERQVBVQUCXC9B", "WATEHS7HFYMNNY4Q", "WATEGCVE3N8FCBSV", "WATEW3Q2JKWC29HW", "WATEYMKNYYH6W6WE", "WATF3K899WR9HWZU", "WATES62QT4DEUJZM", "WATEPJFGHZZ6ZHZU", "WATE54HWHVBZYZ6Q", "WATF3MNUFAQZZBVH", "WATFYUD9EQHVJ5Z5", "WATF5QE2ZYQ2BC5Z", "WATF33NGZRGSZZCZ", "WATENFGK6ZDHHJRB", "WATEP3CASFN8J5FF", "WATEGCVECFAMXYVY", "WATEGCVE2PHZZERZ", "WATEHS7GVZCYXCJV", "WATEPK3M7FGNYQGP", "WATEVYK5CEJEZRYR", "WATETFCNF73EWTPW", "WATF4HQWDKVXRATZ", "WATEHUU4DGHTPZZZ", "WATEBGHFFYQZS35F", "WATEAZYV7HPQASHG", "WATES63YUHVHJH8Z", "WATEY6QAMCPQWZZT", "WATEVYM4XKGZHVBP", "WATEHS7WGAJ6HGYS", "WATEHS7WTKS5UHQC", "WATEKM9QPGH3JX5Y", "WATEQQYXQJHZFJKW"));
// $db->where('p.mp_id', 'WATEAZYVVVGSAKAD');
$db->where('pb.brandName', 'Skmei');
$db->where('p.marketplace', 'flipkart');
$db->where('p.account_id', $current_account->account_id);
// $db->where('p.auto_update', 1);
$db->orderBy('p.sku', 'ASC');
$listings = $db->get(TBL_PRODUCTS_ALIAS . " p", NULL, array('p.*', 'c.categoryName as category', 'pb.brandName', 'COALESCE(pc.is_active, pm.is_active, 0) as is_active'));
// echo $db->getLastQuery();
$listings_count = array_count_values(array_column($listings, 'is_active'))[1];
// var_dump($listings);
// var_dump($listings_count);
// exit;

// $fsps = array("WATFF82GHVRTGBMA" => "532", "WATF2FDU5N3BRRDZ" => "532", "WATEJ5Z3WCDBFAH7" => "532", "WATEH9RK9USHTHCX" => "532", "WATF4SG9XJ3W6MEN" => "532", "WATEXPAZVYBH4CWR" => "532", "WATEH97ENGZB5HFQ" => "532", "WATF5KGUBGWHGBZB" => "532", "WATF3HFBBP7ATBP2" => "532", "WATFYTTR5K3HHPGF" => "532", "WATEX2WEMGBYNWED" => "532", "WATEHW85FMRRDEYN" => "532", "WATFFHBZAA2QMJ72" => "532", "WATF4VRGJWQ9TCY9" => "532", "WATEHW859MVCKYER" => "532", "WATEY6HK2GQ5DXN2" => "532", "WATF3BGYKNYWRKWA" => "532", "WATF3H9Z2NQVZFG2" => "532", "WATFYHSN94JBGDYT" => "532", "WATES62ZGT8CWJTH" => "532", "WATFFHBZEZZXPZRY" => "532", "WATENHF7PF3JTTHM" => "532", "WATERQVBVQUCXC9B" => "532", "WATEHS7HFYMNNY4Q" => "532", "WATEGCVE3N8FCBSV" => "532", "WATEW3Q2JKWC29HW" => "532", "WATEYMKNYYH6W6WE" => "532", "WATF3K899WR9HWZU" => "532", "WATES62QT4DEUJZM" => "532", "WATEPJFGHZZ6ZHZU" => "532", "WATE54HWHVBZYZ6Q" => "532", "WATF3MNUFAQZZBVH" => "532", "WATFYUD9EQHVJ5Z5" => "532", "WATF5QE2ZYQ2BC5Z" => "532", "WATF33NGZRGSZZCZ" => "532", "WATENFGK6ZDHHJRB" => "532", "WATEP3CASFN8J5FF" => "532", "WATEGCVECFAMXYVY" => "532", "WATEGCVE2PHZZERZ" => "532", "WATEHS7GVZCYXCJV" => "532", "WATEPK3M7FGNYQGP" => "532", "WATEVYK5CEJEZRYR" => "532", "WATETFCNF73EWTPW" => "532", "WATF4HQWDKVXRATZ" => "532", "WATEHUU4DGHTPZZZ" => "532", "WATEBGHFFYQZS35F" => "532", "WATEAZYV7HPQASHG" => "532", "WATES63YUHVHJH8Z" => "532", "WATEY6QAMCPQWZZT" => "532", "WATEVYM4XKGZHVBP" => "532", "WATEHS7WGAJ6HGYS" => "532", "WATEHS7WTKS5UHQC" => "532", "WATEKM9QPGH3JX5Y" => "532", "WATEQQYXQJHZFJKW" => "532");

$c = 0;
foreach ($listings as $listing) {
	$fsn = trim($listing['mp_id']);
	$sku = trim($listing['sku']);

	// $details['price_now'] = $fsps[$fsn];
	$tax = new stdClass();
	$tax->hsn = "6104";
	$tax->tax_code = "GST_APPAREL";

	$attributeValues = array(
		// "sku" => $sku,
		// "fsn" => $fsn,
		// "selling_price" => $fsps[$fsn],
		// "hsn"
		"hsn" => '6104',
		"tax_code" => 'GST_APPAREL',
	);

	// var_dump($sku);


	// $db->where('alias_id', $listing['alias_id']);
	// $db->update(TBL_PRODUCTS_ALIAS, $details);
	$status = $fk->update_product_v3($sku, $fsn, $attributeValues);
	// var_dump($attributeValues);
	echo $fsn . ' - ' . $status . '<br />';
	// $log->write ( $j_min_price . $stock_notice . $selling_price['last'] . " Updated price of FSN ".$fsn." with SKU ".$sku." to ". ((int)$selling_price['price'] - 1) ."\nPricing Details: ". json_encode($selling_price) ."\nAttribute Details: ".json_encode($attributeValues)." \nResponse: " .$status);
}
