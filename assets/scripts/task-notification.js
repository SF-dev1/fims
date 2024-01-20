
function playSuccessSound() {
    // Replace 'success.mp3' with the path to your success sound file
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

function fetchData(baseUrl) {
    // Replace 'your_url_here' with the actual URL you want to call
    // const url = '/tasks/ajax_load.php?action=getNotification';

    var formData = new FormData();
    formData.append("action", "getNotification");
    var response = submitForm(formData, "POST");
    // console.log(response);
    if (response.type == "success") {
        var pendingCount = $("#pendingTask");
        pendingCount.text(response.data.length);
        var content = "";
        for (let index = 0; index < response.data.length; index++) {
            content += '<li><a href="' + baseUrl + "/tasks/approvals.php" + '"><span class="task"><span class="desc">' + response.data[index].title + '</span></span></a></li>';
        }
        $("#pendingTaskBody").html(content);
        playSuccessSound();
    }
}

function checkStatus() {
    if (navigator.onLine) {
        document.getElementById("onlineError");
    }
}