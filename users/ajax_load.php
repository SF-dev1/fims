<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	global $db, $log;

	switch ($_REQUEST['action']) {
		case 'get_users':
			$users = $db->get(TBL_USERS, null, 'user_login, display_name, user_nickname, user_email, user_mobile, user_role, user_capabilities, user_status, userID');
			$data = array();
			$i = 0;
			foreach ($users as $user) {
				$data[$i] = $user;
				$user_capabilities = $userAccess->getUserCapabilities($user['userID']);
				$user_capabilities['special_capabilities'] = json_decode(str_replace(1, '"special"', json_encode($user_capabilities['special_capabilities'])), true);
				foreach ($user_capabilities['special_capabilities'] as $cap_key => $cap_value) {
					if (isset($user_capabilities['role_capabilities'][$cap_key])) {
						$user_capabilities['role_capabilities'][$cap_key] = array_merge($user_capabilities['role_capabilities'][$cap_key], $cap_value);
						unset($user_capabilities['special_capabilities'][$cap_key]);
					}
				}

				$capability_data = '<div class="util-btn-group-margin-bottom-5">';
				foreach ($user_capabilities as $capabilities_key => $capabilities) {
					if (!empty($capabilities)) {
						foreach ($capabilities as $cap_key => $cap_value) {
							$capability_data .= '<div class="btn-group"><button class="btn btn-success btn-xs disabled">' . ucwords(str_replace("_", " ", $cap_key)) . '</button>&nbsp;';
							if (is_array($cap_value)) {
								foreach ($cap_value as $capability_key => $capability_value) {
									if (is_array($capability_value)) {
										foreach ($capability_value as $last_cap_key => $last_cap_value) {
											$last_inner_additional = ($last_cap_value == "special" ? "warning" : "info");
											$capability_data .= '<button class="btn btn-' . $last_inner_additional . ' btn-xs disabled">' . ucwords(str_replace("_", " ", $last_cap_key)) . '</button>&nbsp;';
										}
									} else {
										$inner_additional = ($capability_value == "special" ? "warning" : "info");
										$capability_data .= '<button class="btn btn-' . $inner_additional . ' btn-xs disabled">' . ucwords(str_replace("_", " ", $capability_key)) . '</button>&nbsp;';
									}
								}
							}
							$capability_data .= '</div></br >';
						}
					}
				}
				$data[$i]['user_capabilities'] = $capability_data . '</div>';

				$i++;
			}
			$return = array('data' => $data);
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_user':
			$details = $_POST;
			unset($details['action']);

			$details['user_activation_key'] = $userAccess->generateValidationString('email');
			$details['user_otp'] = $userAccess->generateValidationString('mobile');
			$details['user_capabilities'] = json_encode(array());
			$details['createdDate'] = $db->now();

			$userID = $db->insert(TBL_USERS, $details);
			if ($userID) {
				$userAccess->sendVerificationLink($userID);
				$return = array("type" => "success", "msg" => "Successfully added new user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to add new user", "error" => $db->getLastError());
			}
			// header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'delete_user':
			$userID = $_POST['user_id'];
			$db->where('userID', $userID);
			if ($db->delete(TBL_USERS)) {
				$return = array("type" => "success", "msg" => "Successfully deleted user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to delete user");
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'update_user':
			$details = array(
				'display_name' => $_POST['display_name'],
				'user_nickname' => $_POST['user_nickname'],
				'user_role' => strtolower(str_replace(' ', '_', trim($_POST['user_role'])))
			);
			$db->where('userID', $_POST['userID']);
			if ($db->update(TBL_USERS, $details)) {
				$return = array("type" => "success", "msg" => "Successfully updated user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to updated user");
			}
			// header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'update_user_status':
			$details = array('user_status' => $_POST['is_active']);
			$db->where('userID', $_POST['userID']);
			if ($db->update(TBL_USERS, $details)) {
				$return = array("type" => "success", "msg" => "Successfully updated user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to updated user");
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'check_username_availability':
			$db->where('user_login', $_POST['user_login']);

			if ($db->has(TBL_USERS))
				echo 'false';
			else
				echo 'true';
			break;

		case 'check_email_availability':
			$db->where('user_email', $_POST['user_email']);

			if ($db->has(TBL_USERS))
				echo 'false';
			else
				echo 'true';
			break;

		case 'check_mobile_availability':
			$db->where('user_mobile', $_POST['user_mobile']);

			if ($db->has(TBL_USERS))
				echo 'false';
			else
				echo 'true';
			break;

		case 'get_user_capabilities':
			$user_id = $_GET['user_id'];
			$user_role = $_GET['user_role'];

			$user_capabilities = $userAccess->getUserCapabilities($user_id, false, $user_role);

			$access_levels = json_decode(get_option('access_levels'), true);
			$user_role_level_access = '';
			foreach ($access_levels as $level_key => $level_value) {
				$user_role_level_access .= '<div class="form-group">';
				$user_role_level_access .= '<fieldset class="col-md-12">';
				$user_role_level_access .= '<legend><span class="col-md-4">' . ucfirst($level_key) . '</span><span class="col-md-2 permit">All</span><!--<span class="col-md-2 permit">Add</span><span class="col-md-2 permit">Edit</span><span class="col-md-2 permit">Delete</span>--></legend>';
				if (is_array($level_value)) {
					foreach ($level_value as $sub_level_key => $sub_level_value) {
						if (is_array($sub_level_value)) {
							$user_role_level_access .= '<div class="col-md-12">';
							$user_role_level_access .= '<fieldset>';
							$user_role_level_access .= '<legend class="sub_ledend">' . ucwords(str_replace("_", " ", $sub_level_key)) . '</legend>';
							$user_role_level_access .= '<div class="form-group">';
							foreach ($sub_level_value as $sub_menu_level_key => $sub_menu_level_value) {
								$checked = (isset($user_capabilities['role_capabilities'][$level_key][$sub_level_key][$sub_menu_level_key]) ? "checked" : isset($user_capabilities['special_capabilities'][$level_key][$sub_level_key][$sub_menu_level_key])) ? "checked" : "";
								$disabled = isset($user_capabilities['role_capabilities'][$level_key][$sub_level_key][$sub_menu_level_key]) ? "disabled" : "";
								$user_role_level_access .= '<div class="col-md-12"><div class="form-group">';
								$user_role_level_access .= "<label class='col-md-4 control-label'>" . ucwords(str_replace(array("fk", "az", "_"), " ", $sub_menu_level_key)) . "</label><div class='col-md-8'><div class='checkbox-list'><label class='checkbox-inline'><input type='checkbox' value='true' name='capabilities[" . $level_key . "][" . $sub_level_key . "][" . $sub_menu_level_key . "]' " . $checked . " " . $disabled . "></label></div></div>";
								$user_role_level_access .= '</div></div>';
							}
							$user_role_level_access .= '</div>';
							$user_role_level_access .= '</fieldset>';
							$user_role_level_access .= '</div>';
						} else {
							$checked = (isset($user_capabilities['role_capabilities'][$level_key][$sub_level_key]) ? "checked" : isset($user_capabilities['special_capabilities'][$level_key][$sub_level_key])) ? "checked" : "";
							$disabled = isset($user_capabilities['role_capabilities'][$level_key][$sub_level_key]) ? "disabled" : "";
							$user_role_level_access .= '<div class="col-md-12"><div class="form-group">';
							$user_role_level_access .= "<label class='col-md-4 control-label'>" . ucwords(str_replace("_", " ", $sub_level_key)) . "</label><div class='col-md-8'><div class='checkbox-list'><label class='checkbox-inline'><input type='checkbox' value='true' name='capabilities[" . $level_key . "][" . $sub_level_key . "]' " . $checked . " " . $disabled . "></label></div></div>";
							$user_role_level_access .= '</div></div>';
						}
					}
				} else {
					$checked = (isset($user_capabilities['role_capabilities'][$level_key]) ? "checked" : isset($user_capabilities['special_capabilities'][$level_key])) ? "checked" : "";
					$disabled = isset($user_capabilities['role_capabilities'][$level_key]) ? "disabled" : "";
					$user_role_level_access .= '<label class="col-md-4 control-label">' . ucwords(str_replace("_", " ", $level_key)) . '</label>';
					$user_role_level_access .= '<div class="col-md-8"><div class="checkbox-list"><label class="checkbox-inline"><input type="checkbox" value="true" name="capabilities[' . $level_key . ']" ' . $checked . ' ' . $disabled . '></label></div></div>';
				}
				$user_role_level_access .= '</fieldset>';
				$user_role_level_access .= '</div>';
			}
			echo json_encode($user_role_level_access);
			break;

		case 'update_user_capabilities':
			$details['user_role'] = $_POST['user_role'];
			$details['user_capabilities'] = str_replace('"true"', true, json_encode($_POST['capabilities']));

			$db->where('userID', $_POST['user_id']);
			if ($db->update(TBL_USERS, $details))
				$return = array('type' => 'success', 'msg' => 'Successfully updated user role and permissions');
			else
				$return = array('type' => 'error', 'msg' => 'Unable to updated user role and permissions');

			echo json_encode($return);
			break;

		case 'get_roles':
			$user_roles = json_decode(get_option('user_roles'), true);
			$acc_panel = "";
			foreach ($user_roles as $role => $user_level_access) {
				$access_levels = json_decode(get_option('access_levels'), true);
				$user_role_level_access = "";
				foreach ($access_levels as $level_key => $level_value) {
					$user_role_level_access .= '<div class="form-group">';
					$user_role_level_access .= '<fieldset class="col-md-12">';
					$user_role_level_access .= '<legend><span class="col-md-3">' . ucfirst($level_key) . '</span><span class="col-md-2 permit">All</span><span class="col-md-2 permit">Add</span><span class="col-md-2 permit">Edit</span><span class="col-md-2 permit">Delete</span></legend>';
					if (is_array($level_value)) {
						foreach ($level_value as $sub_level_key => $sub_level_value) {
							if (is_array($sub_level_value)) {
								$user_role_level_access .= '<div class="col-md-12">';
								$user_role_level_access .= '<fieldset>';
								$user_role_level_access .= '<legend class="sub_ledend">' . ucwords(str_replace("_", " ", $sub_level_key)) . '</legend>';
								$user_role_level_access .= '<div class="form-group">';
								foreach ($sub_level_value as $sub_menu_level_key => $sub_menu_level_value) {
									$checked = isset($user_level_access[$level_key][$sub_level_key][$sub_menu_level_key]) ? "checked" : "";
									$user_role_level_access .= '<div class="col-md-12"><div class="form-group">';
									$user_role_level_access .= "<label class='col-md-3 control-label'>" . ucwords(str_replace(array("fk", "az", "_"), " ", $sub_menu_level_key)) . "</label><div class='col-md-9'><div class='checkbox-list'><label class='checkbox-inline'><input type='checkbox' value='true' name='" . $level_key . "[" . $sub_level_key . "][" . $sub_menu_level_key . "]' " . $checked . "></label></div></div>";
									$user_role_level_access .= '</div></div>';
								}
								$user_role_level_access .= '</div>';
								$user_role_level_access .= '</fieldset>';
								$user_role_level_access .= '</div>';
							} else {
								$checked = isset($user_level_access[$level_key][$sub_level_key]) ? "checked" : "";
								$user_role_level_access .= '<div class="col-md-12"><div class="form-group">';
								$user_role_level_access .= "<label class='col-md-3 control-label'>" . ucwords(str_replace("_", " ", $sub_level_key)) . "</label><div class='col-md-9'><div class='checkbox-list'><label class='checkbox-inline'><input type='checkbox' value='true' name='" . $level_key . "[" . $sub_level_key . "]' " . $checked . "></label></div></div>";
								$user_role_level_access .= '</div></div>';
							}
						}
					} else {
						$checked = isset($user_level_access[$level_key]) ? "checked" : "";
						$user_role_level_access .= '<label class="col-md-3 control-label">' . ucwords(str_replace("_", " ", $level_key)) . '</label>';
						$user_role_level_access .= '<div class="col-md-9"><div class="checkbox-list"><label class="checkbox-inline"><input type="checkbox" value="true" name="' . $level_key . '" ' . $checked . '></label></div></div>';
					}
					$user_role_level_access .= '</fieldset>';
					$user_role_level_access .= '</div>';
				}
				$acc_panel .= '
					<div class="panel panel-default">
						<div class="panel-heading">
							<div class="row">
								<div class="col-md-11">
									<a class="accordion-toggle" data-toggle="collapse" data-parent="#access_role_accordion" href="#collapse_' . $role . '">
										<h5 class="panel-title">
											<button type="button" class="btn btn-xs btn-default"><i class="fas fa-angle-down"></i></button>
											' . ucwords(str_replace("_", " ", $role), " ") . '
										</h5>
									</a>
								</div>
								<div class="col-md-1">
									<button type="button" class="btn btn-xs btn-danger pull-right remove-role" data-role="' . $role . '"><i class="fas fa-trash"></i></button>
								</div>
							</div>
						</div>
						<div id="collapse_' . $role . '" class="panel-collapse collapse">
							<div class="panel-body">
								<form class="form-horizontal" id="user_role_access_' . $role . '" data-role="' . $role . '" role="form">
									<div class="form-body">
										' . $user_role_level_access . '
									</div>
									<div class="form-actions">
										<div class="row">
											<div class="col-md-offset-10 col-md-2">
												<button type="submit" class="btn btn-success btn-submit pull-right"><i></i> Save Permissions</button>
											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>';
			}
			$return = array("type" => "success", "content" => $acc_panel);
			echo json_encode($return);
			break;

		case 'add_role':
			$role_name = strtolower(str_replace(" ", "_", $_POST['role_name']));
			$user_roles = json_decode(get_option('user_roles'), true);
			$user_roles = array_merge($user_roles, array($role_name => array("")));

			$db->where('option_key', 'user_roles');
			if ($db->update(TBL_OPTIONS, array('option_value' => json_encode($user_roles)))) {
				$return = array('type' => 'success', 'msg' => "Successfully added new role");
			} else {
				$return = array('type' => 'error', 'msg' => "Unable to add new role");
			}

			echo json_encode($return);
			break;

		case 'delete_role':
			$role_name = $_POST['role_name'];
			$user_roles = json_decode(get_option('user_roles'), true);
			unset($user_roles[$role_name]);

			$db->where('option_key', 'user_roles');
			if ($db->update(TBL_OPTIONS, array('option_value' => json_encode($user_roles)))) {
				$return = array('type' => 'success', 'msg' => "Successfully deleted role " . $role_name);
			} else {
				$return = array('type' => 'error', 'msg' => "Unable to delete role");
			}

			echo json_encode($return);
			break;

		case 'update_access_role':
			$user_level_data = $_POST;
			unset($user_level_data['action']);
			unset($user_level_data['access_role']);
			$user_roles = json_decode(get_option('user_roles'), true);
			$user_roles[$_POST['access_role']] = $user_level_data;
			$user_roles = json_encode($user_roles);
			$user_roles = str_replace('"true"', true, $user_roles);

			$db->where('option_key', 'user_roles');
			if ($db->update(TBL_OPTIONS, array('option_value' => $user_roles))) {
				$return = array('type' => 'success', 'msg' => "Successfully updated role permissions");
			} else {
				$return = array('type' => 'error', 'msg' => "Unable to updated role permissions");
			}

			echo json_encode($return);
			break;

		case 'update_profile':
			if (($_POST['display_name'] === $_POST['current_display_name']) && ($_POST['user_email'] === $_POST['current_user_email']) && ($_POST['user_mobile'] === $_POST['current_user_mobile']) && ($_POST['user_nickname'] === $_POST['current_user_nickname'])) {
				return;
			} else {
				$db->where('userID', $_POST['userID']);
				if ($db->update(TBL_USERS, array('display_name' => $_POST['display_name'], 'user_nickname' => $_POST['user_nickname'])))
					$return = array('type' => "success", "msg" => "Profile Updated Successfully");
				else
					$return = array('type' => "success", "msg" => "Unable to update profile");

				echo json_encode($return);
			}
			break;

		case 'update_password':
			$userID = $_POST['userID'];
			$user = $db->getOne(TBL_USERS);

			if ($user['user_pass'] != $_POST['current_pass']) {
				$return = array('type' => 'error', 'msg' => 'Current Password Mismatch. Please enter correct current password');
			} else {
				$db->where('userID', $userID);
				if ($db->update(TBL_USERS, array('user_pass' => $_POST['user_pass'])))
					$return = array('type' => 'success', 'msg' => 'Password successfully updated');
				else
					$return = array('type' => 'success', 'msg' => 'Error updating password');
			}

			echo json_encode($return);
			break;

		case 'update_settings':
			$details = $_POST;
			$userID = $_POST['userID'];

			unset($details['action']);
			unset($details['userID']);

			$db->where('userID', $userID);
			if ($db->update(TBL_USERS, array("user_settings" => json_encode($details))))
				$return = array('type' => 'success', 'msg' => 'Settings successfully updated');
			else
				$return = array('type' => 'success', 'msg' => 'Error updating settings');

			echo json_encode($return);
			break;

			// API
		case 'get_api_users':
			$users = $db->get(TBL_API_USERS, null, 'user_host, user_key, user_secret, user_status, user_id');
			$data = array();
			$i = 0;
			foreach ($users as $user) {
				$data[$i] = $user;
				// $user_capabilities = $userAccess->getUserCapabilities($user['user_id']);
				// $user_capabilities['special_capabilities'] = json_decode(str_replace(1, '"special"', json_encode($user_capabilities['special_capabilities'])), true);
				// foreach ($user_capabilities['special_capabilities'] as $cap_key => $cap_value) {
				// 	if (isset($user_capabilities['role_capabilities'][$cap_key])){
				// 		$user_capabilities['role_capabilities'][$cap_key] = array_merge($user_capabilities['role_capabilities'][$cap_key], $cap_value);
				// 		unset($user_capabilities['special_capabilities'][$cap_key]);
				// 	}
				// }

				// $capability_data = '<div class="util-btn-group-margin-bottom-5">';
				// foreach ($user_capabilities as $capabilities_key => $capabilities) {
				// 	if (!empty($capabilities)){
				// 		foreach ($capabilities as $cap_key => $cap_value) {
				// 			$capability_data .= '<div class="btn-group"><button class="btn btn-success btn-xs disabled">'.ucwords(str_replace("_", " ", $cap_key)).'</button>&nbsp;';
				// 			if (is_array($cap_value)){
				// 				foreach ($cap_value as $capability_key => $capability_value) {
				// 					if (is_array($capability_value)){
				// 						foreach ($capability_value as $last_cap_key => $last_cap_value) {
				// 							$last_inner_additional = ($last_cap_value == "special" ? "warning" : "info");
				// 							$capability_data .= '<button class="btn btn-'.$last_inner_additional.' btn-xs disabled">'.ucwords(str_replace("_", " ", $last_cap_key)).'</button>&nbsp;';
				// 						}
				// 					} else {
				// 						$inner_additional = ($capability_value == "special" ? "warning" : "info");
				// 						$capability_data .= '<button class="btn btn-'.$inner_additional.' btn-xs disabled">'.ucwords(str_replace("_", " ", $capability_key)).'</button>&nbsp;';
				// 					}
				// 				}
				// 			}
				// 			$capability_data .= '</div></br >';
				// 		}
				// 	}
				// }
				// $data[$i]['user_capabilities'] = $capability_data.'</div>';

				$i++;
			}
			$return = array('data' => $data);
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_api_user':
			$details = $_POST;
			unset($details['action']);

			$keySecret = $userAccess->generateAPICredentials($details['user_host']);
			$details['user_key'] = $keySecret['key'];
			$details['user_secret'] = $keySecret['secret'];
			$details['createdDate'] = $db->now();

			$userID = $db->insert(TBL_API_USERS, $details);
			if ($userID) {
				$return = array("type" => "success", "msg" => "Successfully added new user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to add new user", "error" => $db->getLastError());
			}
			// header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'delete_api_user':
			$userID = $_POST['user_id'];
			$db->where('userID', $userID);
			if ($db->delete(TBL_USERS)) {
				$return = array("type" => "success", "msg" => "Successfully deleted user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to delete user");
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'update_api_user_status':
			$details = array('user_status' => $_POST['is_active']);
			$db->where('user_id', $_POST['user_id']);
			if ($db->update(TBL_API_USERS, $details)) {
				$return = array("type" => "success", "msg" => "Successfully updated user");
			} else {
				$return = array("type" => "error", "msg" => "Unable to updated user");
			}
			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'check_userhost_availability':
			$db->where('user_host', $_POST['user_host']);

			if ($db->has(TBL_API_USERS))
				echo 'false';
			else
				echo 'true';
			break;
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}
