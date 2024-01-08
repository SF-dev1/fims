<?php

// include(dirname(dirname(__FILE__)).'/config.php');

/**
 * 
 */
class accountant
{
	/*** Declare instance ***/
	private static $instance = NULL;

	private function __construct()
	{
	}

	public function get_current_dues($party_type, $party_id)
	{
		global $db, $client_id;

		$total_due = 0;
		$client_table = $client_id == 0 ? "" : '_' . $client_id;

		if ($party_type == "customer") {
			$db->where('so_status', 'created');
			$db->where('so_party_id', $party_id);
			$totalBillAmount = $db->getOne(TBL_SALE_ORDER . $client_table, 'SUM(so_total_amount) as totalBillAmount');
			$totalBillAmount = ((float)$totalBillAmount['totalBillAmount']);
			$old_system_due = $this->old_system_dues($party_id);
			$totalBillAmount += $old_system_due[0]['amount_cr'];

			$db->where('party_id', $party_id);
			$totalPaidAmount = $db->getOne(TBL_SALE_RETURN_ITEMS, 'SUM(return_amount) as totalReturnAmount');
			$totalReturnAmount = ((float)$totalPaidAmount['totalReturnAmount']);

			$db->where('party_id', $party_id);
			$totalPaidAmount = $db->getOne(TBL_SALE_PAYMENTS, 'SUM(payment_amount) as totalPaidAmount');
			$totalPaidAmount = ((float)$totalPaidAmount['totalPaidAmount']);
			$total_due = $totalBillAmount - $totalPaidAmount - $totalReturnAmount;
		}

		if ($party_type == "supplier") {
			$total_due = 0;
			// $db->where('so_status', 'created');
			// $db->where('so_party_id', $party_id);
			// $totalBillAmount = $db->getOne(TBL_SALE_ORDER, 'SUM(so_total_amount) as totalBillAmount');
			// $totalBillAmount = ((float)$totalBillAmount['totalBillAmount']);

			// $db->where('party_id', $party_id);
			// $totalPaidAmount = $db->getOne(TBL_SALE_PAYMENTS, 'SUM(payment_amount) as totalPaidAmount');
			// $totalPaidAmount = ((float)$totalPaidAmount['totalPaidAmount']);
			// $total_due = $totalBillAmount - $totalPaidAmount;
		}

		if ($party_type == "vendor") {
			$total_due = 0;
			$db->where('party_id', $party_id);
			$totalBillAmount = $db->getOne(TBL_AMS_INVOICES, 'SUM(ams_invoice_total) as totalBillAmount');
			$totalBillAmount = ((float)$totalBillAmount['totalBillAmount']);

			$db->where('party_id', $party_id);
			$totalPaidAmount = $db->getOne(TBL_AMS_PAYMENTS, 'SUM(payment_amount) as totalPaidAmount');
			$totalPaidAmount = ((float)$totalPaidAmount['totalPaidAmount']);
			$total_due = $totalBillAmount - $totalPaidAmount;
		}

		return number_format($total_due, 2, '.', '');
	}

	public function get_party_leadger($party_type, $party_id, $limit = NULL, $date_range = array())
	{
		global $db;

		$end_date = date('Y-m-d', $date_range['end_date']);
		$date_range['end_date'] = time();

		if ($party_type == "customer") {
			$old_system_due = array();
			if (date('Y-m-d', $date_range['start_date']) <= '2019-07-22')
				$old_system_due = $this->old_system_dues($party_id);
			$sales = $this->get_all_transactions('sales', $party_id, $limit, $date_range);
			$sales_returns = $this->get_all_transactions('sales_return', $party_id, $limit, $date_range);
			$sales_payments = $this->get_all_transactions('sales_payment', $party_id, $limit, $date_range);
			$ledger = array_merge($old_system_due, $sales, $sales_returns, $sales_payments);
		}

		if ($party_type == "vendor") {
			$vendor_service = $this->get_all_transactions('vendor_service', $party_id, $limit, $date_range);
			$vendor_payment = $this->get_all_transactions('vendor_payment', $party_id, $limit, $date_range);
			$ledger = array_merge($vendor_service, $vendor_payment);
		}

		usort($ledger, function ($a, $b) {
			return strtotime($a["date"]) - strtotime($b["date"]);
		});

		$ledger = array_reverse($ledger);
		$balance = $this->get_current_dues($party_type, $party_id);
		$i = 0;
		$last = count($ledger) - 1;
		$bal_cf = 0;
		foreach ($ledger as $ledger_item) {
			$ledger[$i]['balance'] = number_format($balance, 2, '.', '');
			$is_first_old_system_due = ($ledger_item['description'] == "OLD SYSTEM DUE" ? 1 : 0);
			if (isset($ledger_item['amount_cr'])) {
				if ($i == $last && $ledger_item['amount_cr'] != $balance && !$is_first_old_system_due)
					$bal_cf = $balance - $ledger_item['amount_cr'];
				$balance -= number_format($ledger_item['amount_cr'], 2, '.', '');
			} else {
				if ($i == $last && $ledger_item['amount_dr'] != $balance)
					$bal_cf = $balance + $ledger_item['amount_dr'];
				$balance += number_format($ledger_item['amount_dr'], 2, '.', '');
			}
			$i++;
		}

		$ledger = array_reverse($ledger);

		if ($bal_cf != 0) {
			$balance_cf = array(
				'date' => date('Y-m-d', $date_range['start_date']) . ' 00:00:00',
				'description' => 'Balance C/F',
				'balance' => number_format($bal_cf, 2, '.', ''),
			);

			array_unshift($ledger, $balance_cf);
		}

		foreach ($ledger as $ledger_key => $ledger_item) {
			if ($ledger_item['date'] > $end_date . ' 23:59:59')
				unset($ledger[$ledger_key]);
		}

		return $ledger;
	}

