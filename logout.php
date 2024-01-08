<?php
include_once(dirname(__FILE__) . '/config.php');

$userAccess->sec_session_destroy();

if (isset($_GET['return']))
	header("Location: " . BASE_URL . "/login/?msg=2&return=" . urlencode($_GET['return']));
else
	header("Location: " . BASE_URL . "/login/?msg=1");
