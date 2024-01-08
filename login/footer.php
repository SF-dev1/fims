		<!-- END CONTENT -->
		<!-- BEGIN COPYRIGHT -->
		<div class="copyright">
			<?php echo date('Y', time()); ?> &copy; FIMS
		</div>
		<!-- END COPYRIGHT -->
		<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
		<!-- BEGIN CORE PLUGINS -->
		<script src="<?php echo BASE_URL; ?>/assets/plugins/jquery-3.5.1.min.js" type="text/javascript"></script>
		<script src="<?php echo BASE_URL; ?>/assets/plugins/jquery-migrate-3.3.1.min.js" type="text/javascript"></script>
		<!-- IMPORTANT! Load jquery-ui-1.10.3.custom.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->
		<script defer src="<?php echo BASE_URL; ?>/assets/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>
		<script defer src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
		<script defer src="<?php echo BASE_URL; ?>/assets/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
		<script async src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
		<script async src="<?php echo BASE_URL; ?>/assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
		<script async src="<?php echo BASE_URL; ?>/assets/plugins/jquery.blockui.min.js" type="text/javascript"></script>
		<script async src="<?php echo BASE_URL; ?>/assets/plugins/bootstrap-toastr/toastr.min.js" type="text/javascript"></script>
		<script async src="<?php echo BASE_URL; ?>/assets/plugins/sha512/sha512.js" type="text/javascript"></script>
		<!-- END CORE PLUGINS -->
		<!-- BEGIN PAGE LEVEL PLUGINS -->
		<script async src="<?php echo BASE_URL; ?>/assets/scripts/ui-toastr.js" type="text/javascript"></script>
		<script defer src="<?php echo BASE_URL; ?>/assets/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
		<script async type="text/javascript" src="<?php echo BASE_URL; ?>/assets/plugins/select2/select2.min.js"></script>
		<?php if (!empty($login_init))
			echo '<script defer type="text/javascript" src="' . BASE_URL . '/assets/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js"></script>';
		?>
		<!-- END PAGE LEVEL PLUGINS -->
		<!-- BEGIN PAGE LEVEL SCRIPTS -->
		<script defer src="<?php echo BASE_URL; ?>/assets/scripts/app.js" type="text/javascript"></script>
		<script defer src="<?php echo BASE_URL; ?>/assets/scripts/login.js" type="text/javascript"></script>
		<!-- END PAGE LEVEL SCRIPTS -->
		<script>
			jQuery(document).ready(function() {
				App.init();
				Login.init(<?php echo $login_init; ?>);
			});
		</script>
		<!-- END JAVASCRIPTS -->
		<!-- END LOGIN -->
		</body>
		<!-- END BODY -->

		</html>