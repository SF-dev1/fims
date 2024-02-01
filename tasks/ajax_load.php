<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
    include(dirname(dirname(__FILE__)) . '/config.php');
    include(ROOT_PATH . '/includes/vendor/autoload.php');
    global $db, $current_user, $log, $stockist;

    switch ($_REQUEST['action']) {
        case 'getTasks':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_USERS . " u", "u.userID = t.userID", "LEFT");
            $db->join(TBL_INVENTORY . " i", "i.ctn_id = t.ctnId", "LEFT");
            $flag = true;
            if ($current_user["user_role"] != "super_admin") {
                $db->where("t.userRoles LIKE ?", $current_user["user_role"]);
                $flag = false;
            } else {
                $db->where("t.userId", null, "IS NOT");
            }
            $db->orWhere("(t.createdBy = ? AND t.status = ?)", [$current_user["userID"], "2"]);
            $db->groupBy("i.ctn_id");
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name, count(i.inv_id) as quantity");

            echo json_encode(["type" => "success", "data" => $tasks, "flag" => $flag, "user" => $current_user["user_role"]]);
            break;

        case 'getApprovals':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy", "LEFT");
            $db->join(TBL_INVENTORY . " i", "i.ctn_id = t.ctnId", "LEFT");
            $db->where("t.createdDate", date("Y-m-d H:i:s"), "<=");
            $flag = true;
            if ($current_user["user_role"] != "super_admin") {
                $db->where("t.userRoles LIKE ?", $current_user["user_role"]);
                $flag = false;
            }
            $db->where("t.userId", null, "IS");
            $db->groupBy("i.ctn_id");
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name, count(i.inv_id) as quantity, i.expectedLocation");

            echo json_encode(["type" => "success", "data" => $tasks, "flag" => $flag, "user" => $current_user["user_role"]]);
            break;

        case 'addSystemTask':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';

            $details = array(array(
                "title" => "Products have some issue! Check and solve the issue.",
                "ctnId" => "2",
                "createdBy" => "0",
                "category" => "qc_failed",
                "userRoles" => 'repairs',
                "status" => "0"
            ), array(
                "title" => "Products requesting some components.",
                "ctnId" => "3",
                "createdBy" => "0",
                "category" => "components_requested",
                "userRoles" => "admin",
                "status" => "0"
            ));
            $db->where("expectedLocation", "SFQCRJ_00001");
            $count = $db->getValue(TBL_INVENTORY, "count(inv_id)");
            if ($count > 0) {
                $details[] = array(
                    "title" => "Product's soft qc rejected.",
                    "ctnId" => "7",
                    "createdBy" => "0",
                    "category" => "qc_rejected",
                    "userRoles" => 'qc',
                    "status" => "0"
                );
            }

            $db->insertMulti(TBL_TASKS, $details);
            echo json_encode(["type" => "success"]);
            break;

        case 'getNotification':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->where("(userRoles LIKE ? OR userRoles LIKE ? OR userRoles LIKE ? OR userRoles LIKE ?)", ["%[" . $current_user["userID"] . ",%", "%," . $current_user["userID"] . ",%", "%," . $current_user["userID"] . "]%", "[" . $current_user["userID"] . "]"]);
            $db->where("status", "0");
            $db->where("userId", null, "IS");
            $tasks = $db->get(TBL_TASKS, null, "*");

            if (count($tasks) != 0)
                echo json_encode(["type" => "success", "data" => $tasks]);
            else
                echo json_encode(["type" => "error"]);
            break;

        case 'taskAction':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $taskId = $_REQUEST["taskId"];
            $taskAction = $_REQUEST["taskAction"];
            $db->where("id", $taskId);
            $taskDetails = $db->getOne(TBL_TASKS, "*");
            $isBreached = "0";
            if (date("Y-m-d", strtotime($taskDetails["createdDate"])) != date("Y-m-d"))
                $isBreached = "1";
            if ($taskAction == "decline") {
                $details = array(
                    "rejectReason" => "The task is rejected by " . $current_user["display_name"] . " \nReason : " . $_REQUEST["reason"],
                    "status" => "2",
                    "isBreached" => $isBreached
                );
            } else {
                if ($taskDetails["category"] != "component_requested" || $taskDetails["category"] != "qc_failed") {
                    $db->rawQuery("UPDATE " . TBL_INVENTORY . " SET currentLocation = expectedLocation WHERE ctn_id = " . $taskDetails["ctnId"]);
                } else {
                    $db->rawQuery("UPDATE " . TBL_INVENTORY . " SET currentLocation = expectedLocation WHERE inv_status = " . $taskDetails["category"]);
                }

                $details = array(
                    "userId" => $current_user["userID"],
                    "status" => "1",
                    "isBreached" => $isBreached
                );
            }
            $db->where("id", $taskId);
            $tasks = $db->update(TBL_TASKS, $details);

            echo json_encode(["type" => "success", "message" => "Details successfully updated!"]);
            break;

        case 'markDone':
            $taskId = $_REQUEST["taskId"];
            // get completed task cartoon id
            $db->where("id", $taskId);
            $taskDetails = $db->getOne(TBL_TASKS, "*");

            // update task details
            $details = array(
                "status" => "3",
            );
            $db->where("id", $taskId);
            $tasks = $db->update(TBL_TASKS, $details);


            if ($taskDetails["category"] == "printing") {
                $db->where("ctn_id", $taskDetails["ctnId"]);
                $capacity = $db->getValue(TBL_INVENTORY, "count(inv_id)");
                $expectedLocation = $stockist->get_expected_location_by_status("qc_pending", $capacity);

                // getting new user id for next task assignment
                $db->where("user_role", "qc");
                $db->orWhere("user_role", "super_admin");
                $ids = array_map(function ($innerArray) {
                    return $innerArray["userID"];
                }, $db->get(TBL_USERS, null, "userID"));

                $details = array(
                    "expectedLocation" => $expectedLocation,
                    "currentLocation" => null,
                    "locationConformation" => "0"
                );
                $db->where("ctn_id", $taskDetails["ctnId"]);
                $db->update(TBL_INVENTORY, $details);

                // new task details
                $details = array(
                    "title" => "Products Ready For QC Process",
                    "status" => "0",
                    "ctnId" => $taskDetails["ctnId"],
                    "createdBy" => $current_user["userID"],
                    "category" => "qc",
                    "userRoles" => json_encode($ids)
                );
                $db->insert(TBL_TASKS, $details);
            }

            echo json_encode(["type" => "success", "message" => "Details successfully updated!"]);
            break;
    }
}
