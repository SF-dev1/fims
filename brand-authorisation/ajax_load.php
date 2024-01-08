<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	global $db, $userAccess, $current_user;

	switch ($_REQUEST['action']) {

			// VERIFY MOBILE
		case 'verify_certificate':
			$auth_code_formated = trim($_POST['auth_code']);
			$auth_code = substr($_POST['auth_code'], -6);
			$db->where('certificate_id', $auth_code);
			if ($db->has(TBL_BRAND_AUTHORISATION)) {

				$db->join(TBL_PRODUCTS_BRAND . " br", "br.brandid=ba.brand_id", "LEFT");
				$db->join(TBL_PARTIES . " p", "ba.party_id=p.party_id", "LEFT");
				$db->where('ba.certificate_id', $auth_code);
				$certificate = $db->ObjectBuilder()->getOne(TBL_BRAND_AUTHORISATION . ' ba', 'br.brandName, p.party_name, p.party_gst, ba.from_date, ba.to_date');
				$status = "Expired";
				if (strtotime($certificate->to_date) >= time())
					$status = 'Active';
				$data = array(
					'serial_no' => $auth_code_formated,
					'brand_name' => $certificate->brandName,
					'seller_name' => $certificate->party_name,
					'seller_gst' => $certificate->party_gst,
					'from_date' => date('d M, Y', strtotime($certificate->from_date)),
					'to_date' => date('d M, Y', strtotime($certificate->to_date)),
					'status' => $status
				);
				$return = array('type' => 'success', 'msg' => 'success', 'data' => $data);
			} else {
				$return = array('type' => 'error', 'msg' => 'Invalid certificate number', 'data' => array('serial_no' => $auth_code_formated, 'status' => 'Invalid'));
			}

			echo json_encode($return);
			break;
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}
