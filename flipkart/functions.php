<?php
global $fk;

$GLOBALS['tier'] = $fk->payments->get_seller_tier(); // returns default current tier.;
$fk->payments->tier = $tier;
$GLOBALS['min_settlement_value'] = 41;
$GLOBALS['processing_cost'] = 25; // Cost including man power, returns reconcilation and wastage due to returns added to overall cost
$GLOBALS['delivery_charges'] = get_max_shipping_charges();
$GLOBALS['shipping_fees'] = get_max_shipping_charges();
$GLOBALS['service_gst'] = 18;
$GLOBALS['gst'] = 18;
$GLOBALS['sort'] = false;
$GLOBALS['product'] = "";

/*
 * Auto Price Updater & Listing Eligibility Checker Function
 */
function get_minimum_comp_price($fsn, $pincode = "482001", $all = false)
{
	global $current_account, $sort, $other_accounts;

	$body = new stdClass();
	$body->requestContext = new stdClass;
	$body->requestContext->productId = $fsn;
	$body->locationContext = new stdClass;
	// $body->locationContext->pincode = "800001"; // RANCHI
	$body->locationContext->pincode = $pincode; // JABALPUR
	$fields = json_encode($body);

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://2.rome.api.flipkart.com/api/3/page/dynamic/product-sellers",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => $fields,
		CURLOPT_HTTPHEADER => array(
			"cache-control: no-cache",
			"content-length: 90",
			"host: 2.rome.api.flipkart.com",
			"origin: https://www.flipkart.com",
			"referer: https://www.flipkart.com/",
			"content-type: application/json",
			"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36",
			"X-User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36 FKUA/website/42/website/Desktop",
			"Cookie: SN=VI0EC5ADFB74E7450EA46977C49D1A6026.TOKCC49889D3B5F49AEB3D1B84D57B40A6F." . time() . ".LI"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);
	$j_response = json_decode($response);

	curl_close($curl);

	if ($err || isset($j_response->ERROR_MESSAGE)) {
		if (isset($j_response->ERROR_MESSAGE))
			$err = $j_response->ERROR_MESSAGE;

		return "Error Processing Request. ERROR: " . $err;
		// throw new Exception("Error Processing Request. ERROR: ".$err, 1);
	} else {
		// echo $response;
		$result = json_decode($response);
		$sellers = $result->RESPONSE->data->product_seller_detail_1->data;
		// var_dump($sellers);
		$return = array();
		$i = 0;
		$our_data = array();
		foreach ($sellers as $seller) {
			// var_dump($seller->value);
			$seller_name = $seller->value->sellerInfo->value->name;
			if (in_array($seller_name, $other_accounts) && !$all)
				continue;
			$return[$i]['seller'] = $seller_name;
			$return[$i]['seller_id'] = $seller->value->sellerInfo->value->id;
			// if ($seller->value->pricing->value->prices[1]->discount >= "15")
			// 	$seller->value->pricing->value->prices[1]->discount = 5;
			$return[$i]['offer'] = ((!isset($seller->value->pricing->value->prices[1]->discount) && isset($seller->value->offers[0]->id) && $seller->value->offers[0]->id != "PRICING") ? 0 : $seller->value->pricing->value->prices[1]->discount);
			$fulfilled = $seller->value->metadata->faAvailable;
			$return[$i]['fulfilled'] = $fulfilled;
			$prices = $seller->value->pricing->value->prices;
			$return[$i]['selling_price'] = $prices[count($prices) - 1]->value;
			$shipping = 0;
			if (!$seller->value->deliveryMessages[0]->freeDelivery)
				$shipping = (int)$seller->value->deliveryMessages[0]->charge->value;
			if ($fulfilled)
				$shipping = 0;
			// if ($fulfilled && $return[$i]['selling_price'] < 500)
			// 	$shipping = 40;

			$return[$i]['shipping'] = $shipping;
			$return[$i]['price'] = $prices[count($prices) - 1]->value + $shipping;
			$return[$i]['rating'] = isset($seller->value->sellerInfo->value->rating->average) ? $seller->value->sellerInfo->value->rating->average : 0;

			if ($return[$i]['seller'] == $current_account->account_name) {
				$opted_value = get_opted_in_offer_value($seller->value->listingId);
				$return[$i]['offer'] = $opted_value['promoValue'];
				$our_data = $return[$i];
			}

			// if ($i == 0 && $return[$i]['seller'] == $current_account->account_name)
			// 	$sort = true;

			$i++;
		}

		if ($sort) {
			usort($return, function ($a, $b) {
				return $a['price'] - $b['price'];
			});
		}
		$data = array('all_data' => $return, 'our_data' => $our_data);

		return $data;
	}
}

