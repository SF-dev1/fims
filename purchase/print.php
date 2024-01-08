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
$action = $_REQUEST['action'];
switch ($action) {
	case 'print_labels':
		$type = $_REQUEST['type'];
		switch ($type) {
			case 'ctn_label':
				$grn_id = $_REQUEST['id'];
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.item_id=pgi.item_id", "LEFT");
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.po_id=pgi.po_id");
				$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', "poi.item_id=poil.item_id", "LEFT");
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS . " poi", "poi.po_id=poil.po_id");
				$db->join(TBL_PURCHASE_GRN . ' pg', "pg.grn_id=pgi.grn_id", "LEFT");
				$db->join(TBL_PURCHASE_LOT . ' pl', "pl.lot_id=pg.lot_id", "LEFT");

				$db->join(TBL_PURCHASE_ORDER . ' po', 'poi.po_id=po.po_id', 'LEFT');
				$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'LEFT');
				$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=pgi.item_id');
				$db->join(TBL_PRODUCTS_BRAND . " b", "b.brandid=pm.brand");
				$db->join(TBL_PRODUCTS_CATEGORY . " pc", "pm.category=pc.catid");
				$db->where('pgi.grn_id', $grn_id);
				$grn_details = $db->objectBuilder()->get(TBL_PURCHASE_GRN_ITEMS . " pgi", NULL, array('pgi.po_id', 'pm.sku', 'pl.lot_number', 'SUBSTR(UPPER(p.party_name), 1, 2) as party_name', 'SUBSTR(UPPER(b.brandName), 1, 2) as brandName', 'SUBSTR(UPPER(pc.categoryName), 1, 2) as categoryName', 'pgi.item_qty', 'pgi.item_box'));

				$mpdf = new \Mpdf\Mpdf([
					// 'format' => 'A4-P',
					'margin_left' => 2,
					'margin_right' => 2,
					'margin_top' => 2,
					'margin_bottom' => 2,
					'showBarcodeNumbers' => TRUE,
					'mode' => 'utf-8',
					'format' => [50, 20],
					'default_font' => 'sans-serif',
					// 'orientation' => 'L'
				]);

				$title = sprintf('GRN_%06d', $grn_id);
				$mpdf->SetProtection(array('print'));
				$mpdf->SetTitle($title);
				$mpdf->SetAuthor('FIMS');
				$mpdf->SetDisplayMode('fullpage');

				foreach ($grn_details as $grn_detail) {
					for ($i = 0; $i < $grn_detail->item_box; $i++) {
						$mpdf->AddPage();
						$mpdf->SetFontSize(20);
						$mpdf->SetXY(0, 1);
						$mpdf->SetFont('sans-serif', 'B');
						$mpdf->WriteCell(0, 6, $grn_detail->lot_number, 0, 1, 'C');
						$mpdf->SetXY(0, 9);
						$mpdf->AutosizeText($grn_detail->sku, 50, "sans-serif", "B", "10");
						$mpdf->SetXY(0, 13);
						$mpdf->SetFontSize(10);
						$mpdf->WriteCell(0, 0, sprintf('PO_%06d', $grn_detail->po_id), 0, 1, 'C');
						$mpdf->SetXY(0, 17);
						$mpdf->WriteCell(20, 0, 'QTY:', 0, 1, 'L');
						$mpdf->SetXY(30, 17);
						$mpdf->WriteCell(10, 0, 'BOX:', 0, 1, 'R');
					}
				}

				$mpdf->Output($title . '.pdf', 'I');

				break;
		}
		break;

	case 'purchase_order':
		$po_id = $_GET['po_id'];

		$details['po_id'] = $po_id;

		$db->join(TBL_PURCHASE_ORDER . ' po', 'poi.po_id=po.po_id', 'INNER');
		// $db->joinWhere(TBL_PURCHASE_ORDER.' po', 'po.po_status', 'created');
		$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'INNER');
		$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=poi.item_id', 'INNER');
		$db->where('poi.po_id', $po_id);
		$items = $db->get(TBL_PURCHASE_ORDER_ITEMS . ' poi', null, 'poi.po_id, p.party_name, pm.sku, poi.item_qty, poi.item_status, pm.thumb_image_url, COALESCE(po.createdDate, po.insertDate) as createdDate');
		$details['po_id'] = $items[0]['po_id'];
		$details['party_name'] = $items[0]['party_name'];
		$details['status'] = $items[0]['item_status'];
		$details['createdDate'] = $items[0]['createdDate'];
		$item_details = "<tr>";
		$i = 0;
		foreach ($items as $item) {
			if ($i % 4 == 0 && $i != 0) {
				$item_details .= '</tr><tr>';
			}
			$i++;
			$item_details .= "<td width='25%'>
							<table width='100%' style='border-collapse: collapse; text-align: center; margin-bottom: 15px;' border='1' cellpadding='0'>
								<tr>
									<td class='thead'>
										" . $item['sku'] . "
									</td>
								</tr>
								<tr>
									<td>
										<img src='../uploads/products/" . $item['thumb_image_url'] . "' height='150' width='150' style='padding: 3mm;'>
									</td>
								</tr>
								<tr>
									<td style='padding: 1mm 0;'>
										QTY: " . $item['item_qty'] . "
									</td>
								</tr>
							</table>
						</td>";
		}
		$item_details .= "</tr>";

		$is_draft_prefix = "";
		if ($details['status'] == "draft")
			$is_draft_prefix = "PERFORMA ";

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
						font-size: 12px;
					}
					td {
						vertical-align: top;
					}
					table .thead {
						background-color: #EEEEEE;
						text-align: center;
						border: 0.1mm solid #000000;
						font-variant: small-caps;
						padding: 1mm 0;
						vertical-align: middle;
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
				</style>
			</head>
			<body>
			<!--mpdf
				<htmlpageheader name="myheader">
					<table width="100%">
						<tr>
							<td width="50%" style="vertical-align: middle; text-align: left;"><span style="font-weight: bold; font-size: 24pt; text-align: left;">Purchase Order</span></td>
							<td width="15%" style="text-align: right; vertical-align: middle;">Purchases Order <br />#<span style="font-weight: bold; font-size: 10pt;">' . sprintf('PO_%06d', $details['po_id']) . '</span></td>
							<td width="35%">
								<barcode code="' . sprintf('PO_%06d', $details['po_id']) . '" type="C128B" class="barcode" />
							</td>
						</tr>
					</table>
					<hr />
					<table width="100%" cellpadding="0">
						<tr>
							<td width="45%">
								<span style="font-size:9pt;color:#555555;font-family:sans;">SUPPLIER NAME:&nbsp;</span>' . strtoupper($details['party_name']) . '
							</td>
							<td width="10%">&nbsp;</td>
							<td width="45%" style="text-align:right">
								<span style="font-size:9pt;color:#555555;font-family:sans;">DATE:&nbsp;</span> ' . date('dS F Y', strtotime($details['createdDate'])) . '
							</td>
						</tr>
					</table>
					<hr />
				</htmlpageheader>
				<htmlpagefooter name="myfooter">
					<div style="border-top: 1px solid #000000; font-size: 9pt; padding-top: 3mm; ">
						<table class="footer" width="100%" style="border-collapse: collapse; " cellpadding="0">
							<tr>
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
				<table class="items" width="100%" style="font-size:9pt;border-collapse:collapse;" cellpadding="0">
					<!-- START ITEMS HERE -->
					' . $item_details . '
					<!-- END ITEMS HERE -->
				</table>
			</body>

			</html>
		';

		// echo $html;

		$mpdf = new \Mpdf\Mpdf([
			// 'format' => 'A4-P',
			'margin_left' => 10,
			'margin_right' => 10,
			'margin_top' => 37,
			'margin_bottom' => 10,
			'margin_header' => 5,
			'margin_footer' => 5,
			'showBarcodeNumbers' => FALSE
		]);

		$title = sprintf('PO_%06d', $details['po_id']) . " - " . strtoupper($details['party_name']);
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

		break;
}

?>
