<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
if (isset($_POST["submit"]) && isset($_POST['type']) && isset($_POST['company']) && $_POST['type'] != "" && $_POST['company'] != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
	global $db;

	$accounts = $db->ObjectBuilder()->get('bas_fk_account');

	$target_dir = ROOT_PATH . "/labels/uploads/";
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;
	$fileType = pathinfo($target_file, PATHINFO_EXTENSION);

	if (isset($_POST["submit"]) && isset($_POST['type']) && isset($_POST['company']) && $_POST['type'] != "" && $_POST['company'] != "") {
		// Allow certain file formats
		if ($fileType != "csv" && $_POST['type'] == "forms") {
			echo "Sorry, only CSV files are allowed for Forms.";
			$uploadOk = 0;
			exit;
		}

		if (($_POST['type'] == "labels" || $_POST['type'] == "invoices") && $fileType != "pdf") {
			echo "Sorry, only PDF files are allowed for Invoices and Labels";
			$uploadOk = 0;
			exit;
		}

		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
			echo "Sorry, your file was not uploaded.";
			// if everything is ok, try to upload file
		} else {
			if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
				$file = basename($_FILES["fileToUpload"]["name"]);
				$company = (int)$_POST['company'];
				$current_account = $accounts[$company];
				if ($_POST['type'] == "labels") {
					get_labels($target_file, $current_account->account_name);
				}
				if ($_POST['type'] == "invoices") {
					get_forms_invoices($target_file, $current_account->account_name, true);
				}
				if ($_POST['type'] == "forms") {
					get_forms_invoices($target_file, $current_account->account_name, false, true);
				}
			} else {
				echo "Sorry, there was an error uploading your file.";
			}
		}
	} else {
		echo 'Go <a href="javascript:history.go(-1)">back</a> and fill all the fields';
	}
} else {
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
		<title>Form Generator</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script src="../assets/plugins/jquery-1.8.3.min.js"></script>
	</head>

	<body>
		<form method="post" action="" name="form-update" enctype="multipart/form-data" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;">
			<table>
				<tr>
					<td>CSV File:</td>
					<td><input type="file" name="fileToUpload" id="fileToUpload"></td>
					<td>
						<select name="type" id="type">
							<option value=""></option>
							<option value="labels">Labels</option>
							<option value="invoices">Invoices</option>
							<option value="forms">Forms</option>
						</select>
					</td>
					<td>
						<select name="company" id="company">
							<option value=""></option>
							<option value="0">BabyNSmile</option>
							<option value="1">StyleFeathers</option>
						</select>
					</td>
					<td><input type="submit" name="submit" value="Generate" /><br /></td>
				</tr>
			</table>
		</form>
	</body>

	</html>
<?php
}