function get_selling_price($mp_id, $gross_price, $deep_lookup = true, $incentive = 0)
{
	global $db, $current_account, $min_settlement_value, $delivery_charges, $product, $gst, $reduced_slab_products;

	if (empty($product)) {
		$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
		$db->join(TBL_FK_ACCOUNTS . " a", "p.account_id=a.account_id", "LEFT");
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_MASTER . " pm", "pm.is_active", 1);
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_COMBO . " pc", "pc.is_active", 1);
		$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
		$db->where('p.mp_id', $mp_id);
		$db->where('p.account_id', $current_account->account_id);
		$product = $db->getOne(TBL_PRODUCTS_ALIAS . " p", array('p.*', 'pm.sku as parentSku', 'c.categoryName as category', 'pb.brandName', 'COALESCE(pc.is_active, pm.is_active, 0) as is_active, a.is_ams, a.party_id'));
	}
	$class = 'pid';
	$id = $product['pid'];
	if ($product['cid']) {
		$class = 'cid';
		$id = $product['cid'];
	}

	// if ($product['fulfilled']){
	// 	$delivery_charges = 0;
	// }

	$is_invoiced = false;
	$pricing_details = get_cost_price($class, $id);
	$cost_price = $pricing_details['cost_price'];
	if ($product['catID'] == 2 || $product['catID'] == 3) {
		$gst = 18;
		$cost_price = $pricing_details['cost_price'] - (($pricing_details['cost_price'] * $gst) / ($gst + 100));
		$is_invoiced = true;
	}

	if ($product['brandName'] == "Skmei" || $product['brandName'] == "Skmei 1" || $product['brandName'] == "Curren" || in_array($product['mp_id'], $reduced_slab_products[$current_account->account_id])) {
		$gst = 5;
	}

	$units = $pricing_details['units'];
	$selling_price['cost_price'] = $cost_price;
	$selling_price['units'] = $units;
	$found_settlement_value = do_fk_calcuation($gross_price, $cost_price, $units, $is_invoiced, $incentive);

	$selling_price['final_settlement'] = $found_settlement_value;
	$selling_price['last'] = '';

	if (($found_settlement_value <= $min_settlement_value) || $deep_lookup) {
		$selling_price['last'] = 'Competitors price too low ';
		if ($deep_lookup) {
			$selling_price['last'] = 'Least possible price ';
			// $gross_price = $cost_price+200; // When we are the only seller
		}

		$diff_add = $min_settlement_value - $found_settlement_value; // Add difference to the selling price.
		$gross_price += $diff_add;
		$selling_price['price'] = calculate_min_selling_price($gross_price, $cost_price, $units, $is_invoiced, $incentive);
		// $selling_price['final_settlement'] = $min_settlement_value; // by default it should be same as min_settlement_value
	} else {
		$selling_price['price'] = $gross_price;
	}

	$selling_price['price'] = (int)number_format($selling_price['price'] - $delivery_charges, 0, '.', '');
	$selling_price['final_settlement'] = (int)number_format($selling_price['final_settlement'], 0, '.', '');
	return $selling_price;
}

