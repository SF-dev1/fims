<?php
if (!$userAccess->checkCapability($current_page, true)) {
?>
	<!-- BEGIN CONTENT -->
	<div class="page-content-wrapper">
		<div class="page-content">
			<!-- BEGIN PAGE HEADER-->
			<h3 class="page-title">
				Access <small>Denied</small>
			</h3>
			<div class="page-bar">
				<?php echo $breadcrumps; ?>
			</div>
			<!-- END PAGE HEADER-->
			<!-- BEGIN PAGE CONTENT-->
			<div class="row">
				<div class="col-md-12 page-500">
					<div class=" number">
						401
					</div>
					<div class=" details">
						<h3>Unauthorised</h3>
						<p>
							You are not authorised to access this page!<br />
							Please contact administrator to get access to this page.<br /><br />
						</p>
					</div>
				</div>
			</div>
			<!-- END PAGE CONTENT-->
		</div>
	</div>
	<!-- END CONTENT -->
<?php
	include_once(ROOT_PATH . '/footer.php');
	exit;
}
?>