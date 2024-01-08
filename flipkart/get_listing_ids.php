<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

if (isset($_POST["submit"]) && isset($_POST['account_id']) && $_POST['account_id'] != "" && isset($_POST['listing_ids']) && ($_POST['listing_ids'] != "")) {
	ini_set('max_execution_time', 0);
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');

	global $db, $accounts, $current_account;

	$account_id = (int)$_POST['account_id'];
	$account_key = "";
	foreach ($accounts['flipkart'] as $a_key => $account) {
		if ($account_id == $account->account_id)
			$account_key = $a_key;
	}

	$GLOBALS['current_account'] = $accounts['flipkart'][$account_key]; // Shreehan - 2, Sylvi - 1, Baby - 0
	$service_tax = 18;

	if (isset($_POST["submit"])) {
		echo '<pre>';
		$fk = new connector_flipkart((object)$current_account);
		include(ROOT_PATH . '/flipkart/functions.php'); // will add this file after $current_account is found
		$listings = explode(',', $_POST['listing_ids']);
		$output = "<table><thead><th>LID</th><th>FSN</th><th>SKU</th><th>Parent SKU</th><th>Fulfilment Mode</th><th>MRP</th><th>Offer</th></thead><tbody>";

		foreach ($listings as $listing) {
			$db->where('mp_id', trim($listing));
			// $db->where('account_id', $current_account->account_id);
			$db->join(TBL_FK_ACCOUNTS . " a", "p.account_id=a.account_id", "LEFT");
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->where('p.marketplace', 'flipkart');
			$db->where('p.account_id', $current_account->account_id);

			$product = $db->getOne(TBL_PRODUCTS_ALIAS . " p", 'p.sku, COALESCE(pc.sku, pm.sku) as parent_sku');
			// var_dump($product);
			$output .= "<tr>";
			if ($product == NULL) {
				$output .= "<td colspan=5> ALIAS NOT - " . trim($listing) . "</td>";
			} else {
				$current_pricing = get_minimum_comp_price(trim($listing));
				$item = $fk->get_products_details($product['sku']);
				$item = $item->available->{$product['sku']};
				$output .= "<td>" . $item->listing_id . "</td>";
				$output .= "<td>" . $item->product_id . "</td>";
				$output .= "<td>" . $product['sku'] . "</td>";
				$output .= "<td>" . $product['parent_sku'] . "</td>";
				$output .= "<td>" . $item->fulfillment_profile . "</td>";
				$output .= "<td>" . $item->price->mrp . "</td>";
				$output .= "<td>" . $current_pricing['our_data']['offer'] . "</td>";
			}

			$output .= "</tr>";
		}

		$output .= "</tbody></table>";
		echo $output;
	}
} else {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/functions.php');

	global $accounts;
	$options = "";

	foreach ($accounts['flipkart'] as $account) {
		$options .= "<option value='" . $account->account_id . "'>" . $account->fk_account_name . "</option>";
	}
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
		<title>Get Listing IDs</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script src="assets/js/jquery.min.js"></script>
	</head>

	<body>
		<form method="post" action="get_listing_ids.php" name="form-update" enctype="multipart/form-data" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;">
			<table>
				<tr>
					<td>FSN (Comma seperated):</td>
					<td><input type="text" name="listing_ids" value="" /><br /></td>
					<td>
						<select name="account_id">
							<option value=""></option>
							<?php echo $options; ?>
						</select>
					</td>
					<td><input type="submit" name="submit" value="Get Listing IDs" /><br /></td>
				</tr>
			</table>
		</form>
	</body>

	</html>
<?php
}