function do_fk_calcuation($gross_price, $cost_price, $units, $is_invoiced = false, $incentive_rate = 0)
{
	global $min_settlement_value, $shipping_fees, $service_gst, $product, $gst, $log, $processing_cost, $accountant;

	// Marketplace Fees
	$comm_rate = get_platform_fees($gross_price, $product['category'], $product['brandName'], $product['lid']);
	$commission = ($gross_price * $comm_rate['basic_rate']) / 100;
	if ($incentive_rate === 0) {
		$incentive = ($gross_price * $comm_rate['incentive']) / 100;
	} else {
		$incentive = ($gross_price * $incentive_rate) / 100;
	}
	$fixed_fees = get_fixed_fees('NON_FBF', $gross_price);
	$collection_fees = get_collection_fees($gross_price);
	$service_gst_charges = number_format((($commission + $collection_fees + $fixed_fees + $shipping_fees) * $service_gst) / 100, 2, '.', '');
	$market_place_fees = $commission + $collection_fees + $fixed_fees + $shipping_fees + $service_gst_charges - $incentive;
	$tcs = number_format(($gross_price / (100 + $gst)), 2, '.', '');
	$tds = number_format(($gross_price / (100 + $gst)), 2, '.', '');
	$net_payable = $gross_price - $market_place_fees - $tcs - $tds;

	// Tax Payable
	// $packing_charges = 23.50; // Includes Box 20 + 3.5 all other cost
	$packing_charges = get_packing_charges($units);
	$purchase_gst_charges = 0;
	if ($is_invoiced)
		$purchase_gst_charges = (($cost_price * $units) * $gst) / 100; // If purchased in invoice with GST
	$sales_gst_charges = number_format(($gross_price * $gst) / (100 + ($gst)), 2, '.', ''); // Selling price is including all taxes
	$gst_payable = number_format($sales_gst_charges - $purchase_gst_charges - $service_gst_charges - $tcs - $tds, 2, '.', '');

	// Actual Payout after tax
	$net_payout = $net_payable - ($cost_price * $units) - $purchase_gst_charges - $packing_charges - $processing_cost;

	// Final Settlement
	$final_settlement = number_format($net_payout - $gst_payable, 0, '.', '');
	if ($product['is_ams']) {
		$ams_commission = $accountant->get_ams_slab_rate($product['party_id'], 'commission_fees', date('Y-m-d H:i:s', time()));
		$ams_fixed = $accountant->get_ams_slab_rate($product['party_id'], 'fixed_fees', date('Y-m-d H:i:s', time()), $gross_price);

		$ams_commission_fee = round($gross_price * ($ams_commission['value'] / 100), 2);
		$ams_processing_fee = round($ams_fixed['value'], 2);
		$ams_tax = round(($ams_commission_fee + $ams_processing_fee) * 0.18, 2);
		$total_ams = $ams_commission_fee + $ams_processing_fee + $ams_tax;
		$final_settlement = number_format($final_settlement - $total_ams + $processing_cost, 0, '.', '');
	}

	$log_details = "SKU: " . $product['sku'] . "\n";
	$log_details .= "FSN: " . $product['mp_id'] . "\n";
	$log_details .= "BRAND: " . $product['brandName'] . "\n";
	$log_details .= "Gross Price: " . $gross_price . "\n";
	$log_details .= "Commission: " . $commission . "\n";
	$log_details .= "Incentive: " . $incentive . "\n";
	$log_details .= "Fixed Fees: " . $fixed_fees . "\n";
	$log_details .= "Collection Fees: " . $collection_fees . "\n";
	$log_details .= "Shipping Fees: " . $shipping_fees . "\n";
	$log_details .= "Service GST Charge: " . $service_gst_charges . "\n";
	$log_details .= "TCS: " . $tcs . "\n";
	$log_details .= "TDS: " . $tcs . "\n";
	$log_details .= "Settlement Value: " . $net_payable . "\n";
	$log_details .= "\n";
	$log_details .= "Cost Price: " . $cost_price . "\n";
	$log_details .= "Units: " . $units . "\n";
	$log_details .= "Packing Charges: " . $packing_charges . "\n";
	$log_details .= "Processing Cost: " . $processing_cost . "\n";
	$log_details .= "Purchase GST Charge: " . $purchase_gst_charges . "\n";
	$log_details .= "Net Payout: " . $net_payout . "\n";
	$log_details .= "\n";
	$log_details .= "Service GST Rate: " . $service_gst . "\n";
	$log_details .= "GST Rate: " . $gst . "\n";
	$log_details .= "Sales GST: " . $sales_gst_charges . "\n";
	$log_details .= "GST Payable: " . $gst_payable . "\n";
	$log_details .= "Final Settlement: " . $final_settlement . "\n";
	// $log->write($log_details, 'fk-calculation');
	if (isset($_GET['test']) && $_GET['test'] == "1") {
		echo "SKU: " . $product['sku'] . "<br />";
		echo "FSN: " . $product['mp_id'] . "<br />";
		echo "BRAND: " . $product['brandName'] . "<br />";
		echo "Gross Price: " . $gross_price . "<br />";
		echo "Commission: " . $commission . "<br />";
		echo "Incentive: " . $incentive . "<br />";
		echo "Fixed Fees: " . $fixed_fees . "<br />";
		echo "Collection Fees: " . $collection_fees . "<br />";
		echo "Shipping Fees: " . $shipping_fees . "<br />";
		echo "Service GST Charge: " . $service_gst_charges . "<br />";
		echo "TCS: " . $tcs . "<br />";
		echo "TDS: " . $tcs . "<br />";
		echo "Settlement Value: " . $net_payable . "<br />";
		echo "=====<br />";
		echo "Cost Price: " . $cost_price . "<br />";
		echo "Units: " . $units . "<br />";
		echo "Packing Charges: " . $packing_charges . "<br />";
		echo "Processing Cost: " . $processing_cost . "<br />";
		echo "Purchase GST Charge: " . $purchase_gst_charges . "<br />";
		echo "Net Payout: " . $net_payout . "<br />";
		echo "=====<br />";
		echo "Service GST Rate: " . $service_gst . "<br />";
		echo "GST Rate: " . $gst . "<br />";
		echo "Sales GST: " . $sales_gst_charges . "<br />";
		echo "GST Payable: " . $gst_payable . "<br />";
		if ($product['is_ams']) {
			echo "AMS Charges: " . $total_ams . "<br />";
		}
		echo "Final Settlement: " . $final_settlement . "<br />";
		echo "=====<br />";
		echo "<br /><br /><br />";
	}

	return (int)number_format($final_settlement, 0, '.', '');
}

