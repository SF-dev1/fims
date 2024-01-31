"use_strict";

var Tasks = function () {
	var currentRequest = null;

	var submitForm = function (formData, $type) {
		var currentReq = null;
		var $ret = "";
		currentReq = $.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: true,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			showLoader: true,
			beforeSend: function () {
				if (currentRequest != null) {
					currentRequest.abort();
				} else {
					currentRequest = currentReq;
				}
			},
			success: function (s) {
				if (s != "") {
					$ret = $.parseJSON(s);

					if ($ret.redirectUrl) {
						window.location.href = $ret.redirectUrl;
					}
				}
			},
			error: function (e) {
				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
			}
		});
		return $ret;
	};

	function myTask_handleTable_v1() {
		var table = $('#myTasks');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=getTasks&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [{
				data: "id",
				title: "#",
			}, {
				data: "title",
				title: "Task Title",
			}, {
				data: "createdBy",
				title: "status",
			}, {
				data: "rejectReason",
				title: "Reject Reason",
			}, {
				data: "status",
				title: "Status",
			},],
			order: [
				[0, 'asc']
			],
			columnDefs: [],
			initComplete: function () { }
		});
	}

	function myTask_handleTable() {
		var tableBody = $('#taskBody');
		var formData = new FormData();

		formData.append("action", "getTasks");
		var response = submitForm(formData, "POST");
		console.log(response.data);
		var content = "";
		if (response.type == "success") {
			for (let i = 0; i < response.data.length; i++) {
				var status = "";
				var rejectReason = "";
				if (response.data[i].status == "1") {
					status = "<span class='badge badge-warning'> Pending </span>";
				} else if (response.data[i].status == "2") {
					status = "<span class='badge badge-danger'> Rejected </span>";
				} else {
					status = "<span class='badge badge-success'> Completed </span>";
				}

				if (response.data[i].rejectReason == null) {
					rejectReason = "<span class='badge badge-info'> Not Rejected </span>";
				}
				content += "<tr id='row_" + response.data[i].id + "'>";
				content += "<td>" + (i + 1) + "</td>";
				content += "<td>" + response.data[i].title + "</td>";
				content += "<td>CTN" + String(response.data[i].ctnId).padStart(9, '0') + "</td>";
				content += "<td>" + ((response.data[i].name != null) ? response.data[i].name : "The System - BY Jay Chauhan") + "</td>";
				content += "<td>" + rejectReason + "</td>";
				content += "<td>" + status + "</td>";
				content += "<td><button id='actionBtn' class='btn btn-sm btn-success' data-taskid='" + response.data[i].id + "'>Mark Done</button></td>";
				content += "</tr>";
			}
			tableBody.html(content);
		}

		$("#actionBtn").click(function () {
			var action = confirm("Are you sure you want to proceed?\nThis task will marked as completed by you!");

			if (action) {
				var button = $(this);
				var taskId = button.data("taskid");

				var formData = new FormData();
				formData.append("action", "markDone");
				formData.append("taskId", taskId);

				var response = submitForm(formData, "POST");
				if (response.type == "error") {
					UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
				} else {
					UIToastr.init('success', 'Congrats!', response.message);
					$("#row_" + taskId).remove();
				}
			}
		});
	}

	function approval_handleTable() {
		var tableBody = $('#approvalsBody');
		var formData = new FormData();

		formData.append("action", "getApprovals");
		var response = submitForm(formData, "POST");
		// console.log(response.data);
		var content = "";
		if (response.type == "success") {
			for (let i = 0; i < response.data.length; i++) {
				var status = "";
				var rejectReason = "";
				if (response.data[i].status == "0") {
					status = "<span class='badge badge-warning'> Pending </span>";
				} else if (response.data[i].status == "2") {
					status = "<span class='badge badge-danger'> Rejected </span>";
				} else {
					status = "<span class='badge badge-success'> Completed </span>";
				}

				// Convert the date string to a Date object
				var dateObject = new Date(response.data[i].createdDate);
				var formattedDateTime = dateObject.toLocaleString('en-US', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });

				rejectReason = (response.data[i].rejectReason == null) ? "<span class='badge badge-info'> Not Rejected </span>" : response.data[i].rejectReason;
				content += "<tr id='row_" + response.data[i].id + "'>";
				content += "<td>" + (i + 1) + "</td>";
				content += "<td>" + response.data[i].title + "</td>";
				content += "<td>CTN" + String(response.data[i].ctnId).padStart(9, '0') + "</td>";
				content += "<td>" + response.data[i].count + "</td>";
				content += "<td>" + response.data[i].expectedLocation + "</td>";
				content += "<td>" + ((response.data[i].name != null) ? response.data[i].name : "The System - BY Jay Chauhan") + "</td>";
				content += "<td>" + status + "</td>";
				content += "<td>" + formattedDateTime + "</td>";
				content += "<td><a role='button' href='#reject_task' class='btn btn-sm btn-success actionBtn' data-action='accept' data-taskid='" + response.data[i].id + "'>Accept</a>&nbsp;&nbsp;&nbsp;";
				content += "<a role='button' href='#reject_task' class='btn btn-sm btn-danger actionBtn' data-action='decline' data-toggle='modal' data-taskid='" + response.data[i].id + "'>Decline</a></td>";
				content += "</tr>";
			}
			tableBody.html(content);
		}

		$(".actionBtn").click(function () {

			var button = $(this);
			var taskId = button.data("taskid");
			var taskAction = button.data("action");

			if (taskAction == "decline") {
				var form = $("#rejectTask");
				form.submit(function (event) {
					event.preventDefault();
					var reason = $("#reason").val();
					var formData = new FormData();
					formData.append("action", "taskAction");
					formData.append("taskId", taskId);
					formData.append("taskAction", taskAction);
					formData.append("reason", reason);

					$(".submit").click(function () {
						var r = submitForm(formData, "POST");
						console.log(r);
					});
				});
			} else {
				var action = confirm("Are you sure you want to proceed?\nThis task will assign to you!");
				if (action) {
					var formData = new FormData();
					formData.append("action", "taskAction");
					formData.append("taskId", taskId);
					formData.append("taskAction", taskAction);

					var response = submitForm(formData, "POST");
					if (response.type == "error") {
						UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
					} else {
						UIToastr.init('success', 'Congrats!', response.message);
						$("#row_" + taskId).remove();
					}
				}
			}
		});
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'my_tasks':
					myTask_handleTable();
					break;

				case 'approvals':
					approval_handleTable();
					break;

			}
		}
	};
}();
