jQuery(document).ready(function ($) {
	initTable();
});

var initTable = function () {

	if (!jQuery().dataTable) {
		return;
	}

	console.log('initTable');
	$type = "active";
	incidents_handleTable($type);

	// Order Status Tabs
	$(".incident_status a").click(function () {
		if ($(this).parent().attr('class') == "active") {
			return;
		}
		$tab = $(this).attr('href');
		$type = $tab.substr($tab.indexOf("_") + 1);
		console.log($type);

		var tables = $.fn.dataTable.fnTables(true);
		$(tables).each(function () {
			$(this).dataTable().fnDestroy();
		});

		incidents_handleTable($type);
		// REQUESTS
		// eval("request_"+$type+"()");
		// }
	});

	var a_options = "<option value=''></option>";
	var accountMenu = $('.account_id');

	// Append Marketplace and Account details 
	$.each(accounts, function (account_k, account) {
		if (account_k == 'flipkart') {
			$.each(account, function (k, v) {
				a_options += "<option value=" + v.account_id + ">" + v.account_name + "</option>";
			});
		}
	});
	accountMenu.empty().append(a_options);
	$("select.account_id").select2({
		placeholder: "Select Account",
		allowClear: true,
		// multiple: true,
	});
};

var incidents_handleTable = function ($type) {

	if (jQuery().dataTable) {
		var tables = $.fn.dataTable.fnTables(true);
		$(tables).each(function () {
			$(this).dataTable().fnDestroy();
		});
	}

	if ($type == 'closed') {
		$table_format = '<"row" <"col-md-4 col-sm-12" l><"col-md-8 col-sm-12 dataTables_length" <"#filters.navbar-right filter-panel"> <"#reload.navbar-right"> f>>rt<"table-scrollable" <"col-md-5 col-sm-12" i><"col-md-7 col-sm-12" p>>';
	} else {
		$table_format = '<"row" <"col-md-4 col-sm-12" l><"col-md-8 col-sm-12 dataTables_length" <"#filters.navbar-right filter-panel"> <"#reload.navbar-right"> <"#add_incident.navbar-right"> f>>rt<"table-scrollable" <"col-md-5 col-sm-12" i><"col-md-7 col-sm-12" p>>';
	}

	console.log('incidents_' + $type);
	var table = $('#incidents_' + $type);
	var oTable = table.DataTable({
		"lengthMenu": [
			[20, 50, 100, 200, 500, -1],
			[20, 50, 100, 200, 500, "All"] // change per page values here
		],
		// set the initial value
		// "autoWidth": false,
		"pageLength": 20,
		"pagingType": "bootstrap_full_number",
		"language": {
			"emptyTable": "No Reports Found",
			"lengthMenu": "  _MENU_ records per page",
			"paginate": {
				"previous": "Prev",
				"next": "Next",
				"last": "Last",
				"first": "First"
			}
		},
		"scrollX": true,
		// "sort": false,
		// "ordering": true,
		"destroy": true,
		"processing": true,
		// "deferRender": true,
		"columnDefs": [
			{
				"orderable": false,
				"targets": '_all'
			}
		],
		"fixedHeader": {
			"headerOffset": 40
		},
		"sDom": $table_format,
		"ajax": {
			url: "ajax_load.php?action=get_" + $type + "_incidents&token=" + new Date().getTime(),
			type: "GET",
			cache: false,
		},
		drawCallback: function () {
			handleTagsInput();
			handleActions();
			$('.dataTables_paginate a').off('click').on('click', function () {
				App.scrollTop();
			});
		},
		initComplete: function () {
			// this.api().columns().every( function () {
			// 	var column = this;
			// 	var select = $('<select class="select2me form-control input-small"><option value=""></option></select>')
			// 		.appendTo( $(column.header()) )
			// 		.on( 'change', function () {
			// 			var val = $.fn.dataTable.util.escapeRegex(
			// 				$(this).val()
			// 			);

			// 			column
			// 				.search( val ? '^'+val+'$' : '', true, false )
			// 				.draw();
			// 		} );

			// 	column.data().unique().sort().each( function ( d, j ) {
			// 		select.append( '<option value="'+d+'">'+d+'</option>' )
			// 	} );
			// } );
		}
	});

	$("#add_incident").html('<a href="#" title="Add Incident" data-target="#add-incident" id="add-incident" role="button" class="btn btn-default add-incident" data-toggle="modal"><i class="fa fa-plus"></i></a>');
	$("#reload").html('<a href="#" title="Reload" class="btn btn-default reload"><i class="fa fa-sync"></i></a>');
	$("#reload").on('click', function (e) { // off on to restrict multiple occurence of events registration
		e.preventDefault();
		oTable.clear().draw();
		oTable.ajax.reload();
		console.log('reload');
	});

	if ($type == "active") {
		oTable.order([[6, 'asc'], [1, 'asc']]).draw();
		console.log("active order");
	}

	// var tableWrapper = jQuery('#dashboard_'+$type+'_wrapper');

	var filters = {
		'account': {
			'type': 'select',
			'label': 'Account',
			'class': 'account_name',
			'search_index': 0, // index of the dataTable to seach for
			'options': accounts.flipkart,
		},
		// 'payment_type' : {
		// 	'type' : 'select',
		// 	'label': 'Payments Type',
		// 	'class' : 'payment_type',
		// 	'search_index' : 2, // index of the dataTable to seach for
		// 	'options' : [
		// 		'Settled Transactions',
		// 		'Unsettled Tranasctions',
		// 	]
		// },
		// 'status' : {
		// 	'type' : 'select',
		// 	'label': 'Status',
		// 	'class' : 'status',
		// 	'search_index' : 6, // index of the dataTable to seach for
		// 	'options' : [
		// 		'Queued',
		// 		'Generated',
		// 		'Completed',
		// 	]
		// }
	}

	handleFilters(filters, oTable);
	addIncidentValidate.init("#add_new_incident");
};

