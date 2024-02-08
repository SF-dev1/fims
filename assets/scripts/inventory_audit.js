"use strict";

var InventoryAudit = function () {

    function submitForm(formData, $type) {
        var getParams = "&";

        var ajaxObj;
        if ($type == "GET") {
            formData = Object.fromEntries(formData);
            getParams += new URLSearchParams(formData).toString();
        }
        var $url = "ajax_load.php?token=" + new Date().getTime();

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

    function handle_locationAudit() {
        // $(".locationId").on('paste', function (event) {
        //     event.preventDefault();
        //     alert("Pasting is not allowed in this Text Box.\nType manually or scan barcode with the barcode scanner");
        // });
        $("#locationContent").submit(function (event) {
            event.preventDefault();
            load_content();
        });

        $("#locationAudit").submit(function (event) {
            event.preventDefault();
            $("#uid").prop("readonly", true);
            let uid = $("#uid").val();
            let formData = new FormData()
            var audit_id = $("#audit_id").val();
            var locationId = $("#locationId").val().toUpperCase();

            formData.append("action", "audit_uid");
            formData.append("uid", uid);
            formData.append("audit_id", audit_id);
            formData.append("locationId", locationId);

            submitForm(formData, "POST").then(function (response) {
                if (response.type == "success") {
                    if (response.status.status == "Success") {
                        UIToastr.init("success", "Audit Done", "UID Audited Successfully!");
                    } else if (response.status.status == "Audit Log Unsuccessful") {
                        UIToastr.init("warning", "Audit Done", "UID Audit Done Log Issue!");
                    } else if (response.status.status == "Audit Unsuccessful") {
                        UIToastr.init("error", "Audit Pending", "UID Audit Unsuccessful!");
                    } else if (response.status.status == "Already Audited") {
                        UIToastr.init("info", "Already Audited", "UID Already Audited!");
                    }
                    load_content();
                    $("#uid").prop("readonly", false);
                }
            });
        });

        function load_content() {
            var locationId = $("#locationId").val().toUpperCase();
            var audit_id = $("#audit_id").val();
            $("#locationId").prop("readonly", true);
            $('.btn-success i').removeClass('fa-search').addClass('fa-sync fa-spin');

            var formData = new FormData();
            formData.append("action", "getLocationInventory");
            formData.append("locationId", locationId);
            formData.append("audit_id", audit_id);

            submitForm(formData, "POST").then(function (response) {
                if (response.type == "success") {
                    $("#locationAudit").parent().parent().removeClass("hide");
                    var invData = response.data;
                    var html = '';

                    var cartoons = Object.keys(invData);
                    var masterCount = 0;
                    cartoons.forEach(ctn => {
                        var master = '';
                        let ctnCount = 0;
                        var boxes = Object.keys(invData[ctn]);
                        var boxContent = "";
                        boxes.forEach(box => {
                            boxContent += '<div class="panel panel-default"><div class="panel-heading"><strong>BOX' + String(box).padStart(9, '0') + '</strong></div><div class="panel-body">';
                            var invIds = invData[ctn][box];
                            invIds.forEach(inv => {
                                let badge = 'outline';
                                if (inv.auditStatus)
                                    badge = "success";
                                var invContent = '<span class="badge badge-' + badge + '">' + inv.invId + '</span>&nbsp;'
                                boxContent += invContent;
                                masterCount++;
                                ctnCount++;
                            });
                            boxContent += '</div></div>';
                        });
                        master += '<div class="panel panel-default"><div class="panel-heading"><center><strong>CTN' + String(ctn).padStart(9, '0') + ' [' + ctnCount + ']</strong></center></div></div>' + boxContent;
                        html += master;
                    });

                    $(".content_details").html('<div class="panel panel-default"><div class="panel-heading"><marquee><strong>' + locationId + ' [' + masterCount + ']</strong></marquee></div></div>' + html);

                    $("#locationId").prop("readonly", false);
                    $('.btn-success i').addClass('fa-search').removeClass('fa-sync fa-spin');
                } else {
                    $("#locationId").prop("readonly", false);
                    $('.btn-success i').addClass('fa-search').removeClass('fa-sync fa-spin');
                    UIToastr.init("error", "0 Inventory", response.message);
                }
            });
            return true;
        }

    }

    return {
        init: function (type) {
            switch (type) {
                case "locationAudit":
                    handle_locationAudit();
                    break;
            }
        }
    }
}();
