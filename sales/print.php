<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include(ROOT_PATH . '/includes/vendor/autoload.php');

// Activate Session and get current_user data
$userAccess->sec_session_start();
$userAccess->login_check();

// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
?>
<?php
$type = $_REQUEST['action'];
if ($type == 'print_sales_order' || $type == 'view_sales_order') {
	$so_id = $_REQUEST['id'];
	$client_id = $_REQUEST['client_id'];
	if ($client_id != 0) {
		$db->where('party_id', $client_id);
		$client_name = $db->getValue(TBL_PARTIES, 'party_name');
	}

	$client_table = $client_id == 0 ? "" : '_' . $client_id;
	$db->join(TBL_PARTIES . ' p', 'p.party_id=so.so_party_id', 'LEFT');
	$db->where('so_id', $so_id);
	$party = $db->getOne(TBL_SALE_ORDER . $client_table . ' so', 'p.party_id, p.party_name, so.createdDate, so.so_status, so.so_notes');

	$db->where('so_id', $so_id);
	// $db->join(TBL_PRODUCTS_MASTER.' pm', '(CONCAT("pid-", pm.pid))=soi.item_id');
	$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=soi.item_id');
	$db->where('soi.client_id', $client_id);
	$sos['items'] = $db->get(TBL_SALE_ORDER_ITEMS . ' soi', null, 'soi.item_id, pm.sku as item_name, soi.item_price, soi.item_qty');
	$details = array_merge(array('so_id' => $so_id), $party, $sos);
	if (is_null($details['createdDate']))
		$details['createdDate'] = date('d M Y', time());

	$is_draft_prefix = "";
	if ($party['so_status'] == "draft")
		$is_draft_prefix = "PERFORMA ";

	$is_cancelled = "";
	if ($party['so_status'] == "cancelled")
		$is_cancelled = 1;

	$so_notes = json_decode($party['so_notes'], true);

	if ($party['party_id'] == 0) {
		$details['party_name'] = $so_notes['customer_name'];
	}

	$i = 1;
	$total_due = $accountant->get_current_dues('customer', $party['party_id']);
	$total = $total_qty = 0;
	$item_details = "";
	foreach ($details['items'] as $item) {
		$currency_symbol = "&#8377;";
		$sum_qty = $item['item_qty'];
		$total_qty += $sum_qty;

		$sum_total = (int)$item['item_qty'] * (float)$item['item_price'];
		$total += $sum_total;

		$item_details .= "<tr>
			<td align='center'>
				" . $i . ".
			</td>
			<td>
				" . $item['item_name'] . "
			</td>
			<td class='hidden-480'>
				" . $currency_symbol . $item['item_price'] . "
			</td>
			<td class='hidden-480'>
				" . $sum_qty . "
			</td>
			<td>
				" . $currency_symbol . $sum_total . "
			</td>
		</tr>";
		$i++;
	}

	if ($type == 'view_sales_order') {
?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
			<h4 class="modal-title">View Sales Order</h4>
		</div>
		<div class="modal-body printable-content invoice">
			<div class="row invoice-logo">
				<div class="col-xs-6 invoice-logo-space">
					<?php echo $is_draft_prefix; ?>SALES ORDER<br />
					<div class="vendor_details">Customer: <br />
						<span> <?php echo strtoupper($details['party_name']); ?></span>
					</div>
				</div>
				<div class="col-xs-6">
					<p>
						#<?php echo sprintf('SO_%06d', $details['so_id']); ?> / <?php echo date('d M Y', strtotime($details['createdDate'])); ?>
						<span class="barcode"><?php echo sprintf('SO_%06d', $details['so_id']); ?></span>
					</p>
				</div>
			</div>
			<hr />
			<div class="row">
				<div class="col-xs-12">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th width="5%" style="text-align:center;">
									#
								</th>
								<th width="55%">
									Item & Description
								</th>
								<th width="15%">
									Unit Price
								</th>
								<th width="10%">
									Quantity
								</th>
								<th width="15%">
									Amount
								</th>
							</tr>
						</thead>
						<tbody>
							<?php echo $item_details; ?>
							<tr>
								<td colspan="3"><strong>Total:</strong></td>
								<td class="hidden-480">
									<strong><?php echo $total_qty; ?> pcs</strong>
								</td>
								<td>
									<strong><?php echo $currency_symbol . $total; ?></strong>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="well">
						<strong>Notes: </strong><?php echo htmlspecialchars_decode($so_notes['notes']); ?>
					</div>
				</div>
				<!-- <div class="col-xs-8 invoice-block">
						<ul class="list-unstyled amounts">
							<li>
								<strong>Sub - Total amount:</strong> $9265
							</li>
							<li>
								<strong>Discount:</strong> 12.9%
							</li>
							<li>
								<strong>VAT:</strong> -----
							</li>
							<li>
								<strong>Total Qty:</strong> 
							</li>
							<li>
								<strong>Grand Total:</strong> 
							</li>
						</ul>
					</div> -->
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-info preview-print" data-so_id="<?php echo $details['so_id']; ?>" data-client_id="<?php echo $client_id ?>">Print <i class="fa fa-print"></i></button>
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		</div>
<?php }

	if ($type == 'print_sales_order') {
		if ($party['party_id'] == 0) {
			$total_due = $total;
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
				            <td width="50%" style="vertical-align: middle; text-align: left;"><span style="font-weight: bold; font-size: 24pt; text-align: left;">' . ucfirst(strtolower($is_draft_prefix)) . 'Sales Order</span></td>
				            <td width="15%" style="text-align: right; vertical-align: middle;">Sales Order #<br /><span style="font-weight: bold; font-size: 10pt;">' . sprintf('SO_%06d', $details['so_id']) . '</span></td>
				            <td width="35%">
				                <barcode code="' . sprintf('SO_%06d', $details['so_id']) . '" type="C128B" class="barcode" />
				            </td>
				        </tr>
				    </table>
				    <hr />
				    <table width="100%" style="font-family: serif;" cellpadding="0">
				        <tr>
				            <td width="45%">
				            	<span style="font-size:9pt; color: #555555; font-family: sans;">CUSTOMER NAME:&nbsp;</span>' . strtoupper($details['party_name']) . '
				            </td>
				            <td width="10%">&nbsp;</td>
				            <td width="45%" style="text-align: right">
				            	<div style="text-align: right"><span style="font-size:9pt; color: #555555; font-family: sans;">DATE:&nbsp;</span> ' . date('dS F Y', strtotime($details['createdDate'])) . '</div>
				            </td>
				        </tr>
				    </table>
				    <hr />
				</htmlpageheader>
				<htmlpagefooter name="myfooter">
					<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
						<tr>
			                <td width="66%" rowspan="4">
			                	<b>Notes: </b><span>' . htmlspecialchars_decode($so_notes["notes"]) . '</span>
			                </td>
			                <td width="20%" class="totals">Subtotal:</td>
			                <td width="15%" class="totals cost">' . $currency_symbol . number_format($total, 2, ".", ",") . '</td>
			            </tr>
			            <tr>
			                <td width="20%" class="totals">Paid:</td>
			                <td width="15%" class="totals cost">' . $currency_symbol . '0.00</td>
			            </tr>
			            <tr>
			                <td width="20%" class="totals"><b>Order Total:</b></td>
			                <td width="15%" class="totals cost"><b>' . $currency_symbol . number_format($total, 2, ".", ",") . '</b></td>
			            </tr>
			            <tr>
			                <td width="20%" class="totals due"><b>Total Due Amount:</b></td>
			                <td width="15%" class="totals cost due"><b>' . $currency_symbol . number_format($total_due, 2, ".", ",") . '</b></td>
			            </tr>
					</table>				
					<div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
						<strong>For Office Use:</strong>
						<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
							<tr>
								<td width="33.33%">Prepared by: ' . $current_user['user_nickname'] . '</td>
								<td width="33.33%">Picked by:</td>
								<td width="33.33%">Checked by:</td>
							</tr>
						</table>
					</div>
					<div style="border-top: 1px solid #000000; font-size: 9pt; padding-top: 3mm; ">
						<table class="footer" width="100%" style="border-collapse: collapse; " cellpadding="0">
							<tr>
								<td style="text-align: left;">
									' . $client_name . '
								</td>
								<td style="text-align: right;">
									Page {PAGENO} of {nb}
								</td>
							</tr>
						</table>
					</div>
				</htmlpagefooter>
				<sethtmlpageheader name="myheader" value="on" show-this-page="all" />
				<sethtmlpagefooter name="myfooter" value="on" />
			mpdf-->
			    <table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
			        <thead>
			            <tr>
			                <td width="5%">#</td>
			                <td width="55%">Particulars</td>
			                <td width="15%">Unit Price</td>
			                <td width="10%">Quantity</td>
			                <td width="15%">Amount</td>
			            </tr>
			        </thead>
			        <tbody>
			            <!-- ITEMS HERE -->
			            ' . $item_details . '
			            <!-- END ITEMS HERE -->
			        </tbody>
			    </table>
			</body>

			</html>
		';

		$mpdf = new \Mpdf\Mpdf([
			// 'format' => 'A4-P',
			'margin_left' => 10,
			'margin_right' => 5,
			'margin_top' => 40,
			'margin_bottom' => 60,
			'margin_header' => 5,
			'margin_footer' => 8,
			'showBarcodeNumbers' => FALSE
		]);

		$title = sprintf('SO_%06d', $details['so_id']) . " - " . strtoupper($details['party_name']);
		$mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor(strtoupper($details['party_name']));
		$mpdf->SetDisplayMode('fullpage');
		if ($is_cancelled || !empty($is_draft_prefix)) {
			$mpdf->SetWatermarkText('CANCELLED');
			if (!empty($is_draft_prefix))
				$mpdf->SetWatermarkText('PROFORMA');
			$mpdf->watermark_font = 'DejaVuSansCondensed';
			$mpdf->showWatermarkText = true;
		}

		try {
			$mpdf->WriteHTML($html);
		} catch (\Mpdf\MpdfException $e) {
			die($e->getMessage());
		}

		$mpdf->Output($title . '.pdf', 'I');
		$mpdf->WriteHTML($html);
	}
}

if ($type == 'customer_ledger_print') {
	$party_id = $_GET['party_id'];
	$start_date = strtotime($_GET['start_date'] . ' 00:00:00');
	$end_date = strtotime($_GET['end_date'] . ' 23:59:59');

	$db->where('party_id', $party_id);
	$details = $db->getOne(TBL_PARTIES);

	$ledger = $accountant->get_party_leadger("customer", $party_id, NULL, array('start_date' => $start_date, 'end_date' => $end_date));
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
						<span style="font-weight: bold; font-size: 20pt;">Customer Ledger</span>
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
						<span style="font-size:9pt; color: #555555; font-family: sans;">CUSTOMER NAME:&nbsp;</span>' . strtoupper($details['party_name']) . '
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
		// 'margin_left' => 20,
		// 'margin_right' => 15,
		// 'margin_top' => 45,
		// 'margin_bottom' => 25,
		// 'margin_header' => 10,
		// 'margin_footer' => 10
	]);

	$title = "Ledger - " . strtoupper($details['party_name']);
	$mpdf->SetProtection(array('print'));
	$mpdf->SetTitle($title);
	$mpdf->SetAuthor(strtoupper($details['party_name']));
	$mpdf->SetDisplayMode('fullpage');
	// if ($is_cancelled || !empty($is_draft_prefix)){
	// 	$mpdf->SetWatermarkText('CANCELLED');
	// 	if (!empty($is_draft_prefix))
	// 		$mpdf->SetWatermarkText('PROFORMA');
	// 	$mpdf->watermark_font = 'DejaVuSansCondensed';
	// 	$mpdf->showWatermarkText = true;
	// }

	try {
		$mpdf->WriteHTML($html);
	} catch (\Mpdf\MpdfException $e) {
		die($e->getMessage());
	}

	$mpdf->Output($title . '.pdf', 'I');

	$mpdf->WriteHTML($html);
}

?>