var addIncidentValidate = function () {
	return {
		//main function to initiate the module
		init: function (id) {

			var form = $(id);
			var error = $('.alert-danger', form);
			error.hide();

			form.validate({
				debug: true,
				errorElement: 'span', //default input error message container
				errorClass: 'help-inline', // default input error message class
				focusInvalid: false, // do not focus the last invalid input
				ignore: "",
				rules: {
					account_id: {
						required: true
					},
					incident_type: {
						required: true
					},
					incident_id: {
						minlength: 21,
						maxlength: 21,
						required: true
					},
					subject: {
						required: true
					}
				},
				messages: { // custom messages for radio buttons and checkboxes
					incident_id: {
						required: "Please enter ticket reference number."
					},
				},

				errorPlacement: function (error, element) { // render error placement for each input type
					// if (element.attr("name").indexOf("product_condition") >= 0) { // for uniform radio buttons, insert the after the given container
					// 	$this_element = element[Object.keys(element)[0]];
					// 	$div_id = $($this_element).attr("class");
					// 	error.addClass("no-left-padding").insertAfter("#"+$div_id+ " .form_product_condition_error");
					// } else if(element.attr("name") == "claim"){
					// 	error.addClass("no-left-padding").insertAfter(id+ " .form_claim_error");
					// } else {
					// error.insertAfter(element); // for other inputs, just perform default behavoir
					// }
				},

				invalidHandler: function (event, validator) { //display error alert on form submit   
					error.show();
				},

				highlight: function (element) { // hightlight error inputs
					$(element)
						.closest('.form-group').removeClass('success').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change dony by hightlight
					$(element)
						.closest('.form-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					if (label.attr("for") == "product_condition" || label.attr("for") == "claim") { // for checkboxes and radio buttons, no need to show OK icon
						label
							.closest('.form-group').removeClass('has-error').addClass('success');
						label.remove(); // remove error label here
					} else { // display success icon for other inputs
						label
							.closest('.form-group').removeClass('has-error').addClass('success'); // set success class to the control group
					}
				},

				submitHandler: function (form) {
					error.hide();
					$account_id = $('#account_id option:selected', form).val();
					$incident_type = $('#incident_type option:selected', form).val();
					$incident_id = $(id + " input[name='incident_id']").val();
					$subject = $(id + " input[name='subject']").val();
					$base_incident = $(id + " input[name='base_incident']").val();
					$order_item_ids = $(id + " input[name='order_item_ids']").val();

					$(id + ' .form-actions .btn-success').attr('disabled', true);
					$(id + ' .form-actions i').addClass('icon-refresh');
					$(id + ' .re_error').text("");

					$.ajax({
						url: "ajax_load.php?token=" + new Date().getTime(),
						cache: false,
						type: 'POST',
						data: "action=add_new_incident&account_id=" + $account_id + "&incident_type=" + $incident_type + "&incident_id=" + $incident_id + "&subject=" + $subject + "&base_incident=" + $base_incident + "&order_item_ids=" + $order_item_ids,
						success: function (s) {
							console.log(s);
							s = $.parseJSON(s);
							$(id + ' btn-success').attr('disabled', false);
							if (s.type == 'success') {
								// save and close
								setTimeout(function () {
									$(id).closest('div.modal').modal('hide');
									$(id + ' .form-actions .btn-success').attr('disabled', false);
									$(id + ' .form-actions i').removeClass('icon-refresh');
								}, 500);
								$(form)[0].reset();
								$('#account_id', form).select2("val", "");
								$('#incident_type', form).select2("val", "");
								$(".reload").trigger("click");
							}
							if (s.type == 'info') {
								$(id + ' .form-actions .btn-success').attr('disabled', false);
								$(id + ' .form-actions i').removeClass('icon-refresh');
								$(id + ' .re_error').text('Incident ID already exists');
							}
							if (s.type == 'error') {
								$(id + ' .form-actions .btn-success').attr('disabled', false);
								$(id + ' .form-actions i').removeClass('icon-refresh');
								$(id + ' .re_error').text('Unable to process request. Please retry');
							}
							UIToastr.init(s.type, 'Incident Added', s.message);
						},
						error: function () {
							// NProgress.done(true);
							alert('Error Processing your Request!!');
						}
					});
				}
			});

			//apply validation on chosen dropdown value change, this only needed for chosen dropdown integration.
			$('.chosen, .chosen-with-diselect', form).change(function () {
				form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
			});
		}
	};
}();

