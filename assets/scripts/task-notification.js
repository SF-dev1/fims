var TasksNotification = function () {
	function playSuccessSound() {
		// $("#notifyAudio").click();
		var audio = $("#notifyAudio")[0];
		audio.play();
	}

	function submitForm(formData, $type) {
		var getParams = "&";

		if ($type == "GET") {
			formData = Object.fromEntries(formData);
			getParams += new URLSearchParams(formData).toString();
		}
		$url = base_url + "/tasks/ajax_load.php?token=" + new Date().getTime();

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
	function fetchPendingApprovals() {
		var formData = new FormData();
		formData.append("action", "getNotification");

		submitForm(formData, "POST").then(function (response) {
			if (response.type == "success") {
				var pendingCount = $("#pendingTask");
				pendingCount.text(response.data.length);
				var content = "";
				for (let index = 0; index < response.data.length; index++) {
					var icon;
					switch (response.data[index].category) {
						case "qc_cooling":
							icon = '<span class="label label-sm label-icon label-warning"><i class="fa fa-snowflake"></i></span>';
							break;
						case "components_requested":
							icon = '<span class="label label-sm label-icon label-info"><i class="fa fa-hand-paper"></i></span>';
							break;
						case "repairs":
							icon = '<span class="label label-sm label-icon label-danger"><i class="fa fa-tools"></i></span>';
							break;
						case "qc_verified":
							icon = '<span class="label label-sm label-icon label-success"><i class="fa fa-thumbs-up"></i></span>';
							break;
						case "tagging":
							icon = '<span class="label label-sm label-icon label-primary"><i class="fa fa-tags"></i></span>';
							break;
						case "qc_verified":
							icon = '<span class="label label-sm label-icon label-success"><i class="fa fa-thumbs-up"></i></span>';
							break;
						case "qc_verified":
							icon = '<span class="label label-sm label-icon label-success"><i class="fa fa-thumbs-up"></i></span>';
							break;
					}
					content += '<li><a href="' + base_url + '/tasks/approvals.php"><span class="task"><span class="desc">' + icon + response.data[index].title + '</span></span></a></li>';
				}
				$("#pendingTaskBody").html(content);
				playSuccessSound();
			}
		});
	}
	/*
	function addCoolingApprovals() {
		var formData = new FormData();
		formData.append("action", "addCoolingEndTask");
		var response = submitForm(formData, "POST");
		if (response.type == "success") {
			// var pendingCount = $("#pendingTask");
			// pendingCount.text(response.data.length);
			// var content = "";
			// for (let index = 0; index < response.data.length; index++) {
			//     content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
			// }
			// $("#pendingTaskBody").append(content);
			// playSuccessSound();
		}
	}

	function addVerifiedApprovals() {
		var formData = new FormData();
		formData.append("action", "addQcVerifiedTask");
		var response = submitForm(formData, "POST");
		if (response.type == "success") {
			// var pendingCount = $("#pendingTask");
			// pendingCount.text(response.data.length);
			// var content = "";
			// for (let index = 0; index < response.data.length; index++) {
			//     content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
			// }
			// $("#pendingTaskBody").append(content);
			// playSuccessSound();
		}
	}

	function addFailedApprovals() {
		var formData = new FormData();
		formData.append("action", "addQcFailedTask");
		var response = submitForm(formData, "POST");
		if (response.type == "success") {
			// var pendingCount = $("#pendingTask");
			// pendingCount.text(response.data.length);
			// var content = "";
			// for (let index = 0; index < response.data.length; index++) {
			//     content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
			// }
			// $("#pendingTaskBody").append(content);
			// playSuccessSound();
		}
	}

	function addComponentApprovals() {
		var formData = new FormData();
		formData.append("action", "addComponentRequestedTask");
		var response = submitForm(formData, "POST");
		if (response.type == "success") {
			// var pendingCount = $("#pendingTask");
			// pendingCount.text(response.data.length);
			// var content = "";
			// for (let index = 0; index < response.data.length; index++) {
			//     content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
			// }
			// $("#pendingTaskBody").append(content);
			// playSuccessSound();
		}
	}

	function checkStatus() {
		if (navigator.onLine) {
			document.getElementById("onlineError");
		}
	}

	*/

	return {
		//main function to initiate the module
		init: function () {
			fetchPendingApprovals();
		}
	};
}();