	/**
	 * GET ALL TRANSACTIONS
	 * @param string $party_type 
	 * @param int $party_id 
	 * @param string $tranaction_type 
	 * @param int|null $limit 
	 * @param string|array $date_range ('start_date' => UNIX timestamp, 'end_date' => UNIX timestamp)
	 * @return array
	 */
	public function get_all_transactions($tranaction_type, $party_id, $limit = NULL, $date_range = array())
	{
		global $db;

		$transactions = array();

		if ($tranaction_type == "sales") {
			$db->where('so_status', 'created');
			$db->where('so_party_id', $party_id);
			$db->orderBy('insertDate', 'DESC');
			if (!empty($date_range)) {
				$db->where('createdDate', array(date('Y-m-d H:i:s', $date_range['start_date']), date('Y-m-d H:i:s', $date_range['end_date'])), 'BETWEEN');
			}
			$transactions = $db->get(TBL_SALE_ORDER, $limit, array('createdDate AS date, CONCAT("SALES #SO_", LPAD(so_id, 6, 0)) AS description, so_total_amount AS amount_cr, so_notes as notes'));
		}

		if ($tranaction_type == "sales_return") {
			$db->where('party_id', $party_id);
			$db->orderBy('insertDate', 'DESC');
			if (!empty($date_range)) {
				$db->where('insertDate', array(date('Y-m-d H:i:s', $date_range['start_date']), date('Y-m-d H:i:s', $date_range['end_date'])), 'BETWEEN');
			}
			$transactions = $db->get(TBL_SALE_RETURN_ITEMS, $limit, array('insertDate AS date, CONCAT("SALES RETURN #SR_", LPAD(sor_id, 6, 0)) AS description, return_amount AS amount_dr'));
		}

		if ($tranaction_type == "sales_payment") {
			$db->where('party_id', $party_id);
			$db->orderBy('insertDate', 'DESC');
			if (!empty($date_range)) {
				$db->where('insertDate', array(date('Y-m-d H:i:s', $date_range['start_date']), date('Y-m-d H:i:s', $date_range['end_date'])), 'BETWEEN');
			}
			$transactions = $db->get(TBL_SALE_PAYMENTS, $limit, array('insertDate AS date, CONCAT(UPPER(payment_mode), " PAYMENT #PR_", LPAD(sales_payment_id, 6, 0)) AS description, payment_amount AS amount_dr, payment_notes as notes, payment_reference as reference'));
		}

		if ($tranaction_type == "vendor_service") {
			$db->where('party_id', $party_id);
			$db->orderBy('createdDate', 'DESC');
			if (!empty($date_range)) {
				$db->where('createdDate', array(date('Y-m-d H:i:s', $date_range['start_date']), date('Y-m-d H:i:s', $date_range['end_date'])), 'BETWEEN');
			}
			$transactions = $db->get(TBL_AMS_INVOICES, $limit, array('createdDate AS date, ams_invoice_number AS description, ams_invoice_total AS amount_cr'));
		}

		if ($tranaction_type == "vendor_payment") {
			$db->where('party_id', $party_id);
			$db->orderBy('insertDate', 'DESC');
			if (!empty($date_range)) {
				$db->where('insertDate', array(date('Y-m-d H:i:s', $date_range['start_date']), date('Y-m-d H:i:s', $date_range['end_date'])), 'BETWEEN');
			}
			$transactions = $db->get(TBL_AMS_PAYMENTS, $limit, array('insertDate AS date, CONCAT(UPPER(payment_mode), " PAYMENT #PR_", LPAD(ams_payment_id, 6, 0)) AS description, payment_amount AS amount_dr, payment_notes as notes, payment_reference as reference'));
		}

		return $transactions;
	}