var handleFilters = function (filters, table) {
	var panel = $('.filter-panel');

	jQuery('.dataTables_wrapper #filters').append('<div class="toggler"><i class="fa fa-filter"></i></div><div class="filter-options"></div>');

	var filter_content = "";
	$.each(filters, function (index, value) {
		filter_content += '<div class="filter-option ' + value.class + '"><span>' + value.label + ': </span>';
		if (value.type == "select") {
			var options = '<option value="">';
			$.each(value.options, function (op_index, op_value) {
				if (index == 'account') {
					op_value = op_value.account_name;
				}
				options += '<option value=' + op_value + '>' + op_value + '</option>';
			});
			filter_content += "<select class='form-control input-inline input-small'>" + options + "</select></div>";
		}
	});

	jQuery('.filter-options').append(filter_content);

	if (jQuery().select2) {
		$('.filter-options select').select2({
			placeholder: "Select",
			allowClear: true
		});
	}

	// Bind to table for search
	$.each(filters, function (index, value) {
		if (value.type == "select") {
			jQuery('.dataTables_wrapper .' + value.class + ' select').bind('change', function () {
				table.columns(value.search_index).search(jQuery(this).val()).draw();
				// table.fnDraw();
			});
		}
	});

	$('.toggler').click(function () {
		$(this).toggleClass("open");
		$('.filter-panel > .filter-options').toggle();
	});
};

