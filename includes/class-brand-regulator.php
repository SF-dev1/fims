<?php
include_once(ROOT_PATH . '/includes/vendor/autoload.php');

/**
 * 
 */
class brandRegulator
{
	/*** Declare instance ***/
	private static $instance = NULL;

	function __construct()
	{
		# code...
	}

	function generate_certificate($brand, $party_name, $party_gst, $certificate_id, $dates, $output = true)
	{

		$brand_initial = $brand['brandInitial'];
		$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
		$fontData = $defaultFontConfig['fontdata'];

		$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
		$fontDirs = $defaultConfig['fontDir'];

		$mpdf = new \Mpdf\Mpdf([
			'format' => 'A4-P',
			'margin_left' => 0,
			'margin_right' => 0,
			'margin_top' => 0,
			'margin_bottom' => 0,
			'margin_header' => 0,
			'margin_footer' => 0,
			'fontDir' => array_merge($fontDirs, [
				ROOT_PATH . '/assets/fonts',
			]),
			'fontdata' => $fontData + [
				'mavenpro' => [
					'R' => 'MavenPro-Regular.ttf',
				],
				'mavenpromedium' => [
					'R' => 'MavenPro-Medium.ttf',
				],
				'mavenprobold' => [
					'R' => 'MavenPro-Bold.ttf',
				],
			],
			'default_font' => 'mavenpro',
		]);
		$mpdf->SetSourceFile(ROOT_PATH . "/assets/templates/" . strtolower($brand_initial) . "_letterhead.pdf");
		$tplId = $mpdf->ImportPage(1);
		$mpdf->UseTemplate($tplId, 0, 0, 210, 297);
		$mpdf->AddFontDirectory('fonts');

		$mpdf->SetFont('mavenpro', '');
		$mpdf->SetFontSize(22);
		$mpdf->SetXY(43, 59.5);
		$mpdf->MultiCell(124, 7, 'To Whom So Ever It May Concern', 0, 'C', 0);

		$mpdf->SetFontSize(12);
		$mpdf->SetXY(125, 75);
		$mpdf->MultiCell(70, 7, 'Date: ' . date('dS F, Y'), 0, 'R', 0);

		$content = "<div style='width:180mm; margin-left:15.5mm; font-size:17px; text-align: justify;'>Subject: Brand Authorisation<br /><br />Brand Name: " . $brand['brandName'] . "<br /><br />" . $brand['brandOwner'] . " has a registered tradmark certificate #" . $brand['brandTM'] . " under class " . $brand['brandTMClass'] . ".<br /><br />
		This is to certify that <b>" . $party_name . "</b> with <b>GSTIN " . $party_gst . "</b> is authorised to sell products with brand " . $brand['brandName'] . " on online marketplaces. The authorisation is valid till <b>" . date('dS F, Y', strtotime($dates['end'])) . "</b>. The seller will take all the after sale responsiblity such as warranties and repairs.<br /><br />
		" . $brand['brandName'] . " reserves all rights to revoke the authorisation in any case at its desecration.<br /><br />
		Regards,<br /><br /><br /><br />" . $brand['brandOwnerName'] . "<br />" . $brand['brandOwnerDesignation'] . "<br />" . $brand['brandOwner'] . "</div>";

		$mpdf->SetXY(15.5, 91);
		$mpdf->WriteHTML($content);
		$mpdf->Image(ROOT_PATH . "/assets/templates/" . strtolower($brand_initial) . "-sign-stamp.png", 22, 162, 32, 32, 'png', '', true, true);

		$mpdf->SetFontSize(12);
		$mpdf->SetXY(125, 245);
		$mpdf->MultiCell(70, 7, 'Serial No: #' . $brand_initial . '-' . date('Y') . '-' . $certificate_id, 0, 'R', 0);

		$mpdf->SetFontSize(8);
		$mpdf->SetXY(15.5, 252);
		$mpdf->MultiCell(179, 3, "Verify the certificate with above serial number on \n" . BASE_URL . "/brand-authorisation", 0, 'R', 0);

		$title = $brand_initial . '-' . date('Y') . "-Q" . $dates['quarter'] . "-" . str_replace(" ", "_", strtoupper($party_name)) . '-' . $certificate_id;
		$mpdf->SetProtection(array());
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor("Ishan Kukadia");
		$mpdf->SetDisplayMode('fullpage');

		if ($output)
			$mpdf->Output($title . '.pdf', 'I');
		else {
			$seller_short_name = str_replace(" ", "", $party_name);
			$mpdf->Output(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf', 'F');
			return array('type' => 'success', 'file' => $title . '.pdf');
		}
	}

	function generateTNC($brand, $seller, $distributor)
	{
		$mpdf = new \Mpdf\Mpdf([
			'format' => 'A4-P',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 10,
			'margin_header' => 0,
			'margin_footer' => 0,
			'tempDir' => ROOT_PATH . '/assets/fontstmp',
			'default_font' => 'DejaVuSans',
		]);

		$distributor_on_behalf = "";
		$distributor_details = "";
		$distributor_provider = '<strong>' . $brand . '</strong> will provide its products ';
		if (!is_null($distributor["name"])) {
			$distributor_details = '<p style="font-size:12px; line-height: 4px;">and</p><p style="font-size:12px;"><strong>' . $distributor['name'] . '</strong> having its office at <strong>' . $distributor['address'] . '</strong> and GSTIN <strong>' . $distributor['gst'] . '</strong> acting as a authorised distributor appointed by ' . $brand . '</p>';
			$distributor_on_behalf = '<p style="text-align: center; font-size:11px; line-height: -1px;">(An authorised distributor of ' . $brand . ')</p>';
			$distributor_provider = '<strong>' . $distributor["name"] . '</strong> will provide <strong>' . $brand . '</strong> products ';
		}

		$content = '<p style="text-align: center; font-size:16px; line-height: 4px;"><strong>Memorandum of Understanding</strong></p>
			<p style="text-align: center; font-size:12px; line-height: 4px;"><strong>between</strong></p>
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>' . (is_null($distributor["name"]) ? $brand : $distributor["name"]) . '</em></strong></p>
			' . $distributor_on_behalf . '
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>&amp;</em></strong></p>
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>' . $seller["name"] . '</em></strong></p>
			<p style="font-size:12px; line-height: 4px;">This Memorandum of Understanding is made on 08 July 2019 at Surat, Gujarat between</p>
			<p style="font-size:12px;"><strong>' . $brand . '</strong> having its office at <strong>4002, Silver Business Point, Nr. Uttran Amroli ROB Approch, Uttran Amroli Road, Surat, Gujarat, India &ndash; 394105</strong></p>
			' . $distributor_details . '
			<p style="font-size:12px; line-height: 4px;">and</p>
			<p style="font-size:12px;"><strong>' . $seller["name"] . '</strong>, having its registered office at <strong>' . $seller["address"] . ' </strong>and GSTIN<strong> ' . $seller["gst"] . '</strong></p>
			<p style="font-size:6px;">&nbsp;</p>
			<p style="text-align: center; font-size:12px; line-height: 4px;"><strong>Purpose of this MOU</strong></p>
			<p style="font-size:6px;">&nbsp;</p>
			<p style="font-size:12px; line-height: 4px;"><strong>Basic Overview:</strong></p>
			<p style="font-size:12px;">' . $distributor_provider . ' to the <strong>' . $seller["name"] . '</strong> and will allow the seller to sell on the following terms and conditions only:</p>
			<p style="font-size:12px; line-height: 4px;"><strong>Terms and Conditions:</strong></p>
			<ol style="font-size:12px;">
			<li>The listing should be created under the brand name of &ldquo;' . $brand . '&rdquo; only. Any listings found under any other brand name will be considered as a violation of this terms and condition.</li>
			<li>The seller cannot alter the images of the product distributed by &ldquo;' . $brand . '&rdquo;. Any alteration without our approval will be termed as a violation.</li>
			<li>The seller will have to sell the product with in the price range decided by the brand. If the seller wants to provide any additional discount on the products of &ldquo;' . $brand . '&rdquo; they should first get approval from brand in advance.</li>
			<li>The seller should not latch the listing of other sellers. They will have to create their own listing.</li>
			<li>The seller will have to renew its certification every quarter before 10 days of expiry to continue selling the products of &ldquo;' . $brand . '&ldquo;</li>
			<li>Re-selling of the product is prohibited until unless you are authorized to do so.</li>
			<li>No one is allowed to create a copy/duplicate the models of the brand &ldquo;' . $brand . '&rdquo;. Any such activity will lead to a legal lawsuit against the identified seller. Recovery of the damage done in terms of goodwill or financial or both will be done from the seller if any.</li>
			<li></li>
			<li>All after-sales services will have to be provided by the seller.</li>
			</ol>
			<p style="font-size:12px;">If the seller doesn&rsquo;t follow any of the above terms and condition, ' . $brand . ' has all the rights to demand the loss in terms of financially or reputational to be recovered from the seller. The violation may also lead to the suspension or blacklisting the seller.</p>
			<p style="font-size:12px;">By signing below <strong>' . $seller["name"] . '</strong> agrees to follow the terms &amp; conditions above.</p>
			<p style="font-size:12px;"><strong>For, </strong></p>
			<p style="font-size:12px;"><strong>' . $seller["name"] . '</strong></p>
			<p style="font-size:12px;"><strong>Sign:&nbsp;&nbsp;&nbsp; </strong><strong>________________________</strong></p>
			<p style="font-size:12px;"><strong>Name: ________________________</strong></p>
			<p style="font-size:12px;"><strong>Date:&nbsp;&nbsp; ________________________</strong></p>';

		$mpdf->WriteHTML($content);

		$title = 'T&C-' . str_replace(" ", "_", strtoupper($seller['name'])) . '-' . strtoupper($brand);
		// $mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor("Ishan Kukadia");
		$mpdf->SetDisplayMode('fullpage');

		$seller_short_name = str_replace(" ", "", $seller['name']);
		if (!is_dir(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name))
			mkdir(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name);
		$mpdf->Output(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf', 'F');
		// $mpdf->Output($title.'.pdf', 'I');
	}

	function generateDistTNC($brand, $seller, $distributor)
	{
		$mpdf = new \Mpdf\Mpdf([
			'format' => 'A4-P',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 10,
			'margin_header' => 0,
			'margin_footer' => 0,
			'tempDir' => ROOT_PATH . '/assets/fontstmp',
			'default_font' => 'DejaVuSans',
		]);

		$content = '<p style="text-align: center; font-size:16px; line-height: 4px;"><strong>Memorandum of Understanding</strong></p>
			<p style="text-align: center; font-size:12px; line-height: 4px;"><strong>between</strong></p>
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>' . (is_null($distributor["name"]) ? $brand : $distributor["name"]) . '</em></strong></p>
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>&amp;</em></strong></p>
			<p style="text-align: center; font-size:16px; line-height: 4px;"><strong><em>' . $seller["name"] . '</em></strong></p>
			<p style="font-size:12px; line-height: 4px;">This Memorandum of Understanding is made on 02 September 2019 at Surat, Gujarat between</p>
			<p style="font-size:12px;"><strong>' . $brand . '</strong> having its office at <strong>4002, Silver Business Point, Nr. Uttran Amroli ROB Approch, Uttran Amroli Road, Surat, Gujarat, India &ndash; 394105</strong></p>
			<p style="font-size:12px; line-height: 4px;">and</p>
			<p style="font-size:12px;"><strong>' . $seller["name"] . '</strong>, having its registered office at <strong>' . $seller["address"] . ' </strong>and GSTIN<strong> ' . $seller["gst"] . '</strong></p>
			<p style="font-size:6px;">&nbsp;</p>
			<p style="text-align: center; font-size:12px; line-height: 4px;"><strong>Purpose of this MOU</strong></p>
			<p style="font-size:6px;">&nbsp;</p>
			<p style="font-size:12px; line-height: 4px;"><strong>Basic Overview:</strong></p>
			<p style="font-size:12px;"><strong>' . $brand . '</strong> has appointed <strong>' . $seller["name"] . '</strong> as and <i>Authorized Distributor</i> on the following terms and conditions only:</p>
			<p style="font-size:12px; line-height: 4px;"><strong>Terms and Conditions:</strong></p>
			<ol style="font-size:12px;">
			<li>The distributorship is limited to sell the products only to the sellers who are selling the products on online marketplaces. The distributor is not allowed to sell the &ldquo;' . $brand . '&ldquo; products through any other medium of sales.</li>
			<li>The distributor will have to register the sellers with the information requested by the brand on brands portal. These information may include all the details related to the seller like business name, GSTIN, business address, marketplaces seller is currently selling on and the number of account with such marketplaces, brands seller is currently selling, categories seller is currently selling, etc.</li>
			<li>The distributor has to on-board the seller who wishes to sell the product of &ldquo;' . $brand . '&ldquo;. Once the seller is onboarded by a distributor, the seller cannot re-onboard with another distributor.</li>
			<li>The distributor has to provide all the details of the products listed by its sellers against the &ldquo;' . $brand . '&ldquo; products and will have to upload the details on brands portal immediately.</li>
			<li>The distributor is not allowed to sell the product to the seller of its fellow distributor.</li>
			<li>The distributor is not allowed to take more/less price from the sellers then the price decided by the brand.</li>
			<li>The distributor is liable to keep a check on its sellers and intimate us in case of any mis-practice or fraudulent activity done by the sellers.</li>
			<li>The distributor will have to explain all the important selling terms & condition to the seller.</li>
			</ol>
			<p style="font-size:12px;">&ldquo;' . $brand . '&ldquo; will manage and control all the authorisation and certification process from it\'s end and if, in case the distributor doesn&rsquo;t follow any of the above terms and condition, &ldquo;' . $brand . '&ldquo; has all the rights cancel this distributorship and demand the loss in terms of financially or goodwill to be recovered from the distributor. The violation may also lead to the suspension or blacklisting the distributorship.</p>
			<p style="font-size:12px;">By signing below <strong>' . $seller["name"] . '</strong> agrees to follow the terms &amp; conditions above.</p>
			<p style="font-size:12px;"><strong>For, </strong></p>
			<p style="font-size:12px;"><strong>' . $seller["name"] . '</strong></p>
			<p style="font-size:12px;"><strong>Sign:&nbsp;&nbsp;&nbsp;________________________</strong></p>
			<p style="font-size:12px;"><strong>Name: ________________________</strong></p>
			<p style="font-size:12px;"><strong>Date:&nbsp;&nbsp; ________________________</strong></p>';

		$mpdf->WriteHTML($content);

		$title = 'DISTRIBUTOR-T&C-' . str_replace(" ", "_", strtoupper($seller['name'])) . '-' . strtoupper($brand);
		$mpdf->SetProtection(array('print'));
		$mpdf->SetTitle($title);
		$mpdf->SetAuthor("Ishan Kukadia");
		$mpdf->SetDisplayMode('fullpage');

		$seller_short_name = str_replace(" ", "", $seller['name']);
		if (!is_dir(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name))
			mkdir(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name);
		$mpdf->Output(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf', 'F');
		// $mpdf->Output($title.'.pdf', 'I');
	}

	/**
	 * Compute the start and end date of some fixed o relative quarter in a specific year.
	 * @param mixed $quarter  Integer from 1 to 4 or relative string value:
	 *                        'this', 'current', 'previous', 'first' or 'last'.
	 *                        'this' is equivalent to 'current'. Any other value
	 *                        will be ignored and instead current quarter will be used.
	 *                        Default value 'current'. Particulary, 'previous' value
	 *                        only make sense with current year so if you use it with
	 *                        other year like: get_dates_of_quarter('previous', 1990)
	 *                        the year will be ignored and instead the current year
	 *                        will be used.
	 * @param int $year       Year of the quarter. Any wrong value will be ignored and
	 *                        instead the current year will be used.
	 *                        Default value null (current year).
	 * @param string $format  String to format returned dates
	 * @return array          Array with two elements (keys): start and end date.
	 */
	function get_dates_of_quarter($quarter = 'current', $year = null, $format = null)
	{
		if (!is_int($year)) {
			$year = (new DateTime)->format('Y');
		}

		$current_quarter = ceil((new DateTime)->format('n') / 3);
		switch (strtolower($quarter)) {
			case 'this':
			case 'current':
				$quarter = ceil((new DateTime)->format('n') / 3);
				break;

			case 'next':
				$year = (new DateTime)->format('Y');
				if ($current_quarter == 4) {
					$quarter = 1;
					$year++;
				} else {
					$quarter =  $current_quarter + 1;
				}
				break;

			case 'previous':
				$year = (new DateTime)->format('Y');
				if ($current_quarter == 1) {
					$quarter = 4;
					$year--;
				} else {
					$quarter =  $current_quarter - 1;
				}
				break;

			case 'first':
				$quarter = 1;
				break;

			case 'last':
				$quarter = 4;
				break;

			default:
				$quarter = (!is_int($quarter) || $quarter < 1 || $quarter > 4) ? $current_quarter : $quarter;
				break;
		}
		if ($quarter === 'this') {
			$quarter = ceil((new DateTime)->format('n') / 3);
		}
		$start = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
		$end = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');

		return array(
			'start' => $format ? $start->format($format) : $start,
			'end' => $format ? $end->format($format) : $end,
			'quarter' => $quarter
		);
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new brandRegulator;
		}
		return self::$instance;
	}
}
