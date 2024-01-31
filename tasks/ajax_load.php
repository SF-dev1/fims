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
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy", "LEFT");
            $db->where("t.userId", $current_user["userID"]);
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'getApprovals':
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy", "LEFT");
            $db->join(TBL_INVENTORY . " i", "i.ctn_id = t.ctnId", "LEFT");
            $db->where("(t.userRoles LIKE ? OR t.userRoles LIKE ?)", ["%" . $current_user["userID"] . ",%", "%" . $current_user["userID"] . "]%"]);
            $db->where("t.userId", null, "IS");
            $db->where("t.rejectReason", null, "IS");
            $db->groupBy("i.inv_status");
            $db->groupBy("t.id");
            $db->groupBy("i.expectedLocation");
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name, i.expectedLocation, count(i.inv_id) as count");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'addCoolingEndTask':
            $db->join(TBL_INVENTORY_LOG . " l", "l.inv_id = i.inv_id");
            $db->where("i.inv_status", "qc_cooling");
            $db->groupBy("i.ctn_id");
            $db->orderBy("MAX(l.invi_id)");
            $cartoons = $db->get(TBL_INVENTORY . " i", null, "i.ctn_id");

            $db->where("user_role", "qc");
            $db->orWhere("user_role", "super_admin");
            $ids = array_map(function ($innerArray) {
                return $innerArray["userID"];
            }, $db->get(TBL_USERS, null, "userID"));
            $details = array();
            foreach ($cartoons as $cartoon) {
                $details[] = array(
                    "createdBy" => "0",
                    "title" => "Products Ready For QC Verification",
                    "ctnId" => $cartoon["ctn_id"],
                    "category" => "qc_cooling",
                    "userRoles" => json_encode($ids),
                    "status" => "0"
                );
            }
            try {
                $ids = $db->insertMulti(TBL_TASKS, $details);
            } catch (Exception $e) {
            }

            echo json_encode(["type" => "success", "message" => "task Added Successfully"]);
            break;

        case 'addQcVerifiedTask':
            $db->join(TBL_INVENTORY_LOG . " l", "l.inv_id = i.inv_id");
            $db->where("i.inv_status", "qc_verified");
            $db->where("l.log_type", "qc_failed");
            $db->groupBy("i.ctn_id");
            $db->orderBy("MAX(l.invi_id)");
            $cartoons = $db->get(TBL_INVENTORY . " i", null, "i.ctn_id");

            $db->where("user_role", "warehouse_operations");
            $db->orWhere("user_role", "super_admin");
            $ids = array_map(function ($innerArray) {
                return $innerArray["userID"];
            }, $db->get(TBL_USERS, null, "userID"));
            $details = array();
            foreach ($cartoons as $cartoon) {
                $details[] = array(
                    "createdBy" => "0",
                    "title" => "Products Ready For Sale",
                    "ctnId" => $cartoon["ctn_id"],
                    "category" => "qc_verified",
                    "userRoles" => json_encode($ids),
                    "status" => "0"
                );
            }
            try {
                $ids = $db->insertMulti(TBL_TASKS, $details);
            } catch (Exception $e) {
            }

            echo json_encode(["type" => "success", "message" => "task Added Successfully"]);
            break;

        case 'addQcFailedTask':
            $db->join(TBL_INVENTORY_LOG . " l", "l.inv_id = i.inv_id");
            $db->where("i.inv_status", "qc_failed");
            $db->where("l.log_type", "qc_failed");
            $products = $db->get(TBL_INVENTORY . " i", null, "i.inv_id, i.ctn_id");

            $db->where("category", "qc_failed");
            $db->where("status", "0");
            if ($db->has(TBL_TASKS)) {
                $details = array(
                    "title" => count($products) . " products have some issue",
                );
                $db->where("category", "qc_failed");
                $db->update(TBL_TASKS, $details);
            } else {
                $db->where("user_role", "warehouse_operations");
                $db->orWhere("user_role", "repairs");
                $ids = array_map(function ($innerArray) {
                    return $innerArray["userID"];
                }, $db->get(TBL_USERS, null, "userID"));
                $details = array();
                $details = array(
                    "createdBy" => "0",
                    "title" => count($products) . " products have some issue",
                    "ctnId" => $products[0]["ctn_id"],
                    "category" => "qc_failed",
                    "userRoles" => json_encode($ids),
                    "status" => "0"
                );
                $db->insert(TBL_TASKS, $details);
            }

            echo json_encode(["type" => "success", "message" => "task Added Successfully"]);
            break;

        case 'addComponentRequestedTask':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->where("inv_status", "components_requested");
            $products = $db->get(TBL_INVENTORY, null, "inv_id, ctn_id");

            $db->where("category", "components_requested");
            $db->where("status", "0");
            if ($db->has(TBL_TASKS)) {
                $details = array(
                    "title" => count($products) . " products are in components required state",
                );
                $db->where("category", "components_requested");
                $db->update(TBL_TASKS, $details);
            } else {
                $details = array(
                    "createdBy" => "0",
                    "title" => count($products) . " products are in components required state",
                    "ctnId" => $products[0]["ctn_id"],
                    "category" => "components_requested",
                    "userRoles" => "[2]",
                    "status" => "0"
                );
                $db->insert(TBL_TASKS, $details);
            }

            echo json_encode(["type" => "success", "message" => "task Added Successfully"]);
            break;

        case 'getCoolingEndData':
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy");
            $db->join(TBL_INVENTORY . " i", "i.ctn_id = t.ctnId", "LEFT");
            $db->where("t.userId", null, "IS");
            $db->where("t.rejectReason", null, "IS");
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name, i.expectedLocation");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'getNotification':
            $db->where("(userRoles LIKE ? OR userRoles LIKE ?)", ["%" . $current_user["userID"] . ",%", "%" . $current_user["userID"] . "]%"]);
            $db->where("status", "0");
            $db->where("userId", null, "IS");
            $tasks = $db->get(TBL_TASKS, null, "*");

            echo json_encode(["type" => "success", "data" => $tasks]);
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
                    "rejectReason" => "The task is rejected by " . $current_user["userID"] . " \nReason : " . $_REQUEST["reason"],
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
                $expectedLocation = $stockist->get_expected_location_status("qc_pending", $capacity);

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