function get_labels($target_file, $company)
{
	$file_time = time();
	$path = ROOT_PATH . '/labels/';
	$filename = 'Flipkart-Labels-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . $company;
	$pdf = new FPDI('P', 'mm', array('105', '148'));
	$pageCount = $pdf->setSourceFile($target_file);

	for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

		$pdf->AddPage();
		$pdf->SetMargins(0, 0);

		// import page 1
		$tplIdx = $pdf->importPage($pageNo);

		// get the label
		$pdf->useTemplate($tplIdx, -52.5, 0, 0);
		$pdf->SetLineWidth(2);
		$pdf->SetDrawColor(255, 255, 255);
		$pdf->Line(7.25, 0, 7.25, 148);
		$pdf->Line(97.75, 0, 97.75, 148);
		$pdf->Line(0, 146, 105, 146);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFontSize(12);
		$pdf->SetFont('Times', '');

		$i = 0;
		foreach ($sku[$pageNo - 1] as $s_sku) {
			if (in_array($s_sku, $wrong_sku)) {
				$correct_sku[array_search($s_sku, $wrong_sku)] . ' | ' . $p_name[$pageNo - 1][$i] . '<br />';
				$pdf->SetLineWidth(3);
				$pdf->Line(11, 96, 77.75, 96);
				$pdf->Line(11, 98.75, 77.75, 98.75);

				$pdf->SetFont('Times', 'B');
				$pdf->SetFontSize(6.5);
				$pdf->SetXY(9.5, 95);
				$pdf->MultiCell(10, 2.75, 'watch', 0, 'L', 0);

				$pdf->SetFont('Times', '');
				$pdf->SetXY(9.5, 95);
				$pdf->MultiCell(60, 2.75, '           | ' . $correct_sku[array_search($s_sku, $wrong_sku)] . ' | ' . $p_name[$pageNo - 1][$i], 0, 'L');
				$i++;
			}
		}

		if ($dispatch_date[$pageNo - 1] == date('d-M-Y', time())) {
			$pdf->SetDrawColor(0, 0, 0);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Times', 'B', 7);
			$pdf->SetXY(72, 6);
			$pdf->Cell(15, 3, 'PRIORITY', 0,  0, 'C', 1);
		}
	}

	// $filenames[] = $path.$filename.'.pdf';

	ob_clean();
	// Save & Output the file
	$pdf->Output($path . 'final/' . $filename . '-' . $file_time . '.pdf', 'F');
	$pdf->Output($filename . '-' . $file_time . '.pdf', 'I');
	exit;
}