function calculate_min_selling_price($selling_price, $cost_price, $units, $is_invoiced, $incentive)
{
	global $min_settlement_value, $service_gst, $service_gst_fees, $product, $log, $current_account;

	$gross_price = $selling_price;
	$found_settlement_value = do_fk_calcuation($gross_price, $cost_price, $units, $is_invoiced, $incentive);

	if ($min_settlement_value <= $found_settlement_value) {
		$selling_price = $selling_price;
		// $log_details = "FINAL: \tcost_price: ".$cost_price."\tunits: ".$units."\tselling_price: ".$selling_price."\tProduct: ".$product['mp_id']."\tSKU: ".$product['sku']."\tAccount: ".$current_account->account_id;
		// $log->write($log_details, 'negative-calculation');
	} else {
		$diff_add = $min_settlement_value - $found_settlement_value; // Add difference to the selling price.
		// $diff_add = 1;
		$selling_price = $selling_price + $diff_add;
		// $log_details = "\tcost_price: ".$cost_price."\tunits: ".$units."\tselling_price: ".$selling_price."\tDifference: ".$diff_add."\tProduct: ".$product['mp_id']."\tSKU: ".$product['sku']."\tAccount: ".$current_account->account_id;
		// $log->write($log_details, 'negative-calculation');
		$selling_price = calculate_min_selling_price($selling_price, $cost_price, $units, $is_invoiced, $incentive);
	}

	return $selling_price;
}

function get_platform_fees($gross_price, $category, $brand, $lid)
{
	global $fk;

	$platform_fees = $fk->payments->get_platform_fees($gross_price, $category, $brand, $lid);

	return $platform_fees;
}