	private function old_system_dues($party_id)
	{

		global $db;

		$db->where('party_id', $party_id);
		$osd = $db->getOne(TBL_PARTIES, 'osd');
		$past_dues = array(
			"date" => "2019-07-22 00:00:00",
			"description" => "OLD SYSTEM DUE",
			"amount_cr" => number_format($osd['osd'], 2, '.', ''),
			"notes" => ""
		);

		return array($past_dues);
	}

	/**
	 * CREATE AMS INVOICES
	 * @param type $party_id 
	 * @param array $dates (startDate => YYYY-MM-DD, endDate => YYYY-MM-DD) 
	 * @return array
	 */
	public function create_ams_invoice($party_id, $date_range)
	{
		global $db;

		// GET PARTY DETAILS
		$db->where('party_id', $party_id);
		$ams_details = $db->getOne(TBL_PARTIES, 'party_name, party_ams_firm_id');
		$firm_id = $ams_details['party_ams_firm_id'];

		$db->where('ams_invoice_date', date('Y-m-t', strtotime($date_range['startDate'])));
		$db->where('party_id', $party_id);
		$invoice = $db->getOne(TBL_AMS_INVOICES);
		if ($invoice) {
			return array('type' => 'success', 'msg' => 'Invoice already existing for date ' . date('Y-m-t', strtotime(date('Y-m') . " -1 month")) . ' for ' . $ams_details['party_name'], 'ams_invoice_id' => $invoice['ams_invoice_id']);
		}

		// GET LAST INVOICE# AND NAME OF FIRM
		$current_invoice = get_option('last_invoice_firm_' . $firm_id) + 1;
		$current_credit_note = get_option('last_credit_note_firm_' . $firm_id) + 1;
		$db->where('firm_id', $firm_id);
		$firm_name = strtoupper(substr($db->getValue(TBL_FIRMS, 'firm_name'), 0, 3));
		$fy = get_option('current_fy');

		// GET ORDERS
		$transactions = $this->get_ams_orders($party_id, $date_range);
		if ($transactions) {
			$order_marketplaces = array_values(array_unique(array_map(function ($i) {
				return $i['marketplace'];
			}, $transactions)));

			$items = array();
			foreach ($order_marketplaces as $marketplace) {
				$items[strtolower($marketplace)]['fixed_fees_rate'] = array_values(array_filter(array_unique(array_map(function ($item) use ($marketplace) {
					if ($item['marketplace'] == $marketplace)
						return $item['fixed_fees_rate'];
				}, $transactions))));

				$items[strtolower($marketplace)]['commission_fees_rate'] = array_values(array_filter(array_unique(array_map(function ($item) use ($marketplace) {
					if ($item['marketplace'] == $marketplace)
						return $item['commission_fee_rate'];
				}, $transactions))));

				$items[strtolower($marketplace)]['orders'] = array_sum(array_map(function ($item) use ($marketplace) {
					if ($item['marketplace'] == $marketplace)
						return 1;
				}, $transactions));
			}

			$marketplaces = array();
			$total_orders = count($transactions);
			$total = array();
			foreach ($transactions as $transaction) {
				$marketplace = strtolower($transaction['marketplace']);
				$fixed_fees_rate = $transaction['fixed_fees_rate'];
				if ($fixed_fees_rate !== 0) {
					if (empty($transaction['return_type']))
						$marketplaces[$marketplace]['fixed_fees'][$fixed_fees_rate]['qty'] += $transaction['order_quantity'];
					$marketplaces[$marketplace]['fixed_fees'][$fixed_fees_rate]['rate'] = $transaction['fixed_fees_rate'];
					// $marketplaces[$marketplace]['fixed_fees']['rate'] = '';
					$marketplaces[$marketplace]['fixed_fees'][$fixed_fees_rate]['amount'] += $transaction['fixed_fees'];
					$marketplaces[$marketplace]['fixed_fees'][$fixed_fees_rate]['particular'] = 'Order Processing - ' . $transaction['marketplace'];
				}

				$marketplaces[$marketplace]['commission_fee']['qty'] += $transaction['order_amount'];
				$marketplaces[$marketplace]['commission_fee']['rate'] = implode(',', $items[$marketplace]['commission_fees_rate']);
				$marketplaces[$marketplace]['commission_fee']['amount'] += $transaction['commission_fee'];
				$marketplaces[$marketplace]['commission_fee']['particular'] = 'Fixed Commission - ' . $transaction['marketplace'];

				$total['subtotal'] += $transaction['fixed_fees'] + $transaction['commission_fee'];
				$total['cgst'] += $transaction['cgst'];
				$total['sgst'] += $transaction['sgst'];
				$total['total'] += $transaction['fixed_fees'] + $transaction['commission_fee'] + $transaction['cgst'] + $transaction['sgst'];
			}
			$marketplaces['total'] = $total;

			$i = 0;
			$lineItems = array();
			foreach ($marketplaces as $marketplace => $order_details) {
				foreach ($order_details as $order_detail) {
					if (!is_array($order_detail))
						continue;

					if (!isset($order_detail['qty'])) {
						foreach ($order_detail as $detail) {
							$lineItems[$i] = $detail;
							$i++;
						}
					} else {
						$lineItems[$i] = $order_detail;
						$i++;
					}
				}
			}
			$lineItems[$i] = $marketplaces['total'];
			$invoice_total = $marketplaces['total']['total'];

			$invoice_suffix = sprintf('%04d', $current_invoice);
			if ($invoice_total < 0)
				$invoice_suffix = 'CN' . sprintf('%04d', $current_credit_note);

			$invoice_number = $firm_name . '-' . $fy . '-AMS-' . $invoice_suffix;

			$details = array(
				'ams_invoice_number' => $invoice_number,
				'ams_invoice_date' => date('Y-m-t', strtotime(date('Y-m') . " -1 month")),
				'ams_invoice_total' => $invoice_total,
				'ams_invoice_items' => json_encode($lineItems),
				'ams_invoice_rates' => json_encode($date_range),
				'ams_invoice_status' => 'pending',
				'party_id' => $party_id,
				'firm_id' => $firm_id,
			);

			if ($id = $db->insert(TBL_AMS_INVOICES, $details)) {
				set_option('last_invoice_firm_' . $firm_id, $current_invoice);
				if (strpos($current_credit_note, 'CN') !== FALSE)
					set_option('last_credit_note_firm_' . $firm_id, $current_credit_note);

				$result = array('type' => 'success', 'msg' => 'Successfully created invoice for ' . $ams_details['party_name'] . ' with Invoice# ' . $invoice_number, 'ams_invoice_id' => $id);
			} else {
				$result = array('type' => 'error', 'msg' => 'Failed creating invoice for party #' . $party_id, 'error' => $db->getLastError());
			}
		} else {
			$result = array('type' => 'error', 'msg' => 'No transactions found for party #' . $party_id, 'error' => 'No Transaction for this month found.');
		}

		return $result;
	}

