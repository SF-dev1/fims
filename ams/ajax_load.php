<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/includes/vendor/autoload.php'); // MPDF
	global $db, $log;

	switch ($_REQUEST['action']) {
		case 'get_invoices':
			$db->join(TBL_PARTIES . ' p', 'p.party_id=ai.party_id');
			$invoices = $db->get(TBL_AMS_INVOICES . ' ai', NULL, array('ai.ams_invoice_date', 'ai.ams_invoice_number', 'p.party_name', 'ai.ams_invoice_total', 'ai.ams_invoice_status', 'ai.ams_invoice_id'));

			$return['data'] = array();
			$i = 0;
			foreach ($invoices as $invoice) {
				$return['data'][$i] = $invoice;
				// $return['data'][$i]['pending_amount'] = $accountant->get_current_dues('ams', $invoice['party_id']);
				$return['data'][$i]['actions'] = '<a class="btn btn-default btn-xs" href="ajax_load.php?action=create_invoice_report&invoice_id=' . $invoice['ams_invoice_id'] . '" target="_blank" title="Invoice Report"><i class="fa fa-file-csv"></i></a> <a class="btn btn-default btn-xs" href="ajax_load.php?action=print_invoice&invoice_id=' . $invoice['ams_invoice_id'] . '" target="_blank" title="PDF Invoice"><i class="fa fa-file-invoice"></i></a> <a class="btn btn-default btn-xs send-mail" data-invoice_id="' . $invoice['ams_invoice_id'] . '" title="Email Invoice & Report"><i class="fa fa-envelope"></i></a>';
				$i++;
			}

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'create_invoice':
			$ams_sellers = get_all_vendors(true);
			$startDate = date('Y-m-d 00:00:00', strtotime(date('Y-m') . " -1 month"));
			$endDate = date('Y-m-t 23:59:59', strtotime(date('Y-m') . " -1 month"));
			$ams_invoice_ids = array();
			foreach ($ams_sellers as $sellers) {
				$return = $accountant->create_ams_invoice($sellers->party_id, array('startDate' => $startDate, 'endDate' => $endDate));
				$ams_invoice_ids[$return['ams_invoice_id']]['type'] = $return['type'];
				$ams_invoice_ids[$return['ams_invoice_id']]['msg'] = $return['msg'];
			}
			$return = $ams_invoice_ids;

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'create_invoice_report':
			$invoice_id = $_GET['invoice_id'];
			$accountant->create_ams_invoice_csv($invoice_id, "download");
			break;

		case 'print_invoice':
			$invoice_id = $_GET['invoice_id'];
			$invoice = $accountant->generate_tax_invoice_pdf($invoice_id, 'ams');
			break;

		case 'email_invoice_pdf_csv':
			if (isset($_GET['invoice_id'])) {
				$invoices[] = array('ams_invoice_id' => $_GET['invoice_id']);
			} else {
				$db->where('ams_invoice_date', date('Y-m-t', strtotime(date('Y-m') . " -1 month")));
				$invoices = $db->get(TBL_AMS_INVOICES, NULL, 'ams_invoice_id');
			}

			$return = array();
			foreach ($invoices as $invoice) {
				$invoice_id = $invoice['ams_invoice_id'];
				$invoice = $accountant->generate_tax_invoice_pdf($invoice_id, 'ams', 'save');
				$csv = $accountant->create_ams_invoice_csv($invoice_id, 'save');
				$return[$invoice_id] = array("type" => "error", "error" => array('invoice' => $invoice, 'csv' => $csv));
				if ($invoice && $csv) {
					$response = $accountant->send_mail_notification($invoice_id, array($invoice, $csv));
					if ($response === true)
						$return[$invoice_id] = array("type" => "success", "msg" => "Email Successfully sent");
					else
						$return[$invoice_id] = array("type" => "success", "msg" => "Unable to send email", "error" => $response);
				}
			}

			echo json_encode($return);
			break;

		case 'get_pending_invoices':
			$db->where('party_id', $_GET['party_id']);
			$db->where('(ams_invoice_status =?  OR ams_invoice_status =?)', array('pending', 'partial-paid'));
			$invoices = $db->get(TBL_AMS_INVOICES, NULL, 'ams_invoice_id, ams_invoice_number, ams_invoice_total');

			// $transactions = 
			if ($invoices)
				$return = array('type' => 'success', 'data' => $invoices);
			else
				$return = array('type' => 'error', 'msg' => 'Unable to get pending invoices details');

			echo json_encode($return);
			break;

		case 'save_payment':
			$details['party_id'] = $_POST['party_id'];
			$details['payment_amount'] = $_POST['payment_amount'];
			$details['insertDate'] = date('Y-m-d ', strtotime($_POST['payment_date'])) . date('H:i:s', time());
			$details['payment_mode'] = $_POST['payment_mode'];
			$details['payment_reference'] = $_POST['payment_reference'];
			$details['payment_notes'] = $_POST['payment_remarks'];
			$payment_id = $_POST['payment_id'];
			if ($payment_id != "") {
				unset($details['insertDate']);
				$db->where('ams_payment_id', $payment_id);
				if ($db->update(TBL_AMS_PAYMENTS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully updated payment'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to update payment'));
			} else {
				if ($db->insert(TBL_AMS_PAYMENTS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully added new payment'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to added new payment', 'error' => $db->getLastError()));
			}
			break;

		case 'delete_payment':
			$payment_id = $_POST['payment_id'];
			$db->where('ams_payment_id', $payment_id);
			if ($db->delete(TBL_SALE_PAYMENTS)) {
				echo json_encode(array('type' => 'success', 'msg' => 'Successfully deleted transaction.'));
			} else {
				echo json_encode(array('type' => 'success', 'msg' => 'Error deleting transaction.'));
			}
			break;

		case 'get_vendor_ledger':
			$party_id = $_GET['party_id'];
			$start_date = strtotime($_GET['start_date'] . ' 00:00:00');
			$end_date = strtotime($_GET['end_date'] . ' 23:59:59');

			$ledger = $accountant->get_party_leadger("vendor", $party_id, NULL, array('start_date' => $start_date, 'end_date' => $end_date));
			$transactions_details = "";
			foreach ($ledger as $ledger_item) {
				$transactions_details .= '<tr>
									<td>
										' . $ledger_item["date"] . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										' . $ledger_item["amount_cr"] . '
									</td>
									<td>
										' . $ledger_item["balance"] . '
									</td>
								</tr>';
			}
			// echo '<table cellpadding=5 border=1>'.$transactions_details.'</table>';
			$return['transactions'] = $transactions_details;
			echo json_encode($return);
			break;

		case 'get_payments_details':
			$return['due'] = $accountant->get_current_dues('vendor', $_GET['party_id']);
			$ledger = $accountant->get_all_transactions('vendor_payment', $_GET['party_id'], 10);
			$transactions_details = "";
			foreach ($ledger as $ledger_item) {
				$paymentId = (int) filter_var($ledger_item["description"], FILTER_SANITIZE_NUMBER_INT);
				$paymentMode = strtolower(strtok($ledger_item["description"], ' '));
				$transactions_details .= '<tr>
									<td>
										' . $ledger_item["date"] . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										<a href="#" data-paymentid="' . $paymentId . '" data-amount="' . $ledger_item["amount"] . '" data-date="' . $ledger_item["date"] . '" data-mode="' . ucfirst($paymentMode) . '" data-reference="' . $ledger_item["reference"] . '" data-notes="' . $ledger_item["motes"] . '" class="btn btn-default btn-xs black payment_edit"><i class="fa fa-edit"></i> Edit</a>
										<a href="#" data-paymentid="' . $paymentId . '" class="btn btn-default btn-xs black payment_delete"><i class="fa fa-trash"></i> Delete</a>
									</td>
								</tr>';
			}
			// echo '<table cellpadding=5 border=1>'.$transactions_details.'</table>';
			$return['transactions'] = $transactions_details;
			echo json_encode($return);
			break;

		case 'ledger_print':
			$party_id = $_GET['party_id'];
			$start_date = strtotime($_GET['start_date'] . ' 00:00:00');
			$end_date = strtotime($_GET['end_date'] . ' 23:59:59');

			$db->where('party_id', $party_id);
			$details = $db->getOne(TBL_PARTIES);

			$ledger = $accountant->get_party_leadger("vendor", $party_id, NULL, array('start_date' => $start_date, 'end_date' => $end_date));
			$transactions_details = "";
			$sr_no = 1;
			foreach ($ledger as $ledger_item) {
				$ledger_date = isset($ledger_item["date"]) ? date('d M, Y', strtotime($ledger_item["date"])) : "";
				$transactions_details .= '<tr>
									<td>
										' . $sr_no++ . '.
									</td>
									<td>
										' . $ledger_date . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										' . $ledger_item["amount_cr"] . '
									</td>
									<td>
										' . $ledger_item["balance"] . '
									</td>
								</tr>';
			}

			$html = '
				<html>
				<head>
				    <style>
					    body {
					        font-family: sans-serif;
					        font-size: 10pt;
					    }

					    p {
					        margin: 0pt;
					    }

					    table.items {
					        border: 0.1mm solid #000000;
					    }

					    td {
					        vertical-align: top;
					    }

					    .items td {
					        border-left: 0.1mm solid #000000;
					        border-right: 0.1mm solid #000000;
					    }

					    table thead td {
					        background-color: #EEEEEE;
					        text-align: center;
					        border: 0.1mm solid #000000;
					        font-variant: small-caps;
					    }

					    .items td.blanktotal {
					        background-color: #EEEEEE;
					        border: 0.1mm solid #000000;
					        background-color: #FFFFFF;
					        border: 0mm none #000000;
					        border-top: 0.1mm solid #000000;
					        border-right: 0.1mm solid #000000;
					    }

					    .items td.totals {
					        text-align: right;
					        border: 0.1mm solid #000000;
					    }

					    .items td.cost {
					        text-align: "."right;
					    }
					    .barcode {
							padding: 1.5mm;
							margin: 0;
							vertical-align: top;
							color: #000000;
						}
						.barcodecell {
							text-align: center;
							vertical-align: middle;
							padding: 0;
						}
						.items td.due{
							color: #FFF;
							background-color: #000;
					        border: 0mm none #000000;
					        border-left: 0.1mm solid #FFFFFF;
						}
				    </style>
				</head>
				<body>
				<!--mpdf
				
				<htmlpageheader name="myheader">
					<table width="100%">
						<tr>
							<td colspan="3" style="vertical-align: middle; text-align: center;">
								<span style="font-weight: bold; font-size: 20pt;">AMS Vendor Ledger</span>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="vertical-align: bottom; text-align: center;">
								<span style="font-size:9pt; color: #555555; font-family: sans;">LEDGER PERIOD:&nbsp;</span>' . strtoupper($_GET["start_date"] . ' - ' . $_GET["end_date"]) . '
							</td>
						</tr>
					</table>
					<hr / >
					<table width="100%">
						<tr>
							<td width="45%">
								<span style="font-size:9pt; color: #555555; font-family: sans;">VENDOR NAME:&nbsp;</span>' . strtoupper($details['party_name']) . '
							</td>
							<td width="10%">&nbsp;</td>
							<td width="45%" style="text-align: right">
								<div style="text-align: right"><span style="font-size:9pt; color: #555555; font-family: sans;">DATE:&nbsp;</span> ' . date('dS F Y', time()) . '</div>
							</td>
						</tr>
					</table>
					<hr />
				</htmlpageheader>

				<htmlpagefooter name="myfooter">
					<div style="border-top: 1px solid #000000; font-size: 9pt; text-align: right; padding-top: 3mm; ">
						Page {PAGENO} of {nb}
					</div>
				</htmlpagefooter>

				<sethtmlpageheader name="myheader" value="on" show-this-page="all" />
				<sethtmlpagefooter name="myfooter" value="on" />

				mpdf-->
				    <table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
				        <thead>
				            <tr>
				            	<td width="05%">#</td>
				                <td width="15%">Date</td>
				                <td width="35%">Particulars</td>
				                <td width="15%">DR</td>
				                <td width="15%">CR</td>
				                <td width="15%">Balance</td>
				            </tr>
				        </thead>
				        <tbody>
				            <!-- ITEMS HERE -->
				            ' . $transactions_details . '
				            <!-- END ITEMS HERE -->
				        </tbody>
				    </table>
				</body>

				</html>
			';

			$mpdf = new \Mpdf\Mpdf([
				// 'format' => 'A4-P',
				'margin_left' => 5,
				'margin_right' => 5,
				'margin_top' => 40,
				'margin_bottom' => 10,
				'margin_header' => 5,
				'margin_footer' => 8
			]);

			$title = "AMS LEDGER - " . strtoupper($details['party_name']);
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle($title);
			$mpdf->SetAuthor(strtoupper($details['party_name']));
			$mpdf->SetDisplayMode('fullpage');

			try {
				$mpdf->WriteHTML($html);
			} catch (\Mpdf\MpdfException $e) {
				die($e->getMessage());
			}

			$mpdf->Output($title . '.pdf', 'I');
			break;

		case 'get_ams_rate_slab':
			$slabs_raw = $db->objectBuilder()->get(TBL_AMS_RATE_SLABS);
			$slabs = new stdClass();
			$i = 0;
			foreach ($slabs_raw as $slab) {
				$slabs->{$slab->slab_name_slug}->{'slab_name'} = $slab->slab_name;
				$slabs->{$slab->slab_name_slug}->{'slab_charges'}[$i]->{'id'} = $slab->slab_id;
				// $slabs->{$slab->slab_name_slug}->{'slab_charges'}[$i]->{'start_date'} = $slab->start_date;
				// $slabs->{$slab->slab_name_slug}->{'slab_charges'}[$i]->{'end_date'} = $slab->end_date;
				$slabs->{$slab->slab_name_slug}->{'slab_charges'}[$i]->{'slab_card'} = json_decode($slab->slab_card);
				$i++;
			}

			$table_content = array();
			$i = 0;
			foreach ($slabs as $slab) {
				foreach ($slab->slab_charges as $slab_config) {
					$table_content['data'][$i]['name'] = $slab->slab_name;
					$table_content['data'][$i]['start_date'] = $slab_config->start_date;
					$table_content['data'][$i]['end_date'] = $slab_config->end_date;
					foreach ($slab_config->slab_card as $fee_name => $fees) {
						// $slab_card .= '<b>'.ucwords(str_replace('_', ' ', $fee_name)).'</b>';
						$slab_card = "";
						if (is_object($fees->value)) {
							foreach ($fees->value as $order_volume => $selling_prices) {
								if (strpos($order_volume, '-') === FALSE)
									$slab_card .= ' <b>Orders: ' . $order_volume . '+</b><br />';
								else
									$slab_card .= ' <b>Orders: ' . $order_volume . '</b><br />';

								foreach ($selling_prices as $selling_price => $value) {
									if (strpos($selling_price, '-') === FALSE)
										$slab_card .= ' SP: ' . $selling_price . '+';
									else
										$slab_card .= ' SP: ' . $selling_price;

									if ($fees->type == "percentage")
										$slab_card .= ' @ ' . $value . '%';
									else
										$slab_card .= ' @ ₹' . $value;

									$slab_card .= ' (' . ucfirst($fees->gst) . ' GST)<br />';
								}
							}
						} else {
							if ($fees->type == "percentage")
								$slab_card .= $fees->value . '%';
							else
								$slab_card .= '₹' . $fees->value;

							$slab_card .= ' (' . ucfirst($fees->gst) . ' GST)';
						}
						$table_content['data'][$i][$fee_name] = $slab_card;
					}
					$table_content['data'][$i]['actions'] = "<button class='btn btn-xs btn-default' data-slabid='" . $slab_config->id . "'><i class='fa fa-edit'></i> Edit</button>&nbsp;<button data-target='#apply-rateSlab' data-slabid='" . $slab_config->id . "' data-slabname='" . $slab->slab_name . "' role='button' class='btn btn-xs btn-default apply_slab' data-toggle='modal'>Apply Slab</button>";
					$i++;
				}
			}
			header('Content-Type: application/json');
			echo json_encode($table_content);
			break;

		case 'add_ams_rate_slab':
			$card = array(
				'commission_fees' => array(
					'value' => $_POST['commission_fees'],
					'type' => 'percentage',
					'gst' => (isset($_POST['commission_fees_gst']) ? 'including' : 'excluding')
				),
				'fixed_fees' => array(
					'value' => $_POST['fixed_fees'],
					'type' => $_POST['fixed_fees_type'],
					'gst' => (isset($_POST['fixed_fees_gst']) ? 'including' : 'excluding')
				)
			);

			if ($_POST['fixed_fees_type'] == "variable") {
				$order_value_slab = array();
				foreach ($_POST['fixed_fees'] as $fixed_fee_slabs) {
					foreach ($fixed_fee_slabs as $slab_type => $slab_values) {
						$order_volume = $slab_values['min'] . '-' . $slab_values['max'];
						$selling_price =  $slab_values['selling_price']['min'] . '-' . $slab_values['selling_price']['max'];
						$order_value_slab[$order_volume][$selling_price] = $slab_values['selling_price']['rate'];
					}
				}
				$card['fixed_fees']['value'] = $order_value_slab;
			}

			$details = array(
				'slab_name' => $_POST['slab_name'],
				'slab_name_slug' => str_replace(array(' ', '-'), '_', strtolower($_POST['slab_name'])),
				// 'start_date' => date('Y-m-d 00:00:00', strtotime($_POST['start_date'])),
				// 'end_date' => date('Y-m-d 23:59:59', strtotime($_POST['end_date'])),
				'is_active' => true,
				'createdDate' => $db->now(),
				'slab_card' => json_encode($card)
			);

			if ($db->insert(TBL_AMS_RATE_SLABS, $details))
				echo json_encode(array('type' => 'success', 'msg' => 'Successfully added new slab.'));
			else
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to add new slab', 'error' => $db->getLastError()));
			break;

		case 'set_ams_rate_slab':
			$vendor_id = $_POST['vendor'];
			$db->where('party_id', $vendor_id);
			$party_ams_rate_slab = json_decode($db->getValue(TBL_PARTIES, 'party_ams_rate_slab'));

			$new_slab = new stdClass;
			$new_slab->slab_id = $_POST['slab_id'];
			$new_slab->startDate = date('Y-m-d 00:00:00', strtotime($_POST['start_date']));
			if (!empty($_POST['end_date']))
				$new_slab->endDate = date('Y-m-d 23:59:59', strtotime($_POST['end_date']));
			else
				$new_slab->endDate = date('Y-m-d 23:59:59', strtotime($_POST['start_date'] . ' + 2years'));

			$party_ams_rate_slab[] = $new_slab;

			$db->where('party_id', $vendor_id);
			if ($db->update(TBL_PARTIES, array('party_ams_rate_slab' => json_encode($party_ams_rate_slab))))
				echo json_encode(array('type' => 'success', 'msg' => 'Successfully applied slab.'));
			else
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to apply slab', 'error' => $db->getLastError()));

			break;
	}
}