function get_fixed_fees($type, $gross_price)
{
	global $fk;

	$fixed_fees = $fk->payments->get_fixed_fees($type, $gross_price);

	return $fixed_fees;
}

function get_collection_fees($gross_price, $payment_type = "cod")
{
	global $fk;

	$collection_fees = $fk->payments->get_collection_fees($gross_price, $payment_type);

	return $collection_fees;
}

function get_shipping_values($max = false)
{
	global $fk;

	if ($max)
		$shippingValues = $fk->payments->get_shipping_fees('NON_FBF');
	else {
		$shippingValues = array(
			"national" => (int)$fk->payments->get_shipping_fees('NON_FBF', 'national'),
			"zonal" => (int)$fk->payments->get_shipping_fees('NON_FBF', 'zonal'),
			"local" => (int)$fk->payments->get_shipping_fees('NON_FBF', 'local'),
			// "national" => round($fk->payments->get_shipping_fees('NON_FBF', 'national'), 0, PHP_ROUND_HALF_DOWN),
			// "zonal" => round($fk->payments->get_shipping_fees('NON_FBF', 'zonal'), 0, PHP_ROUND_HALF_DOWN),
			// "local" => round($fk->payments->get_shipping_fees('NON_FBF', 'local'), 0, PHP_ROUND_HALF_DOWN),
		);
	}

	return $shippingValues;
}

function get_max_shipping_charges()
{
	global $fk;

	$shipping_charges = get_shipping_values(true);

	return $shipping_charges;
}

function get_gst_rate($hsnCode, $invoiceAmount)
{
	global $fk;

	return $fk->payments->get_gst_rate($hsnCode, $invoiceAmount);
}

function get_packing_charges($unit = 1)
{
	global $db, $current_account, $product;

	// echo '====<br/>';
	// var_dump($product);
	// echo '====<br/>';
	$class = 'pid';
	$id = $product['pid'];
	if ($product['cid']) {
		$class = 'cid';
		$id = $product['cid'];
	}

	$packing_cost = 5;
	$outer_packaging_cost = 4.5;

	if ($class == 'cid') {
		$db->where($class, $id);
		$sku = $db->getValue(TBL_PRODUCTS_COMBO, 'sku');

		if ((strpos($sku, 'MXRE-COMBO3') !== FALSE) || (strpos($sku, 'MXRE-COMBO2') !== FALSE) || (strpos($sku, 'IIK') !== FALSE) || (strpos($sku, 'MXRE-COMBO4') !== FALSE) || (strpos($sku, 'MXRE-COMBO5') !== FALSE) || (strpos($sku, 'MXRE-COMBO6') !== FALSE) || (strpos($sku, 'MXRE-COMBO7') !== FALSE)) { // MXRE more then 3
			$packing_cost = 3.5 + 2.5;
		} else if (strpos($sku, 'COM-3-12') !== FALSE) { // HERBA Combo
			$packing_cost = 1 + 3;
		} else {
			$packing_cost = (15 * $unit) + 2;
		}

		if ($product['catID'] == "19" && $unit < 3) {
			$packing_cost = 4;
		} else if ($product['catID'] == "19" && $unit > 3) {
			$packing_cost = 7;
		}
	} else {
		$db->where('pid', $id);
		$sku = $db->getValue(TBL_PRODUCTS_MASTER, 'sku');

		$single_small_box = array("Colourful", "Curren-Blackdial", "Curren-Temp-Blackdial", "DW-WHITE-BLUE", "DW-WHITE-BLUE-RED", "FT-Digital", "IIK-Black", "IIK-Black-Ladies", "IIK-Gold", "IIK-Gold-Ladies", "IIK-Silver", "IIK-Silver-Ladies", "Mxre-Black", "Mxre-Blue", "Mxre-Brown", "Mxre-Pink", "Mxre-Purple", "Mxre-Red", "Mxre-White", "ROSRA-R01-BLACKDIAL-BLACK", "ROSRA-R01-BLACKDIAL-BLACK-LADIES", "ROSRA-R01-GOLDDIAL-SILVER-GOLD", "ROSRA-R01-GOLDDIAL-SILVER-GOLD-LADIES", "Sport-Blue", "Sport-Orange", "Sport-Red", "Sport-Yellow", "Trans-Gold");

		// $is_skmei_low_range = false;
		// if ($product['parentSku'] == 'SKMEI-1155-BLACK' || $product['parentSku'] == 'SKMEI-1155-BLUE' || $product['parentSku'] == 'SKMEI-1155-GOLD' || $product['parentSku'] == 'SKMEI-1155-RED' || $product['parentSku'] == 'SKMEI-1155-ARMY' || $product['parentSku'] == 'SKMEI-1117-BLACK' || $product['parentSku'] == 'SKMEI-1117-RED' || $product['parentSku'] == 'SKMEI-1117-BLUE' || $product['parentSku'] == 'SKMEI-0990-ARMY' || $product['parentSku'] == "SKMEI-5019-RED" || $product['parentSku'] == "SKMEI-5019-BLUE" || $product['parentSku'] == "SKMEI-5019-GOLD")
		// 	$is_skmei_low_range = true;

		if (in_array($sku, $single_small_box)) {
			$packing_cost = 3.5 + $outer_packaging_cost;
		} else if ($product['catID'] == 2 || $product['catID'] == 3) {
			$packing_cost = 1 + $outer_packaging_cost;
		} else if ($product['brandName'] == 'Skmei 1') {
			$packing_cost = (7.5 * $unit) + $outer_packaging_cost;
		} else if ($product['brandName'] == 'Skmei') {
			$packing_cost = (11 * $unit) + $outer_packaging_cost;
		} else if ($product['brandName'] == 'Curren') {
			$packing_cost = (12 * $unit) + $outer_packaging_cost;
		} else {
			$packing_cost = (20 * $unit) + $outer_packaging_cost;
		}
	}

	return $packing_cost;
}

