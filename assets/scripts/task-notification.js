var TasksNotification = function () {
	function playSuccessSound() {
		// $("#notifyAudio").click();
		var audio = $("#notifyAudio")[0];
		audio.play();
	}

	var submitForm = function (formData, $type) {
		var currentReq = null;
		var $ret = "";
		currentReq = $.ajax({
			url: base_url+"/tasks/ajax_load.php?token=" + new Date().getTime(),
			cache: true,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			showLoader: true,
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

	function fetchPendingApprovals() {
		var formData = new FormData();
		formData.append("action", "getNotification");
		var response = submitForm(formData, "POST");

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
				content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + icon + response.data[index].title + '</span></span></a></li>';
			}
			$("#pendingTaskBody").html(content);
			playSuccessSound();
		}
	}

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

	return {
		//main function to initiate the module
		init: function () {
			fetchPendingApprovals();
			addCoolingApprovals();
			addVerifiedApprovals();
			addFailedApprovals();
			addComponentApprovals();
		}
	};
}();
