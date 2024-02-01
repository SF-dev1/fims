"use_strict";

var Tasks = function () {
	var currentRequest = null;

	function submitForm(formData, $type) {
		var getParams = "&";

		if ($type == "GET") {
			formData = Object.fromEntries(formData);
			getParams += new URLSearchParams(formData).toString();
		}
		$url = "ajax_load.php?token=" + new Date().getTime();

		return $.ajax({
			type: $type,
			contentType: false,
			processData: false,
			url: $url + getParams,
			data: formData,
		})
			.then(function (data) {
				if (data) {
					try {
						ajaxObj = JSON.parse(data);
					} catch (e) {
						ajaxObj = data;
					}
					if (ajaxObj.redirectUrl) {
						window.location.href = ajaxObj.redirectUrl;
					}
					return ajaxObj;
				}

				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
				return false;
			}, function () {
				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
				return false;
			});
	}

	function myTask_handleTable() {
		var tableBody = $('#taskBody');
		var formData = new FormData();

		formData.append("action", "getTasks");
		submitForm(formData, "POST").then(function (response) {
			var content = "";
			if (response.type == "success") {
				for (let i = 0; i < response.data.length; i++) {
					var status = "";
					var rejectReason = response.data[i].rejectReason;
					var action = "<td>no action</td>";
					if (response.flag) {
						if ((response.user == response.data[i].userRoles))
							action = "<td><button class='actionBtn btn btn-sm btn-success' data-taskid='" + response.data[i].id + "'>Mark Done</button></td>";
					}

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
					content += "<td><span class='badge " + (response.data[i].rejectReason == null ? "badge-success" : "badge-danger") + "'>" + response.data[i].quantity + "</td>";
					content += "<td>" + ((response.data[i].name != null) ? response.data[i].name : "The Great FIMS System") + "</td>";
					content += "<td>" + rejectReason + "</td>";
					content += "<td>" + status + "</td>";
					content += action;
					content += "</tr>";
				}
				tableBody.html(content);
				TableAdvanced.init();

				$(".actionBtn").click(function () {
					var button = $(this);
					button.attr("disabled", true).html("<i class='fa fa-sync fa-spin'></i>");
					var action = confirm("Are you sure you want to proceed?\nThis task will marked as completed by you!");

					if (action) {
						var button = $(this);
						var taskId = button.data("taskid");

						var formData = new FormData();
						formData.append("action", "markDone");
						formData.append("taskId", taskId);

						submitForm(formData, "POST").then(function (response) {
							if (response.type == "error") {

								UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
							} else {
								UIToastr.init('success', 'Congrats!', response.message);
								$("#row_" + taskId).remove();
							}
							button.attr("disabled", false).html("mark done");
						});
					}
				});
			}
		});
	}

	function approval_handleTable() {
		var tableBody = $('#approvalsBody');
		var formData = new FormData();

		formData.append("action", "getApprovals");
		submitForm(formData, "POST").then(function (response) {
			if (response.type == "success") {
				var content = "";
				for (let i = 0; i < response.data.length; i++) {
					var status = "";
					var rejectReason;
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
					var action = "<td>no action</td>";
					if (response.flag) {
						if ((response.user == response.data[i].userRoles))
							action = "<td><a role='button' href='#' class='btn btn-sm btn-success actionBtn' data-action='accept' data-taskid='" + response.data[i].id + "'>Accept</a>&nbsp;&nbsp;&nbsp;<a role='button' href='#reject_task' class='btn btn-sm btn-danger actionBtn' data-action='decline' data-toggle='modal' data-taskid='" + response.data[i].id + "'>Decline</a></td>";
					}

					rejectReason = (response.data[i].rejectReason == null) ? "<span class='badge badge-info'> Not Rejected </span>" : response.data[i].rejectReason;
					content += "<tr id='row_" + response.data[i].id + "'>";
					content += "<td>" + (i + 1) + "</td>";
					content += "<td>" + response.data[i].title + "</td>";
					content += "<td>CTN" + String(response.data[i].ctnId).padStart(9, '0') + "</td>";
					content += "<td>" + response.data[i].quantity + "</td>";
					content += "<td>" + response.data[i].expectedLocation + "</td>";
					content += "<td>" + ((response.data[i].name != null) ? response.data[i].name : "The Great FIMS System") + "</td>";
					content += "<td>" + status + "</td>";
					content += "<td>" + formattedDateTime + "</td>";
					content += action;
					content += "</tr>";
				}
				tableBody.html(content);
				TableAdvanced.init();

				$(".actionBtn").click(function () {
					var button = $(this);
					button.attr('disabled', true).html("<i class='fa fa-sync fa-spin'></i>");
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
							$(".submit").attr('disabled', true).html("<i class='fa fa-sync fa-spin'></i>");
							submitForm(formData, "POST").then(function (response) {
								if (response.type == "success") {
									$(".submit").attr('disabled', false).html("Submit");
									button.attr('disabled', false).html("Decline");
									UIToastr.init('info', 'Task Rejected', 'Task rejection process completed!!');
									$("#row_" + taskId).remove();
									$("#reject_task").modal('hide');
								} else {
									$(".submit").attr('disabled', false).html("Submit");
									button.attr('disabled', false).html("Decline");
									UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
								}
							});
						});
					} else {
						var action = confirm("Are you sure you want to proceed?\nThis task will assign to you!");
						if (action) {
							var formData = new FormData();
							formData.append("action", "taskAction");
							formData.append("taskId", taskId);
							formData.append("taskAction", taskAction);

							submitForm(formData, "POST").then(function (response) {
								if (response.type == "error") {
									button.attr('disabled', false).html("Accept");
									UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
								} else {
									button.attr('disabled', false).html("Accept");
									UIToastr.init('success', 'Congrats!', response.message);
									$("#row_" + taskId).remove();
								}
							});
						}
					}
				});
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
