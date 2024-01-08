<?php
if ((isset($_POST["submit"]) && isset($_POST['account_id']) && $_POST['account_id'] != "" && isset($_POST['discount']) && ($_POST['discount'] != "" || $_POST['discount'] != 0))) {
	// error_reporting(E_ALL);
	// ini_set('display_errors', '1');
	// echo "<pre>";
	// $_GET['test'] = "1";
	// var_dump($_POST);
	// exit;

	ini_set('max_execution_time', 0);
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
	include_once(ROOT_PATH . '/includes/connectors/flipkart/flipkart-dashboard.php');

	global $db, $accounts, $current_account;

	$account_id = (int)$_POST['account_id'];
	$account_key = "";
	foreach ($accounts['flipkart'] as $a_key => $account) {
		if ($account_id == $account->account_id)
			$account_key = $a_key;
	}

	$GLOBALS['current_account'] = $accounts['flipkart'][$account_key]; // Shreehan - 2, Sylvi - 1, Baby - 0
	$GLOBALS['fk'] = "";
	$fk = new flipkart_dashboard((object)$current_account);
	include_once(ROOT_PATH . '/flipkart/functions.php'); // will add this file after $current_account is found
	// $min_settlement_value = 31; // Override for mis settlement value
	$log->logfile = TODAYS_LOG_PATH . '/elegible-listings-' . $current_account->fk_account_name . '.log';
	$GLOBALS['comm_rate'] = 0;
	$fsn = $_POST['fsn'] != "" ? explode(',', str_replace(' ', '', $_POST['fsn'])) : "";

	if ($fsn == "") {
		$target_dir = ROOT_PATH . "/uploads/elegible-listings/";
		if (!is_dir($target_dir))
			mkdir($target_dir, '775');
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$fileType = pathinfo($target_file, PATHINFO_EXTENSION);
	}
	$uploadOk = 1;
	$service_tax = 18;

	$discount = (int)$_POST['discount'];
	$discountType = $_POST['discountType'];
	$mpInc = (int)$_POST['incentive'];

	if (isset($_GET['test']) && $_GET['test'] == "1")
		$debug = true;

	if (isset($_POST["submit"])) {
		// Allow certain file formats
		if ($fileType != "csv" && $fsn == "") {
			echo "Sorry, only CSV files are allowed.";
			$uploadOk = 0;
		}

		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
			echo "Sorry, your file was not uploaded.";
			// if everything is ok, try to upload file
		} else {
			if (!isset($_POST['internal']) && !$_POST['internal']) {
				echo '<pre>';
				$_POST['startDate'] = strtotime($_POST['startDate']);
				$_POST['endDate'] = strtotime($_POST['endDate'] . ' 23:59:59');
			}

			if ($fsn == "") {
				if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
					$file = basename($_FILES["fileToUpload"]["name"]);
					$listings = csv_to_array($target_file);
				}
			} else {
				$listings = array();
				foreach ($fsn as $product) {
					$db->where('mp_id', $product);
					$db->where('account_id', $current_account->account_id);
					$db->where('marketplace', 'flipkart');
					$sku = $db->getValue(TBL_PRODUCTS_ALIAS, 'sku');
					$prod = $fk->get_products_details($sku);
					$lid = $prod->available->{$sku}->listing_id;
					$ancor_price = $fk->get_usual_price($lid);
					if ($discountType == "Percentage") {
						$extra_discount = number_format((($ancor_price * $discount) / 100), 0, '.', '');
						$customer_price = $ancor_price - $extra_discount;
					} else {
						$extra_discount = number_format($discount, 0, '.', '');
						$customer_price = $extra_discount;
					}

					$list = array(
						'Listing ID' => $lid,
						'FSN' => $product,
						'SKU ID' => $sku,
						'Usual Price(Rs)' => $ancor_price,
						'Extra Discount(Rs)' => $extra_discount,
						'Price for Customer (Rs)' => $customer_price
					);
					$listings[] = $list;
				}
			}

			if (!empty($listings)) {
				$output = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
				$output .= "<table><thead><th>LID</th><th>FSN</th><th>SKU</th><th>Parent SKU</th><th>Brand</th><th>Fulfilment Type</th><th>SP</th><th>Discount</th><th>FSP</th><th>Incentive Amount</th><th>Net Percentage</th><th>Settlement</th><th>Is Eligible?</th><th>In Stock?</th><th>New SP</th><th>Offer ID</th></thead>";
				$internal_output = array();

				foreach ($listings as $listing) {
					$fsn = trim($listing['FSN']);
					$sku = trim($listing['SKU ID']);
					$fulfilment = "NON_FBF";
					$is_eligible = "";
					$gst = 18; // set to global on each instance
					$delivery_charges = get_max_shipping_charges();
					$is_mp_inc_offer = false;
					$active_mp_inc_offer_id = "";

					$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=p.catID", "INNER");
					$db->where('p.mp_id', $fsn);
					$db->where('p.account_id', $current_account->account_id);
					$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
					$db->join(TBL_PRODUCTS_BRAND . " b", "b.brandid=pm.brand", "LEFT");
					// $db->where('p.auto_update', 1);
					$query = $db->getOne(TBL_PRODUCTS_ALIAS . " p", 'p.*, c.categoryName as category, b.brandName, pm.is_offer_optin_allowed');
					$product = $query;
					// echo $db->getLastQuery();
					// $comm_rate = $query['comm'];

					if (is_null($product) || empty($product)) {
						$output .= "<tr><td>" . $listing['Listing ID'] . "</td>";
						$output .= "<td>" . $listing['FSN'] . "</td>";
						$output .= "<td>" . $listing['SKU ID'] . "</td>";
						$output .= "<td></td>";
						$output .= "<td></td>";
						$output .= "<td></td>";
						$output .= "<td>" . $listing['Usual Price(Rs)'] . "</td>";
						$output .= "<td>" . $listing['Extra Discount(Rs)'] . "</td>";
						$output .= "<td>" . $listing['Price for Customer (Rs)'] . "</td>";
						$output .= "<td></td>";
						$output .= "<td></td>";
						$output .= "<td></td>";
						$output .= "<td></td>";
						$output .= "<td>ALIAS NOT FOUND</td>";
						$output .= "<td></td>";
						$output .= "<td></td></tr>";
						continue;
					}

					$parent_sku = "";
					if ($query['cid']) {
						$db->where('cid', $query['cid']);
						$products = $db->getOne(TBL_PRODUCTS_COMBO, 'sku');
						$parent_sku = $products['sku'];
					} else {
						$db->where('pid', $query['pid']);
						$products = $db->getOne(TBL_PRODUCTS_MASTER, 'sku');
						$parent_sku = $products['sku'];
					}
					$applicable_discount = $discount;
					if (isset($_POST['startDate']) && !empty($_POST['startDate']) && isset($_POST['endDate']) && !empty($_POST['endDate'])) {
						$addition_discount = get_opted_in_offer_value($listing['Listing ID'], array('startDate' => $_POST['startDate'], 'endDate' => $_POST['endDate']));
					} else {
						$addition_discount = get_opted_in_offer_value($listing['Listing ID']); // BAU DAYS
						// $addition_discount = get_opted_in_offer_value($listing['Listing ID'], array('startDate' => '1576866600', 'endDate' => '1577125799')); // BOSS2 SALE
						// $addition_discount = get_opted_in_offer_value($listing['Listing ID'], array('startDate' => '1576866600', 'endDate' => '1577125799')); // BOSS2 SALE
						// $addition_discount = get_opted_in_offer_value($listing['Listing ID'], array('startDate' => '1576002600', 'endDate' => '1576434599')); // BOSS SALE
						// $addition_discount = get_opted_in_offer_value($listing['Listing ID'], array('startDate' => '1571596200', 'endDate' => '1572028199')); // BDS2 SALE
						// $addition_discount['promoValue'] = 0;
					}
					if ($discountType == "percentage")
						$applicable_discount = (int)$addition_discount['promoValue'] + $discount;
					$is_mp_inc_offer = (int)$addition_discount['promoMpInc'] > (int)0 ? true : false;
					$active_mp_inc_offer_id = $addition_discount['offerId'];

					// var_dump($listing['Listing ID']);
					// var_dump($applicable_discount);
					if ($discountType == "flat") // FLAT PRICE OVERRIDE
						$selling_price = $discount;
					else
						$selling_price = (int)($listing['Usual Price(Rs)'] - (($listing['Usual Price(Rs)'] * ($applicable_discount)) / 100));

					if ($query['fulfilled'] && $selling_price < 500) {
						$delivery_charges = 40;
						$fulfilment = 'FBF_LITE';
					} else if ($query['fulfilled']) {
						$delivery_charges = 0;
						$fulfilment = 'FBF_LITE';
					}
					$gross_price = $selling_price + $delivery_charges;
					if ($discountType == "flat")  // FLAT PRICE OVERRIDE
						$listing['Usual Price(Rs)'] = $gross_price;

					$pricing_details = get_selling_price($fsn, (int)$gross_price, true, $mpInc);
					$final_settlement_value = (int)$pricing_details['final_settlement'];
					$unit_price = (int)$pricing_details['cost_price'];
					$units = (int)$pricing_details['units'];
					$brand_name = $query['brandName'];
					if ($discountType == "flat") { // FLAT PRICE OVERRIDE)
						$discount_amount = number_format($applicable_discount, 0, '.', '');
						$customer_price = number_format($discount, 0, '.', '');
					} else {
						$discount_amount = number_format((($listing['Usual Price(Rs)'] * $applicable_discount) / 100), 0, '.', '');
						$customer_price = number_format(($listing['Usual Price(Rs)'] - (($listing['Usual Price(Rs)'] * $applicable_discount) / 100)), 0, '.', '');
					}

					$incentive_amount = number_format((($customer_price * $mpInc) / 100), 2, '.', '');
					$comm_rate = get_platform_fees($gross_price, $product['category'], $product['brandName'], $product['lid']);
					if ($gross_price * $comm_rate['basic_rate'] < $incentive_amount)
						$incentive_amount = $gross_price * $comm_rate['basic_rate'];
					$net_percentage = floatval(number_format($final_settlement_value / ($unit_price * $units), 2, '.', ''));
					$new_sp = (int)($pricing_details['price'] / (1 - ($applicable_discount / 100)));
					$is_eligible = 'No';

					$output .= "<tr><td>" . $listing['Listing ID'] . "</td>";
					$output .= "<td>" . $listing['FSN'] . "</td>";
					$output .= "<td>" . $listing['SKU ID'] . "</td>";
					$output .= "<td>" . $parent_sku . "</td>";
					$output .= "<td>" . $brand_name . "</td>";
					$output .= "<td>" . $fulfilment . "</td>";
					$output .= "<td>" . $listing['Usual Price(Rs)'] . "</td>";
					$output .= "<td>" . $discount_amount . "</td>";
					$output .= "<td>" . $customer_price . "</td>";
					$output .= "<td>" . $incentive_amount . "</td>";
					$output .= "<td>" . $net_percentage . "</td>";
					$output .= "<td>" . $final_settlement_value . "</td>";
					$is_eligible = ($final_settlement_value < $min_settlement_value || !$product['is_offer_optin_allowed']) ? "No" : ($is_mp_inc_offer ? "No" : "Yes");
					// if (time() < strtotime('2019-07-04') && $brand_name == "Curren")
					// 	$is_eligible = "No";
					// if (strpos($parent_sku, 'SYLVI-YD-') !== FALSE)
					// 	$is_eligible = "Yes";
					$output .= "<td>" . $is_eligible . "</td>";
					$in_stock = (get_stock_status($fsn) == false ? 'No' : 'Yes');
					$output .= "<td>" . $in_stock . "</td>";
					if ($is_eligible == "Yes")
						$output .= "<td></td>";
					else
						$output .= "<td>" . $new_sp . "</td>";
					$output .= "<td>" . $active_mp_inc_offer_id . ($is_mp_inc_offer ? " (MP_INC)" : "") . "</td>";

					if ($is_eligible == "Yes") {
						$internal_output['eligible'][$listing['Listing ID']] = array(
							'listing_id' => $listing['Listing ID'],
							'fsn' => $listing['FSN'],
							'sku' => $listing['SKU ID'],
							'parent_sku' => $parent_sku,
							'brand' => $brand_name,
							'fulfilment_type' => $fulfilment,
							'usual_price' => $listing['Usual Price(Rs)'],
							'discount_amount' => $discount_amount,
							'customer_price' => $customer_price,
							'incentive_amount' => $incentive_amount,
							'net_percentage' => $net_percentage,
							'settlement' => $final_settlement_value,
							'is_eligible' => $is_eligible,
							'in_stock' => $in_stock,
							'new_sp' => "",
							'active_mp_inc_offer_id' => $active_mp_inc_offer_id,
						);
					} else {
						$internal_output['ineligible'][$listing['Listing ID']] = array(
							'listing_id' => $listing['Listing ID'],
							'fsn' => $listing['FSN'],
							'sku' => $listing['SKU ID'],
							'parent_sku' => $parent_sku,
							'brand' => $brand_name,
							'fulfilment_type' => $fulfilment,
							'usual_price' => $listing['Usual Price(Rs)'],
							'discount_amount' => $discount_amount,
							'customer_price' => $customer_price,
							'incentive_amount' => $incentive_amount,
							'net_percentage' => $net_percentage,
							'settlement' => $final_settlement_value,
							'is_eligible' => $is_eligible,
							'in_stock' => $in_stock,
							'new_sp' => $new_sp,
							'active_mp_inc_offer_id' => $active_mp_inc_offer_id,
						);
					}

					$output .= "</tr>";
				}

				$output .= "<tbody></tbody></table>";
			}

			if (isset($_POST['internal']) && $_POST['internal']) {
				$log->write(json_encode($internal_output));
				echo json_encode($internal_output);
			} else {
				$log->write(json_encode($output));
				echo $output;
			}
		}
	}
} else {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/functions.php');

	global $accounts;
	$options = "";

	foreach ($accounts['flipkart'] as $account) {
		$options .= "<option value='" . $account->account_id . "'>" . $account->fk_account_name . "</option>";
	}

	$test = "";
	if (isset($_GET['test']) && $_GET['test'] == "1")
		$test = "?test=1";
?>
	<!DOCTYPE html>
	<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
	<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
	<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
	<!--[if gt IE 8]><!-->
	<html class="no-js"> <!--<![endif]-->

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>Get Eligible Listings</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script src="assets/js/jquery.min.js"></script>
	</head>

	<body>
		<form method="post" action="get_eligible_listings.php<?php echo $test; ?>" name="form-update" enctype="multipart/form-data" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;">
			<table>
				<tr>
					<td>CSV File:</td>
					<td><input type="file" name="fileToUpload" id="fileToUpload"> OR </td>
					<td>FSN (comma seperated): <input type="text" name="fsn" placeholder="FSN (comma seperated)" id="fsn"></td>
				</tr>
				<tr>
					<td>Account:</td>
					<td>
						<select name="account_id">
							<option value=""></option>
							<?php echo $options; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Discount Value:</td>
					<td><input type="number" name="discount" placeholder="Discount Value" value="" /><br /></td>
				</tr>
				<tr>
					<td>Discount Type:</td>
					<td><input type="radio" name="discountType" value="percentage" id="discountPercentage" /><label for="discountPercentage">Percentage</label> OR <input type="radio" name="discountType" value="flat" id="discountFlat" /><label for="discountFlat">Flat</label></td>
				</tr>
				<tr>
					<td>Incentive Value:</td>
					<td><input type="number" name="incentive" placeholder="Incentive" value="" />%<br /></td>
				</tr>
				</tr>
				<tr>
					<td>Promotion Date:</td>
					<td><input type="date" name="startDate" placeholder="Promotion Start Date" value="" /> - <input type="date" name="endDate" placeholder="Promotion End Date" value="" /><br /></td>
				</tr>
				<tr>
					<td><input type="submit" name="submit" value="Get Eligible Listings" /><br /></td>
				</tr>
			</table>
		</form>
	</body>

	</html>
<?php
}
