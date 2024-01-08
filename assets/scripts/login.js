var Login = function () {

	var handleLogin = function () {
		$('.login-form').validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			rules: {
				username: {
					required: true
				},
				password: {
					required: true
				},
				remember: {
					required: false
				}
			},

			messages: {
				username: {
					required: "Username is required."
				},
				password: {
					required: "Password is required."
				}
			},

			invalidHandler: function (event, validator) { //display error alert on form submit   
				$('.alert-danger', $('.login-form')).show();
			},

			highlight: function (element) { // hightlight error inputs
				$(element)
					.closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error');
				label.remove();
			},

			errorPlacement: function (error, element) {
				error.insertAfter(element.closest('.input-icon'));
			},

			submitHandler: function (form) {
				// Create a new element input, this will be our hashed password field. 
				var p = document.createElement("input");

				password = form.password;

				// Add the new element to our form. 
				form.appendChild(p);
				p.name = "token";
				p.type = "hidden";
				p.value = hex_sha512(password.value);

				// Make sure the plaintext password doesn't get sent. 
				password.value = "";

				// Finally submit the form. 
				form.submit();
			}
		});

		$('.login-form input').keypress(function (e) {
			if (e.which == 13) {
				if ($('.login-form').validate().form()) {
					$('.login-form').submit();
				}
				return false;
			}
		});
	}

	var handleForgetPassword = function () {
		$('.forget-form').validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				user_email: {
					required: true,
					email: true,
					remote: {
						url: "ajax_load.php?action=check_email_registered",
						type: "POST"
					}
				}
			},

			messages: {
				user_email: {
					remote: "Email address is not registeted!",
					required: "Email is required."
				}
			},

			invalidHandler: function (event, validator) { //display error alert on form submit   

			},

			highlight: function (element) { // hightlight error inputs
				$(element)
					.closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error');
				label.remove();
			},

			errorPlacement: function (error, element) {
				error.insertAfter(element.closest('.input-icon'));
			},

			submitHandler: function (form) {
				$button = $(this);
				$button.attr('disabled', true);
				$button.find('i').addClass('fa fa-sync fa-spin');

				var formData = new FormData();
				formData.append('action', 'resend_verification');
				formData.append('user_email', $('[name="user_email"').val());
				formData.append('type', $('[name="type"').val());

				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: "POST",
					data: formData,
					contentType: false,
					processData: false,
					async: true,
					success: function (s) {
						console.log(s);
						s = $.parseJSON(s);
						if (s.type == "success") {
							$('.reset_form').hide();
							$('.reset_link').removeClass('hide').show();
						} else {
							$button.attr('disabled', false);
							$button.find('i').removeClass('fa fa-sync fa-spin');
							UIToastr.init('error', 'Resend Email Verification Link', "Error processing request. Please try again later.");
						}
					},
					error: function (e) {
						alert('Error Processing your Request!!');
					}
				});
			}
		});

		$('.forget-form input').keypress(function (e) {
			if (e.which == 13) {
				if ($('.forget-form').validate().form()) {
					$('.forget-form').submit();
				}
				return false;
			}
		});

		jQuery('#forget-password').click(function () {
			jQuery('.login-form').hide();
			jQuery('.forget-form').show();
		});

		jQuery('.back-login').click(function () {
			jQuery('.login-form').show();
			jQuery('.forget-form').hide();
		});
	}

	var handleEmailVerification = function () {
		$('.resend_verification_link').click(function (e) {
			e.preventDefault();
			$('.form-actions .btn-submit', $(this)).attr('disabled', true);
			$('.form-actions .btn-submit i', $(this)).addClass('fa fa-sync fa-spin');

			var formData = new FormData();
			formData.append('action', 'resend_verification');
			formData.append('user_email', $(this).data('email'));
			formData.append('case', $(this).data('case'));

			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				async: true,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == "success") {
						$('.note-error').hide();
						$('.reset_link').removeClass('hide').show();
					} else {
						UIToastr.init('error', 'Resend Email Verification Link', "Error processing request. Please try again later.");
					}
				},
				error: function (e) {
					alert('Error Processing your Request!!');
				}
			});
			$('.form-actions .btn-submit', $(this)).attr('disabled', false);
			$('.form-actions .btn-submit i', $(this)).removeClass('fa-sync fa-spin');
		});
	}

	var handleReset = function () {
		$('.reset-password').validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				password: {
					required: true
				},
				rpassword: {
					equalTo: "#register_password"
				},
				// tnc: {
				//     required: true
				// }
			},

			messages: { // custom messages for radio buttons and checkboxes
				// tnc: {
				//     required: "Please accept TNC first."
				// }
			},

			invalidHandler: function (event, validator) { //display error alert on form submit   

			},

			highlight: function (element) { // hightlight error inputs
				$(element)
					.closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error');
				label.remove();
			},

			errorPlacement: function (error, element) {
				if (element.attr("name") == "tnc") { // insert checkbox errors after the container                  
					error.insertAfter($('#register_tnc_error'));
				} else if (element.closest('.input-icon').size() === 1) {
					error.insertAfter(element.closest('.input-icon'));
				} else {
					error.insertAfter(element);
				}
			},

			submitHandler: function (form) {
				// Create a new element input, this will be our hashed password field. 
				var p = document.createElement("input");

				password = form.password;

				// Add the new element to our form. 
				form.appendChild(p);
				p.name = "p";
				p.type = "hidden";
				p.value = hex_sha512(password.value);

				// Make sure the plaintext password doesn't get sent. 
				password.value = "";
				form.submit();
			}
		});

		// $('.register-form input').keypress(function (e) {
		// 	if (e.which == 13) {
		// 		if ($('.register-form').validate().form()) {
		// 			$('.register-form').submit();
		// 		}
		// 		return false;
		// 	}
		// });
	}

	var handlePasswordStrengthChecker = function () {
		var initialized = false;
		var input = $("#register_password");

		input.keydown(function () {
			if (initialized === false) {
				// set base options
				input.pwstrength({
					raisePower: 1.4,
					minChar: 8,
					verdicts: ["Weak", "Normal", "Medium", "Strong", "Very Strong"],
					scores: [17, 26, 40, 50, 60]
				});

				// add your own rule to calculate the password strength
				input.pwstrength("addRule", "demoRule", function (options, word, score) {
					return word.match(/[a-z].[0-9]/) && score;
				}, 10, true);

				// set as initialized 
				initialized = true;
			}
		});
	}

	var handleMobileVerification = function () {
		var form = $('#verify-mobile');
		var error1 = $('.alert-danger', form);

		$("[name='user_otp']").inputmask({
			"mask": "999999",
		});

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				// error1.show();
				App.scrollTo(error1, -200);
			},

			highlight: function (element) { // hightlight error inputs
				$(element)
					.closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			unhighlight: function (element) { // revert the change done by hightlight
				$(element)
					.closest('.form-group').removeClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				error.appendTo(element.parent("div").parent("div"));
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'verify_mobile');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: "POST",
					data: formData,
					contentType: false,
					processData: false,
					async: true,
					success: function (s) {
						s = $.parseJSON(s);
						if (s.type == "success" || s.type == "error") {
							UIToastr.init(s.type, 'Mobile Verification', s.msg);
							if (s.type == "error") {
								$(".resend_otp").removeClass('hide');
								$("#resend_otp").click(function () {
									$button = $(this);
									$button.attr('disabled', true);
									$button.find('i').addClass('fa-spin');

									$user_mobile = $('[name="user_mobile"]').val();
									var formData = new FormData();
									formData.append('action', 'resend_verification');
									formData.append('user_mobile', $user_mobile);
									formData.append('case', 'mobile_verification');

									$.ajax({
										url: "ajax_load.php?token=" + new Date().getTime(),
										cache: false,
										type: "POST",
										data: formData,
										contentType: false,
										processData: false,
										async: true,
										success: function (s) {
											sr = $.parseJSON(s);
											if (sr.type == "success")
												UIToastr.init(sr.type, 'OTP Resend', 'OTP resent successfully');
											else
												UIToastr.init(sr.type, 'OTP Resend', 'Unable to send request. Please try again later.');

											$button.find('i').removeClass('fa-spin');
											window.setTimeout(function () {
												$button.attr('disabled', false);
											}, 30 * 1000); // x second * 1000
										}
									});
								});
							} else {
								$('#verify-mobile').hide();
								$('.mobile_verified').removeClass('hide');
								$token = $('#token').val();
								window.setTimeout(function () {
									window.location.href = 'reset_password.php?token=' + $token;
								}, 5000);
							}
						} else {
							UIToastr.init('error', 'Mobile Verification', "Error processing request. Please try again later.");
						}
					},
				});
				$('.form-actions .btn-submit', $(form)).attr('disabled', false);
				$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	var handleDeviceVerification = function () {
		$("[name='access_otp']").inputmask({
			"mask": "999999",
			autoUnmask: true,
			removeMaskOnSubmit: true,
			oncomplete: function () {
				$(this).addClass('spinner').attr('readonly', true);

				var form = $('#verify-device');
				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'verify_device_otp');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				console.log(other_data);

				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: "POST",
					data: formData,
					contentType: false,
					processData: false,
					async: true,
					success: function (s) {
						s = $.parseJSON(s);
						if (s.type == "success" || s.type == "error") {
							UIToastr.init(s.type, 'Two Factor Authentication', s.msg);
							$('#access_otp').removeClass('spinner').attr('readonly', false).val("");
							if (s.type == "error") {
								$("#resend_otp").attr('disabled', false);
								$("#resend_otp").click(function () {
									$button = $(this);
									// $button.attr('disabled', true);
									$button.find('i').addClass('fa fa-sync fa-spin');

									var uid = $('#uid').val();
									var formData = new FormData();
									formData.append('action', 'resend_device_otp');
									formData.append('uid', uid);

									$.ajax({
										url: "ajax_load.php?token=" + new Date().getTime(),
										cache: false,
										type: "POST",
										data: formData,
										contentType: false,
										processData: false,
										async: true,
										success: function (s) {
											sr = $.parseJSON(s);
											if (sr.type == "success") {
												UIToastr.init(sr.type, 'Resend OTP', 'OTP resent successfully');
											} else
												UIToastr.init(sr.type, 'Resend OTP', 'Unable to send request. Please try again later.');

											$button.find('i').removeClass('fa fa-sync fa-spin');
											window.setTimeout(function () {
												$button.attr('disabled', false);
											}, 1000); // x second * 1000
										}
									});
								});
							} else {
								$('#access_otp').attr('disabled', true);
								$('.form-group').addClass('hide');
								$('.btn-submit').removeClass('hide').attr('disabled', false);
							}
						} else {
							UIToastr.init('error', 'Two Factor Authentication', "Error processing request. Please try again later.");
						}
					},
				});
			}
		});
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'tfa':
					handleDeviceVerification();
					break;

				default:
					handleLogin();
					handleForgetPassword();
					break;
			}
		},
		initVerify: function () {
			handleEmailVerification();
			handleMobileVerification();
		},
		initReset: function () {
			handleReset();
			handlePasswordStrengthChecker();
		}
	};

}();