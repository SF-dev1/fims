<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
global $db, $accounts;

$uploadOk = 1;
$target_file = dirname(dirname(__FILE__)) . "/uploads/10off-16-SF.csv";
// $target_file = $target_dir . ;
$fileType = pathinfo($target_file, PATHINFO_EXTENSION);
$reverse = true;

// Allow certain file formats
if ($fileType != "csv") {
	echo "Sorry, only CSV files are allowed.";
	$uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
	echo "Sorry, your file was not uploaded.";
	// if everything is ok, try to upload file
} else {
	echo '<pre>';
	$listings = csv_to_array($target_file);
	$selected_account = $accounts['flipkart'][1];

	$fk = new connector_flipkart($selected_account);
	foreach ($listings as $listing) {

		if (isset($listing['New SP']) && $listing['New SP'] != "") {
			$atttibutes = array(
				"package_length" => "25",
				"package_breadth" => "17",
				"package_height" => "3",
				"package_weight" => "0.200",
				// "procurement_type" => "express",
				// "procurement_sla" => "2",
			);
			if ($reverse)
				$atttibutes["selling_price"] = $listing['SP'];
			else
				$atttibutes["selling_price"] = $listing['New SP'];

			$update = $fk->update_product($listing['SKU'], $listing['FSN'], $atttibutes);
			var_dump($update);
		}
	}
}

function csv_to_array($filename = '', $sorting = FALSE, $delimiter = ',')
{

	ini_set('auto_detect_line_endings', TRUE);
	if (!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
			if (!$header) {
				$header = $row;
			} else {
				if (count($header) > count($row)) {
					$difference = count($header) - count($row);
					for ($i = 1; $i <= $difference; $i++) {
						$row[count($row) + 1] = $delimiter;
					}
				}
				$data[] = array_combine($header, $row);
			}
		}
		fclose($handle);
	}

	if ($sorting) {
		$sort = array();
		foreach ($data as $k => $v) {
			$sort['qty'][$k] = $v['Quantity'];
			if (in_array($v['SKU'], $wrong_sku)) {
				$sort['sku'][$k] = $correct_sku[array_search($v['SKU'], $wrong_sku)];
			} else {
				$sort['sku'][$k] = $v['SKU'];
			}
		}
		# sort by event_type desc and then title asc
		array_multisort($sort['qty'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['sku'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $data);
	}

	// echo '<pre>';
	// var_dump($data);
	// foreach ($data as $in) {
	// 	echo $in['Ready to Ship by date'] .' - '.$in['Quantity'] .' x ' .$in['SKU Code'] .'<br />';
	// }
	// exit;

	return $data;
}