function get_stock_status($mp_id)
{
	global $db, $current_account, $product;

	// $db->where('mp_id', $mp_id);
	// $db->where('account_id', $current_account->account_id);
	// $product = $db->getOne(TBL_PRODUCTS_ALIAS);
	$class = 'pid';
	$id = $product['pid'];
	if ($product['cid']) {
		$class = 'cid';
		$id = $product['cid'];
	}

	if ($class == 'cid') {
		$db->where($class, $id);
		$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
		$products = json_decode($products['pid']);

		$in_stock = array();
		foreach ($products as $prod) {
			$db->where('pid', $prod);
			$status = $db->getOne(TBL_PRODUCTS_MASTER, 'in_stock');
			if ($product['fulfilled'])
				$in_stock[] = true;
			else
				$in_stock[] = $status['in_stock'];
		}

		$return = (in_array(false, $in_stock) ? false : true);
	} else {
		$db->where('pid', $id);
		$status = $db->getOne(TBL_PRODUCTS_MASTER, 'in_stock');

		if ($product['fulfilled'])
			$return = true;
		else
			$return = $status['in_stock'];
	}

	return $return;
}

function get_opted_in_offer_value($lid, $timespan = array())
{
	global $db;

	if (empty($timespan)) {
		$timespan['startDate'] = time();
		$timespan['endDate'] = time();
	}

	$db->where('listingId', $lid);
	$db->where('promoStartDate', array(date('Y-m-d H:i:s', $timespan['startDate']), date('Y-m-d H:i:s', $timespan['endDate'])), 'BETWEEN');
	$db->where('promoEndDate', array(date('Y-m-d H:i:s', $timespan['startDate']), date('Y-m-d H:i:s', $timespan['endDate'])), 'BETWEEN');

	$value = $db->getOne(TBL_FK_PROMOTIONS, 'SUM(promoValue) as promoValue, promoMpInc, offerId');
	// echo $db->getLastQuery().'<br />';

	return $value;
}