function get_forms_invoices($target_file, $company, $invoices = false, $forms = false)
{

	if ($invoices) {
		$file_time = time();
		$path = ROOT_PATH . '/labels/';
		$filename = 'Flipkart-Invoices-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . $current_account->account_name . '-' . $file_time;
		$pdf = new FPDI('L', 'mm', 'A5');
		$pageCount = $pdf->setSourceFile($target_file);
		// set the source file

		for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

			$pdf->AddPage();

			// import page 1
			$tplIdx = $pdf->importPage($pageNo);
			// use the imported page and place it at point 10,10 with a width of 100 mm

			$pdf->useTemplate($tplIdx, 0, -145, 0);

			$pdf->SetDrawColor(255, 255, 255);
			$pdf->SetLineWidth(8);
			$pdf->Line(0, 4, 248, 4);
		}
		$pdf->Output($path . 'final/' . $filename . '.pdf', 'F');
		$pdf->Output($filename . '.pdf', 'I');
	}

	if ($forms) {
		$file_time = time();
		$filename = 'Flipkart-Forms-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . $company . '-' . $file_time;
		$uploadOk = 1;
		$fileType = pathinfo($target_file, PATHINFO_EXTENSION);
		$target_dir = ROOT_PATH . "/labels/";


		$all_orders = csv_to_array($target_file);
		$pdfm = new PDFMerger;
		foreach ($all_orders as $list) {
			$order_item_id = str_replace("'", "", $list['ORDER ITEM ID']);
			$j_forms = $list['Form requirement'];
			$forms = explode(', ', $j_forms);
			$invoice_no = $list['Invoice No.'];
			$buyer_name = $list['Buyer name'];
			$city = $list['City'];
			$state = $list['State'];

			$shipping_address = $list['Address Line 1'] . ' ' . $list['Address Line 2'] . ' ' . $city . ' ' . $state . '-' . $list['PIN Code'];
			$order_id = $list['Order Id'];
			$invoice_amount = $list['Invoice Amount'];

			$items[0]['name'] = $list['SKU'];
			$items[0]['qty'] = $list['Quantity'];
			$items[0]['line_total'] = (int)$list['Quantity'] * ((int)$list['Selling Price Per Item'] + (int)$list['Shipping Charge per item']);

			if (($state == 'Madhya pradesh') && !in_array('MP_50', $forms)) {
				$forms[] = 'MP_50';
			}

			// Forms array sorting
			sort($forms);

			if (!empty($forms)) {
				ob_start();
				$pdf = new FPDI('P', 'mm', 'A4');
				$file_n = $target_dir . 'single/Form-' . $order_item_id . '.pdf';
				if (file_exists($file_n)) {
					$pdfm->addPDF($file_n, 'all');
				} else {
					foreach ($forms as $form) {
						if ($form == "GJ_402") {
							// Page 1
							$pdf->AddPage();

							// set the source file
							$pdf->setSourceFile(ROOT_PATH . "/includes/default-402.pdf");

							// import page 1
							$tplIdx = $pdf->importPage(1);
							// use the imported page and place it at point 10,10 with a width of 100 mm
							$pdf->useTemplate($tplIdx, -15, -10, 240);

							// now write some text above the imported page
							$pdf->SetFont('Helvetica');
							$pdf->SetTextColor(0, 0, 0);

							// Seller City and State - Static
							$pdf->SetXY(102, 82);
							$pdf->Cell(40, 5, 'Surat', 0, 1, 'C');
							$pdf->SetXY(162, 82);
							$pdf->Cell(20, 5, 'Gujarat', 0, 1, 'C');

							// Customer City and State
							$pdf->SetXY(96, 87.5);
							$pdf->Cell(47, 5, ucfirst(strtolower($city)), 0, 1, 'C');
							$pdf->SetXY(159, 87.5);
							$pdf->Cell(34, 5, ucwords(strtolower($state)), 0, 1, 'L');

							// Invoice Number and Date
							$pdf->SetXY(80, 93);
							$pdf->Cell(30, 5, $invoice_no, 0, 1, 'C');
							$pdf->SetXY(120, 93);
							$pdf->Cell(34, 5, date('d-M-y'), 0, 1, 'C');

							// Consignor's Details - Static
							if ($company == "BabyNSmile") {
								$consignor_add = "BabyNSmile \n805, Adatiya Awas, Bombay \nMarket, Umarwada, \nSurat";
								$consignor_detail = "Gujarat \n\n24221706148 \n7-May-2015\n\n24721706148\n7-May-2015";
							}
							if ($company == 'StyleFeathers' || $company == 'Sylvi') {
								$consignor_add = "Style Feathers \n805, Adatiya Awas, Bombay \nMarket, Umarwada, \nSurat";
								$consignor_detail = "Gujarat \n\n24221706721 \n24-Oct-2015\n\n24721706721\n24-Oct-2015";
							}
							$pdf->SetXY(49, 103);
							$pdf->MultiCell(63, 5.5, $consignor_add, 0, 'L');
							$pdf->SetXY(147, 103);
							$pdf->MultiCell(63, 5.5, $consignor_detail, 0, 'L');

							// Nature of Transaction - Static
							$pdf->SetXY(90, 148);
							$pdf->Cell(5, 5, 'x', 0, 1, 'C');

							// Consignee Details
							$pdf->SetFontSize(11);
							$consignee_add = ucwords(strtolower(str_replace('<br/>', "\n", $buyer_name . '<br/>' . $shipping_address)));
							$pdf->SetXY(49, 186);
							$pdf->MultiCell(63, 5.5, $consignee_add, 0, 'L');

							// Consignment Value
							$pdf->SetXY(72, 225);
							$pdf->Cell(5, 5, $invoice_amount, 0, 1, 'C');

							// Items
							// 5.5+
							$row_lines = array('241.5', '247', '252.5', '258');
							$item_i = 0;

							foreach ($items as $item) {
								$pdf->SetXY(35, $row_lines[$item_i]);
								$pdf->Cell(48, 5, ucwords(strtolower(substr($item['name'], 0, 25))), 0, 1, 'L'); // Max 25 character
								// Item Qty
								$pdf->SetXY(120, $row_lines[$item_i]);
								$pdf->Cell(8, 5, $item['qty'], 0, 1, 'C');
								// Item Tax
								// $tax = (float)get_post_meta( $item['product_id'], '_vat', true );
								// $tax += (float)get_post_meta( $item['product_id'], '_addvat', true );
								// $pdf->SetXY(146, $row_lines[$item_i]);
								// $pdf->Cell(12,5,$tax.'%',0,1,'C');

								// Item Price
								$pdf->SetXY(170, $row_lines[$item_i]);
								$pdf->Cell(12, 5, $item['line_total'], 0, 1, 'C');
								$item_i++;
							}

							$pdf->SetXY(90, 264);
							$pdf->Cell(30, 5, 'eKart Logistics', 0, 1, 'L');

							$pdf->SetFontSize(12);
						}

						if ($form == "MP_50") {
							var_dump($form);

							// Page 1
							$pdf->AddPage();

							// set the source file
							$pdf->setSourceFile(ROOT_PATH . "/includes/default-50.pdf");

							// import page 1
							$tplIdx = $pdf->importPage(1);
							// use the imported page and place it at point 10,10 with a width of 100 mm
							$pdf->useTemplate($tplIdx, -15, -10, 240);

							// now write some text above the imported page
							$pdf->SetFont('Helvetica');
							$pdf->SetTextColor(0, 0, 0);
							$pdf->SetFontSize(10);

							// Consignor's Details - Static
							if ($company == "BabyNSmile") {
								$consignor_add = "BabyNSmile 805, Adatiya Awas, Bombay Market, Umarwada, Surat";
								$consignor_detail = "Gujarat \n24221706148 w.e.f. 7-May-2015 \n24721706148 w.e.f. 7-May-2015";
							}
							if ($company == 'StyleFeathers' || $company == 'Sylvi') {
								$consignor_add = "Style Feathers 805, Adatiya Awas, Bombay Market, Umarwada, \nSurat";
								$consignor_detail = "Gujarat \n24221706721 w.e.f. 24-Oct-2015 \n24721706721 w.e.f. 24-Oct-2015";
							}
							$pdf->SetXY(132, 34);
							$pdf->MultiCell(63, 5, $consignor_add, 0, 'L');
							$pdf->SetXY(132, 46);
							$pdf->MultiCell(63, 4, $consignor_detail, 0, 'L');

							// Consignee Details
							$consignee_add = ucwords(strtolower(str_replace('<br/>', "\n", $buyer_name))) . ", " . $city;
							$pdf->SetXY(132, 59);
							$pdf->MultiCell(63, 5.5, $consignee_add, 0, 'L');

							// Seller City and State - Static
							$pdf->SetXY(132, 65);
							$pdf->Cell(40, 5, 'Surat, Gujarat', 0, 1, 'L');

							// Invoice Number and Date
							$pdf->SetXY(132, 70);
							$pdf->Cell(30, 5, $invoice_no . ' - ' . date('d-M-y'), 0, 1, 'L');

							// Customer City and State
							$pdf->SetXY(132, 76);
							$pdf->Cell(47, 5, $city . ', ' . $state, 0, 1, 'L');

							// Items
							$pdf->SetXY(132, 81.5);
							$pdf->Cell(30, 5, substr($list['SKU'] . ' ...', 0, 30), 0, 1, 'L');

							$pdf->SetXY(132, 87);
							$pdf->Cell(30, 5, $list['Quantity'], 0, 1, 'L');

							$pdf->SetXY(132, 92.5);
							$line_total = "Rs. " . $invoice_amount;
							$pdf->Cell(30, 5, $line_total, 0, 1, 'L');

							$pdf->SetXY(132, 98);
							$pdf->Cell(30, 5, 'eKart Logistics', 0, 1, 'L');
						}
					}
					ob_clean();
					$pdf->Output($file_n, 'F');
					$pdfm->addPDF($file_n, 'all');
				}
			}
		}

		$pdfm->merge('file', $target_dir . 'final/' . $filename . '-' . $file_time . '.pdf');
		$pdfm->merge('browser', $filename . '-' . $file_time . '.pdf');
	}

	exit;
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
	//  echo $in['Ready to Ship by date'] .' - '.$in['Quantity'] .' x ' .$in['SKU Code'] .'<br />';
	// }
	// exit;

	return $data;
}
?>