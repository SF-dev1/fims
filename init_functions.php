<?php

function get_option($option)
{
	global $db;

	$db->where('option_key', $option, 'LIKE');
	$value = $db->getValue(TBL_OPTIONS, 'option_value');
	return $value;
}


function get_template($type, $template)
{
	return get_option($type . '_template_' . $template);
}

function curPageURL()
{
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	// if ($_SERVER["SERVER_PORT"] != "80") {
	// 	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	// } else {
	$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	// }

	return $pageURL;
}