	public function generate_tax_invoice_pdf($invoice_id, $type, $output = "")
	{
		global $db;

		if ($type == "ams") {
			$db->join(TBL_PARTIES . ' p', 'p.party_id=ai.party_id');
			$db->join(TBL_FIRMS . ' f', 'f.firm_id=ai.firm_id');
			$db->where('ams_invoice_id', $invoice_id);
			$invoice_details = $db->getOne(TBL_AMS_INVOICES . ' ai', 'f.firm_name, f.firm_address, f.firm_gst, f.firm_bank_name, f.firm_bank_branch, f.firm_bank_account, f.firm_bank_ifsc, p.party_name, p.party_address, p.party_gst, ai.ams_invoice_items, ai.ams_invoice_number, ai.ams_invoice_date');
			extract($invoice_details);
			$invoices_number = $ams_invoice_number;
			$ams_invoice_items = json_decode($ams_invoice_items);
			$last_row = count($ams_invoice_items) - 1;
			$invoice_totals = $ams_invoice_items[$last_row];
			$invoice_type = "TAX INVOICE";
			$invoice_type_alt = "Invoice";
			if (strpos($invoices_number, 'CN') !== FALSE) {
				$invoice_type = "CREDIT NOTE";
				$invoice_type_alt = "Credit Note";
			}

			$lineItems = "<tbody>";
			$i = 1;
			foreach ($ams_invoice_items as $ams_invoice_item) {
				if ($i == $last_row + 1)
					continue;

				$lineItems .= "<tr>";
				$lineItems .= "<td>" . $i . "</td>";
				$lineItems .= "<td>" . $ams_invoice_item->particular . "</td>";
				$lineItems .= "<td>999799</td>"; // FIXED SERVICE HSN CODE
				$lineItems .= "<td>" . $ams_invoice_item->qty . "</td>";
				if (strpos($ams_invoice_item->rate, "%") !== FALSE)
					$lineItems .= "<td>" . $ams_invoice_item->rate . "</td>";
				else
					$lineItems .= "<td>&#8377;" . $ams_invoice_item->rate . "</td>";
				$lineItems .= "<td class='last'>&#8377;" . number_format($ams_invoice_item->amount, 2, '.', '') . "</td>";
				$lineItems .= "</tr>";
				$i++;
			}
			$lineItems .= "</tbody>";

			$total_words = $this->convertToIndianWordCurrency(round($invoice_totals->total));
			if (strpos($invoices_number, 'CN') !== FALSE)
				$total_words = 'Negative ' . $this->convertToIndianWordCurrency(round($invoice_totals->total) * -1);

			$find = array(
				"##INVOICE TYPE##",
				"##INVOICE TYPE ALT##",
				"##FIRM NAME##",
				"##FIRM ADDRESS##",
				"##FIRM GST##",
				"##INVOICE NUMBER##",
				"##INVOICE DATE##",
				"##DUE DATE##",
				"##PARTY NAME##",
				"##PARTY ADDRESS##",
				"##PARTY GST##",
				"##LINE ITEMS##",
				"##TOTAL WORDS##",
				"##BANK NAME##",
				"##BRANCH NAME##",
				"##ACCOUNT TYPE##",
				"##ACCOUNT NUMBER##",
				"##BANK IFSC##",
				"##SUB TOTAL##",
				"##CGST##",
				"##SGST##",
				"##ROUNDOFF##",
				"##TOTAL##",
				// "##TOTAL DUE##"
			);
			$replace = array(
				$invoice_type,
				$invoice_type_alt,
				$firm_name,
				nl2br($firm_address),
				strtoupper($firm_gst),
				$ams_invoice_number,
				$ams_invoice_date,
				date('Y-m-d', strtotime($ams_invoice_date . ' + 10 days')),
				$party_name,
				nl2br($party_address),
				$party_gst,
				$lineItems,
				$total_words,
				$firm_bank_name,
				$firm_bank_branch,
				"Current Account",
				$firm_bank_account,
				$firm_bank_ifsc,
				number_format($invoice_totals->subtotal, 2, '.', ''),
				number_format($invoice_totals->cgst, 2, '.', ''),
				number_format($invoice_totals->sgst, 2, '.', ''),
				number_format((round($invoice_totals->total) - $invoice_totals->total), 2, '.', ''),
				number_format(round($invoice_totals->total), 2, '.', '')
			);

			$stylesheet = file_get_contents(BASE_URL . '/assets/templates/invoice.css'); // AMS CSS
		}

		$html = get_template('invoice', $type);
		// $html = file_get_contents(BASE_URL.'/assets/templates/invoice.html');
		$html = str_replace($find, $replace, $html);

		$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
		$fontDirs = $defaultConfig['fontDir'];

		$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
		$fontData = $defaultFontConfig['fontdata'];

		$mpdf = new \Mpdf\Mpdf([
			'format' => 'A4-P',
			'margin_left' => 5,
			'margin_right' => 5,
			'margin_top' => 5,
			'margin_bottom' => 5,
			'margin_header' => 0,
			'margin_footer' => 0,
			'showBarcodeNumbers' => FALSE,
			'fontDir' => array_merge($fontDirs, [
				ROOT_PATH . '/assets/fonts',
			]),
			'fontdata' => [
				'nunito' => [
					'R' => 'Nunito-Regular.ttf',
					'I' => 'Nunito-Italic.ttf',
					'B' => 'Nunito-Bold.ttf',
					'BI' => 'Nunito-BoldItalic.ttf',
				]
			],
			'default_font' => 'nunito'
		]);

		$mpdf->WriteHTML($stylesheet, 1);
		$mpdf->WriteHTML($html);
		// nunito

		$title = $invoices_number;
		$mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor('FIMS');
		$mpdf->SetDisplayMode('fullpage');
		if ($output == "save") {
			$file_name = UPLOAD_PATH . '/invoices/' . $title . '.pdf';
			$mpdf->Output($file_name, 'F');
			return $file_name;
		} else if ($output == "string") {
			return $mpdf->Output($title . '.pdf', 'S');
		} else {
			$mpdf->Output($title . '.pdf', 'I');
		}
	}

