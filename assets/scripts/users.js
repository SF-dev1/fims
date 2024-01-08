var Users = function () {

	// Submit Form
	var submitForm = function (formData, $type) {
		$ret = "";
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: false,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			success: function (s) {
				if (s != "") {
					$ret = $.parseJSON(s);

					if ($ret.redirectUrl) {
						window.location.href = $ret.redirectUrl;
					}
				}
			},
			error: function (e) {
				alert('Error Processing your Request!!');
			}
		});
		return $ret;
	};

	var users_handleTable = function () {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {};

		var table = $('#editable_users');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, "All"]], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			fixedHeader: {
				"headerOffset": 40
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_users&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "user_login",
					title: "Username",
					columnFilter: 'inputFilter'
				}, {
					data: "display_name",
					title: "Name",
					columnFilter: 'inputFilter'
				}, {
					data: "user_nickname",
					title: "Nickmame",
					columnFilter: 'inputFilter'
				}, {
					data: "user_email",
					title: "Email",
					columnFilter: 'inputFilter'
				}, {
					data: "user_mobile",
					title: "Mobile",
					columnFilter: 'inputFilter'
				}, {
					data: "user_role",
					title: "Role",
					columnFilter: 'selectFilter'
				}, {
					data: "user_capabilities",
					title: "Capabilities",
					columnFilter: ''
				}, {
					data: "user_status",
					title: "Status",
					columnFilter: ''
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			columnDefs: [
				{
					targets: [5],
					render: function (a, t, e, s) {
						return e.user_role.replace(/(^|_)./g, s => ' ' + s.slice(-1).toUpperCase());
					}
				}, {
					targets: [6],
					orderable: !1,
				}, {
					targets: [7],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-userid="' + e.userID + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "5%",
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="edit-capabilities btn btn-default btn-xs purple" title="Edit Capabilities" data-userid="' + e.userID + '" data-userrole="' + e.user_role + '" data-username="' + e.user_login + '" data-toggle="modal" data-target="#edit_user_role" ><i class="fa fa-user-edit"></i></a>&nbsp;' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function () {
				// Initialize checkbox for enable/disable user
				$(".bsSwitch").bootstrapSwitch();
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					editableTable(),
					userCapabilities_handleValidation();
			}
		});

		var loadFilters = function (t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, "YYYY-MM-DD").format('YYYY-MM-DD');
				var dateArray = d.split("-");
				var parsedDate = dateArray[2] + dateArray[1] + dateArray[0];
				return parsedDate;
			};

			var isRangeFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "rangeFilter")
						return true;
				return false;
			};

			var isDateFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "dateFilter")
						return true;

				return false;
			};
			// FILTERING
			var f = $('<tr class="filter"></tr>').appendTo($(oTable.table().header()));
			t.api().columns().every(function () {
				var s;
				switch (this.getColumnFilter()) {
					case 'inputFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input" data-col-index="' + this.index() + '"/>');
						break;

					case 'rangeFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="To" data-col-index="' + this.index() + '"/>');
						break;

					case 'dateFilter':
						s = $('\t\t\t\t\t\t\t<div class="input-daterange"><div class="input-group input-group-sm date date-picker margin-bottom-5">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="From" \t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t<div class="input-group input-group-sm date date-picker">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="To"\t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div></div>');
						break;

					case 'selectFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
							$(s).append('<option value="' + t + '">' + t + "</option>");
						});
						break;

					case 'statusFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
							$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
						});
						break;

					case 'actionFilter':
						var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
							r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
						$("<th>").append(i).append(r).appendTo(f);
						var sD, eD, minR, maxR;
						$(i).on("click", function (ev) {
							ev.preventDefault();
							var n = {};
							$(function () { }).find(".filter-input").each(function () {
								var t = $(this).data("col-index");
								n[t] ? n[t] += "|" + $(this).val() : n[t] = $(this).val()
							}),
								$.each(n, function (e, a) {
									if (isRangeFilter(e)) { // RANGE FILTER
										if (a == "")
											return;

										var range = a.split('|', 2);
										minR = range[0];
										maxR = range[1];

										var fR = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalRange = v === "" ? 0 : v;
												// if ((isNaN(minR) && isNaN(maxR)) || (evalRange >= minR && evalRange <= maxR)) {
												if ((isNaN(minR) && isNaN(maxR)) ||
													(isNaN(minR) && evalRange <= maxR) ||
													(minR <= evalRange && isNaN(maxR)) ||
													(minR <= evalRange && evalRange <= maxR)) {
													return true;
												}
												return false;
											});

										var r = "";
										for (var count = 0; count < fR.length; count++) {
											r += fR[count] + "|";
										}
										r = r.slice(0, -1);
										oTable.column(e).search("^" + r + "$", 1, 1, 1);
									} else if (isDateFilter(e)) { // DATE FILTER
										if (a == "")
											return;

										var dates = a.split('|', 2);
										sD = dates[0];
										eD = dates[1];
										var dS = parseDateValue(sD);
										var dE = parseDateValue(eD);

										var fD = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalDate = v === "" ? 0 : parseDateValue(v);
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE)) {
													return true;
												}
												return false;
											});

										var d = "";
										for (var count = 0; count < fD.length; count++) {
											d += fD[count] + "|";
										}

										d = d.slice(0, -1);
										oTable.column(e).search(d ? "^" + d + "$" : "^" + "-" + "$", 1, !1, 1);
									} else { // DEFAULT FILTER
										oTable.column(e).search(a || "", !1, !1);
									}
								}),
								oTable.table().draw();
							afterInitDataTable();
						}),
							$(r).on("click", function (ev) {
								ev.preventDefault(), $(f).find(".filter-input").each(function () {
									$(this).val(""), oTable.column($(this).data("col-index")).search("", !1, !1)
									$('.select2').val("").trigger('change');

								}), oTable.table().draw();
								afterInitDataTable();
							});
						break;

					default:
						s = "";
						break;
				}
				"Actions" !== this.title() && $(s).appendTo($("<th>").appendTo(f));
			});
			var n = function () {
				t.api().columns().every(function () {
					this.visible() ? $(f).find("th").eq(this.index()).show() : $(f).find("th").eq(this.index()).hide();
				});
			};
			n(), window.onresize = n;

			// DEFAULT FUNCTIONS
			if (jQuery().datepicker) {
				$('.date-picker').datepicker({
					format: 'yyyy-mm-dd',
					autoclose: true,
				});
			}

			if (jQuery().select2) {
				$('select.select2, .dataTables_length select').select2({
					placeholder: "Select",
					allowClear: true
				});
			}

			// FILTER TOGGLE
			$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
			$('.filter').hide();
			$('.btn-advance').bind('click', function () {
				$('.filter').toggle();
			});
		};

		var afterInitDataTable = function () {
			// Reload DataTable
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload(afterInitDataTable, false);
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		};

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
			}

			function editRow(oTable, nRow) {
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					if (cData[i] == null)
						cData[i] = "";

					aData.push(cData[i]);
				}

				jqTds[1].innerHTML = '<input type="text" class="form-control" value="' + aData[1] + '">';
				jqTds[2].innerHTML = '<input type="text" class="form-control" value="' + aData[2] + '">';
				jqTds[5].innerHTML = '<select id="roles_selectMenu" class="selection form-control"></select>';
				jqTds[8].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				$('#roles_selectMenu').append('<option value=""></option');
				for (i = 0; i < roles.length; i++) {
					var selected = "";
					if (aData[5] == roles[i]) {
						selected = "selected = 'selected'";
					}
					$('#roles_selectMenu').append('<option ' + selected + ' value=' + roles[i] + '>' + roles[i].replace(/(^|_)./g, s => ' ' + s.slice(-1).toUpperCase()) + '</option>');
				}

				// Initialize select2me
				$('.selection').select2({
					placeholder: "Select",
					allowClear: true
				});

				$('.bsSwitch').bootstrapSwitch('disabled', true);
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);
				var jqSelect = $('select', nRow);

				var formData = new FormData();
				formData.append('action', 'update_user');
				formData.append('userID', aData.userID);
				formData.append('display_name', jqInputs[0].value);
				formData.append('user_nickname', jqInputs[1].value);
				formData.append('user_role', $(jqSelect[0]).find('option:selected').text());

				aData.display_name = jqInputs[0].value;
				aData.user_nickname = jqInputs[1].value;
				aData.user_role = $(jqSelect[0]).find('option:selected').text();

				// oTable.row(nRow).data(aData); // SET NEW DATA TO TABLE

				var s = submitForm(formData, 'POST');
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'User Role Update', s.msg);
				} else {
					UIToastr.init('error', 'User Role Update', 'Error Processing Request. Please try again later.');
				}

				oTable.ajax.reload();

				$('.bsSwitch').bootstrapSwitch('disabled', false);
			}

			var nEditing = null;
			var nNew = false;

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_product');
					// formData.append('pid', aData.pid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Product', "Successfull deleted product");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
				}
			});

			table.on('click', '.cancel', function (e) {
				e.preventDefault();

				if (nNew) {
					oTable.fnDeleteRow(nEditing);
					nNew = false;
				} else {
					restoreRow(oTable, nEditing);
					nEditing = null;
				}
			});

			table.on('click', '.edit', function (e) {
				e.preventDefault();

				/* Get the row as a parent of the link that was clicked on */
				var nRow = $(this).parents('tr')[0];

				if (nEditing !== null && nEditing != nRow) {
					/* Currently editing - but not this row - restore the old before continuing to edit mode */
					restoreRow(oTable, nEditing);
					editRow(oTable, nRow);
					nEditing = nRow;
				} else if (nEditing == nRow && this.innerHTML == '<i class="fa fa-check"></i>') {
					/* Editing this row and want to save it */
					saveRow(oTable, nEditing);
					nEditing = null;
					// oTable.ajax.reload();
					oTable.draw();
					// alert("Updated! Do not forget to do some ajax to sync with backend :)");
				} else {
					/* No edit in progress - let's start one */
					editRow(oTable, nRow);
					nEditing = nRow;
				}
			});
		}
	};

	var users_handleBootstrapSwitch = function () {
		if (!$().bootstrapSwitch) {
			return;
		}

		$('#editable_users').on('switchChange.bootstrapSwitch', 'input[class="bsSwitch"]', function (e, data) {
			NProgress.start();
			var userID = $(this).data('userid');
			console.log(userID);

			if ($(this).is(":checked")) {
				var is_active = 1;
			} else {
				var is_active = 0;
			}
			$("[class='bsSwitch']").bootstrapSwitch('disabled', true);
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_user_status&userID=" + userID + "&is_active=" + is_active,
				success: function (s) {
					UIToastr.init(s.type, 'User Status Update', s.msg);
					$("[class='bsSwitch']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'User Status Update', 'Error Processing your Request!!! Please try again later.');
				}
			});
		});
	};

	var user_handleValidation = function () {
		var form = $('#add-user');
		var error1 = $('.alert-danger', form);

		$("[name='user_mobile']").inputmask({
			"mask": "9999999999",
		});

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				user_login: {
					required: true,
					remote: {
						url: "ajax_load.php?action=check_username_availability",
						type: "POST"
					}
				},
				user_email: {
					required: true,
					email: true,
					remote: {
						url: "ajax_load.php?action=check_email_availability",
						type: "POST"
					}
				},
				user_mobile: {
					required: true,
					digits: true,
					minlength: 10,
					maxlength: 10,
					remote: {
						url: "ajax_load.php?action=check_mobile_availability",
						type: "POST"
					}
				},
			},
			messages: {
				user_login: {
					remote: "Username already in use!"
				},
				user_email: {
					remote: "Email address already in use!",
					email: "This is not a valid email!",
				},
				user_mobile: {
					remote: "Mobile number already in use!",
				}
			},

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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error1.hide();
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_user');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Add User', s.msg);
					if (s.type == "success") {
						$("#add_user").modal('toggle');
						$(form)[0].reset();
						$(".reload").trigger('click');
					}
				} else {
					UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
				}
				$('.form-actions .btn-submit', $(form)).attr('disabled', false);
				$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});

		// $('#add_user').on('hidden.bs.modal', function () {
		// 	$(this)
		// 		.find("input,textarea,select")
		// 			.val('')
		// 			.end()
		// 		.find("input[type=checkbox], input[type=radio]")
		// 			.prop("checked", "")
		// 			.end();
		// }
	};

	var userCapabilities_handleValidation = function () {
		$user_id = "";
		$user_role = "";
		$user_name = "";
		$('.edit-capabilities').off('click').on('click', function () {
			$user_id = $(this).data('userid');
			$user_role = $(this).data('userrole');
			$user_name = $(this).data('username');
		});

		$('#edit_user_role').off('show.bs.modal').on('show.bs.modal', function (e) {
			$('[name="user_id"]').val($user_id);
			$('.user_name').val($user_name);
			$('.user_role').select2("val", $user_role);

			load_role_access_levels($user_id, $user_role);
			$('.user_role').on('change', function (e) {
				$user_role = $(this).val();
				$('.user_capabilities').html("");
				load_role_access_levels($user_id, $user_role);
			});

			function load_role_access_levels($user_id, $user_role) {
				var formData = 'action=get_user_capabilities&user_id=' + $user_id + '&user_role=' + $user_role;
				var s = submitForm(formData, "GET");
				$('.user_capabilities').html(s);
				App.initUniform();
			}
		});

		var form = $('#update-user-access');
		var error1 = $('.alert-danger', form);

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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error1.hide();

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");
				if (s.type == 'success') {
					$('#edit_user_role').modal('hide');
					$('#reload-users').trigger('click');
					UIToastr.init(s.type, 'User Permission Update', s.msg);
				} else {
					UIToastr.init('error', 'User Permission Update', "Error processing request. Please try again later.");
				}
			}
		});
	}

	var userRole_handleAccordian = function () {
		$("#access_role_accordion").html("<center>Processing...</center>");
		var formData = 'action=get_roles';
		var s = submitForm(formData, "GET");
		$("#access_role_accordion").html(s.content);
		App.initUniform();
		userRole_handleValidation();
		userRole_handleForm();

		$('.remove-role').click(function () {
			if (confirm('Are you sure you want to delete this role?')) {
				role_name = $(this).data('role');

				var formData = new FormData();
				formData.append('action', 'delete_role');
				formData.append('role_name', role_name);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Role Delete', s.msg);
				} else {
					UIToastr.init('error', 'Role Delete', "Error processing request. Please try again later.");
				}
			}
		});
	};

	var userRole_handleValidation = function () {
		var form = $('#add-role');
		var error1 = $('.alert-danger', form);

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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error1.hide();
				var role_name = $("#role_name").val();

				var formData = new FormData();
				formData.append('action', 'add_role');
				formData.append('role_name', role_name);

				var s = submitForm(formData, "POST");
				if (s.type == 'success') {
					$('#add_role').modal('hide');
					userRole_handleAccordian();
				} else {
					error1.text('Error Processing your Request!! ' + s.message).show();
				}
			}
		});
	};

	var userRole_handleForm = function () {
		$('form[id^="user_role_access_"]').submit('click', function (e) {
			e.preventDefault();
			$('.form-actions .btn-submit', $(this)).attr('disabled', true);
			$('.form-actions .btn-submit i', $(this)).addClass('fa fa-sync fa-spin');

			var other_data = $(this).serializeArray();
			var access_role = $(this).data("role");
			var formData = new FormData();
			formData.append('action', 'update_access_role');
			formData.append('access_role', access_role);
			$.each(other_data, function (key, input) {
				formData.append(input.name, input.value);
			});

			var s = submitForm(formData, "POST");
			if (s.type == "success" || s.type == "error") {
				UIToastr.init(s.type, 'Role Permission Update', s.msg);
			} else {
				UIToastr.init('error', 'Role Permission Update', "Error processing request. Please try again later.");
			}
			$('.form-actions .btn-submit', $(this)).attr('disabled', false);
			$('.form-actions .btn-submit i', $(this)).removeClass('fa-sync fa-spin');
		});
	};

	var userProfile_handleValidation = function () {
		var form = $('#update-profile');
		var error1 = $('.alert-danger', form);

		$("[name='user_mobile']").inputmask({
			"mask": "9999999999",
		});

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				display_name: {
					required: true,
				},
				nick_name: {
					required: true,
				},
				/*user_email: {
					required: true,
					email: true,
					remote: {
						param: {
							url: "users/ajax_load.php?action=check_email_availability",
							type: "POST",
						},
						depends: function(element) {
							return ($(element).val() !== $('#current_user_email').val());
						}
					},
				},
				user_mobile: {
					required: true,
					digits: true,
					minlength: 10,
					maxlength: 10,
					remote: {
						param: {
							url: "users/ajax_load.php?action=check_mobile_availability",
							type: "POST",
						},
						depends: function(element) {
							return ($(element).val() !== $('#current_user_mobile').val());
						}
					},
				}*/
			},
			messages: {
				/*user_email: {
					remote: "Email address already in use!",
					email: "This is not a valid email!",
				},
				user_mobile: {
					remote: "Mobile number already in use!",
					email: "This is not a valid mobile number!",
				}*/
			},

			invalidHandler: function (event, validator) { //display error alert on form submit
				// error1.show();
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
				error.insertAfter(element.parent('div').parent('div').find('.control-label'));
			},

			submitHandler: function (form) {
				error1.hide();
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'update_profile');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'User Profile Update', s.msg);
				} else {
					UIToastr.init('error', 'User Profile Update', "Error processing request. Please try again later.");
				}
				$('.form-actions .btn-submit', $(form)).attr('disabled', false);
				$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	var userProfileMobile_handleValidation = function () {
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
				error.appendTo(element.parent("div"));
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

				var s = submitForm(formData, "POST");
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

							var sr = submitForm(formData, "POST");
							if (sr.type == "success")
								UIToastr.init(sr.type, 'OTP Resend', 'OTP resent successfully');
							else
								UIToastr.init(sr.type, 'OTP Resend', 'Unable to send request. Please try again later.');

							window.setTimeout(function () {
								$button.attr('disabled', true);
								$button.find('i').removeClass('fa-spin');
							}, 500);
						});
					} else {
						$('#mobile_verify').modal('hide');
						location.reload();
					}
				} else {
					UIToastr.init('error', 'Mobile Verification', "Error processing request. Please try again later.");
				}
				$('.form-actions .btn-submit', $(form)).attr('disabled', false);
				$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	var userPassword_handleValidation = function () {
		var form = $('#update-password');
		var error1 = $('.alert-danger', form);

		jQuery.validator.addMethod("newCurrentSame", function (value, element) {
			return this.optional(element) || value !== $('[name="current_pass"]').val();
		}, "New password and current password cannot be same");

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				current_pass: {
					required: true,
				},
				user_pass: {
					required: true,
					newCurrentSame: true,
				},
				ruser_pass: {
					equalTo: "#user_pass"
				},
			},

			invalidHandler: function (event, validator) { //display error alert on form submit
				// error1.show();
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
				error.insertAfter(element.parent('div').find('.control-label'));
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-submit', $(form)).attr('disabled', true);
				$('.btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();

				var formData = new FormData();
				formData.append('action', 'update_password');
				$.each(other_data, function (key, input) {
					if (input.value) {
						if (input.name == "user_pass" || input.name == "current_pass" || input.name == "ruser_pass")
							formData.append(input.name, hex_sha512(input.value));
						else
							formData.append(input.name, input.value);
					}
				});

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'User Password Update', s.msg);
					if (s.type == "success") {
						$(form)[0].reset();
					}
				} else {
					UIToastr.init('error', 'User Password Update', "Error processing request. Please try again later.");
				}
				$('.btn-submit', $(form)).attr('disabled', false);
				$('.btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	var handlePasswordStrengthChecker = function () {
		var initialized = false;
		var input = $("#user_pass");

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
	};

	var userSettings_handleValidation = function () {
		var form = $('#update-settings');
		var error1 = $('.alert-danger', form);

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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-submit', $(form)).attr('disabled', true);
				$('.btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'update_settings');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Display Settings', s.msg);
				} else {
					UIToastr.init('error', 'Display Settings', "Error processing request. Please try again later.");
				}
				$('.btn-submit', $(form)).attr('disabled', false);
				$('.btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	// API
	var users_api_handleTable = function () {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {};

		var table = $('#editable_api_users');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, "All"]], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			fixedHeader: {
				"headerOffset": 40
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_api_users&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "user_host",
					title: "Host Name",
					columnFilter: 'inputFilter'
				}, {
					data: "user_key",
					title: "User Key",
					columnFilter: 'inputFilter'
				}, {
					data: "user_secret",
					title: "User Secret",
					columnFilter: 'inputFilter'
				}, {
					data: "user_status",
					title: "Status",
					columnFilter: ''
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			columnDefs: [
				{
					targets: [3],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-userid="' + e.user_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "5%",
					render: function (a, t, e, s) {
						$return = '<a class="delete btn btn-default btn-xs purple" href="javascript:void()" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function () {
				// Initialize checkbox for enable/disable user
				$(".bsSwitch").bootstrapSwitch();
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					userCapabilities_handleValidation();
			}
		});

		var loadFilters = function (t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, "YYYY-MM-DD").format('YYYY-MM-DD');
				var dateArray = d.split("-");
				var parsedDate = dateArray[2] + dateArray[1] + dateArray[0];
				return parsedDate;
			};

			var isRangeFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "rangeFilter")
						return true;
				return false;
			};

			var isDateFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "dateFilter")
						return true;

				return false;
			};
			// FILTERING
			var f = $('<tr class="filter"></tr>').appendTo($(oTable.table().header()));
			t.api().columns().every(function () {
				var s;
				switch (this.getColumnFilter()) {
					case 'inputFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input" data-col-index="' + this.index() + '"/>');
						break;

					case 'rangeFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="To" data-col-index="' + this.index() + '"/>');
						break;

					case 'dateFilter':
						s = $('\t\t\t\t\t\t\t<div class="input-daterange"><div class="input-group input-group-sm date date-picker margin-bottom-5">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="From" \t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t<div class="input-group input-group-sm date date-picker">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="To"\t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div></div>');
						break;

					case 'selectFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
							$(s).append('<option value="' + t + '">' + t + "</option>");
						});
						break;

					case 'statusFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
							$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
						});
						break;

					case 'actionFilter':
						var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
							r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
						$("<th>").append(i).append(r).appendTo(f);
						var sD, eD, minR, maxR;
						$(i).on("click", function (ev) {
							ev.preventDefault();
							var n = {};
							$(function () { }).find(".filter-input").each(function () {
								var t = $(this).data("col-index");
								n[t] ? n[t] += "|" + $(this).val() : n[t] = $(this).val()
							}),
								$.each(n, function (e, a) {
									if (isRangeFilter(e)) { // RANGE FILTER
										if (a == "")
											return;

										var range = a.split('|', 2);
										minR = range[0];
										maxR = range[1];

										var fR = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalRange = v === "" ? 0 : v;
												// if ((isNaN(minR) && isNaN(maxR)) || (evalRange >= minR && evalRange <= maxR)) {
												if ((isNaN(minR) && isNaN(maxR)) ||
													(isNaN(minR) && evalRange <= maxR) ||
													(minR <= evalRange && isNaN(maxR)) ||
													(minR <= evalRange && evalRange <= maxR)) {
													return true;
												}
												return false;
											});

										var r = "";
										for (var count = 0; count < fR.length; count++) {
											r += fR[count] + "|";
										}
										r = r.slice(0, -1);
										oTable.column(e).search("^" + r + "$", 1, 1, 1);
									} else if (isDateFilter(e)) { // DATE FILTER
										if (a == "")
											return;

										var dates = a.split('|', 2);
										sD = dates[0];
										eD = dates[1];
										var dS = parseDateValue(sD);
										var dE = parseDateValue(eD);

										var fD = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalDate = v === "" ? 0 : parseDateValue(v);
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE)) {
													return true;
												}
												return false;
											});

										var d = "";
										for (var count = 0; count < fD.length; count++) {
											d += fD[count] + "|";
										}

										d = d.slice(0, -1);
										oTable.column(e).search(d ? "^" + d + "$" : "^" + "-" + "$", 1, !1, 1);
									} else { // DEFAULT FILTER
										oTable.column(e).search(a || "", !1, !1);
									}
								}),
								oTable.table().draw();
							afterInitDataTable();
						}),
							$(r).on("click", function (ev) {
								ev.preventDefault(), $(f).find(".filter-input").each(function () {
									$(this).val(""), oTable.column($(this).data("col-index")).search("", !1, !1)
									$('.select2').val("").trigger('change');

								}), oTable.table().draw();
								afterInitDataTable();
							});
						break;

					default:
						s = "";
						break;
				}
				"Actions" !== this.title() && $(s).appendTo($("<th>").appendTo(f));
			});
			var n = function () {
				t.api().columns().every(function () {
					this.visible() ? $(f).find("th").eq(this.index()).show() : $(f).find("th").eq(this.index()).hide();
				});
			};
			n(), window.onresize = n;

			// DEFAULT FUNCTIONS
			if (jQuery().datepicker) {
				$('.date-picker').datepicker({
					format: 'yyyy-mm-dd',
					autoclose: true,
				});
			}

			if (jQuery().select2) {
				$('select.select2, .dataTables_length select').select2({
					placeholder: "Select",
					allowClear: true
				});
			}

			// FILTER TOGGLE
			$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
			$('.filter').hide();
			$('.btn-advance').bind('click', function () {
				$('.filter').toggle();
			});
		};

		var afterInitDataTable = function () {
			// Reload DataTable
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload(afterInitDataTable, false);
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		};
	};

	var users_api_handleValidation = function () {
		var form = $('#add-api-user');
		var error1 = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				user_host: {
					required: true,
					remote: {
						url: "ajax_load.php?action=check_userhost_availability",
						type: "POST"
					}
				},
			},
			messages: {
				user_host: {
					remote: "Userhost already in use!"
				}
			},

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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error1.hide();
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_api_user');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Add API User', s.msg);
					if (s.type == "success") {
						$("#add_api_user").modal('toggle');
						$(form)[0].reset();
						$(".reload").trigger('click');
					}
				} else {
					UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
				}
				$('.form-actions .btn-submit', $(form)).attr('disabled', false);
				$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
			}
		});
	};

	var users_api_handleBootstrapSwitch = function () {
		if (!$().bootstrapSwitch) {
			return;
		}

		$('#editable_api_users').on('switchChange.bootstrapSwitch', 'input[class="bsSwitch"]', function (e, data) {
			NProgress.start();
			var userID = $(this).data('userid');
			console.log(userID);

			if ($(this).is(":checked")) {
				var is_active = 1;
			} else {
				var is_active = 0;
			}
			$("[class='bsSwitch']").bootstrapSwitch('disabled', true);
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_api_user_status&user_id=" + userID + "&is_active=" + is_active,
				success: function (s) {
					console.log(s);
					UIToastr.init(s.type, 'User Status Update', s.msg);
					$("[class='bsSwitch']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'User Status Update', 'Error Processing your Request!!! Please try again later.');
				}
			});
		});
	};

	return {
		//main function to initiate the module
		init: function ($type) {
			if ($type == 'user') {
				users_handleTable();
				user_handleValidation();
				users_handleBootstrapSwitch();
			} else if ($type == "roles") {
				userRole_handleAccordian();
			} else if ($type == "profile") {
				userProfile_handleValidation();
				userProfileMobile_handleValidation();
				userPassword_handleValidation();
				handlePasswordStrengthChecker();
				userSettings_handleValidation();
			} else if ($type == 'user_api') {
				users_api_handleTable();
				users_api_handleValidation();
				users_api_handleBootstrapSwitch();
			}
		}
	};
}();