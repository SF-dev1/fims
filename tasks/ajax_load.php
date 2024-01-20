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
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy");
            $db->where("t.userId", $current_user["userID"]);
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'getApprovals':
            $db->join(TBL_USERS . " u", "u.userID = t.createdBy");
            $db->where("t.userId", null, "IS");
            $tasks = $db->get(TBL_TASKS . " t", null, "t.*, u.display_name as name");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'getNotification':
            $db->where("userRoles", "%" . $current_user["userId"] . "%", "LIKE");
            $db->where("status", "0");
            $db->where("userId", null, "IS");
            $tasks = $db->get(TBL_TASKS, null, "*");

            echo json_encode(["type" => "success", "data" => $tasks]);
            break;

        case 'acceptTask':
            $taskId = $_REQUEST["taskId"];
            $details = array(
                "userId" => $current_user["userID"],
                "status" => "3"
            );
            $tasks = $db->update(TBL_TASKS, $details);

            echo json_encode(["type" => "success", "message" => "Task assigned to you!"]);
            break;
    }
}
