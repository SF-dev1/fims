<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	// include(ROOT_PATH.'/includes/class-upload.php');
	include(ROOT_PATH . '/includes/class-brand-regulator.php');
	$GLOBALS['brandRegulator'] = brandRegulator::getInstance(); // REQUIRES MPDF

	global $db, $log, $notification;

	switch ($_REQUEST['action']) {
		case 'add_seller':
			$data = $_POST;
			unset($data['action']);
			unset($data['marketplace_checkbox']);
			if (isset($data['other_marketplace_input']))
				unset($data['other_marketplace_input']);

			$data['party_approved_brands'] = json_encode($data['party_approved_brands']);
			$data['party_marketplace'] = json_encode($data['party_marketplace']);
			$data['party_approval_status'] = json_encode(array('gst' => 'pending', 'tnc' => 'pending'));
			$data['party_status'] = json_encode(array("seller" => 'signed_docs_pending'));
			$data['party_reseller'] = 1;
			$data['createdDate'] = $db->now();
			$success = false;
			$party_id = NULL;
			if (isset($_POST['party_id']) && !empty($_POST['party_id'])) {
				unset($data['party_id']);
				$db->where('party_id', $_POST['party_id']);
				if ($db->update(TBL_PARTIES, $data)) {
					$success = true;
					$party_id = $_POST['party_id'];
					$return = array('type' => 'success', 'msg' => 'Successfully added new seller');
				}
			} else {
				$party_id = $db->insert(TBL_PARTIES, $data);
				if ($party_id) {
					$success = true;
					$return = array('type' => 'success', 'msg' => 'Successfully added new seller');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to add new seller :: ' . $db->getLastError());
				}
			}

			if ($party_id) {
				$db->where('p.party_id', $party_id);
				$db->join(TBL_PARTIES . " d", "d.party_id=p.client_id", "LEFT");
				$seller_data = $db->getOne(TBL_PARTIES . ' p', 'p.party_name, p.party_gst, p.party_address, p.party_mobile, p.party_email, p.party_approved_brands,  d.party_name as distributor_name, d.party_gst as distributor_gst, d.party_address as distributor_address, d.party_mobile as distributor_mobile, d.party_email as distributor_email');
				extract($seller_data);

				$seller = array(
					"name" => $party_name,
					"gst" => $party_gst,
					"address" => $party_address,
				);

				$distributor = array(
					"name" => null,
					"gst" => null,
					"address" => null,
				);

				if ($distributor_name != "Cash Sale") {
					$distributor = array(
						"name" => $distributor_name,
						"gst" => $distributor_gst,
						"address" => $distributor_address,
					);
				}

				$attachments = array();
				foreach (json_decode($party_approved_brands) as $brand) {
					$brandRegulator->generateTNC($brand, $seller, $distributor);
					$seller_short_name = str_replace(" ", "", $seller['name']);
					$title = 'T&C-' . str_replace(" ", "_", strtoupper($seller['name'])) . '-' . strtoupper($brand);
					$attachments[] = UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf';
				}

				$distributor_name = is_null($distributor_name) ? "Style Feathers" : $distributor_name;
				$distributor_email = is_null($distributor_email) ? "support@skmienterprise.com" : $distributor_email;
				$distributor_mobile = is_null($distributor_mobile) ? get_option('sms_notification_number') : $distributor_mobile;

				$data = new stdClass();
				$data->medium = 'email';
				$data->subject = 'Welcome Onboard | FIMS';
				$to_emails = array(
					"email" => $party_email,
					'name' => $party_name
				);
				$data->to[0] = (object)$to_emails;
				$data->to_others->cc = $distributor_email;
				if ($distributor_email != "support@skmienterprise.com")
					$data->to_others->bcc = 'support@skmienterprise.com';

				// SEND MAIL TO SELLER AND CC TO DISTRIBUTOR 
				$data->body = str_replace(array("##SELLERNAME##", "##DISTRIBUTORNAME##", "##DISTRIBUTOREMAIL##"), array($party_name, $distributor_name, $distributor_email), get_template('email', 'welcome_onboard'));
				$data->attachments = $attachments;
				$mail = $notification->send($data);

				// SEND SMS OF APPROVAL TO SELLER AND DISTRIBUTOR
				$sms_message = str_replace('##SELLERNAME##', $party_name, get_template('sms', 'sms_brand_approval'));
				$sms->send_sms($sms_message, array('91' . $party_mobile), 'IMSDAS');
				$sms_message = str_replace(array('##DISTRIBUTORNAME##', '##SELLERNAME##'), array($distributor_name, $party_name), get_template('sms', 'seller_onboard'));
				$sms->send_sms($sms_message, array('91' . $distributor_mobile), 'IMSDAS');
			}

			echo json_encode($return);
			break;

		case 'view_sellers':
			$db->where('client_id', $_GET['client_id']);
			$db->where("(party_customer = ? OR party_distributor = ? OR party_reseller = ?)", array(1, 1, 1));
			$parties = $db->get(TBL_PARTIES, NULL, array('party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_status', 'party_approval_status', 'party_id', 'party_distributor'));

			$return['data'] = array();
			$i = 0;
			foreach ($parties as $party) {
				if (($party['party_name'] == "Cash Sale") || $party['party_distributor'])
					continue;
				$return['data'][$i] = $party;
				$return['data'][$i]['party_address'] = str_replace("\n", "<br />", $party['party_address']);
				$party_status = json_decode($party['party_status'], true);
				$action_call_out = " ";
				if ($party_status['seller'] == "signed_docs_pending") {
					$return['data'][$i]['status'] = 'signed_docs_pending';
					$action_call_out .= ' <button data-target="#upload_docs" class="btn btn-xs btn-primary upload_docs" data-toggle="modal" data-sellername="' . $party['party_name'] . '" data-sellergst="' . $party['party_gst'] . '" data-sellerid="' . $party['party_id'] . '">Upload Docs</button>';
					$action_call_out .= ' <button class="btn btn-xs btn-default resend_tnc" data-sellerid="' . $party['party_id'] . '">Resend T&C</button>';
				} else if ($party_status['seller'] == "reupload_docs") {
					$return['data'][$i]['status'] = 'reupload_documents';
					$party_approval_status = json_decode($party['party_approval_status'], true);
					$approvedgst = 1;
					$approvedtnc = 1;
					if ($party_approval_status['gst'] != "approved")
						$approvedgst = 0;
					if ($party_approval_status['tnc'] != "approved")
						$approvedtnc = 0;
					$action_call_out .= ' <button data-target="#upload_docs" class="btn btn-xs btn-primary reupload_docs" data-toggle="modal" data-sellername="' . $party['party_name'] . '" data-sellergst="' . $party['party_gst'] . '" data-sellerid="' . $party['party_id'] . '" data-approvedgst="' . $approvedgst . '" data-approvedtnc="' . $approvedtnc . '">Reupload Docs</button>';
				} else if ($party_status['seller'] == "renewal_requested" || $party_status['seller'] == "awaiting_approval") {
					$return['data'][$i]['status'] = $party_status['seller'];
				} else if ($party_status['seller'] == "approved") {
					$party['to_date'] = "";
					$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=ba.brand_id');
					$db->where('party_id', $party['party_id']);
					$brands_auths = $db->get(TBL_BRAND_AUTHORISATION . ' ba', NULL, 'DISTINCT(ba.brand_id), pb.brandName');
					if ($brands_auths) {
						$brands = array();
						foreach ($brands_auths as $brands_auth) {
							$brands = $brands_auth['brandName'];
							$db->where('brand_id', $brands_auth['brand_id']);
							$db->where('party_id', $party['party_id']);
							$db->orderBy('createdDate', 'DESC');
							$to_date = $db->getValue(TBL_BRAND_AUTHORISATION, 'to_date');
						}
						$return['data'][$i]['brands'] = implode(', ', $brands);
						$party['to_date'] = $to_date;
					}

					if (strtotime($party['to_date']) > time()) {
						if (strtotime($party['to_date'] . ' -14 days') < time())
							$action_call_out .= ' <button class="btn btn-xs btn-primary request_renewal" data-sellerid="' . $party['party_id'] . '">Request Renewal</button>';
						$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="' . $party['party_id'] . '"">Suspend</button>';
						$status = 'approved';
						// } else if (strtotime($party['to_date'] .' +30 days') < time()){
						// 	$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="'.$party['party_id'].'"">Suspend</button>';
						// 	$status = 'expired';
					} else if (strtotime($party['to_date']) < time()) {
						$action_call_out .= ' <button class="btn btn-xs btn-primary request_renewal" data-sellerid="' . $party['party_id'] . '"><i></i> Request Renewal</button>';
						$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="' . $party['party_id'] . '">Suspend</button>';
						$status = 'expired';
					}
					$return['data'][$i]['status'] = $status;
				} else if ($party_status['seller'] == "suspended") {
					$return['data'][$i]['status'] = 'suspended';
					$action_call_out .= ' <button class="btn btn-xs btn-danger seller_blacklist" data-sellerid="' . $party['party_id'] . '">Blacklist</button>';
					// REONBOARDING PROCESS
				} else if ($party_status['seller'] == "blacklisted") {
					$return['data'][$i]['status'] = 'blacklisted';
				} else {
					$return['data'][$i]['status'] = "onboard";
					$action_call_out .= ' <a href="' . BASE_URL . '/distributor/seller_new.php?seller_id=' . $party['party_id'] . '" target="_blank" class="btn btn-xs btn-default seller_onboard">Onboard</button>';
				}

				$return['data'][$i]['actions'] = trim($action_call_out);

				unset($return['data'][$i][7]); // REMOVE PARTY_STATUS FROM THE DATATABLE
				unset($return['data'][$i][8]); // REMOVE PARTY_APPROVAL_STATUS FROM THE DATATABLE
				unset($return['data'][$i][9]); // REMOVE PARTY_DISTRIBUTOR FROM THE DATATABLE

				$i++;
			}

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'resend_tnc':
			$party_id = $_POST['seller_id'];
			$db->where('p.party_id', $party_id);
			$db->join(TBL_PARTIES . " d", "d.party_id=p.client_id", "LEFT");
			$seller_data = $db->getOne(TBL_PARTIES . ' p', 'p.party_name, p.party_gst, p.party_address, p.party_mobile, p.party_email, p.party_approved_brands,  d.party_name as distributor_name, d.party_gst as distributor_gst, d.party_address as distributor_address, d.party_mobile as distributor_mobile, d.party_email as distributor_email');
			extract($seller_data);

			$seller = array(
				"name" => $party_name,
				"gst" => $party_gst,
				"address" => $party_address,
			);

			$distributor = array(
				"name" => null,
				"gst" => null,
				"address" => null,
			);

			if ($distributor_name != "Cash Sale") {
				$distributor = array(
					"name" => $distributor_name,
					"gst" => $distributor_gst,
					"address" => $distributor_address,
				);
			}

			$attachments = array();
			foreach (json_decode($party_approved_brands) as $brand) {
				$brandRegulator->generateTNC($brand, $seller, $distributor);
				$seller_short_name = str_replace(" ", "", $seller['name']);
				$title = 'T&C-' . str_replace(" ", "_", strtoupper($seller['name'])) . '-' . strtoupper($brand);
				$attachments[] = UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $title . '.pdf';
			}

			$distributor_name = ($distributor_name == "Cash Sale") ? "Style Feathers" : $distributor_name;
			$distributor_email = ($distributor_name == "Cash Sale") ? "support@skmienterprise.com" : $distributor_email;
			$distributor_mobile = ($distributor_name == "Cash Sale") ? get_option('sms_notification_number') : $distributor_mobile;

			$data = new stdClass();
			$data->medium = 'email';
			$data->subject = 'Welcome Onboard | FIMS';
			$to_emails = array(
				"email" => $party_email,
				'name' => $party_name
			);
			$data->to[0] = (object)$to_emails;
			$data->to_others->cc = $distributor_email;
			if ($distributor_email != "support@skmienterprise.com")
				$data->to_others->bcc = 'support@skmienterprise.com';

			// SEND MAIL TO SELLER AND CC TO DISTRIBUTOR 
			$data->body = str_replace(array("##SELLERNAME##", "##DISTRIBUTORNAME##", "##DISTRIBUTOREMAIL##"), array($party_name, $distributor_name, $distributor_email), get_template('email', 'welcome_onboard'));
			$data->attachments = $attachments;
			$mail = $notification->send($data);

			if ($response)
				$return = array('type' => 'success', 'msg' => 'Successfully sent T&C');
			else
				$return = array('type' => 'error', 'msg' => 'Unable to send T&C :: ' . $response);

			echo json_encode($return);
			break;

		case 'upload_docs':
			$gst_uploded = false;
			$tnc_uploded = false;
			$dis_tnc_uploaded = false;
			$seller_short_name = str_replace(" ", "", $_POST['party_name']);
			$is_reupload = $_POST['is_reupload'];
			$party_approval_status = array('gst' => 'approved', 'tnc' => 'approved');
			$db->where('party_id', $_POST['party_id']);
			$party_status = json_decode($db->getValue(TBL_PARTIES, 'party_status'), true);
			$party_signed_tnc = array();

			if (isset($_FILES['party_gst_certificate'])) {
				$handle = new \Verot\Upload\Upload($_FILES['party_gst_certificate']);
				if ($handle->uploaded) {
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->mime_check = true;
					$handle->allowed = array('application/pdf');
					$handle->file_new_name_body = $seller_short_name . '-gst_cetificate';
					$handle->process(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name);
					if (!$handle->processed && strpos($handle, 'Incorrect type of file') !== FALSE) {
						echo json_encode(array("msg" => 'error', "message" => 'Invalid file type for GST Certificate'));
						return;
					}
					$handle->clean();
					$gst_uploded = true;
					$party_approval_status['gst'] = "pending";
				}
			}

			if (isset($_FILES['party_signed_tnc'])) {
				$handle = new \Verot\Upload\Upload($_FILES['party_signed_tnc']);
				if ($handle->uploaded) {
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->mime_check = true;
					$handle->allowed = array('application/pdf');
					$handle->file_new_name_body = $seller_short_name . '-signed_tnc';
					$handle->process(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name);
					if (!$handle->processed && strpos($handle, 'Incorrect type of file') !== FALSE) {
						echo json_encode(array("msg" => 'error', "message" => 'Invalid file type for Signed Terms & Condition'));
						return;
					}
					$handle->clean();
					$tnc_uploded = true;
					$party_approval_status['tnc'] = "pending";
					$party_signed_tnc['seller_tnc'] = $seller_short_name . '-signed_tnc.pdf';
				}
			}

			if (isset($_FILES['dist_signed_tnc'])) {
				$handle = new \Verot\Upload\Upload($_FILES['dist_signed_tnc']);
				if ($handle->uploaded) {
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->mime_check = true;
					$handle->allowed = array('application/pdf');
					$handle->file_new_name_body = $seller_short_name . '-signed_dist_tnc';
					$handle->process(UPLOAD_PATH . '/sellers_docs/' . $seller_short_name);
					if (!$handle->processed && strpos($handle, 'Incorrect type of file') !== FALSE) {
						echo json_encode(array("msg" => 'error', "message" => 'Invalid file type for Signed Terms & Condition'));
						return;
					}
					$handle->clean();
					$dis_tnc_uploaded = true;
					$party_approval_status['dist_tnc'] = "pending";
					$party_signed_tnc['dist_tnc'] = $seller_short_name . '-signed_dist_tnc.pdf';
					$party_status['distributor'] = 'awaiting_approval';
				}
			}

			if (isset($_POST['is_reupload']) && ($gst_uploded || $tnc_uploded)) {
				$gst_uploded = true;
				$tnc_uploded = true;
				$party_status['seller'] = "awaiting_approval";
			}

			if (($gst_uploded && $tnc_uploded) || $dis_tnc_uploaded) {
				$details = array(
					'party_signed_tnc' => json_encode($party_signed_tnc),
					'party_status' => json_encode($party_status),
					'party_approval_status' => json_encode($party_approval_status)
				);
				$db->where('party_id', $_POST['party_id']);
				if ($db->update(TBL_PARTIES, $details)) {
					$return = array('type' => 'success', 'msg' => 'Successfully uploaded the documents');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to update the details', 'error' => $db->getLastError());
				}
				// SEND SMS FOR UPDATE TO THE ADMIN & THE SELLER :: Dear xxxx, we have successfull received your documents. We will update you once the verification is completed. Sylvi
				echo json_encode($return);
			}
			break;

		case 'update_doc_status':
			$party_id = $_POST['party_id'];
			$party_doc = $_POST['party_doc'];
			$doc_staus = $_POST['doc_staus'];
			$gst_checked = $_POST['gst_checked'];
			$tnc_checked = $_POST['tnc_checked'];

			$db->where('party_id', $party_id);
			$party_details = $db->getOne(TBL_PARTIES, 'party_approval_status, party_status');
			$party_approval_status = json_decode($party_details['party_approval_status'], true);
			$party_status = json_decode($party_details['party_status'], true);
			$party_approval_status[$party_doc] = $doc_staus;

			if ($gst_checked && $tnc_checked && ($party_approval_status['gst'] != "approved" || $party_approval_status['tnc'] != "approved")) {
				$details['party_status']['seller'] = "reupload_docs";
				$details['party_status'] = json_encode($details['party_status']);
			}
			$details['party_approval_status'] = json_encode($party_approval_status);

			if ($party_doc == "dist_tnc" && $doc_staus == "approved") {
				$party_status['distributor'] = "awaiting_approval";
				$details['party_status'] = json_encode($party_status);

				// CREATE SALE ORDER TABLE FOR THE SELLER
				$db->rawQuery('CREATE TABLE IF NOT EXISTS ' . TBL_SALE_ORDER . '_' . $party_id . ' LIKE ' . TBL_SALE_ORDER);

				// UPDATE ROLE TO DISTRIBUTOR FOR ALL THE ACCOUNTS RELATED TO CLIENT
				// $db->where('party_id', $party_id);
				// $db->update(TBL_USERS, array('user_role' => "distributor"));
			} else if ($party_doc == "dist_tnc" && $doc_staus != "approved") {
				$party_status['distributor'] = "signed_docs_pending";
				$details['party_status'] = json_encode($party_status);
			}

			$db->where('party_id', $party_id);
			if ($db->update(TBL_PARTIES, $details))
				$return = array('type' => 'success', 'msg' => 'Successfully approved the document', 'details' => $details);
			else
				$return = array('type' => 'error', 'msg' => 'Unable to update the document status');

			echo json_encode($return);
			break;

		case 'view_unapproved_sellers':
			$db->where('JSON_EXTRACT(party_status, "$.seller")', 'awaiting_approval');
			$db->orWhere('JSON_EXTRACT(party_status, "$.seller")', 'renewal_requested');
			$parties = $db->get(TBL_PARTIES, NULL, array('party_id', 'party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_brands', 'party_brands_others', 'party_marketplace', 'party_approval_status', 'party_approved_brands', 'JSON_UNQUOTE(JSON_EXTRACT(party_status, "$.seller")) as party_status'));

			$return['data'] = array();
			$i = 0;
			foreach ($parties as $party) {
				$return['data'][$i] = array_values($party);

				$return['data'][$i][3] = str_replace("\n", "<br />", $party['party_address']);

				$return['data'][$i][9] = "";
				foreach (json_decode($party['party_marketplace'], true) as $marketplace => $accounts) {
					foreach ($accounts as $account) {
						$return['data'][$i][9] .= ucfirst($marketplace) . ' - ' . $account . '<br />';
					}
				}

				$gst_success = "";
				$gst_disabled = "";
				$gst_checked = "0";
				$docs_approval = json_decode($party['party_approval_status'], true);
				if ($docs_approval['gst'] == "approved") {
					$gst_success = "btn-success";
					$gst_checked = "1";
					// $gst_disabled = "disabled";
				} else if ($docs_approval['gst'] != "pending") {
					$gst_success = "btn-danger";
					$gst_checked = "1";
					// $gst_disabled = "disabled";
				}

				$tnc_success = "";
				$tnc_disabled = "";
				$tnc_checked = "0";
				if ($docs_approval['tnc'] == "approved") {
					$tnc_success = "btn-success";
					$tnc_checked = "1";
					// $tnc_disabled = "disabled";
				} else if ($docs_approval['tnc'] != "pending") {
					$tnc_success = "btn-danger";
					$tnc_checked = "1";
					// $tnc_disabled = "disabled";
				}

				// $upload_certificate = '<button disabled class="btn btn-xs btn-success approve" data-partyid="'.$party['party_id'].'" data-partyname="'.$party['party_name'].'"><i class="fa fa-check"></i></button>';
				$upload_certificate = "";
				if ($docs_approval['gst'] == "approved" && $docs_approval['tnc'] == "approved") {
					$is_renewal = 0;
					if ($party['party_status'] == "renewal_requested")
						$is_renewal = 1;
					$upload_certificate = '<button class="btn btn-xs btn-default upload_certificate" role="button" data-toggle="modal" data-is_renewal=' . $is_renewal . ' data-brands_applied="' . implode(',', (json_decode($party['party_approved_brands']))) . '" data-target="#upload-certificate" data-partyid="' . $party['party_id'] . '"><i class="fa fa-upload"></i></button>';
				}

				unset($return['data'][$i][10]); // REMOVE PARTY_APPROVAL_STATUS FROM THE DATATABLE
				unset($return['data'][$i][11]); // REMOVE PARTY_APPROVED_BRANDS FROM THE DATATABLE
				unset($return['data'][$i][12]); // REMOVE PARTY_STATUS FROM THE DATATABLE
				$return['data'][$i] = array_values($return['data'][$i]); // RESET THE ARRAY KEY

				$seller_short_name = str_replace(" ", "", $party['party_name']);
				$return['data'][$i][] = '<a data-partyid="' . $party['party_id'] . '" data-partydoc="gst" data-href="' . BASE_URL . '/uploads/sellers_docs/' . $seller_short_name . '/' . $seller_short_name . '-gst_cetificate.pdf" data-checkedgst="' . $gst_checked . '" data-checkedtnc="' . $tnc_checked . '" class="btn btn-xs btn-default view ' . $gst_success . '" ' . $gst_disabled . '><i class="fa fa-eye"></i> GST</a>&nbsp;<a data-partyid="' . $party['party_id'] . '" data-partydoc="tnc" data-href="' . BASE_URL . '/uploads/sellers_docs/' . $seller_short_name . '/' . $seller_short_name . '-signed_tnc.pdf" data-checkedgst="' . $gst_checked . '" data-checkedtnc="' . $tnc_checked . '" class="btn btn-xs btn-default view ' . $tnc_success . '" ' . $tnc_disabled . ' ><i class="fa fa-crosshairs"></i> TNC</a>';
				$return['data'][$i][] = $upload_certificate;
				$i++;
			}
			echo json_encode($return);
			break;

		case 'view_approved_sellers':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			// $db->join($ba, 'p.client_id=ba.party_id', "RIGHT");
			$db->join(TBL_PARTIES . " d", "p.client_id=d.party_id", "LEFT");
			$db->where('JSON_EXTRACT(p.party_status, "$.seller")', 'approved');
			$db->orWhere('JSON_EXTRACT(p.party_status, "$.seller")', 'renewal_requested');
			$db->orderBy('party_name', 'ASC');
			$parties = $db->get(TBL_PARTIES . ' p', NULL, array('p.party_id', 'd.party_name as distributor_name', 'p.party_name', 'p.party_poc', 'p.party_email', 'p.party_mobile', 'd.party_email as distributor_email', 'd.party_mobile as distributor_mobile, p.party_approved_brands, "" as expiry_date, JSON_UNQUOTE(JSON_EXTRACT(p.party_status, "$.seller")) as status'));

			$return['data'] = $parties;

			if (empty($return))
				$return['data'] = array();

			$return['data'] = $parties;

			if (empty($return))
				$return['data'] = array();
			else {
				$i = 0;
				foreach ($parties as $party) {
					if ($party['distributor_name'] == "Cash Sale") {
						$return['data'][$i]['distributor_name'] = "Self";
						$return['data'][$i]['distributor_email'] = "support@skmienterprise.com";
						$return['data'][$i]['distributor_mobile'] = get_option('sms_notification_number');
					}

					$brands = json_decode($party['party_approved_brands'], true);
					$return['data'][$i]['party_approved_brands'] = implode(', ', $brands);

					$db->where('party_id', $party['party_id']);
					$db->orderBy('createdDate', 'DESC');
					$expiry_date = $db->getValue(TBL_BRAND_AUTHORISATION, "to_date", 1);
					if (strtotime($expiry_date) < time() && $party['status'] == "approved")
						$status = "expired";
					else if ($party['status'] == "renewal_requested")
						$status = "renewal_requested";
					else
						$status = "active";

					$return['data'][$i]['expiry_date'] = date('d M Y', strtotime($expiry_date));
					$return['data'][$i]['status'] = $status;
					$return['data'][$i]['action'] = "";
					if ($status == "expired")
						$return['data'][$i]['action'] = '<button class="btn btn-xs btn-primary request_renewal" data-sellerid="' . $party['party_id'] . '">Request Renewal</button>';

					$i++;
				}
			}
			echo json_encode($return);
			break;

		case 'generate_certificate':
			$dates = $brandRegulator->get_dates_of_quarter("current", null, 'Y-m-d H:i:s');
			$is_renewal = (isset($_REQUEST['is_renewal']) && $_REQUEST['is_renewal'] == "1") ? true : false;
			if (strtotime($dates['end']) - 60 * 60 * 24 * 15 < time() || $is_renewal) { // 15 DAYS
				$original_start_date = $dates['start'];
				$dates = $brandRegulator->get_dates_of_quarter("next", null, 'Y-m-d H:i:s');
				if (!$is_renewal)
					$dates['start'] = $original_start_date;
			}

			$brand = get_brand_details_by_name($_REQUEST['brand_name'], 0);
			// $brand = $brand_detail['opt'][0];
			$brand_id = $brand['brandid'];
			$brand_name = $brand['brandName'];
			$db->where('b.brand_id', $brand_id);
			$db->where('b.party_id', $_REQUEST['party_id']);
			$db->where('b.from_date', $dates['start']);
			$db->where('b.to_date', $dates['end']);
			$db->join(TBL_PARTIES . " p", "p.party_id=b.party_id", "LEFT");
			$authoriastion_details = $db->getOne(TBL_BRAND_AUTHORISATION . ' b', 'b.brand_id, b.certificate_id, b.to_date, p.party_name, p.party_gst');
			if (is_null($authoriastion_details)) {
				$certificate_id = strtoupper(substr(md5(microtime()), rand(0, 26), 6));

				// if (strtotime($dates['start']) < time())
				// 	$dates['start'] = date('Y-m-d H:i:s');

				$details = array(
					'brand_id' => $brand_id,
					'certificate_id' => $certificate_id,
					'party_id' => $_REQUEST['party_id'],
					'from_date' => $dates['start'],
					'to_date' => $dates['end'],
					'createdDate' => $db->now(),
				);

				if ($db->insert(TBL_BRAND_AUTHORISATION, $details)) {
					$db->where('party_id', $_REQUEST['party_id']);
					$party_details = $db->getOne(TBL_PARTIES, 'party_name, party_gst');
					$party_name = $party_details['party_name'];
					$party_gst = $party_details['party_gst'];
					$response = $brandRegulator->generate_certificate($brand, $party_name, $party_gst, $certificate_id, $dates, false);
					if ($response['type'] == "success")
						echo json_encode(array('type' => 'success', 'msg' => 'Certificate for ' . $brand_name . ' successfull generated with id #' . $certificate_id . ' for ' . $party_name, 'file' => $response['file']));
				} else {
					echo $db->getLastError();
					exit("Unable to generate certificate");
				}
			} else {
				$party_name = $authoriastion_details['party_name'];
				$party_gst = $authoriastion_details['party_gst'];
				$certificate_id = $authoriastion_details['certificate_id'];
				if (isset($_REQUEST['view']) && $_REQUEST['view'] == 1)
					$brandRegulator->generate_certificate($brand, $party_name, $party_gst, $certificate_id, $dates);
				else {
					$response = $brandRegulator->generate_certificate($brand, $party_name, $party_gst, $certificate_id, $dates, false);
					if ($response['type'] == "success")
						echo json_encode(array('type' => 'success', 'msg' => 'Certificate of ' . $brand_name . ' successfull generated with id #' . $certificate_id . ' for ' . $party_name, 'file' => $response['file']));
				}
			}
			break;

		case 'upload_certificate':
			$party_id = $_POST['party_id'];
			$is_renewal = $_POST['is_renewal'];
			$is_distributor = $_POST['is_distributor'];
			$db->where('p.party_id', $party_id);
			$db->join(TBL_PARTIES . " d", "d.party_id=p.client_id", "LEFT");
			$seller_data = $db->getOne(TBL_PARTIES . ' p', 'p.party_id, p.party_name, p.party_mobile, p.party_email, p.party_approved_brands, p.client_id as distributor_id, d.party_name as distributor_name, d.party_mobile as distributor_mobile, d.party_email as distributor_email');
			extract($seller_data);

			$data = new stdClass();
			$data->to_others->cc = $distributor_email;
			$brands_approved = json_decode($party_approved_brands, true);
			if ($distributor_id == 0) {
				$distributor_name = "Sylvi";
				$distributor_email = "support@stylefeathers.com";
				$distributor_mobile = get_option('sms_notification_number');
			} else {
				$data->to_others->bcc = 'support@skmienterprise.com';
			}

			$seller_short_name = str_replace(" ", "", $party_name);
			$files = array();
			foreach ($_POST['file'] as $file) {
				// $handle = new \Verot\Upload\Upload($file);
				if (substr($file, 0, 2) === "SY") {
					$files['sylvi']	= $file;
				} elseif (substr($file, 0, 2) === "SF") {
					$files['stylefeathers']	= $file;
				} else {
					$files['maso']	= $file;
				}
				$attachments[] = UPLOAD_PATH . '/sellers_docs/' . $seller_short_name . '/' . $file;
			}

			if ($is_distributor)
				$details['party_status'] = json_encode(array("distributor" => "approved"));
			else
				$details['party_status'] = json_encode(array("seller" => "approved"));
			$details['party_signed_certificate'] = json_encode($files);
			$db->where('party_id', $party_id);
			if ($db->update(TBL_PARTIES, $details)) {
				$return = array("type" => 'success', "msg" => 'Successfully approved seller ' . $party_name . ' for distributor ' . $distributor_name);
			} else {
				$return = array("type" => 'error', "msg" => 'Unable to approve seller ' . $party_name . ' for distributor ' . $distributor_name . ' ERROR :: ' . $db->getLastError());
			}

			// SEND MAIL TO SELLER AND CC TO DISTRIBUTOR 
			$brand_string = '<b>' . implode('</b> & <b>', $brands_approved) . '</b>';
			$template = "brand_approval";
			$template_title = "Brand Approval";
			if ($is_renewal)
				$template = "brand_renewal";
			$template_title = "Brand Renewal";

			$data->body = str_replace(array("##SELLERNAME##", "##DISTRIBUTORNAME##", "##BRAND##"), array($party_name, $distributor_name, $brand_string), get_template('email', $template));
			$data->subject = $template_title . ' | FIMS';
			$data->medium = 'email';
			$to_emails = array(
				"email" => $party_email,
				'name' => $party_name
			);
			$data->to[0] = (object)$to_emails;
			$data->reply_to = $distributor_email;
			$data->attachments = $attachments;
			$notification->send($data);

			// SEND SMS OF APPROVAL TO SELLER AND DISTRIBUTOR
			$sms_message = str_replace(array('##SELLERNAME##', '##EXTRACONENT##'), array($party_name, $party_email), get_template('sms', 'brand_approval'));
			$sms_sent_sell = $sms->send_sms($sms_message, array('91' . $party_mobile), 'FIMSIN');
			$sms_message = str_replace(array('##SELLERNAME##', '##EXTRACONENT##'), array($distributor_name, 'for ' . $party_name), get_template('sms', 'brand_approval'));
			$sms_sent_dist = $sms->send_sms($sms_message, array('91' . $distributor_mobile), 'FIMSIN');

			$return = array("type" => 'success', "msg" => 'Successfully approved seller ' . $party_name . ' for distributor ' . $distributor_name, 'mail' => $mail, 'sms_sent_sell' => $sms_sent_sell, 'sms_sent_dist' => $sms_sent_dist);
			echo json_encode($return);
			break;

		case 'request_renewal':
			$seller_id = $_REQUEST['seller_id'];
			$is_distributor = $_REQUEST['is_distributor'];

			$db->where('party_id', $seller_id);
			$status = $db->getValue(TBL_PARTIES, 'party_status');
			$status = json_decode($status, true);
			if ($is_distributor) {
				$status['distributor'] = 'renewal_requested';
				// SEND CONCENT FOR RENEWAL
			} else {
				$status['seller'] = 'renewal_requested';
			}

			$db->where('party_id', $seller_id);
			if ($db->update(TBL_PARTIES, array('party_status' => json_encode($status))))
				$return = array('type' => 'success', 'msg' => 'Successfully requested renewal');
			else
				$return = array('type' => 'error', 'msg' => 'Error requested renewal. Please try again later.', 'error' => $db->getLastError());

			echo json_encode($return);
			break;

		case 'get_seller_marketplaces':
			$db->where('party_id', $_GET['seller_id']);
			echo $db->getValue(TBL_PARTIES, 'party_marketplace');
			break;

		case 'get_sellers_listings':
			$db->where('l.client_id', $_GET['client_id']);
			$db->join(TBL_PARTIES . ' p', 'p.party_id=l.seller_id', 'INNER');
			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=l.pid', 'INNER');
			$listings = $db->get(TBL_SELLERS_LISTINGS . ' l', NULL, 'p.party_name, l.marketplace, l.account, pm.sku, l.mp_id, l.seller_listing_id');
			$return['data'] = $listings;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_seller_listing':
			$details = array_map('trim', $_POST);
			unset($details['action']);

			$db->where('seller_id', $details['seller_id']);
			$db->where('marketplace', $details['marketplace']);
			$db->where('account', $details['account']);
			$db->where('mp_id', $details['mp_id']);
			if (!$db->has(TBL_SELLERS_LISTINGS)) {
				if ($db->insert(TBL_SELLERS_LISTINGS, $details)) {
					$return = array('type' => 'success', 'msg' => 'Successfully added new seller listing');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to add new seller listing :: ' . $db->getLastError());
				}
			} else {
				$return = array('type' => 'error', 'msg' => 'Duplicate listing. Listing already existing');
			}

			echo json_encode($return);
			break;

		case 'update_seller_listing':
			$details = array_map('trim', $_POST);
			$party_name = $details['party_name'];
			unset($details['action']);
			unset($details['party_name']);

			$db->where('seller_listing_id', $details['seller_listing_id']);
			if ($db->update(TBL_SELLERS_LISTINGS, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfully updated seller listing');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to update seller listing :: ' . $db->getLastError());
			}

			echo json_encode($return);
			break;

		case 'delete_seller_listing':
			$db->where('seller_listing_id', $_POST['seller_listing_id']);
			if ($db->delete(TBL_SELLERS_LISTINGS)) {
				$return = array('type' => 'success', 'msg' => 'Successfully deleted seller listing');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to deleted seller listing :: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'view_distributors':
			$db->where('party_distributor', "1");
			// $distributors = $db->get(TBL_PARTIES, null, array('party_id', 'party_name', 'party_approved_brands', 'party_approval_status', 'party_status'));
			$distributors = $db->get(TBL_PARTIES, NULL, array('party_name', 'party_gst', 'party_address', 'party_poc', 'party_email', 'party_mobile', 'party_status', 'party_approval_status', 'party_id', 'party_distributor', 'party_approved_brands'));

			$return['data'] = array();
			$i = 0;
			foreach ($distributors as $distributor) {
				$return['data'][$i] = array_values($distributor);

				// $gst_success = ""; $gst_disabled = "";
				$docs_approval = json_decode($distributor['party_approval_status'], true);

				$dist_tnc_success = "";
				$dist_tnc_disabled = "";
				// // var_dump($docs_approval['dist_tnc']);
				if ($docs_approval['dist_tnc'] == "approved") {
					$dist_tnc_success = "btn-success";
				} else if ($docs_approval['dist_tnc'] != "pending") {
					$dist_tnc_success = "btn-danger";
				} else if ($docs_approval['dist_tnc'] != "renewal_requested") {
					$dist_tnc_success = "btn-info";
				}

				// $upload_certificate = "";
				// // $upload_certificate = '<button disabled class="btn btn-xs btn-success approve" data-partyid="'.$party['party_id'].'" data-partyname="'.$party['party_name'].'"><i class="fa fa-check"></i></button>';
				// if ($docs_approval['dist_tnc'] == "pending"){
				// 	$upload_certificate .= '<button class="btn btn-xs btn-default upload_certificate" role="button" data-toggle="modal" data-target="#upload-certificate" data-partyid="'.$distributor['party_id'].'" data-partyname="'.$distributor['party_name'].'" ><i class="fa fa-upload"></i></button>';
				// } else {
				// 	$upload_certificate .= '<button class="btn btn-xs btn-default btn-warning suspend_distributor" role="button" data-toggle="modal" data-target="#suspend-distributor" data-partyid="'.$distributor['party_id'].'" data-partyname="'.$distributor['party_name'].'" ><i class="fa fa-hand-paper"></i> Suspend</button>';
				// 	$upload_certificate .= '<button class="btn btn-xs btn-default btn-danger blacklist_distributor" role="button" data-toggle="modal" data-target="#suspend-distributor" data-partyid="'.$distributor['party_id'].'" data-partyname="'.$distributor['party_name'].'" ><i class="fa fa-ban"></i> Blacklist</button>';
				// }

				// $db->where('party_id', $distributor['party_id']);
				// if(!$db->has(TBL_USERS)){
				// 	$upload_certificate .= '<a href="'.BASE_URL.'/users/all_user.php" target="_blank" class="btn btn-xs btn-warning add_user" role="button" ><i class="fa fa-plus"></i> Add User</a>';
				// }

				$seller_short_name = str_replace(" ", "", $distributor['party_name']);
				// $party_status = json_decode($distributor['party_status'], true);

				// $return['data'][$i][2] = implode(json_decode($distributor['party_approved_brands']), ', ');
				// $return['data'][$i][3] = '<a data-partyid="'.$distributor['party_id'].'" data-partydoc="dist_tnc" data-href="'.BASE_URL.'/uploads/sellers_docs/'.$seller_short_name.'/'.$seller_short_name.'-signed_dist_tnc.pdf" class="btn btn-xs btn-default view '.$dist_tnc_success.'" '.$dist_tnc_disabled.' ><i class="fa fa-crosshairs"></i> DIST TNC</a>';
				// $return['data'][$i][4] = $upload_certificate;
				// $return['data'][$i][5] = '<label class="label label-default">'.ucfirst($party_status['distributor']).'</label>';
				// $i++;


				$return['data'][$i] = $distributor;
				$return['data'][$i]['party_address'] = str_replace("\n", "<br />", $distributor['party_address']);
				$distributor_status = json_decode($distributor['party_status'], true);
				$action_call_out = " ";
				if ($distributor_status['distributor'] == "signed_docs_pending") {
					$return['data'][$i]['status'] = 'signed_docs_pending';
					// $action_call_out .= '<button class="btn btn-xs btn-default upload_docs" role="button" data-toggle="modal" data-is_renewal="1" data-brands_applied="'.implode(',', (json_decode($distributor['party_approved_brands']))).'" data-target="#upload-docs" data-partyid="'.$distributor['party_id'].'"><i class="fa fa-upload"></i></button>';
					$action_call_out .= ' <button data-target="#upload-docs" class="btn btn-xs upload_docs" data-toggle="modal" data-sellername="' . $distributor['party_name'] . '" data-sellergst="' . $distributor['party_gst'] . '" data-sellerid="' . $distributor['party_id'] . '" data-approvedgst="' . $approvedgst . '" data-approvedtnc="' . $approvedtnc . '" data-isdistributor="1"><i class="fa fa-upload"></i></button>';
					// $action_call_out .= ' <button class="btn btn-xs btn-default resend_tnc" data-sellerid="'.$distributor['party_id'].'" data-isdistributor="1">Resend T&C</button>';
				} else if ($distributor_status['distributor'] == "reupload_docs") {
					$return['data'][$i]['status'] = 'reupload_documents';
					$distributor_approval_status = json_decode($distributor['party_approval_status'], true);
					$approvedtnc = 1;
					if ($distributor_approval_status['dist_tnc'] != "approved")
						$approvedtnc = 0;
					$action_call_out .= ' <button data-target="#upload-docs" class="btn btn-xs btn-primary reupload_docs" data-toggle="modal" data-sellername="' . $distributor['party_name'] . '" data-sellergst="' . $distributor['party_gst'] . '" data-sellerid="' . $distributor['party_id'] . '" data-approvedgst="' . $approvedgst . '" data-approvedtnc="' . $approvedtnc . '" data-isdistributor="1">Reupload Docs</button>';
				} else if ($distributor_status['distributor'] == "awaiting_approval") {
					$return['data'][$i]['status'] = $distributor_status['distributor'];
					if ($docs_approval['dist_tnc'] == "pending")
						$action_call_out .= '<a data-partyid="' . $distributor['party_id'] . '" data-partydoc="dist_tnc" data-href="' . BASE_URL . '/uploads/sellers_docs/' . $seller_short_name . '/' . $seller_short_name . '-signed_dist_tnc.pdf" class="btn btn-xs btn-default view ' . $dist_tnc_success . '" ' . $dist_tnc_disabled . ' ><i class="fa fa-crosshairs"></i> DIST TNC</a>';
					else
						$action_call_out .= '<button class="btn btn-xs btn-default upload_certificate" role="button" data-toggle="modal" data-is_renewal="1" data-brands_applied="' . implode(',', (json_decode($distributor['party_approved_brands']))) . '" data-target="#upload-certificate" data-partyid="' . $distributor['party_id'] . '"><i class="fa fa-upload"></i></button>';
				} else if ($distributor_status['distributor'] == "renewal_requested") {
					$return['data'][$i]['status'] = $distributor_status['distributor'];
					$action_call_out .= ' <button class="btn btn-xs btn-success concent" data-concent="approved" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1"><i></i> Concent Approved</button>';
					$action_call_out .= ' <button class="btn btn-xs btn-danger concent" data-concent="rejected" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1"><i></i> Concent Rejected</button>';
				} else if ($distributor_status['distributor'] == "approved") {
					$distributor['to_date'] = "";
					$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=ba.brand_id');
					$db->where('party_id', $distributor['party_id']);
					$brands_auths = $db->get(TBL_BRAND_AUTHORISATION . ' ba', NULL, 'DISTINCT(ba.brand_id), pb.brandName');
					if ($brands_auths) {
						$brands = array();
						foreach ($brands_auths as $brands_auth) {
							$brands = $brands_auth['brandName'];
							$db->where('brand_id', $brands_auth['brand_id']);
							$db->where('party_id', $distributor['party_id']);
							$db->orderBy('createdDate', 'DESC');
							$to_date = $db->getValue(TBL_BRAND_AUTHORISATION, 'to_date');
						}
						$return['data'][$i]['brands'] = implode(', ', $brands);
						$distributor['to_date'] = $to_date;
					}

					if (strtotime($distributor['to_date']) > time()) {
						if (strtotime($distributor['to_date'] . ' -14 days') < time())
							$action_call_out .= ' <button class="btn btn-xs btn-primary request_renewal" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1">Request Renewal</button>';
						$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="' . $distributor['party_id'] . '"" data-isdistributor="1">Suspend</button>';
						$status = 'approved';
						// } else if (strtotime($distributor['to_date'] .' +30 days') < time()){
						// 	$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="'.$distributor['party_id'].'"">Suspend</button>';
						// 	$status = 'expired';
					} else if (strtotime($distributor['to_date']) < time()) {
						$action_call_out .= ' <button class="btn btn-xs btn-primary request_renewal" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1"><i></i> Request Renewal</button>';
						$action_call_out .= ' <button class="btn btn-xs btn-warning seller_suspend" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1">Suspend</button>';
						$status = 'expired';
					}
					$return['data'][$i]['status'] = $status;
				} else if ($distributor_status['distributor'] == "suspended") {
					$return['data'][$i]['status'] = 'suspended';
					$action_call_out .= ' <button class="btn btn-xs btn-danger seller_blacklist" data-sellerid="' . $distributor['party_id'] . '" data-isdistributor="1">Blacklist</button>';
					// REONBOARDING PROCESS
				} else if ($distributor_status['distributor'] == "blacklisted") {
					$return['data'][$i]['status'] = 'blacklisted';
				} else {
					$return['data'][$i]['status'] = "onboard";
					$db->where('party_id', $distributor['party_id']);
					if (!$db->has(TBL_USERS)) {
						$action_call_out .= '<a href="' . BASE_URL . '/users/all_user.php" target="_blank" class="btn btn-xs btn-warning add_user" role="button" ><i class="fa fa-plus"></i> Add User</a>';
					}
				}

				$return['data'][$i]['actions'] = trim($action_call_out);

				unset($return['data'][$i][7]); // REMOVE PARTY_STATUS FROM THE DATATABLE
				unset($return['data'][$i][8]); // REMOVE PARTY_APPROVAL_STATUS FROM THE DATATABLE
				unset($return['data'][$i][9]); // REMOVE PARTY_DISTRIBUTOR FROM THE DATATABLE
				unset($return['data'][$i][10]); // REMOVE PARTY_APPROVED_BRANDS FROM THE DATATABLE
				$i++;
			}

			echo json_encode($return);
			break;

		case 'update_distributor_concent':
			$party_id = $_POST['party_id'];
			$concent = $_POST['concent'];
			if ($concent == "approved")
				$status['distributor'] = 'awaiting_approval';

			$db->where('party_distributor', '1');
			$db->where('party_id', $party_id);
			if ($db->update(TBL_PARTIES, array('party_status' => json_encode($status))))
				$return = array('type' => 'success', 'msg' => 'Successfully requested renewal');
			else
				$return = array('type' => 'error', 'msg' => 'Error requested renewal. Please try again later.', 'error' => $db->getLastError());

			echo json_encode($return);
			break;

		case 'check_exists':
			switch ($_REQUEST['type']) {
				case 'gst':
					$db->where('party_gst', $_REQUEST['party_gst']);
					if ($db->has(TBL_PARTIES))
						echo 'false';
					else
						echo 'true';
					break;

				case 'email':
					$db->where('party_email', $_REQUEST['party_email']);
					if ($db->has(TBL_PARTIES))
						echo 'false';
					else
						echo 'true';
					break;

				case 'mobile':
					$db->where('party_mobile', $_REQUEST['party_mobile']);
					if ($db->has(TBL_PARTIES))
						echo 'false';
					else
						echo 'true';
					break;
			}
			break;
	}
}
