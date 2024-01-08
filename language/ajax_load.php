<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
    include(dirname(dirname(__FILE__)) . '/config.php');
    include(ROOT_PATH . '/includes/vendor/autoload.php');
    global $db, $accounts, $log, $stockist;

    switch ($_REQUEST['action']) {
        case 'language':
            $db->orderBy('lang_name', 'ASC');
            $language = $db->get(TBL_LANGUAGE, null, array('lang_name as "lang_name"', 'lang_id'));
            $return['data'] = $language;

            if (empty($return))
                $return['data'] = array();

            header('Content-Type: application/json');
            echo json_encode($return);

            break;

        case 'add_language':
            $details['lang_name'] = $_POST['languageName'];

            $db->where('lang_name', $_POST['languageName']);
            if (!$db->has(TBL_LANGUAGE)) {
                if ($id = $db->insert(TBL_LANGUAGE, $details)) {
                    $return['type'] = "Success";
                    $return = array('type' => 'success', 'msg' => 'Successfull added new language');
                } else {
                    $return = array('type' => 'error', 'msg' => 'Error adding new product :: ' . $db->getLastError());
                }
            } else {
                $return['type'] = "Error";
                $return['message'] = "Duplicate Category Name";
                $return = array('type' => 'error', 'msg' => 'Duplicate Category Name');
            }
            echo json_encode($return);
            break;

        case 'edit_language':
            $langid = $_POST['lang_id'];
            $details['lang_name'] = $_POST['lang_name'];

            $log->logfile = TODAYS_LOG_PATH . '/language.log';

            $db->where('lang_id', $langid);
            if ($db->update(TBL_LANGUAGE, $details)) {
                $return = array('type' => 'success', 'msg' => 'Successfull updated language');
                $log->write('Successfull updated language with name ' . $_POST['lang_name'] . json_encode($details) . ' Query::' . $db->getLastQuery());
            } else {
                $return = array('type' => 'error', 'msg' => 'Unable to updated language');
                $log->write('Unable updated language with name ' . $_POST['lang_name'] . ' ' . $db->getLastError());
            }
            echo json_encode($return);
            break;

        case 'delete_language':
            $langid = $_POST['langid'];
            $db->where('lang_id', $langid);
            $log->logfile = TODAYS_LOG_PATH . '/language.log';
            if ($db->delete(TBL_LANGUAGE)) {
                $return = array('type' => 'success', 'msg' => 'Successfull deleted language');
                $log->write('Successfull deleted language.');
            } else {
                $return = array('type' => 'error', 'msg' => 'Unable to delete language');
                $log->write('Unable delete language. :: ' . $db->getLastError());
            }
            echo json_encode($return);
            break;
    }
} else {
    exit('hmmmm... trying to hack in ahh!');
}
