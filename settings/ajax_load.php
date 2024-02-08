<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

include(dirname(dirname(__FILE__)) . '/config.php');
global $db, $current_user;

$action = $_REQUEST['action'];
switch ($action) {
	case 'get_fk_accounts':
		$db->join(TBL_PARTIES . ' p', 'p.party_id=fa.party_id', 'LEFT');
		$accounts = $db->get(TBL_FK_ACCOUNTS . ' fa', null, array('fa.account_name', 'fa.account_email', 'fa.account_mobile', 'IFNULL(p.party_name, "Self") as party_name', 'IF(fa.account_status, "active", "inactive") as account_status', 'IF(fa.login_status, "active", "inactive") as login_status', 'IF(fa.api_status, "active", "inactive") as api_status', 'account_id'));
		$return['data'] = $accounts;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);
		break;

	case 'add_fk':
		foreach ($_POST as $p_key => $p_value) {
			if ($p_key == "action") {
				continue;
			} elseif ($p_key == "account_password") {
				$content = $db->escape($p_value);
				$details[$p_key] = trim(openssl_encrypt($content, "AES-128-CBC", "CrckvasdjlwinzmcCCskLm24"));
			} else {
				$content = $db->escape($p_value);
				$details[$p_key] = trim($content);
			}
		}

		$details['account_status'] = 1;

		$lastInsertId = $db->insert(TBL_FK_ACCOUNTS, $details);
		if ($lastInsertId)
			$response = array("type" => "success", "account_id" => $lastInsertId, "fetch" => true);
		else
			$response = array("type" => "error", "msg" => $db->getLastError());

		echo json_encode($response);

		break;

	case 'update_fk':
		foreach ($_POST as $p_key => $p_value) {
			if ($p_key == "action" || $p_key == "account_id") {
				continue;
			} else {
				$content = $db->escape($p_value);
				$details[$p_key] = trim($content);
			}
		}

		$db->where('account_id', (int)$_POST['account_id']);
		$response = array();
		if ($db->update(TBL_FK_ACCOUNTS, $details))
			$response = array("type" => "success", "msg" => 'Successfully updated seller details');
		else
			$response = array("type" => "error", "msg" => 'Unable to updated seller details', "error" => $db->getLastError());

		echo json_encode($response);
		break;

	case 'delete_fk':
		$db->where('account_id', (int)$_POST['account_id']);
		if ($db->delete(TBL_FK_ACCOUNTS, $details))
			$response = "success";
		else
			$response = "error";

		echo $response;

		break;

	case 'get_az_accounts':
		$db->join(TBL_PARTIES . ' p', 'p.party_id=aa.party_id', 'LEFT');
		$accounts = $db->get(TBL_AZ_ACCOUNTS . ' aa', null, array('aa.account_name', 'aa.az_account_name', 'aa.account_mobile', 'aa.seller_id', 'aa.marketplace_id', 'IFNULL(p.party_name, "Self") as party_name', 'account_id'));
		$return['data'] = $accounts;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);
		break;

	case 'add_az':
		foreach ($_POST as $p_key => $p_value) {
			if ($p_key == "action") {
				continue;
			} else {
				$content = $db->escape($p_value);
				$details[$p_key] = trim($content);
			}
		}

		if ($db->insert('bas_az_account', $details))
			$response = "success";
		else
			$response = "error";

		echo $response;

		break;

	case 'update_az':
		foreach ($_POST as $p_key => $p_value) {
			if ($p_key == "action" || $p_key == "account_id") {
				continue;
			} else {
				$content = $db->escape($p_value);
				$details[$p_key] = trim($content);
			}
		}

		$db->where('account_id', (int)$_POST['account_id']);
		$response = array();
		if ($db->update('bas_az_account', $details))
			$response = "success";
		else
			$response = "error";

		echo $response;

		break;

	case 'delete_az':
		$db->where('account_id', (int)$_POST['account_id']);
		if ($db->delete('bas_az_account', $details))
			$response = "success";
		else
			$response = "error";

		echo $response;

		break;

	case 'add_sp':
		// code...
		break;

	case 'get_account_details':
		$account_id = $_REQUEST['account_id'];
		$marketplace = $_REQUEST['marketplace'];

		$return = "";
		if ($marketplace == "flipkart") {
			$db->where('account_id', $account_id);
			$return = $db->getOne(TBL_FK_ACCOUNTS);
		}
		echo json_encode(array("type" => "success", "data" => $return));
		break;

	case 'update_account_details':
		$refetch = false;
		$api_refetch = false;
		$table = TBL_FK_ACCOUNTS;
		if ($_POST['marketplace'] == "amazon")
			$table = TBL_AZ_ACCOUNTS;

		$account_id = $_POST['account_id'];

		$db->where('account_id', $account_id);
		$current_details = $db->getOne($table, array('account_email', 'account_password', 'fk_account_name', 'account_name', 'account_mobile', 'is_sellerSmart', 'mp_warehouses', 'app_id', 'app_secret', 'is_ams', 'party_id'));

		$new_details = array();
		$remove = array('account_id', 'marketplaces', 'action', 'is_marketplaceFulfilled');
		foreach ($_POST as $key => $value) {
			if (in_array($key, $remove))
				continue;

			if ($key == "is_sellerSmart")
				$value = boolval($value);

			$new_details[$key] = is_array($value) ? $value : trim($value);
		}
		$update = array_diff($new_details, $current_details);
		if (($_POST['is_marketplaceFulfilled'] == "on" && !isset($_POST['mp_warehouses'])) || !isset($_POST['is_marketplaceFulfilled']))
			$update['mp_warehouses'] = null;

		$return = array('type' => 'success', 'fetch' => $refetch, 'api_refetch' => $api_refetch, 'update' => $update);
		if ($update) {
			if (isset($update['account_password'])) {
				$update['account_password'] = openssl_encrypt($update['account_password'], "AES-128-CBC", "CrckvasdjlwinzmcCCskLm24");
				$refetch = true;
			}

			if (isset($update['app_id']) || isset($update['app_secret']))
				$api_refetch = true;

			if (isset($update['mp_warehouses']))
				$update['mp_warehouses'] = json_encode($update['mp_warehouses']);

			$db->where('account_id', $account_id);
			if ($db->update($table, $update))
				$return = array('type' => 'success', 'fetch' => $refetch, 'api_fetch' => $api_refetch, 'account_id' => $account_id, 'updated' => $update);
			else
				$return = array('type' => 'error', 'message' => 'Error updating details', 'error' => $db->getLastError());
		}
		echo json_encode($return);
		break;

	case 'update_sms_service_status':
		$details['option_value'] = trim($db->escape($_POST['is_active']));

		$db->where('option_key', 'sms_enable');
		$response = array();
		if ($db->update(TBL_OPTIONS, $details))
			$response = "success";
		else
			$response = "error";

		echo $response;
		break;

	case 'update_sms_settings':
		$details['sms_url'] = trim($db->escape($_POST['sms_url']));
		$details['sms_username'] = trim($db->escape($_POST['sms_username']));
		$details['sms_password'] = trim($db->escape($_POST['sms_password']));

		$response = array();
		foreach ($details as $d_key => $d_value) {
			$db->where('option_key', $d_key);
			if ($db->update(TBL_OPTIONS, array('option_value' => $d_value)))
				$response[] = "success";
			else
				$response[] = "error";
		}

		if (in_array('error', $response))
			echo 'error';
		else
			echo 'success';
		break;

	case 'update_email_service_status':
		$details['option_value'] = trim($db->escape($_POST['is_active']));

		$db->where('option_key', 'email_enable');
		$response = array();
		if ($db->update(TBL_OPTIONS, $details))
			$response = "success";
		else
			$response = "error";

		echo $response;
		break;

	case 'update_email_settings':
		$details['email_host'] = trim($db->escape($_POST['email_url']));
		$details['email_username'] = trim($db->escape($_POST['email_username']));
		$details['email_password'] = trim($db->escape($_POST['email_password']));

		$response = array();
		foreach ($details as $d_key => $d_value) {
			$db->where('option_key', $d_key);
			if ($db->update(TBL_OPTIONS, array('option_value' => $d_value)))
				$response[] = "success";
			else
				$response[] = "error";
		}

		if (in_array('error', $response))
			echo 'error';
		else
			echo 'success';
		break;

	case 'get_templates':

		$type = $_REQUEST['type'];
		switch ($type) {
			case 'email':
				# code...
				break;
		}
		$templates = get_option('email_template%');
		var_dump($templates);
		break;

	case 'qz_status':
		$details['option_value'] = $_POST['is_active'];

		$db->where('option_key', 'qz_status');
		$response = array();
		if ($db->update(TBL_OPTIONS, $details))
			$response = array("type" => "success", "msg" => "Successfully updated QZ Service");
		else
			$response = array("type" => "erro", "msg" => "Unable to updated QZ Service");

		echo json_encode($response);
		break;

	case 'save_qz_settings':
		$printers['printer_invoice'] = $_POST['printer_invoice'];
		$printers['printer_mrp_label'] = $_POST['printer_mrp_label'];
		$printers['printer_product_label'] = $_POST['printer_product_label'];
		$printers['printer_weighted_product_label'] = $_POST['printer_weighted_product_label'];
		$printers['printer_ctn_box_label'] = $_POST['printer_ctn_box_label'];
		$printers['printer_shipping_label'] = $_POST['printer_shipping_label'];

		$return = array();
		if ($current_user['user_role'] == "super_admin") {
			$settings['printer_invoice_size'] = $_POST['printer_invoice_size'];
			$settings['printer_mrp_label_size'] = $_POST['printer_mrp_label_size'];
			$settings['printer_product_label_size'] = $_POST['printer_product_label_size'];
			$settings['printer_weighted_product_label_size'] = $_POST['printer_weighted_product_label_size'];
			$settings['printer_ctn_box_label_size'] = $_POST['printer_ctn_box_label_size'];
			$settings['printer_shipping_label_size'] = $_POST['printer_shipping_label_size'];

			foreach ($settings as $set_key => $set_value) {
				if (set_option($set_key, $set_value))
					$return[] = true;
				else
					$return[] = false;
			}
		}

		$db->where('userID', $current_user['userID']);
		$user_settings = json_decode($db->getValue(TBL_USERS, 'user_settings'), true);
		$user_settings['printers'] = $printers;
		$db->where('userID', $current_user['userID']);

		if (!in_array(false, $return) && $db->update(TBL_USERS, array('user_settings' => json_encode($user_settings))))
			echo json_encode(array("type" => "success",  "msg" => "Successfully saved the printing settings"));
		else
			echo json_encode(array("type" => "error", "msg" => "Unable to save printing settings", "error" => $db->getLastError()));

		break;

		// TEMPLATES
	case 'add_template':
		$details['templateName'] = preg_replace('/\s+/', '_', strtolower($_POST['templateName']));
		$db->where('templateName', $details['templateName']);
		$db->where('Status', 'A');

		if (!$db->has(TBL_TEMPLATE_MASTER)) {
			if ($id = $db->insert(TBL_TEMPLATE_MASTER, $details)) {
				$return['type'] = "Success";
				$return = array('type' => 'success', 'msg' => 'Successfull added new Template');
			} else {
				$return = array('type' => 'error', 'msg' => 'Error adding new Template :: ' . $db->getLastError());
			}
		} else {
			$return['type'] = "Error";
			$return['message'] = "Duplicate Template Name";
			$return = array('type' => 'error', 'msg' => 'Duplicate Template Name');
		}
		echo json_encode($return);
		break;

	case 'get_template':
		$db->orderBy('templateName', 'ASC');
		$template = $db->get(TBL_TEMPLATE_MASTER, null, array('templateName as "TemplateName"', 'Status as "status"', 'templateId'));
		$return['data'] = $template;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);

		break;

	case 'edit_template':
		$templateId = $_POST['templateId'];
		$details['TemplateName'] = $_POST['TemplateName'];

		$log->logfile = TODAYS_LOG_PATH . '/products-brand.log';

		$db->where('templateId', $templateId);
		if ($db->update(TBL_TEMPLATE_MASTER, $details)) {
			$return = array('type' => 'success', 'msg' => 'Successfull updated template');
			$log->write('Successfull updated template with name ' . $_POST['TemplateName'] . json_encode($details) . ' Query::' . $db->getLastQuery());
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to updated brand');
			$log->write('Unable updated template with name ' . $_POST['TemplateName'] . ' ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

	case 'delete_template':
		$templateId = $_POST['templateId'];
		$db->where('templateId', $templateId);
		if ($db->delete(TBL_TEMPLATE_MASTER)) {
			$return = array('type' => 'success', 'msg' => 'Successfull deleted template');
			$log->write('Successfull deleted template.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to delete template');
			$log->write('Unable delete template. :: ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

	case 'active-template':
		$templateId = $_POST['templateId'];
		$details['status'] = 1;
		$db->where('templateId', $templateId);
		$status = $db->update(TBL_TEMPLATE_MASTER, $details);
		if ($status) {
			$return = array('type' => 'success', 'msg' => 'Successfull Update status');
			$log->write('Successfull Update status.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to Update status');
			$log->write('Unable Update status. :: ' . $db->getLastError());
		}
		break;

	case 'deactive-template':
		$templateId = $_POST['templateId'];
		$details['status'] = 0;
		$db->where('templateId', $templateId);
		$status = $db->update(TBL_TEMPLATE_MASTER, $details);
		if ($status) {
			$return = array('type' => 'success', 'msg' => 'Successfull Update status');
			$log->write('Successfull Update status.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to Update status');
			$log->write('Unable Update status. :: ' . $db->getLastError());
		}
		break;

		// SMS TEMPLATE
	case 'sms_template':
		$db->join(TBL_CLIENTS_SMS . " cs", "cs.smsClientId=st.clientId", " INNER");
		$db->join(TBL_FIRMS . " f", "f.firm_id=st.firmId");
		$db->join(TBL_TEMPLATE_MASTER . " mt", "mt.templateId=st.masterTemplateId");
		$getSmsTemplate = $db->get(TBL_TEMPLATE_SMS . " st", null, array('st.smsTemplateId', 'st.masterTemplateId', 'st.templateContent', 'st.templateLanguage', 'st.clientId', 'st.firmId', 'cs.smsClientName', 'f.firm_name', 'mt.templateName'));
		$return['data'] = $getSmsTemplate;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);

		break;

	case 'add_sms_template':
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];

		$db->where('masterTemplateId', $_POST['masterTemplateId']);
		if (!$db->has(TBL_TEMPLATE_SMS)) {
			if ($id = $db->insert(TBL_TEMPLATE_SMS, $details)) {
				$return['type'] = "Success";
				$return = array('type' => 'success', 'msg' => 'Successfully added new sms template');
			} else {
				$return = array('type' => 'error', 'msg' => 'Error adding new template :: ' . $db->getLastError());
			}
		} else {
			$return['type'] = "Error";
			$return['message'] = "Duplicate sms template Name";
			$return = array('type' => 'error', 'msg' => 'Duplicate sms template Name');
		}
		echo json_encode($return);
		break;

	case 'edit_sms_template':
		$smsTemplateId = $_POST['smsTemplateId'];
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];
		$getData = $db->where('smsTemplateId', $_POST['smsTemplateId'])->get(TBL_TEMPLATE_SMS)[0];
		if (isset($getData)) {
			if ($getData['masterTemplateId'] != $_POST['masterTemplateId']) {
				$db->where('masterTemplateId', $_POST['masterTemplateId']);
				if (!$db->has(TBL_TEMPLATE_SMS)) {
					$db->where('smsTemplateId', $smsTemplateId);
					if ($db->update(TBL_TEMPLATE_SMS, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfully updated sms template');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated sms template');
					}
				} else {
					$return['type'] = "Error";
					$return['message'] = "Duplicate sms template Name";
					$return = array('type' => 'error', 'msg' => 'Duplicate sms template Name');
				}
			} else {
				$db->where('smsTemplateId', $smsTemplateId);
				if ($db->update(TBL_TEMPLATE_SMS, $details)) {
					$return = array('type' => 'success', 'msg' => 'Successfully updated sms template');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to updated sms template');
				}
			}
		}

		echo json_encode($return);
		break;

	case 'delete_sms_template':
		$smsTemplateId = $_POST['smsTemplateId'];
		$db->where('smsTemplateId', $smsTemplateId);
		$log->logfile = TODAYS_LOG_PATH . '/sms_template.log';
		if ($db->delete(TBL_TEMPLATE_SMS)) {
			$return = array('type' => 'success', 'msg' => 'Successfull deleted sms template');
			$log->write('Successfull deleted sms template.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to delete sms template');
			$log->write('Unable delete sms template. :: ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

		// EMAIL TEMPLATE
	case 'emails_template':
		$db->join(TBL_CLIENTS_EMAIL . " ce", "ce.emailClientId = et.clientId", " INNER");
		$db->join(TBL_FIRMS . " f", "f.firm_id=et.firmId");
		$db->join(TBL_TEMPLATE_MASTER . " mt", "mt.templateId=et.masterTemplateId");
		$getTemplate = $db->get(TBL_TEMPLATE_EMAIL . " et", null, array('et.emailTemplateId', 'et.masterTemplateId', 'et.templateContent', 'et.templateLanguage', 'et.clientId', 'et.firmId', 'ce.emailClientName', 'f.firm_name', 'mt.templateName'));
		$return['data'] = $getTemplate;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);

		break;

	case 'add_emails_template':
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];

		$db->where('masterTemplateId', $_POST['masterTemplateId']);
		if (!$db->has(TBL_TEMPLATE_EMAIL)) {
			if ($id = $db->insert(TBL_TEMPLATE_EMAIL, $details)) {
				$return['type'] = "Success";
				$return = array('type' => 'success', 'msg' => 'Successfull added new email template');
			} else {
				$return = array('type' => 'error', 'msg' => 'Error adding new template :: ' . $db->getLastError());
			}
		} else {
			$return['type'] = "Error";
			$return['message'] = "Duplicate email template Name";
			$return = array('type' => 'error', 'msg' => 'Duplicate email template Name');
		}
		echo json_encode($return);
		break;

	case 'edit_emails_template':
		$emailTemplateId = $_POST['emailTemplateId'];
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];

		$getData = $db->where('emailTemplateId', $_POST['emailTemplateId'])->get(TBL_TEMPLATE_EMAIL)[0];
		if (isset($getData)) {
			if ($getData['masterTemplateId'] != $_POST['masterTemplateId']) {
				$db->where('masterTemplateId', $_POST['masterTemplateId']);
				if (!$db->has(TBL_TEMPLATE_EMAIL)) {
					$db->where('emailTemplateId', $emailTemplateId);
					if ($db->update(TBL_TEMPLATE_EMAIL, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfully updated email template');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated email template');
					}
				} else {
					$return['type'] = "Error";
					$return['message'] = "Duplicate sms template Name";
					$return = array('type' => 'error', 'msg' => 'Duplicate email template Name');
				}
			} else {
				$db->where('emailTemplateId', $emailTemplateId);
				if ($db->update(TBL_TEMPLATE_EMAIL, $details)) {
					$return = array('type' => 'success', 'msg' => 'Successfully updated email template');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to updated email template');
				}
			}
		}

		echo json_encode($return);
		break;

	case 'delete_emails_template':
		$smsTemplateId = $_POST['smsTemplateId'];
		$db->where('smsTemplateId', $smsTemplateId);
		$log->logfile = TODAYS_LOG_PATH . '/sms_template.log';
		if ($db->delete(TBL_TEMPLATE_SMS)) {
			$return = array('type' => 'success', 'msg' => 'Successfull deleted sms template');
			$log->write('Successfull deleted sms template.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to delete sms template');
			$log->write('Unable delete sms template. :: ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

		// WHATSAPP TEMPLATE
	case 'whatsapp_template':
		$db->join(TBL_CLIENTS_WHATSAPP . " cw", "cw.whatsappClientId=wt.clientId", " INNER");
		$db->join(TBL_FIRMS . " f", "f.firm_id=wt.firmId");
		$db->join(TBL_TEMPLATE_MASTER . " mt", "mt.templateId=wt.masterTemplateId");
		$getWhatsappTemplate = $db->get(TBL_TEMPLATE_WHATSAPP . " wt", null, array('wt.whatsappTemplateId', 'wt.masterTemplateId', 'wt.partnerTemplateId', 'wt.templateContent', 'wt.templateLanguage', 'wt.clientId', 'wt.firmId', 'CONCAT(UPPER(SUBSTRING(cw.whatsappClientName,1,1)),LOWER(SUBSTRING(cw.whatsappClientName,2))) AS whatsappClientName', 'f.firm_name', 'mt.templateName'));
		$return['data'] = $getWhatsappTemplate;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);

		break;

	case 'add_whatsapp_template':
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['partnerTemplateId'] = $_POST['partnerTemplateId'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];

		$db->where('masterTemplateId', $_POST['masterTemplateId']);
		if (!$db->has(TBL_TEMPLATE_WHATSAPP)) {
			if ($id = $db->insert(TBL_TEMPLATE_WHATSAPP, $details)) {
				$return['type'] = "Success";
				$return = array('type' => 'success', 'msg' => 'Successfull added new whatsapp template');
			} else {
				$return = array('type' => 'error', 'msg' => 'Error adding new template :: ' . $db->getLastError());
			}
		} else {
			$return['type'] = "Error";
			$return['message'] = "Duplicate whatsapp template Name";
			$return = array('type' => 'error', 'msg' => 'Duplicate whatsapp template Name');
		}
		echo json_encode($return);
		break;

	case 'edit_whatsapp_template':
		$whatsappTemplateId = $_POST['whatsappTemplateId'];
		$details['masterTemplateId'] = $_POST['masterTemplateId'];
		// $details['templateName'] = $_POST['template_name'];
		$details['templateContent'] = $_POST['templateContent'];
		$details['templateLanguage'] = $_POST['templateLanguage'];
		$details['partnerTemplateId'] = $_POST['partnerTemplateId'];
		$details['clientId'] = $_POST['clientId'];
		$details['firmId'] = $_POST['firmId'];

		$db->where('whatsappTemplateId', $_POST['whatsappTemplateId']);
		$getData = $db->getOne(TBL_TEMPLATE_WHATSAPP);
		if (isset($getData)) {
			if ($getData['masterTemplateId'] != $_POST['masterTemplateId']) {
				$db->where('masterTemplateId', $_POST['masterTemplateId']);
				if (!$db->has(TBL_TEMPLATE_WHATSAPP)) {
					$db->where('whatsappTemplateId', $whatsappTemplateId);
					if ($db->update(TBL_TEMPLATE_WHATSAPP, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfully updated whatsapp template');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated whatsapp template');
					}
				} else {
					$return['type'] = "Error";
					$return['message'] = "Duplicate sms template Name";
					$return = array('type' => 'error', 'msg' => 'Duplicate whatsapp template Name');
				}
			} else {
				$db->where('whatsappTemplateId', $whatsappTemplateId);
				if ($db->update(TBL_TEMPLATE_WHATSAPP, $details)) {
					$return = array('type' => 'success', 'msg' => 'Successfully updated whatsapp template');
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to updated whatsapp template');
				}
			}
		}

		echo json_encode($return);
		break;

	case 'delete_whatsapp_template':
		$whatsappTemplateId = $_POST['whatsappTemplateId'];
		$db->where('whatsappTemplateId', $whatsappTemplateId);
		$log->logfile = TODAYS_LOG_PATH . '/whatsapp_template.log';
		if ($db->delete(TBL_TEMPLATE_SMS)) {
			$return = array('type' => 'success', 'msg' => 'Successfully deleted whatsapp template');
			$log->write('Successfull deleted whatsapp template.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to delete whatsapp template');
			$log->write('Unable delete whatsapp template. :: ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

	case 'template_element':
		$getTemplateElement = $db->get(TBL_TEMPLATE_ELEMENTS, null);
		$return['data'] = $getTemplateElement;

		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);

		break;

	case 'template_field_get':
		$tableName = $_REQUEST['tableName'];

		$fieldData = get_all_field_table($tableName);

		echo json_encode($fieldData);

		break;

	case 'add_template_element':
		$details['element_table_name'] = $_POST['tableName'];
		$details['element_field_name'] = $_POST['fieldName'];
		$details['element_status'] = $_POST['status'];
		if ($id = $db->insert(TBL_TEMPLATE_ELEMENTS, $details)) {
			$return['type'] = "Success";
			$return = array('type' => 'success', 'msg' => 'Successfully added new template elements');
		} else {
			$return = array('type' => 'error', 'msg' => 'Error adding new template :: ' . $db->getLastError());
		}

		echo json_encode($return);
		break;

	case 'delete_template_element':
		$whatsappTemplateId = $_POST['TemplateElementId'];
		$db->where('element_id', $whatsappTemplateId);
		$log->logfile = TODAYS_LOG_PATH . '/template_element.log';
		if ($db->delete(TBL_TEMPLATE_ELEMENTS)) {
			$return = array('type' => 'success', 'msg' => 'Successfully deleted template element');
			$log->write('Successfully deleted template element.');
		} else {
			$return = array('type' => 'error', 'msg' => 'Unable to delete template element');
			$log->write('Unable delete template element. :: ' . $db->getLastError());
		}
		echo json_encode($return);
		break;

	case 'edit_template_element':
		$elementId = $_POST['elementId'];
		$details['element_table_name'] = $_POST['tableName'];
		$details['element_field_name'] = $_POST['fieldName'];
		$details['element_status'] = $_POST['status'];
		$getData = $db->where('element_id', $_POST['elementId'])->get(TBL_TEMPLATE_ELEMENTS)[0];
		if (isset($getData)) {
			$db->where('element_id', $elementId);
			if ($db->update(TBL_TEMPLATE_ELEMENTS, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfully updated template element');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated template element');
			}
		}
		echo json_encode($return);
		break;

		// RATE CARD
	case 'mp_data':
		$db->where('(start_date <= ? AND end_date >= ? ) ', array(date("Y-m-d"), date("Y-m-d")));
		$getMpData = $db->get(TBL_MP_FEES, null);
		$return['data'] = $getMpData;

		foreach ($getMpData as $key => $data) {
			$return['data'][$key]['action'] = '<a href="' . BASE_URL . '/settings/rate_card_edit.php?uid=' . $data['fee_id'] . '" target="_blank"  class="btn btn-default btn-xs black rate_edit"><i class="fa fa-edit"></i> Edit</a>';
		}
		if (empty($return))
			$return['data'] = array();

		header('Content-Type: application/json');
		echo json_encode($return);
		break;

	case 'save_rate':
		$details['fee_name'] = $_POST['fee_name'];
		$details['fee_tier'] = $_POST['fee_tier'];
		$details['fee_type'] = $_POST['fee_type'];
		$details['fee_fulfilmentType'] = $_POST['fee_fulfilmentType'];
		$details['fee_brand'] = $_POST['fee_brand'];
		$details['fee_category'] = $_POST['fee_category'];
		$details['fee_column'] = $_POST['fee_column'];
		$details['fee_marketplace'] = $_POST['fee_marketplace'];
		$details['fee_attribute_min'] = $_POST['fee_attribute_min'];
		$details['fee_attribute_max'] = $_POST['fee_attribute_max'];
		$details['fee_constant'] = $_POST['fee_constant'];
		$details['fee_value'] = $_POST['fee_value'];
		$details['end_date'] = $_POST['end_date'];
		$details['start_date'] = $_POST['start_date'];
		$fee_id = $_POST['fee_id'];
		if ($fee_id != "") {
			$db->where('fee_id', $fee_id);
			if ($db->update(TBL_MP_FEES, $details))
				echo json_encode(array('type' => 'success', 'msg' => 'Successfully updated payment', 'url' => BASE_URL . '/settings/rate_card_edit.php'));
			else
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to update payment'));
		}
		break;
	case 'getReviews':
		// error_reporting(E_ALL);
		// ini_set('display_errors', '1');
		// echo '<pre>';
		$db->join(TBL_SP_ORDERS . " o", "o.orderId = r.orderId", "LEFT");
		$db->orderBy("r.updatedDate");
		$data = $db->get(TBL_PROTOTYPE_REVIEW . " r", null, "r.*, o.orderNumberFormated");

		echo json_encode(["type" => "success", "data" => $data]);
		break;
}
