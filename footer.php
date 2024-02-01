	</div>
	<!-- END CONTAINER -->
	<?php if (!$only_body) : ?>
		</div>
		<!-- BEGIN FOOTER -->
		<div class="footer">
			<div class="footer-inner">
				<?php echo date('Y'); ?> &copy; FIMS Dashboard.
			</div>
			<div class="footer-tools">
				<span class="go-top">
					<i class="fa fa-angle-up"></i>
				</span>
			</div>
		</div>
		<!-- END FOOTER -->
	<?php endif; ?>
	<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
	<!-- BEGIN CORE PLUGINS -->
	<script rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-3.5.1.min.js" type="text/javascript">
	</script>
	<script rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-migrate-3.3.1.min.js" type="text/javascript">
	</script>
	<!-- IMPORTANT! Load jquery-ui-1.10.3.custom.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery.blockui.min.js" type="text/javascript">
	</script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/clipboard/clipboard.min.js"></script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/nprogress/nprogress.js" type="text/javascript"></script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-toastr/toastr.min.js" type="text/javascript"></script>
	<script async rel="prefetch" src="<?php echo BASE_URL; ?>/assets/scripts/ui-toastr.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
	<!-- END CORE PLUGINS -->
	<!-- BEGIN GLOBAL SCTIPTS -->
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-idle-timeout/store.min.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/plugins/jquery-idle-timeout/jquery.idletimer.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/scripts/app.js" type="text/javascript"></script>
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/scripts/idle-timeout.js" type="text/javascript">
	</script>
	<!-- Task Notification JS -->
	<script defer rel="prefetch" src="<?php echo BASE_URL; ?>/assets/scripts/task-notification.js" type="text/javascript">
	</script>
	<script defer type="text/javascript">
		var base_url = "<?php echo BASE_URL; ?>";
		// var clipboard = new ClipboardJS('.cpy_btn');
		// clipboard.on('success', function(e) {
		//     UIToastr.init('success', 'Copy to Clipboard', 'Successfully copied to clipboard');
		//     e.clearSelection();
		// });
		// clipboard.on('error', function(e) {
		// 	UIToastr.init('error', 'Copy to Clipboard', 'Error coping to clipboard');
		// });
		jQuery(document).ready(function() {
			App.init(); // initlayout and core plugins
			TasksNotification.init();
			setInterval(function() {
				TasksNotification.init();
			}, 1000 * 60 /* /*10*/ );
			if ('<?= ENV ?>' == 'PRODUCTION')
				IdleTimeout.init("<?php echo BASE_URL; ?>",
					"<?php echo str_replace(BASE_URL, "", curPageURL()); ?>"); // initialize session timeout settings
		});
	</script>
	<?php echo get_footer(); ?>
	<!-- <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script> -->
	<script>
		// var OneSignal = window.OneSignal || [];
		// 	OneSignal.push(function() {
		// 	OneSignal.init({
		// 		appId: "6f3ed6e5-2339-433d-a6c2-080c16ac78b0",
		// 	});
		// });
	</script>
	<!-- END GLOBAL SCTIPTS -->
	<?php
	// Closing the connection to db
	$db->disconnect();
	?>
	</body>
	<!-- END BODY -->

	</html>