	public function create_ams_invoice_csv($invoice_id, $output = "save")
	{
		global $db;

		$db->where('ams_invoice_id', $invoice_id);
		$invoice_details = $db->getOne(TBL_AMS_INVOICES, 'party_id, ams_invoice_rates, ams_invoice_number, ams_invoice_date');
		$party_id = $invoice_details['party_id'];
		extract(json_decode($invoice_details['ams_invoice_rates'], true));
		if (file_exists($invoice_details['ams_invoice_number'] . '-Order_Level_Transactions.csv')) {
			if ($output == "string") {
				return stream_get_contents(UPLOAD_PATH . '/invoices/' . $invoice_details['ams_invoice_number'] . '-Order_Level_Transactions.csv');
			} else if ($output == "save") {
				return $file_name;
			}
		}

		// create a file pointer connected to the output stream
		if ($output == "save") {
			$file_name = UPLOAD_PATH . '/invoices/' . $invoice_details['ams_invoice_number'] . '-Order_Level_Transactions.csv';
			if (!$file = fopen($file_name, 'w+')) return FALSE;
		} else if ($output == "string") {
			if (!$file = fopen('php://temp', 'w+')) return FALSE;
		} else {
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $invoice_details['ams_invoice_number'] . '-Order_Level_Transactions.csv');
			if (!$file = fopen('php://output', 'w+')) return FALSE;
		}