var handleTagsInput = function () {
	$(".btn-tags").off('click').on('click', function () {
		$this = $(this);
		$referenceId = $this.data('referenceid');
		$incidentId = $this.data('incidentid');
		if (confirm('Mark resolve reference ID ' + $referenceId + ' for Incident Id ' + $incidentId)) {
			$this.attr('disabled', true);
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=update_incident_order_status&referenceId=" + $referenceId + "&incidentId=" + $incidentId,
				// contentType: false,
				// processData: false,
				async: true,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == 'success') {
						$this.find("i").remove();
						$this.removeClass('btn-default').addClass('btn-success');
						UIToastr.init('success', 'Update on Incident', s.message);
					} else {
						$this.attr('disabled', false);
						error1.text('Error Processing your Request!! ' + s.message).show();
						UIToastr.init('error', 'Update on Incident', 'Error Processing your Request!! ' + s.message);
					}
				},
				error: function () {
					$this.attr('disabled', false);
					UIToastr.init('error', 'Update on Incident', 'Error Processing your Request. Please try again later!!!');
				}
			});
		}

	});
}

var handleActions = function () {
	$(".btn-resolve").off('click').on('click', function () {
		$this = $(this);
		$incidentId = $this.data('incidentid');
		$referenceIds = $this.data('referenceids');
		if (confirm('Mark resolve Incident with Id ' + $incidentId)) {
			$this.attr('disabled', true);
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=update_incident_status&incidentId=" + $incidentId,
				// contentType: false,
				// processData: false,
				async: true,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == 'success') {
						$referenceIds += ",";
						var odr_array = $referenceIds.split(',');
						$.each(odr_array, function (i, referenceId) {
							$tag = $(".btn-tags[data-referenceid='" + referenceId + "']");
							$tag.find("i").remove();
							$tag.attr('disabled', true);
							$tag.removeClass('btn-default').addClass('btn-success');
							$('#reload').trigger('click');
						});
						UIToastr.init('success', 'Incident Update', s.message);

					} else {
						$this.attr('disabled', false);
						error1.text('Error Processing your Request!! ' + s.message).show();
						UIToastr.init('error', 'Incident Update', 'Error Processing your Request!! ' + s.message);
					}
				},
				error: function () {
					$this.attr('disabled', false);
					UIToastr.init('error', 'Incident Update', 'Error Processing your Request. Please try again later!!!');
				}
			});
		}
	});

	$(".btn-comments").off('click').on('click', function () {
	});

	$(".btn-merge").off('click').on('click', function () {
		$this = $(this);
		$incidentId = $this.data('incidentid');
		$referenceIds = $this.data('referenceids');
		$('#confirm_merge_to_incident').modal('show');
		$(".confirm_merge_incident_modal").off('click').on('click', function (e) { // off on to restrict multiple occurence of events registration
			$confirmThis = $(this);
			$mergeToIncident = $('#merge_to_incident').val();
			if ($mergeToIncident == "" || $mergeToIncident == $incidentId) {
				$('#merge_to_incident').closest('.form-group').addClass('has-error');
				return;
			}

			$confirmThis.attr('disabled', true);
			$confirmThis.find('i').addClass('fa fa-sync fa-spin');
			$('#merge_to_incident').closest('.form-group').removeClass('has-error');
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=merge_incidents&incidentId=" + $incidentId + "&mergeToIncidentId=" + $mergeToIncident,
				async: true,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == 'success') {
						UIToastr.init(s.type, 'Merge Incident', s.message);
						$('#confirm_merge_to_incident').modal('hide');
						$('#reload').trigger('click');
					} else {
						UIToastr.init(s.type, 'Merge Incident', 'Error Processing your Request!! ' + s.message);
					}
					$confirmThis.attr('disabled', false);
					$confirmThis.find('i').removeClass('fa fa-sync fa-spin');
				},
				error: function () {
					$confirmThis.attr('disabled', false);
					$confirmThis.find('i').removeClass('fa fa-sync fa-spin');
					UIToastr.init(s.type, 'Merge Incident', 'Error Processing your Request. Please try again later!!!');
				}
			});
		});
	});
}