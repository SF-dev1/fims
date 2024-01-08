<?php
include_once(dirname(dirname(__FILE__)) . '/config.php');
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en" class="no-js">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
	<meta charset="utf-8" />
	<title>FIMS | Login</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
	<meta content="FIMS - An inhouse ERP of SKMI Enterprise" name="description" />
	<meta content="Ishan Kukadia" name="author" />
	<meta name="MobileOptimized" content="320">
	<meta name="robots" content="noindex">
	<!-- BEGIN GLOBAL MANDATORY STYLES -->
	<link async href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=all" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/plugins/font-awesome/css/all.min.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-toastr/toastr.min.css" rel="stylesheet" type="text/css" />
	<!-- END GLOBAL MANDATORY STYLES -->
	<!-- BEGIN PAGE LEVEL STYLES -->
	<link async rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>/assets/plugins/select2/select2.css" />
	<!-- END PAGE LEVEL SCRIPTS -->
	<!-- BEGIN THEME STYLES -->
	<link async href="<?php echo BASE_URL; ?>/assets/css/style-conquer.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/style-responsive.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/plugins.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/themes/default.css" rel="stylesheet" type="text/css" id="style_color" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/pages/login.css" rel="stylesheet" type="text/css" />
	<link async href="<?php echo BASE_URL; ?>/assets/css/custom.css" rel="stylesheet" type="text/css" />
	<!-- END THEME STYLES -->
	<link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
	<link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
</head>
<!-- BEGIN BODY -->

<body class="login">
	<!-- BEGIN LOGO -->
	<div class="logo">
		<a href="<?php echo BASE_URL; ?>">
			<img src="../assets/img/logo.png" alt="" />
		</a>
	</div>
	<!-- END LOGO -->
	<!-- BEGIN LOGIN -->