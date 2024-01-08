<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/class-brand-regulator.php');
	$GLOBALS['brandRegulator'] = brandRegulator::getInstance(); // REQUIRES MPDF

	global $db, $log, $notification;

	switch ($_REQUEST['action']) {
		case 'get_customers':
			$db->where('client_id', $_GET['client_id']);
			$db->where('(party_distributor = ? OR party_customer = ? )', array(1, 1));
			$parties = $db->get(TBL_PARTIES, NULL, array('party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_distributor', 'party_customer', 'is_active', 'party_id'));

			$return['data'] = array();
			$i = 0;
			foreach ($parties as $party) {
				$return['data'][$i] = $party;
				$return['data'][$i]['pending_amount'] = $accountant->get_current_dues('customer', $party['party_id']);
				$i++;
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'get_suppliers':
			$db->where('client_id', $_GET['client_id']);
			$db->where('(party_supplier = ? OR party_carrier = ? )', array(1, 1));
			$parties = $db->get(TBL_PARTIES, NULL, array('party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_supplier', 'party_carrier', 'is_active', 'party_id'));

			$return['data'] = array();
			$i = 0;
			foreach ($parties as $party) {
				$return['data'][$i] = $party;
				$return['data'][$i]['pending_amount'] = $accountant->get_current_dues('supplier', $party['party_id']);
				$i++;
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'get_vendors':
			$db->where('client_id', $_GET['client_id']);
			$db->where('party_ams', 1);
			$parties = $db->get(TBL_PARTIES, NULL, array('party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_supplier', 'party_ams', 'is_active', 'party_id'));

			$return['data'] = array();
			$i = 0;
			foreach ($parties as $party) {
				$return['data'][$i] = $party;
				$return['data'][$i]['pending_amount'] = $accountant->get_current_dues('ams', $party['party_id']);
				$i++;
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_parties':
			$details = array(
				'party_name' => $_REQUEST['party_name'],
				'party_gst' => $_REQUEST['party_gst'],
				'party_address' => $_REQUEST['party_address'],
				'party_poc' => $_REQUEST['party_poc'],
				'party_email' => $_REQUEST['party_email'],
				'party_mobile' => $_REQUEST['party_mobile'],
				'party_distributor' => $_REQUEST['party_distributor'],
				'party_customer' => $_REQUEST['party_customer'],
				'party_supplier' => $_REQUEST['party_supplier'],
				'party_carrier' => $_REQUEST['party_carrier'],
				'party_ams' => $_REQUEST['party_ams'],
				'client_id' => $_REQUEST['client_id'],
				'createdDate' => $db->now(),
			);

			if (isset($_REQUEST['party_ams_rates']))
				$details['party_ams_rates'] = str_replace('start_date', date('Y-m-d'), $_REQUEST['party_ams_rates']);

			$return['message'] = "";
			if ($db->insert(TBL_PARTIES, $details))
				$return['type'] = 'success';
			else {
				$return['type'] = 'error';
				$return['message'] = '<br />' . str_replace((array('_', '-', 'key')), array(' ', '', 'option'), $db->getLastError());
			}

			echo json_encode($return);
			break;

		case 'update_parties':
			$data = $_POST;
			foreach ($data as $p_key => $p_value) {
				if ($p_key == "action" || $p_key == "party_id") {
					continue;
				} elseif ($p_key == "party_address") {
					$content = htmlentities($p_value);
					$details[$p_key] = trim($content);
				} elseif ($p_key == "party_distributor") {
					$db->where('party_id', (int)$data['party_id']);
					$party = $db->getOne(TBL_PARTIES, 'party_email, party_name, party_gst, party_address, party_approval_status, party_approved_brands, party_status');
					extract($party);
					$documents = json_decode($party_approval_status, true);
					$party_status = json_decode($party_status, true);
					if ($p_value == "1") {
						$documents['dist_tnc'] = "pending";
						$party_status['distributor'] = "signed_docs_pending";

						// SEND DIST T&C MAIL
						$seller = array(
							"name" => $party_name,
							"gst" => $party_gst,
							"address" => $party_address,
						);
						$attachments = array();
						foreach (json_decode($party_approved_brands) as $brand) {
							$distributor = array(
								"name" => $brand,
							);
							$brandRegulator->generateDistTNC($brand, $seller, $distributor);
							$seller_short_name = str_replace(" ", "", $seller['name']);
							$title = 'DISTRIBUTOR-T&C-' . str_replace(" ", "_", strtoupper($seller['name'])) . '-' . strtoupper($brand);
							$attachments[] = UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf';
						}

						$data = new stdClass();
						$data->medium = 'email';
						$data->subject = 'Distributor Onboarding | FIMS';
						$to_emails = array(
							"email" => $party_email,
							'name' => $party_name
						);
						$data->to[0] = (object)$to_emails;
						$data->body = str_replace(array("##SELLERNAME##"), array($party_name), get_template('email', 'welcome_onboard_distributor'));
						$data->attachments = $attachments;

						$mail = $notification->send($data);
					} else {
						unset($documents['dist_tnc']);
						unset($party_status['distributor']);
					}

					$details['party_approval_status'] = json_encode($documents);
					$details['party_status'] = json_encode($party_status);
					$details[$p_key] = $p_value;
				} else {
					$content = $db->escape($p_value);
					$details[$p_key] = trim($content);
				}
			}

			$db->where('party_id', (int)$data['party_id']);
			$response = array();
			if ($db->update(TBL_PARTIES, $details))
				$response = array("type" => "success", "msg" => "Successfully updated seller");
			else
				$response = array("type" => "error", "msg" => "Unable to update. Please try again later", "error" => $db->getLastError());

			echo json_encode($response);
			break;
	}
}
