<?php global $menu; ?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
    <meta charset="utf-8" />
    <title>FIMS | <?php echo get_page_title(); ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="FIMS - An inhouse ERP of SKMI Enterprise" name="description" />
    <meta content="Ishan Kukadia" name="author" />
    <meta name="MobileOptimized" content="320">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link rel="dns-prefetch" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link async
        href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700|Nunito:300,400,400i,600,700&subset=all"
        rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/plugins/font-awesome/css/all.min.css" rel="stylesheet"
        type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/plugins/simple-line-icons/simple-line-icons.min.css"
        rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"
        type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/plugins/uniform/css/uniform.default.css" rel="stylesheet"
        type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-toastr/toastr.min.css" rel="stylesheet"
        type="text/css" />
    <?php echo get_header(); ?>
    <!-- BEGIN THEME STYLES -->
    <link async href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/css/style-conquer.css" rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/css/style-responsive.css" rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/css/plugins.css" rel="stylesheet" type="text/css" />
    <!-- <link async href="<?php echo BASE_URL; ?>/assets/css/pages/tasks.css" rel="stylesheet" type="text/css"/> -->
    <link async href="<?php echo BASE_URL; ?>/assets/css/themes/default.css" rel="stylesheet" type="text/css"
        id="style_color" />
    <link async href="<?php echo BASE_URL; ?>/assets/css/pages/error.css" rel="stylesheet" type="text/css" />
    <link async href="<?php echo BASE_URL; ?>/assets/css/custom.css" rel="stylesheet" type="text/css" />
    <!-- END THEME STYLES -->
    <!-- <link rel="manifest" href="/manifest.json" /> -->
</head>
<!-- END HEAD -->
<!-- BEGIN BODY -->
<?php
$sidebar = "";
$user_settings = json_decode($current_user['user_settings'], 1);
if (isset($user_settings['view_sidebar']) && $user_settings['view_sidebar'] == "0")
	$sidebar = "page-sidebar-closed";

if ($only_body) :
	$body_class = "page-full-page";
else :
	$body_class = "page-header-fixed page-footer-fixed page-sidebar-fixed " . $sidebar;
endif;

if ($current_page == "verify" || $current_page == "reset_password" || $current_page == "brand-authorisation")
	$body_class = 'login';
?>

<body class="<?php echo $body_class;
				echo ' ' . $current_page; ?>">
    <audio id="notifyAudio" src="<?= BASE_URL ?>/assets/task_notification.mp3" preload="auto"></audio>
    <div class="main-container">
        <?php if (!$only_body) : ?>
        <!-- BEGIN HEADER -->
        <div class="header navbar navbar-fixed-top">
            <!-- BEGIN TOP NAVIGATION BAR -->
            <div class="header-inner">
                <!-- BEGIN LOGO -->
                <div class="page-logo">
                    <a href="./">
                        <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="logo" />
                    </a>
                </div>
                <!-- END LOGO -->
                <!-- BEGIN RESPONSIVE MENU TOGGLER -->
                <a href="javascript:;" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <img src="<?php echo BASE_URL; ?>/assets/img/menu-toggler.png" alt="" />
                </a>
                <!-- END RESPONSIVE MENU TOGGLER -->
                <!-- BEGIN TOP NAVIGATION MENU -->
                <ul class="nav navbar-nav pull-right">
                    <!-- BEGIN NOTIFICATION DROPDOWN -->
                    <li class="dropdown" id="header_notification_bar">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                            data-close-others="true">
                            <i class="icon-bell"></i>
                            <span class="badge badge-success"> 6 </span>
                        </a>
                        <ul class="dropdown-menu extended notification">
                            <li>
                                <p>
                                    Notifications coming soon
                                </p>
                            </li>
                        </ul>
                    </li>
                    <!-- END NOTIFICATION DROPDOWN -->
                    <!-- BEGIN INBOX DROPDOWN -->
                    <li class="dropdown" id="header_inbox_bar">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                            data-close-others="true">
                            <i class="icon-envelope-open"></i>
                            <span class="badge badge-info"> 5 </span>
                        </a>
                        <ul class="dropdown-menu extended inbox">
                            <li>
                                <p>
                                    Messages coming soon
                                </p>
                            </li>
                        </ul>
                    </li>
                    <!-- END INBOX DROPDOWN -->
                    <!-- BEGIN TODO DROPDOWN -->
                    <li class="dropdown" id="header_task_bar">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                            data-close-others="true">
                            <i class="icon-calendar"></i>
                            <span class="badge badge-danger" id="pendingTask"> 5 </span>
                        </a>
                        <ul class="dropdown-menu extended tasks">
                            <li>
                                <p>
                                    Pending tasks
                                </p>
                            </li>
                            <li>
                                <ul class="dropdown-menu-list scroller" style="height: 250px;" id="pendingTaskBody">
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <!-- END TODO DROPDOWN -->
                    <li class="devider">
                        &nbsp;
                    </li>
                    <!-- BEGIN USER LOGIN DROPDOWN -->
                    <li class="dropdown user">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                            data-close-others="true">
                            <img alt="" src="<?php echo BASE_URL; ?>/assets/img/profile-pic.png" width="28px" />
                            <span class="username username-hide-on-mobile"><?php echo $current_user['display_name'] ?>
                            </span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo BASE_URL ?>/users/profile.php"><i class="fa fa-user"></i> My
                                    Profile</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL ?>/logout.php"><i class="fa fa-key"></i> Log Out</a>
                            </li>
                        </ul>
                    </li>
                    <!-- END USER LOGIN DROPDOWN -->
                </ul>
                <!-- END TOP NAVIGATION MENU -->
            </div>
            <!-- END TOP NAVIGATION BAR -->
        </div>
        <!-- END HEADER -->
        <div class="clearfix">
        </div>
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
            <!-- BEGIN SIDEBAR -->
            <div class="page-sidebar-wrapper">
                <div class="page-sidebar navbar-collapse collapse">
                    <!-- BEGIN SIDEBAR MENU -->
                    <!-- DOC: for circle icon style menu apply page-sidebar-menu-circle-icons class right after sidebar-toggler-wrapper -->
                    <?php echo $menu; ?>
                    <!-- END SIDEBAR MENU -->
                </div>
            </div>
            <!-- END SIDEBAR -->
            <?php include_once(ROOT_PATH . '/unauthorised.php'); ?>
            <?php endif; ?>