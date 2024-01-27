<?php
include_once(ROOT_PATH . '/includes/class-sms.php');
include_once(ROOT_PATH . '/includes/class-whatsapp.php');
include_once(ROOT_PATH . '/includes/class-mailer.php');

if (!class_exists('Notification')) {
	// global $db;
	class Notification
	{

		private static $instance = NULL;

		function __construct()
		{
		}

		function send($data)
		{
			global $db, $log;

			if (in_array('sms', $data["medium"])) {
				$db->join(TBL_TEMPLATE_MASTER . " m", "m.templateId = sms.masterTemplateId");
				$db->where("m.templateName", $data["type"]);
				$template = $db->getValue(TBL_TEMPLATE_SMS . " sms", "templateContent");
				$sms_message = $this->get_template_body($template, $data);

				$templateData = array(
					"mobile_number" => [$data["contactNumber"]],
					"message" => $sms_message
				);

				$db->where("smsClientId", $data["active_sms_client"]);
				$client = $db->getOne(TBL_CLIENTS_SMS, "*");
				$fileName = "class-sms-" . str_replace(array(" ", "_"), "-", strtolower($client["smsClientName"])) . ".php";
				include_once($fileName);
				$className = "sms" . ucfirst(str_replace(array(" ", "_"), "", strtolower($client["smsClientName"])));
				$sms = new $className((object)$client);
				$return = $sms->sendMessage((object)$templateData);
				return $return;
			}

			if (in_array('whatsapp', $data["medium"])) {
				$db->join(TBL_TEMPLATE_MASTER . " tm", "tm.templateId = wt.masterTemplateId");
				$db->where("wt.masterTemplateId", $data["templateId"]);
				$db->where("wt.clientId", $data["active_whatsapp_id"]);
				$template = $db->getOne(TBL_TEMPLATE_WHATSAPP . " wt", "wt.templateContent, wt.partnerTemplateId, tm.templateName");
				$fields = $this->get_template_data($template["templateContent"], $data);
				$templateData = array(
					"templateId" => $template["partnerTemplateId"],
					"contactNumber" => $fields["contactNumber"],
					"templateData" => array(
						"header" => isset($fields["header"]) ? $fields["header"] : null,
						"body" => isset($fields["body"]) ? $fields["body"] : null,
						"button" => isset($fields["button"]) ? $fields["button"] : null
					),
					"templateName" => $template["templateName"],
				);

				$whatsapp = new whatsApp($data["active_whatsapp_id"]);

				$return = $whatsapp->client->sendMessage($templateData);
				$log->write("Params: " . json_encode($templateData) . "\nResponse" . json_encode($return), 'oc-whatsapp');
				if (isset($return["success"]) && $return["success"])
					$return["status"] = "success";
				return $return;
			}

			if (in_array('email', $data["medium"])) {
				if (isset($data['templateId'])) {
					$db->where("masterTemplateId", $data["templateId"]);
					$db->where("clientId", $data["active_account_id"]);
					$template = $db->getValue(TBL_TEMPLATE_EMAIL, "templateContent");

					$templateBody = $this->get_template_body($template, $data);
				}
				if (isset($data['body'])) {
					$templateBody = $data['body'];
				}

				if (!isset($data['to'])) {
					$db->where("userId", $data["identifierValue"]);
					$to = $db->get(TBL_USERS, NULL, "user_email as email, display_name as name");
				} else {
					$to = $data['to'];
				}
				$templateData = array(
					"to" => $to,
					"from" => array(
						"email" => $data["from"]["email"],
						"name" => $data["from"]["name"]
					),
					"subject" => $data["subject"],
					"email_body" => $templateBody,
					"attachments" => $data["attachments"]
				);
				if (isset($data['to_others']['cc']) || isset($data['to_others']['cc']))
					$templateData['to_others'] = (object)$data['to_others'];

				$db->where("emailClientId", $data["active_email_id"]);
				$client = $db->getValue(TBL_CLIENTS_EMAIL, "emailClientName");
				$fileName = "class-mailer-" . str_replace(array(" ", "_"), "-", strtolower($client)) . ".php";
				include_once($fileName);
				$className = "mailer" . ucfirst(str_replace(array(" ", "_"), "", strtolower($client)));
				$email = new $className();
				$return = $email->send((object)$templateData);
				return $return;
			}
		}

		private function get_template_data($message, $data)
		{
			if (preg_match('/[\'}{]/', $message)) {
				preg_match_all('~\{\{(.*?)\}\}~', $message, $matches);

				$return = null;
				foreach ($matches[1] as $match) {
					global $db;
					$field = substr($match, strpos($match, ".") + 1);
					$table = strtok($match, '.');

					$fields = explode(".", $field);
					if ($data["identifierType"] == "shopify_returns") {
						$db->join(TBL_SP_RETURNS . " r", "r.orderItemId = o.orderItemId");
						$db->join(TBL_FULFILLMENT . " f", "o.orderId = f.orderId");
						$db->where("o.orderId", $data["identifierValue"]);
						$table = ($table == "bas_sp_orders") ? ("o") : (($table == "bas_fulfillment") ? ("f") : ("r"));
						$return["body"][] = (count($fields) > 1) ? (json_decode($db->getValue(TBL_SP_ORDERS . " o", $table . "." . $fields[0]), true)[$fields[1]]) : ($db->getValue(TBL_SP_ORDERS . " o", $table . "." . $fields[0]));
					}
					if ($data["identifierType"] == "shopify") {
						$db->where("orderId", $data["identifierValue"]);
						$return["body"][] = (count($fields) > 1) ? (json_decode($db->getValue($table, $fields[0]), true)[$fields[1]]) : ($db->getValue($table, $fields[0]));
					}
					if ($data["identifierType"] == "cod_refund") {
						$db->join(TBL_SP_REFUNDS . " r", "r.orderId = o.orderId");
						$db->where("o.orderId", $data["identifierValue"]);
						$table = ($table = "bas_sp_orders") ? "o" : "r";
						if ($fields[0] != "refundLinkURL")
							$return["body"][] = (count($fields) > 1) ? (json_decode($db->getValue(TBL_SP_ORDERS, $table . "." . $fields[0]), true)[$fields[1]]) : ($db->getValue(TBL_SP_ORDERS, $table . "." . $fields[0]));
						else {
							$return["button"]["type"] = "url";
							$return["button"]["url"] = (count($fields) > 1) ? (json_decode($db->getValue(TBL_SP_ORDERS, $table . "." . $fields[0]), true)[$fields[1]]) : ($db->getValue(TBL_SP_ORDERS, $table . "." . $fields[0]));
						}
					}
					if ($data["identifierType"] == "shopify_refund") {
						$db->where("orderId", $data["identifierValue"]);
						$return["body"][] = (count($fields) > 1) ? (json_decode($db->getValue($table, $fields[0]), true)[$fields[1]]) : ($db->getValue($table, $fields[0]));
					}
					if ($data["identifierType"] == "rma") {
						$db->where("orderId", $data["identifierValue"]);
						if ($field == "type" && $table == "payment") {
							$return["body"][] = ($data["type"] == "rma_pickup") ? "Shipping Charges" : "Parts Chanrges";
						}
						$return["body"][] = (count($fields) > 1) ? (json_decode($db->getValue($table, $fields[0]), true)[$fields[1]]) : ($db->getValue($table, $fields[0]));
					}
				}

				if ($data["identifierType"] == "shopify") {
					$db->where("orderId", $data["identifierValue"]);
					$cnt = $db->getValue(TBL_SP_ORDERS,  "deliveryAddress");
					$return["contactNumber"] = str_replace(array("+", "+91", " "), "", json_decode($cnt, true)["phone"]);
					$return["email"] = json_decode($cnt, true)["email"];
				}
				return $return;
			}
		}

		private function get_template_body($template, $data)
		{
			$return = $template;
			if (preg_match('/[\'}{]/', $template)) {
				preg_match_all('~\{\{(.*?)\}\}~', $template, $matches);
				foreach ($matches[1] as $match) {
					global $db;
					$field = substr($match, strpos($match, ".") + 1);
					$table = strtok($match, '.');

					$db->where($data["identifierType"], $data["identifierValue"]);
					$return = str_replace("{{" . $match . "}}", $db->getValue($table, $field), $return);
				}
			}
			if (preg_match("/##.*##/", $template)) {
				$return = preg_replace("/##.*##/", $data["link"], $return);
			}
			return $return;
		}

		// private function get_template_message($message, $id = null, $id_key = null, $richText = false)
		// {
		// 	if (preg_match('/[\'}{]/', $message)) {
		// 		preg_match_all('~\{\{(.*?)\}\}~', $message, $matches);
		// 		$return = null;
		// 		foreach ($matches[1] as $match) {
		// 			global $db;
		// 			$field = substr($match, strpos($match, ".") + 1);
		// 			$table = strtok($match, '.');
		// 			if (!$richText) {
		// 				$return[] = array("table" => $table, "field" => $field);
		// 			}
		// 			if ($field && $table) {
		// 				if ($table == 'bas_rma') {
		// 					$db->where('r.rmaId', $id);
		// 					$data = $db->objectBuilder()->getOne(TBL_RMA . ' r', $field);
		// 				} elseif ($table == 'bas_fulfillment') {
		// 					$db->join($table . ' f', 'f.orderId=r.orderId');
		// 					$db->where('r.rmaId', $id);
		// 					$data = $db->objectBuilder()->getOne(TBL_RMA . ' r', 'f.' . $field);
		// 				} elseif ($table == 'bas_rma_payment') {
		// 					$db->join($table . ' rp', 'rp.rmaId=r.rmaId');
		// 					$db->where('r.rmaId', $id);
		// 					$data = $db->objectBuilder()->getOne(TBL_RMA . ' r', 'rp.' . $field);
		// 				} else {
		// 					$db->where($id, $id_key);
		// 					$data = $db->ObjectBuilder()->getOne($table,  $field);
		// 				}


		// 				$return = str_replace('{{' . $match . '}}', $data->$field, $message);
		// 			}
		// 		}
		// 		return $return;
		// 	}
		// 	return $message;
		// }


		public static function getInstance()
		{
			if (!self::$instance) {
				self::$instance = new Notification;
			}
			return self::$instance;
		}
	}
}