		$transactions = $this->get_ams_orders($party_id, array('startDate' => $startDate, 'endDate' => $endDate));
		$header = array("Marketplace", "Account Name", "Order/Return ID", "OrderItem ID", "Shipped Date", "Transaction Type", "Order Amount", "Quantity", "Return Completed Date", "Return Qty", "Return Type", "Processing Rate", "Processing Amount", "Commission Rate", "Commission Amount", "Total Charges", "SGST", "CGST", "Net Charges");
		fputcsv($file, $header);
		foreach ($transactions as $transaction) {
			// var_dump($transaction);
			// $file_output = array($transaction['marketplace'], $transaction['account_name'], $transaction['baseId'], $transaction['orderItemId'], $transaction['date'], $transaction['orderType'], $transaction['invoiceAmount'], $transaction['quantity'], $transaction['r_quantity'], $processing_charge, $processing_fee, $commission_charge, $commission_fee, $total_fees, round($tax/2, 2), round($tax/2, 2), $total_charges);
			fputcsv($file, $transaction);
		}

		if ($output == "string") {
			rewind($file);
			return stream_get_contents($file);
		} else if ($output == "save") {
			return $file_name;
		}
	}

	public function get_ams_orders($party_id, $date_range)
	{
		global $db;

		// EXTRACT DATES & SET VALUES
		extract($date_range);

		// GET ORDERS
		$flipkart = array();
		$db->join(TBL_FK_ACCOUNTS . " a", "o.account_id=a.account_id", "RIGHT");
		$db->join(TBL_PARTIES . " p", "a.party_id=p.party_id", "RIGHT");
		$db->joinWhere(TBL_PARTIES . " p", 'p.party_id', $party_id);
		$db->where('status', 'SHIPPED');
		$db->where('o.shippedDate', array($startDate, $endDate), 'BETWEEN');
		$flipkart_orders = $db->get(TBL_FK_ORDERS . ' o', NULL, '"Flipkart" as marketplace, a.account_name, o.orderId as baseId, CONCAT("OI", o.orderItemId) as orderItemId, o.shippedDate as order_date, "Sales" as orderType, o.invoiceAmount, o.quantity, "" as return_date, 0 as r_quantity, "" as return_type');

		$amazon = array();
		$db->join(TBL_AZ_ACCOUNTS . " a", "o.account_id=a.account_id", "RIGHT");
		$db->join(TBL_PARTIES . " p", "a.party_id=p.party_id", "RIGHT");
		$db->joinWhere(TBL_PARTIES . " p", 'p.party_id', $party_id);
		$db->where('o.status', 'shipped');
		$db->where('o.shippedDate', array($startDate, $endDate), 'BETWEEN');
		$amazon_orders = $db->get(TBL_AZ_ORDERS . ' o', NULL, '"Amazon" as marketplace, a.account_name, o.orderId as baseId, CONCAT("OI", o.orderItemId) as orderItemId, o.shippedDate as order_date, "Sales" as orderType, o.orderTotal as invoiceAmount, o.quantity, "" as return_date, 0 as r_quantity, "" as return_type');

		// GET RETURNS
		$db->join(TBL_FK_ORDERS . ' o', "o.orderItemId=r.orderItemId", "RIGHT");
		$db->join(TBL_FK_ACCOUNTS . " a", "o.account_id=a.account_id", "RIGHT");
		$db->join(TBL_PARTIES . " p", "a.party_id=p.party_id", "RIGHT");
		$db->joinWhere(TBL_PARTIES . " p", 'p.party_id', $party_id);
		$db->where('o.status', 'SHIPPED');
		$db->where('r_shipmentStatus', 'return_completed');
		$db->where('r_completionDate', array($startDate, $endDate), 'BETWEEN');
		$flipkart_returns = $db->get(TBL_FK_RETURNS . ' r', NULL, '"Flipkart" as marketplace, a.account_name, CONCAT("\'", r.returnId) as baseId, CONCAT("OI", o.orderItemId) as orderItemId, o.shippedDate as order_date, "Return" as orderType, REPLACE(FORMAT(IFNULL(IF(o.quantity > r.r_quantity,(o.invoiceAmount * (r.r_quantity / o.quantity)),o.invoiceAmount) * -1,0),2),",", "") AS invoiceAmount, o.quantity, r.r_completionDate as return_date, r.r_quantity, r.r_source as return_type');
		$flipkart = array_merge($flipkart_orders, $flipkart_returns);

		$db->join(TBL_AZ_ORDERS . ' o', "o.orderId=r.orderId", "RIGHT");
		$db->join(TBL_AZ_ACCOUNTS . " a", "o.account_id=a.account_id", "RIGHT");
		$db->join(TBL_PARTIES . " p", "a.party_id=p.party_id", "RIGHT");
		$db->joinWhere(TBL_PARTIES . " p", 'p.party_id', $party_id);
		$db->where('o.status', 'shipped');
		$db->where('rShipmentStatus', 'return_completed');
		$db->where('rCompletionDate', array($startDate, $endDate), 'BETWEEN');
		$amazon_returns = $db->get(TBL_AZ_RETURNS . ' r', NULL, '"Amazon" as marketplace, a.account_name, r.orderId as baseId, CONCAT("OI", o.orderItemId) as orderItemId, o.shippedDate as order_date, "Return" as orderType, REPLACE(FORMAT(IFNULL(IF(o.quantity > r.rQty,(o.orderTotal * (r.rQty / o.quantity)),o.orderTotal) * -1,0),2),",", "") AS invoiceAmount, o.quantity, r.rCompletionDate as return_date, r.rQty as r_quantity, r.rSource as return_type');
		$amazon = array_merge($amazon_orders, $amazon_returns);

		$transactions = array_merge($flipkart, $amazon);
		$date = array_column($transactions, 'order_date');
		array_multisort($date, SORT_ASC, $transactions);

		$total_orders = count(array_merge($flipkart_orders, $amazon_orders));
		$output = array();
		foreach ($transactions as $transaction) {
			$commission = $this->get_ams_slab_rate($party_id, 'commission_fees', $transaction['order_date']);
			$fixed = $this->get_ams_slab_rate($party_id, 'fixed_fees', $transaction['order_date'], $transaction['invoiceAmount'], $total_orders);

			$processing_charge = $fixed['value'];
			$commission_charge = $commission['value'] . '%';

			if ($transaction['orderType'] == "Sales") {
				$total_fees = $processing_fee = round($fixed['value'], 2);
				$total_fees += $commission_fee = round($transaction['invoiceAmount'] * ($commission['value'] / 100), 2);
			} else if (date('m', strtotime($transaction['order_date'])) == date('m', strtotime($transaction['rCompletionDate']))) {
				$total_fees = $processing_fee = round($fixed['value'], 2);
				$commission_fee = 0;
				$commission_charge = 0;
			} else {
				$processing_charge = 0;
				$processing_fee = 0;
				$total_fees = $commission_fee = round(($transaction['invoiceAmount']) * ($commission['value'] / 100), 2);
			}

			$tax = round($total_fees * 0.18, 2);
			$total_charges = $total_fees + $tax;

			$output[] = array(
				'marketplace' => $transaction['marketplace'],
				'account_name' => $transaction['account_name'],
				'base_id' => $transaction['baseId'],
				'order_item_id' => $transaction['orderItemId'],
				'order_date' => $transaction['order_date'],
				'order_type' => $transaction['orderType'],
				'order_amount' => $transaction['invoiceAmount'],
				'order_quantity' => $transaction['quantity'],
				'return_date' => $transaction['return_date'],
				'return_quantity' => $transaction['r_quantity'],
				'return_type' => $transaction['return_type'],
				'fixed_fees_rate' => $processing_charge,
				'fixed_fees' => $processing_fee,
				'commission_fee_rate' => $commission_charge,
				'commission_fee' => $commission_fee,
				'taxable_fees' => $total_fees,
				'sgst' => round($tax / 2, 2),
				'cgst' => round($tax / 2, 2),
				'total_fees' => $total_charges
			);
		}

		return $output;
	}

	public function get_ams_slab_rate($party_id, $rate_type, $date, $gross_price = 0, $total_orders = 0)
	{
		global $db;
		$db->where('party_id', $party_id);
		$party_rate_slabs = json_decode($db->getValue(TBL_PARTIES, 'party_ams_rate_slab'), true);
		$slab_id = "";
		foreach ($party_rate_slabs as $party_rate_slab) {
			if ($date >= $party_rate_slab['startDate'] && $date <= $party_rate_slab['endDate']) {
				$slab_id = $party_rate_slab['slab_id'];
				break;
			}
		}

		$db->where('slab_id', $slab_id);
		// $db->where('start_date', $date, '<=');
		// $db->where('end_date', $date, '>=');
		if ($gross_price < 0) // RETURN TRANSACTION FIX
			$gross_price = $gross_price * -1;
		$rate_slab_card = json_decode($db->getValue(TBL_AMS_RATE_SLABS, 'slab_card'), true);
		if ($rate_type == "fixed_fees" && $rate_slab_card['fixed_fees']['type'] == "variable") {
			$return['gst'] = $rate_slab_card['fixed_fees']['gst'];
			$return['type'] = ($rate_slab_card['fixed_fees']['type'] == "percetage" ? "percetage" : "flat");
			foreach ($rate_slab_card['fixed_fees']['value'] as $order_value => $slab_cards) {
				list($min_orders, $max_orders) = explode('-', $order_value);
				if ($total_orders >= (int)$min_orders && $total_orders <= (int)$max_orders) {
					foreach ($slab_cards as $key => $value) {
						list($min_amount, $max_amount) = explode('-', $key);
						if ($gross_price >= $min_amount && $gross_price <= $max_amount) {
							if ($return['gst'] == "including") {
								$return['gst'] = "excluding";
								$return['value'] = number_format($value - ($value * (18 / 118)), 2, '.', '');
							} else {
								$return['value'] = $value;
							}
							return $return;
						}
					}
				}
			}
		} else {
			if ($rate_slab_card[$rate_type]['gst'] == "including") {
				$value = $rate_slab_card[$rate_type]['value'];
				$rate_slab_card[$rate_type]['gst'] = "excluding";
				$rate_slab_card[$rate_type]['value'] = number_format($value - ($value * (18 / 118)), 2, '.', '');
			}
			return $rate_slab_card[$rate_type];
		}
	}

	public static function send_mail_notification($invoice_id, $attachments = array())
	{
		global $db, $notification, $sms;

		$db->join(TBL_PARTIES . ' p', 'p.party_id=ai.party_id');
		$db->join(TBL_FIRMS . ' f', 'f.firm_id=ai.firm_id');
		$db->where('ams_invoice_id', $invoice_id);
		$invoice_details = $db->getOne(TBL_AMS_INVOICES . ' ai', 'f.firm_name, p.party_name, p.party_email, p.party_mobile, ai.ams_invoice_date');
		extract($invoice_details);

		$data = new stdClass();
		$data->medium = 'email';
		$data->subject = ucwords($party_name) . ' - Monthly Invoice | FIMS';
		$to_emails = array(
			"email" => $party_email,
			'name' => $party_name
		);
		$data->to[0] = (object)$to_emails;
		$data->to_others->bcc = 'support@skmienterprise.com';

		// SEND MAIL TO SELLER AND CC TO DISTRIBUTOR 
		$data->body = str_replace(array("##PARTYNAME##", "##MONTHYEAR##", "##FIRMNAME##"), array($party_name, date('F Y', strtotime($ams_invoice_date)), $firm_name), get_template('email', 'monthly_commission_invoice'));
		$data->attachments = $attachments;
		$mail = $notification->send($data);

		$sms_message = str_replace(array("##MONTHYEAR##", "##FIRMNAME##"), array(date('F Y', strtotime($ams_invoice_date)), $firm_name), get_template('sms', 'monthly_commission_invoice'));
		$sms->send_sms($sms_message, array('91' . $party_mobile), 'IMSDAS');

		return $return;
	}

	private static function convertToIndianWordCurrency($number)
	{
		$no = round($number);
		$decimal = round($number - ($no = floor($number)), 2) * 100;
		$digits_length = strlen($no);
		$i = 0;
		$str = array();
		$words = array(
			0 => '',
			1 => 'One',
			2 => 'Two',
			3 => 'Three',
			4 => 'Four',
			5 => 'Five',
			6 => 'Six',
			7 => 'Seven',
			8 => 'Eight',
			9 => 'Nine',
			10 => 'Ten',
			11 => 'Eleven',
			12 => 'Twelve',
			13 => 'Thirteen',
			14 => 'Fourteen',
			15 => 'Fifteen',
			16 => 'Sixteen',
			17 => 'Seventeen',
			18 => 'Eighteen',
			19 => 'Nineteen',
			20 => 'Twenty',
			30 => 'Thirty',
			40 => 'Forty',
			50 => 'Fifty',
			60 => 'Sixty',
			70 => 'Seventy',
			80 => 'Eighty',
			90 => 'Ninety'
		);
		$digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
		while ($i < $digits_length) {
			$divider = ($i == 2) ? 10 : 100;
			$number = floor($no % $divider);
			$no = floor($no / $divider);
			$i += $divider == 10 ? 1 : 2;
			if ($number) {
				$plural = (($counter = count($str)) && $number > 9) ? 's' : null;
				$str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
			} else {
				$str[] = null;
			}
		}

		$Rupees = implode(' ', array_reverse($str));
		$paise = ($decimal) ? "and " . ($decimal < 21 ? $words[$decimal] : ($words[$decimal - $decimal % 10]) . " " . ($words[$decimal % 10])) . ' Paise'  : '';
		return ($Rupees ? 'Rupees ' . $Rupees : '') . $paise . " Only";
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new accountant;
		}
		return self::$instance;
	}
}
