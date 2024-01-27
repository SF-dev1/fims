
function playSuccessSound() {
    var audio = $("#notifyAudio")[0];
    audio.play();
}

var submitForm = function (formData, $type) {
    var currentReq = null;
    var $ret = "";
    currentReq = $.ajax({
        url: "/tasks/ajax_load.php?token=" + new Date().getTime(),
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
    console.log(response);
    if (response.type == "success") {
        var pendingCount = $("#pendingTask");
        pendingCount.text(response.data.length);
        var content = "";
        for (let index = 0; index < response.data.length; index++) {
            content += '<li><a href="/tasks/approvals.php"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
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