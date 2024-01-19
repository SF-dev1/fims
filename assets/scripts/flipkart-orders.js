// "use strict";

var Flipkart = (function () {
  var currentReq = null;
  // Submit for with mutlipart data containing Image
  function submitForm(formData, $type) {
    var $ret = "";
    currentReq = $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: true,
      type: $type,
      data: formData,
      // contentType: false,
      processData: false,
      crossDomain: false,
      async: false,
      showLoader: false,
      beforeSend: function () {
        if (currentReq != null) {
          currentReq.abort();
        } else {
          currentReq = currentReq;
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
        UIToastr.init(
          "error",
          "Request Error",
          "Error Processing your Request!!"
        );
      },
    });
    return $ret;
  }

  function scanShip_handleInit() {
    var ship_track,
      success_ship = {};
    var audio = document.getElementById("chatAudio");
    var fulfillable_quantity = 0;
    var buttonClicked = true;
    $("#sidelineProduct").prop("disabled", true);

    // var success_ship = [];
    function disableF5(e) {
      if ((e.which || e.keyCode) == 116 || (e.which || e.keyCode) == 82) {
        // console.log(e);
        if (!buttonClicked) {
          e.preventDefault();
          if (confirm("Are You sure?")) {
            endProcess(orderId);
            window.location.reload();
          }
        }
      }
    }
    $(document).on("keydown", disableF5);

    $("form#get-product").submit(function (e) {
      e.preventDefault();
      $(".product_details").addClass("hide");
      var tracking_id = $("#tracking_id").val().toUpperCase();
      $("#tracking_id").addClass("spinner").attr("disabled", true);
      window.setTimeout(function () {
        var s = submitForm(
          "action=scan_ship&tracking_id=" + tracking_id,
          "GET"
        );
        fulfillable_quantity = parseInt(s.fulfillable_quantity);
        if (s.type == "success") {
          $(".product_details .item_groups").html(s.content);
          $(".product_details").removeClass("hide");
          ship_track = s.items;
          success_ship = {};
          $("#tracking_id").removeClass("spinner").attr("disabled", false);
          $("#uid").focus();
          $("#sidelineProduct").prop("disabled", false);
          buttonClicked = false;
        } else {
          UIToastr.init(s.type, "Scan Pack Ship", s.msg);
          $(".product_details").addClass("hide");
          $("#tracking_id")
            .removeClass("spinner")
            .attr("disabled", false)
            .val("")
            .focus();
          audio.play();
        }
      }, 10);
    });

    $("#sidelineProduct").click(function () {
      $("#sidelineProduct i").addClass("fa-spin fa-spinner");
      endProcess(orderId);
      buttonClicked = true;
      $("#tracking_id").removeClass("spinner").attr("disabled", false);
    });

    $("form#uid-scanship").submit(function (e) {
      e.preventDefault();
      var uid = $("#uid").val().toUpperCase();

      const exists = $.map(success_ship, function (v) {
        return v;
      }).includes(uid);
      if (exists) {
        UIToastr.init(
          "info",
          "Scan Pack Ship",
          "Inventory with id " + uid + " is already scanned"
        );
        $("#uid").val("").focus();
        return;
      }

      $("#uid").attr("disabled", true);
      window.setTimeout(function () {
        var s = submitForm("action=get_uid_details&uid=" + uid, "GET");
        if (s.type == "success") {
          $.each(ship_track, function (orderItemId, item) {
            const index = Object.keys(item).find(
              (key) => item[key].item_id === s.item_id
            );
            if (typeof index !== "undefined") {
              if (item[index].scan) return;

              const k = Object.keys(item).find(
                (key) => item[key].item_id === s.item_id && !item[key].scan
              );
              if (typeof k !== "undefined") {
                const v = item[k];
                if (v.scan) {
                  return false;
                }

                if (v.item_id == s.item_id && v.scan) {
                  UIToastr.init(
                    "info",
                    "Scan Pack Ship",
                    "All items of this SKU already packed"
                  );
                  $("#uid").attr("disabled", false).val("").focus();
                  audio.play();
                  return false;
                }

                if (
                  v.item_id == s.item_id &&
                  v.scanned_qty != v.quantity &&
                  !v.scan
                ) {
                  if (!Array.isArray(success_ship[orderItemId])) {
                    success_ship[orderItemId] = new Array();
                  }
                  $(
                    ".item_" + v.item_id + "_" + orderItemId + " .uids"
                  ).removeClass("hide");
                  success_ship[orderItemId].push(uid);
                  item[k].uids.push(uid);
                  item[k].scanned_qty += 1;
                  $(
                    ".item_" + v.item_id + "_" + orderItemId + " .uids .uid"
                  ).text(item[k].uids.toString().replace(/,/g, ", "));

                  if (item[k].scanned_qty == item[k].quantity) {
                    item[k].scan = true;
                    $(
                      ".item_" + v.item_id + "_" + orderItemId + " .scanned"
                    ).removeClass("hide");
                  }
                  fulfillable_quantity = fulfillable_quantity - 1;
                  if (fulfillable_quantity > 0) return false;
                } else {
                  UIToastr.init("error", "Scan Pack Ship", "Incorrect Product");
                  $("#uid").attr("disabled", false).val("").focus();
                  audio.play();
                  return false;
                }
              } else {
                UIToastr.init("error", "Scan Pack Ship", "Incorrect Product");
                $("#uid").attr("disabled", false).val("").focus();
                audio.play();
                return false;
              }
            }
          });

          // const pending_scan = item.some(el => el.scan === false);
          if (fulfillable_quantity === 0) {
            var formData =
              "action=save_scan_ship&scanned_items=" +
              JSON.stringify(success_ship);
            window.setTimeout(function () {
              var s = submitForm(formData, "POST");
              if (s.type == "success" || s.type == "error") {
                if (s.type == "success") {
                  $(".product_details .item_groups").html("");
                  $(".product_details").addClass("hide");
                  $("#tracking_id").val("").focus();
                } else {
                  audio.play();
                }
              } else {
                audio.play();
                UIToastr.init(
                  "error",
                  "Scan Pack Ship",
                  "Error Processing Request. Please try again later."
                );
              }
            }, 10);
          }
        } else if (s.type == "error") {
          audio.play();
          UIToastr.init(s.type, "Scan Pack Ship", s.msg);
        } else {
          audio.play();
          UIToastr.init(
            "error",
            "Scan Pack Ship",
            "Error Processing Request. Please try again later."
          );
        }
        $("#uid").attr("disabled", false).val("").focus();
        $("#tracking_id").removeClass("spinner").attr("disabled", false);
      }, 10);
    });

    function endProcess(orderId) {
      $("#sidelineProduct").prop("disabled", true);
      var formdata = "action=sideline_product&orderId=" + orderId;
      $.when(submitForm(formdata, "GET")).then(function (s) {
        if (s.type == "success" || s.type == "error") {
          if (s.type == "success") {
            UIToastr.init(s.type, "Sideline Order", s.message);
            $(".product_details .item_groups").html("");
            $(".product_details").addClass("hide");
            $("#tracking_id").val("").focus();
            $("#sidelineProduct i").removeClass("fa-spin fa-spinner");
            return true;
          } else {
            audio.play();
            $("#sidelineProduct i").removeClass("fa-spin fa-spinner");
            return false;
          }
        } else {
          audio.play();
          UIToastr.init(
            "error",
            "Sideline Order",
            "Error Processing Request. Please try again later."
          );
          $("#sidelineProduct i").removeClass("fa-spin fa-spinner");
          return false;
        }
      });
    }
  }

  function payments_handleInit() {
    function initTable_payments() {
      if (!jQuery().dataTable) {
        return;
      }

      // loadAjaxData('settlements');
      search_payments();

      // Order Status Tabs
      $(".settlement_type a").click(function () {
        if ($(this).parent().attr("class") == "active") {
          return;
        }
        var tab = $(this).attr("href");
        var tab_type = tab.substr(tab.indexOf("_") + 1);
        if (tab_type == "search_payment") {
          search_payments();
        } else {
          // loadAjaxData($type);
          payments_handleTable(tab_type);
        }
      });
    }

    function payments_handleTable(tab_type, $query = "") {
      $.fn.dataTable.Api.register("column().title()", function () {
        return $(this.header()).text().trim();
      });

      $.fn.dataTable.Api.register("column().getColumnFilter()", function () {
        var e = this.index();
        if (oTable.settings()[0].aoColumns[e].hasOwnProperty("columnFilter"))
          return oTable.settings()[0].aoColumns[e].columnFilter;
        else return "";
      });

      var statusFilters,
        tableFormat = {};
      switch (tab_type) {
        case "settlements":
          tableFormat = {
            columns: [
              {
                title: "Settlement ID",
                columnFilter: "inputFilter",
              },
              {
                title: "Settlement Date",
                columnFilter: "dateFilter",
              },
              {
                title: "Account",
                columnFilter: "selectFilter",
              },
              {
                title: "Settlement Amount",
                columnFilter: "rangeFilter",
              },
              {
                title: "Actions",
                className: "return_hide_column",
                columnFilter: "actionFilter",
              },
            ],
            order: [1, "desc"],
            columnDefs: [
              {
                targets: -1,
                render: function (a, t, e, s) {
                  return a;
                },
              },
            ],
          };
          break;

        case "disputed":
          tableFormat = {
            columns: [
              {
                title: "Account Name",
                columnFilter: "selectFilter",
              },
              {
                title: "Order Item ID",
                columnFilter: "inputFilter",
              },
              {
                title: "Order Id",
                columnFilter: "inputFilter",
              },
              {
                title: "Due Date",
                className: "due_date",
                columnFilter: "dateFilter",
              },
              {
                title: "Expected Payout",
                columnFilter: "rangeFilter",
              },
              {
                title: "Amount Settled",
                columnFilter: "rangeFilter",
              },
              {
                title: "Difference",
                columnFilter: "rangeFilter",
              },
              {
                title: "Tags",
                className: "td-x-scroll",
                columnFilter: "multiSelectFilter",
              },
              {
                title: "Actions",
                className: "return_hide_column",
                columnFilter: "actionFilter",
              },
              {
                className: "return_hide_column",
              },
            ],
            order: [9, "asc"],
            columnDefs: [
              {
                targets: 3,
                render: function (a, t, e, s) {
                  if (tab_type == "disputed")
                    return new Date(a).getTime() <= new Date().getTime()
                      ? a +
                          ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>'
                      : a;
                  else return a;
                },
              },
              {
                targets: 5,
                type: "date",
                render: function (a, t, e, s) {
                  if (tab_type != "disputed" && tab_type != "settlements")
                    return new Date(a).getTime() <= new Date().getTime()
                      ? a +
                          ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>'
                      : a;
                  else return a;
                },
              },
              {
                targets: [6, 8],
                render: function (a, t, e, s) {
                  if (tab_type == "settlements") {
                    return;
                  }
                  if (
                    (tab_type == "disputed" && s.col == 6) ||
                    (tab_type != "settlements" && s.col == 8)
                  )
                    return a > 0
                      ? '<span class="label label-sm label-success">' +
                          a +
                          " </span>"
                      : '<span class="label label-sm label-warning">' +
                          a +
                          " </span>";
                  else return a;
                },
              },
              {
                targets: -2,
                render: function (a, t, e, s) {
                  if (tab_type == "disputed") {
                    var tags = "";
                    $.each(a, function (k, v) {
                      tags +=
                        '<span class="label label-default label-tags">' +
                        v +
                        "</span> ";
                    });
                    return tags;
                  } else {
                    return a < 0
                      ? '<span class="label label-sm label-warning">' +
                          a +
                          "</span>"
                      : '<span class="label label-sm label-success">' +
                          a +
                          "</span>";
                  }
                },
              },
            ],
          };
          break;

        default:
          tableFormat = {
            columns: [
              {
                title: "Account Name",
                columnFilter: "selectFilter",
              },
              {
                title: "Order Item ID",
                columnFilter: "inputFilter",
              },
              {
                title: "Order ID",
                columnFilter: "inputFilter",
              },
              {
                title: "Order Date",
                columnFilter: "dateFilter",
              },
              {
                title: "Shipped Date",
                columnFilter: "dateFilter",
              },
              {
                title: "Due Date",
                columnFilter: "dateFilter",
              },
              {
                title: "Expected Payout",
                columnFilter: "rangeFilter",
              },
              {
                title: "Amount Settled",
                columnFilter: "rangeFilter",
              },
              {
                title: "Difference",
                columnFilter: "rangeFilter",
              },
              {
                title: "Actions",
                className: "return_hide_column",
                columnFilter: "actionFilter",
              },
              {
                className: "return_hide_column",
              },
            ],
            order: [10, "asc"],
            columnDefs: [
              {
                targets: 3,
                render: function (a, t, e, s) {
                  if (tab_type == "disputed")
                    return new Date(a).getTime() <= new Date().getTime()
                      ? a +
                          ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>'
                      : a;
                  else return a;
                },
              },
              {
                targets: 5,
                type: "date",
                render: function (a, t, e, s) {
                  if (tab_type != "disputed" && tab_type != "settlements")
                    return new Date(a).getTime() <= new Date().getTime()
                      ? a +
                          ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>'
                      : a;
                  else return a;
                },
              },
              {
                targets: [6, 8],
                render: function (a, t, e, s) {
                  if (tab_type == "settlements") {
                    return;
                  }
                  if (
                    (tab_type == "disputed" && s.col == 6) ||
                    (tab_type != "settlements" && s.col == 8)
                  )
                    return a > 0
                      ? '<span class="label label-sm label-success">' +
                          a +
                          " </span>"
                      : '<span class="label label-sm label-warning">' +
                          a +
                          " </span>";
                  else return a;
                },
              },
              {
                targets: -2,
                render: function (a, t, e, s) {
                  if (tab_type == "disputed") {
                    var tags = "";
                    $.each(a, function (k, v) {
                      tags +=
                        '<span class="label label-default label-tags">' +
                        v +
                        "</span> ";
                    });
                    return tags;
                  } else {
                    return a < 0
                      ? '<span class="label label-sm label-warning">' +
                          a +
                          "</span>"
                      : '<span class="label label-sm label-success">' +
                          a +
                          "</span>";
                  }
                },
              },
            ],
          };
          break;
      }

      var table = $("#payment_" + tab_type);
      table.empty();
      var oTable = table.DataTable({
        responsive: true,
        dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12 table-filter'><'col-sm-12' <'table-scrollable table-payments' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
        lengthMenu: [
          [20, 50, 100, -1],
          [20, 50, 100, "All"],
        ], // change per page values here
        pageLength: 20,
        debug: true,
        language: {
          lengthMenu: "Display _MENU_",
        },
        searchDelay: 500,
        processing: !0,
        // serverSide: !0,
        destroy: true,
        fixedHeader: {
          headerOffset: 40,
        },
        ajax: {
          url:
            "ajax_load.php?action=get_all_" +
            tab_type +
            $query +
            "&token=" +
            new Date().getTime(),
          cache: false,
          type: "GET",
        },
        columns: tableFormat["columns"],
        order: [tableFormat["order"]],
        columnDefs: tableFormat["columnDefs"],
        createdRow: function (row, data, dataIndex) {
          if (
            tab_type == "unsettled" ||
            tab_type == "disputed" ||
            tab_type == "upcoming" ||
            tab_type == "search_payments" ||
            tab_type == "to_claim"
          ) {
            $(row).attr("id", data[1]).addClass("clickable");
            if (tab_type == "disputed") {
              // $(row).find('td:eq(7)').addClass('td-x-scroll');
              // $(row).find('td:eq(8)').addClass('return_hide_column');
            }
            // $( row ).find('td:eq(9)').addClass('return_hide_column');
          }
        },
        fnDrawCallback: function () {
          loadPaymentsDetails();
        },
        initComplete: function () {
          loadFilters(this), afterInitDataTable();
        },
      });

      function loadFilters(t) {
        var parseDateValue = function (rawDate) {
          var d = moment(rawDate).format("YYYY-MM-DD");
          var dateArray = d.split("-");
          var parsedDate = dateArray[0] + dateArray[1] + dateArray[2];
          return parseInt(parsedDate);
        };

        var isRangeFilter = function (e) {
          if (typeof oTable.settings()[0].aoColumns[e] !== "undefined")
            if (
              oTable
                .settings()[0]
                .aoColumns[e].hasOwnProperty("columnFilter") &&
              oTable.settings()[0].aoColumns[e].columnFilter === "rangeFilter"
            )
              return true;
          return false;
        };

        var isDateFilter = function (e) {
          if (typeof oTable.settings()[0].aoColumns[e] !== "undefined")
            if (
              oTable
                .settings()[0]
                .aoColumns[e].hasOwnProperty("columnFilter") &&
              oTable.settings()[0].aoColumns[e].columnFilter === "dateFilter"
            )
              return true;

          return false;
        };

        var isMultiSelectFilter = function (e) {
          if (typeof oTable.settings()[0].aoColumns[e] !== "undefined")
            if (
              oTable
                .settings()[0]
                .aoColumns[e].hasOwnProperty("columnFilter") &&
              oTable.settings()[0].aoColumns[e].columnFilter ===
                "multiSelectFilter"
            )
              return true;

          return false;
        };

        // FILTERING
        var f = $('<ul class="filter"></ul>').appendTo(".table-filter");
        var tags = [];
        t.api()
          .columns()
          .every(function () {
            var s;
            switch (this.getColumnFilter()) {
              case "inputFilter":
                s = $(
                  '<input type="text" class="form-control form-control-sm form-filter filter-input input-medium margin-bottom-5" placeholder="' +
                    this.title() +
                    '" data-col-index="' +
                    this.index() +
                    '"/>'
                );
                break;

              case "rangeFilter":
                s = $(
                  '<div class="input-range input-medium margin-bottom-5"><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="' +
                    this.title() +
                    ' From" data-col-index="' +
                    this.index() +
                    '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="' +
                    this.title() +
                    ' To" data-col-index="' +
                    this.index() +
                    '"/></div>'
                );
                break;

              case "dateFilter":
                s = $(
                  '<div class="input-daterange input-medium"><div class="input-group input-group-sm date date-range-picker margin-bottom-5"><input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="' +
                    this.title() +
                    '" data-col-index="' +
                    this.index() +
                    '" /><span class="input-group-btn"><button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button></span></div></div>'
                );
                break;

              case "selectFilter":
                (s = $(
                  '<select class="form-control form-control-sm form-filter filter-input select2 margin-bottom-5" data-placeholder="' +
                    this.title() +
                    '" data-col-index="' +
                    this.index() +
                    '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'
                )),
                  this.data()
                    .unique()
                    .sort()
                    .each(function (t, f) {
                      $(s).append(
                        '<option value="' + t + '">' + t + "</option>"
                      );
                    });
                break;

              case "multiSelectFilter":
                (s = $(
                  '<select class="form-control form-control-sm form-filter filter-input select2 margin-bottom-5" title="Select" multiple="multiple" data-col-index="' +
                    this.index() +
                    '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'
                )),
                  this.data()
                    .unique()
                    .sort()
                    .each(function (t, f) {
                      $(t).each(function (k, tag) {
                        if (tags.indexOf(tag) === -1) {
                          tags.push(tag);
                          $(s).append(
                            '<option value="' + tag + '">' + tag + "</option>"
                          );
                        }
                      });
                    });
                break;

              case "statusFilter":
                (s = $(
                  '<select class="form-control form-control-sm form-filter filter-input select2  margin-bottom-5" title="Select" data-col-index="' +
                    this.index() +
                    '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'
                )),
                  this.data()
                    .unique()
                    .sort()
                    .each(function (t, a) {
                      $(s).append(
                        '<option value="' +
                          t +
                          '">' +
                          statusFilters[t].title +
                          "</option>"
                      );
                    });
                break;

              case "actionFilter":
                var i = $(
                    '<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'
                  ),
                  r = $(
                    '<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>'
                  );
                $("<li class='filter-buttons'>")
                  .append(i)
                  .append(r)
                  .appendTo(f);
                var sD, eD, minR, maxR;
                $(i).on("click", function (ev) {
                  ev.preventDefault();
                  var n = {};
                  $(function () {})
                    .find(".filter-input")
                    .each(function () {
                      var t = $(this).data("col-index");
                      n[t]
                        ? (n[t] += "|" + $(this).val())
                        : (n[t] = $(this).val());
                    }),
                    $.each(n, function (e, a) {
                      if (isRangeFilter(e)) {
                        // RANGE FILTER
                        if (a == "") return;

                        var range = a.split("|", 2);
                        minR = range[0];
                        maxR = range[1];

                        var fR = oTable
                          .column(e)
                          .data()
                          .filter(function (v, i) {
                            var evalRange = v === "" ? 0 : v;
                            // if ((isNaN(minR) && isNaN(maxR)) || (evalRange >= minR && evalRange <= maxR)) {
                            if (
                              (isNaN(minR) && isNaN(maxR)) ||
                              (isNaN(minR) && evalRange <= maxR) ||
                              (minR <= evalRange && isNaN(maxR)) ||
                              (minR <= evalRange && evalRange <= maxR)
                            ) {
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
                      } else if (isDateFilter(e)) {
                        // DATE FILTER
                        if (a == "") return;

                        var dates = a.split(" - ", 2);
                        sD = dates[0];
                        eD = dates[1];
                        var dS = parseDateValue(sD);
                        var dE = parseDateValue(eD);

                        var fD = oTable
                          .column(e)
                          .data()
                          .filter(function (v, i) {
                            var evalDate = v === "" ? 0 : parseDateValue(v);
                            if (
                              (isNaN(dS) && isNaN(dE)) ||
                              (evalDate >= dS && evalDate <= dE)
                            )
                              return true;

                            return false;
                          });

                        var d = "";
                        for (var count = 0; count < fD.length; count++) {
                          d += fD[count] + "|";
                        }

                        d = d.slice(0, -1);
                        oTable
                          .column(e)
                          .search(
                            d ? "^" + d + "$" : "^" + "-" + "$",
                            1,
                            !1,
                            1
                          );
                        // oTable.column(e).search("^" + d + "$" , 1, 1, 1);
                      } else if (isMultiSelectFilter(e)) {
                        // MULTI SELECT FILTER
                        if (a == "") return;
                        oTable
                          .column(e)
                          .search(a.join("|") || +"$", 1, !1, !1, !1);
                      } else {
                        // DEFAULT FILTER
                        oTable.column(e).search(a || "", !1, !1);
                      }
                    }),
                    oTable.table().draw(),
                    afterInitDataTable();
                }),
                  $(r).on("click", function (ev) {
                    ev.preventDefault(),
                      $(f)
                        .find(".filter-input")
                        .each(function () {
                          $(this).val(""),
                            oTable
                              .column($(this).data("col-index"))
                              .search("", !1, !1);
                          $(".select2").val(null).trigger("change");
                        }),
                      oTable.table().draw(),
                      afterInitDataTable();
                  });
                break;

              default:
                s = "";
                break;
            }
            "" !== this.title() && $(s).appendTo($("<li>").appendTo(f));
          });
        var n = function () {
          t.api()
            .columns()
            .every(function () {
              this.visible()
                ? $(f).find("li").eq(this.index()).show()
                : $(f).find("li").eq(this.index()).hide();
            });
        };
        n(), (window.onresize = n);

        // DEFAULT FUNCTIONS
        if (jQuery().daterangepicker) {
          var start = moment().subtract(3, "years");
          var end = moment().add(59, "days");

          $(".date-range-picker").daterangepicker({
            autoApply: true,
            ranges: {
              Today: [moment(), moment()],
              Tomorrow: [moment().add(1, "days"), moment().add(1, "days")],
              "Next 7 Days": [moment(), moment().add(6, "days")],
              "Next 30 Days": [moment(), moment().add(29, "days")],
              "This Month": [
                moment().startOf("month"),
                moment().endOf("month"),
              ],
            },
            alwaysShowCalendars: true,
            minDate: start,
            maxDate: end,
          });

          $(".date-range-picker").on(
            "apply.daterangepicker",
            function (ev, picker) {
              $(this)
                .find("input")
                .val(
                  picker.startDate.format("MMM DD, YYYY") +
                    " - " +
                    picker.endDate.format("MMM DD, YYYY")
                );
            }
          );
        }

        if (jQuery().select2) {
          $("select.select2, .dataTables_length select").select2({
            placeholder: "Select",
            allowClear: true,
            debug: true,
          });
        }

        // FILTER TOGGLE
        $(".btn-advance").html(
          '<button class="btn btn-default"><i class="fa fa-filter"></i></button>'
        );
        $(".filter").hide();
        $(".btn-advance").bind("click", function () {
          $(".filter").slideToggle();
        });
      }

      function afterInitDataTable() {
        // Reload DataTable
        $(".reload")
          .off()
          .on("click", function (e) {
            e.preventDefault();
            var el = jQuery(this).closest(".portlet").children(".portlet-body");
            App.blockUI({ target: el });
            $(".select2").val("").trigger("change");
            $(".filter-cancel").trigger("click");
            oTable.ajax.reload();
            window.setTimeout(function () {
              App.unblockUI(el);
            }, 500);
          });
      }

      function loadPaymentsDetails() {
        // Array to track the ids of the details displayed rows
        var detailRows = [];
        $("#payment_" + tab_type + " tbody")
          .off("click", "tr.clickable")
          .on("click", "tr.clickable", function () {
            var tr = $(this);
            var row = oTable.row(tr);
            var idx = $.inArray(tr.attr("id"), detailRows);

            if (row.child.isShown()) {
              tr.removeClass("parent");
              row.child.hide("slow");

              // Remove from the 'open' array
              detailRows.splice(idx, 1);
            } else {
              tr.addClass("parent");
              row
                .child('<center><i class="fa fa-sync fa-spin"></i></center>')
                .show("slow");
              window.setTimeout(function () {
                var content = getTransactionHistory(
                  tr.attr("id"),
                  tr.find("td:last").prev().text(),
                  tab_type
                );
                row.child(content);
                App.scrollTo(tr, -83);
                App.initTooltips();

                // /*// Add to the 'open' array
                if (idx === -1) {
                  detailRows.push(tr.attr("id"));
                }

                $(".update_difference")
                  .off("click")
                  .on("click", function () {
                    var tr = $("tr#" + $(this).attr("data-itemid"));
                    row = oTable.row(tr);
                    row
                      .child(
                        '<center><i class="fa fa-sync fa-spin"></i></center>'
                      )
                      .show("slow");
                    var el = $(this);
                    window.setTimeout(function () {
                      updateDifference(el);
                      var details = getTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      if (tab_type == "disputed") {
                        tr.find("td:eq(4)").html(details["netPayout"]);
                        tr.find("td:eq(5)").html(details["netSettlement"]);
                        tr.find("td:eq(6)").html(details["difference"]);
                      } else {
                        tr.find("td:eq(6)").html(details["netPayout"]);
                        tr.find("td:eq(7)").html(details["netSettlement"]);
                        tr.find("td:eq(8)").html(details["difference"]);
                      }
                    }, 10);

                    // Refetch
                    tr.removeClass("parent");
                    row.child.hide("slow");

                    // Remove from the 'open' array
                    detailRows.splice(tr.attr("id"), 1);
                    tr.trigger("click"); // Open
                  });

                $(".reload")
                  .off("click")
                  .on("click", function () {
                    tr = $("tr#" + $(this).attr("data-itemid"));
                    row = oTable.row(tr);
                    row
                      .child(
                        '<center><i class="fa fa-sync fa-spin"></i></center>'
                      )
                      .show("slow");
                    window.setTimeout(function () {
                      getPaymentsTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      var details = getTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      if (tab_type == "disputed") {
                        tr.find("td:eq(4)").html(details["netPayout"]);
                        tr.find("td:eq(5)").html(details["netSettlement"]);
                        tr.find("td:eq(6)").html(details["difference"]);
                      } else {
                        tr.find("td:eq(6)").html(details["netPayout"]);
                        tr.find("td:eq(7)").html(details["netSettlement"]);
                        tr.find("td:eq(8)").html(details["difference"]);
                      }
                    }, 10);

                    // // Refetch
                    tr.removeClass("parent");
                    row.child.hide("slow");

                    // // Remove from the 'open' array
                    detailRows.splice(tr.attr("id"), 1);
                    tr.trigger("click"); // Open
                  });

                $(".refresh_payout")
                  .off("click")
                  .on("click", function () {
                    tr = $("tr#" + $(this).attr("data-itemid"));
                    row = oTable.row(tr);
                    row
                      .child(
                        '<center><i class="fa fa-sync fa-spin"></i></center>'
                      )
                      .show("slow");
                    window.setTimeout(function () {
                      refreshPayout(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      var details = getTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      if (tab_type == "disputed") {
                        tr.find("td:eq(4)").html(details["netPayout"]);
                        tr.find("td:eq(5)").html(details["netSettlement"]);
                        tr.find("td:eq(6)").html(details["difference"]);
                      } else {
                        tr.find("td:eq(6)").html(details["netPayout"]);
                        tr.find("td:eq(7)").html(details["netSettlement"]);
                        tr.find("td:eq(8)").html(details["difference"]);
                      }
                    }, 10);

                    // Refetch
                    tr.removeClass("parent");
                    row.child.hide("slow");

                    // Remove from the 'open' array
                    detailRows.splice(tr.attr("id"), 1);
                    tr.trigger("click"); // Open
                  });

                $(".refetch_billing")
                  .off("click")
                  .on("click", function () {
                    tr = $("tr#" + $(this).attr("data-itemid"));
                    row = oTable.row(tr);
                    row
                      .child(
                        '<center><i class="fa fa-sync fa-spin"></i></center>'
                      )
                      .show("slow");
                    window.setTimeout(function () {
                      refetchBillingDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      var details = getTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );

                      if (tab_type == "disputed") {
                        tr.find("td:eq(4)").html(details["netPayout"]);
                        tr.find("td:eq(5)").html(details["netSettlement"]);
                        tr.find("td:eq(6)").html(details["difference"]);
                      } else {
                        tr.find("td:eq(6)").html(details["netPayout"]);
                        tr.find("td:eq(7)").html(details["netSettlement"]);
                        tr.find("td:eq(8)").html(details["difference"]);
                      }
                    }, 10);

                    // Refetch
                    tr.removeClass("parent");
                    row.child.hide("slow");

                    // Remove from the 'open' array
                    detailRows.splice(tr.attr("id"), 1);
                    tr.trigger("click"); // Open
                  });

                $(".mark_settled")
                  .off("click")
                  .on("click", function () {
                    if (confirm("Order will be marked settled. \nContinue?")) {
                      window.setTimeout(function () {
                        var details = markOrderSettled(
                          tr.attr("id"),
                          tr.find("td:last").prev().text()
                        );
                        if (details == "success") {
                          if (tab_type != "search_payments") {
                            tr.removeClass("parent");
                            row.child.hide("slow");

                            // Remove from the 'open' array
                            detailRows.splice(tr.attr("id"), 1);
                            tr.remove();
                            row.remove();
                          }
                          UIToastr.init(
                            "success",
                            "Order Settlement Status",
                            "Order successfully marked settled."
                          );
                        }
                      }, 10);
                    }
                  });

                $(".update_notes")
                  .off("click")
                  .on("click", function (e) {
                    e.preventDefault();

                    var form = $(this).closest("form");
                    var orderItemId = $(form)
                      .find("input[name='orderItemId']")
                      .val();
                    var incidentId = $(form)
                      .find("input[name='incidentId']")
                      .val();
                    var account_id = $(form)
                      .find("input[name='account_id']")
                      .val();
                    var notes = $(form)
                      .find("input[name='settlementNotes']")
                      .val()
                      .toUpperCase();

                    $(form)
                      .find("input[name='settlementNotes']")
                      .closest(".form-group")
                      .removeClass("has-error");
                    $(this).attr("disabled", true);
                    window.setTimeout(function () {
                      if (
                        update_notes(orderItemId, notes, incidentId, account_id)
                      ) {
                        $(form)
                          .find("input[name='incidentId']")
                          .attr("disabled", true);
                      }
                    }, 10);
                    $(form).find("input[name='settlementNotes']").val(notes);
                    $(form).find("input[name='incidentId']").val(incidentId);
                  });

                $(".shopsy_order")
                  .off("click")
                  .on("click", function () {
                    tr = $("tr#" + $(this).attr("data-itemid"));
                    is_shopsy = $(this).attr("data-isshopsy");
                    row = oTable.row(tr);
                    row
                      .child(
                        '<center><i class="fa fa-sync fa-spin"></i></center>'
                      )
                      .show("slow");
                    window.setTimeout(function () {
                      updateOrderMarketplace(
                        tr.attr("id"),
                        "is_shopsy",
                        is_shopsy
                      );
                      refreshPayout(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      var details = getTransactionDetails(
                        tr.attr("id"),
                        tr.find("td:last").prev().text()
                      );
                      if (tab_type == "disputed") {
                        tr.find("td:eq(4)").html(details["netPayout"]);
                        tr.find("td:eq(5)").html(details["netSettlement"]);
                        tr.find("td:eq(6)").html(details["difference"]);
                      } else {
                        tr.find("td:eq(6)").html(details["netPayout"]);
                        tr.find("td:eq(7)").html(details["netSettlement"]);
                        tr.find("td:eq(8)").html(details["difference"]);
                      }
                    }, 10);

                    // Refetch
                    tr.removeClass("parent");
                    row.child.hide("slow");

                    // Remove from the 'open' array
                    detailRows.splice(tr.attr("id"), 1);
                    tr.trigger("click"); // Open
                  });

                $(".marketplace-fee-row")
                  .off("click")
                  .on("click", function () {
                    $(".marketplace-fee-row i").toggleClass("fa-chevron-up");
                    $(".marketplace-fee-child").toggle();
                  });

                $(".waiver-fee-row")
                  .off("click")
                  .on("click", function () {
                    $(".waiver-fee-row i").toggleClass("fa-chevron-up");
                    $(".waiver-fee-child").toggle();
                  });

                $(".taxes-row")
                  .off("click")
                  .on("click", function () {
                    $(".taxes-row i").toggleClass("fa-chevron-up");
                    $(".taxes-child").toggle();
                  });

                $("#settlementNotes").select2({
                  tags: payments_issues,
                });
              }, 10);
            }
          });
      }
    }

    function getTransactionDetails(orderItemId, account_id) {
      var $return = "";
      var formData =
        "action=get_transaction_details&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id;
      var s = submitForm(formData, "GET");
      if (s.type == "success") {
        $return = s.data[orderItemId];
      } else {
        UIToastr.init(
          "error",
          "Transaction Details",
          "Error fetching transaction details!! Please retry later."
        );
      }
      return $return;
    }

    function getPaymentsTransactionDetails(orderItemId, account_id) {
      var $return = "";
      var formData =
        "action=get_payment_transactions_details&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id;
      var s = submitForm(formData, "GET");
      if (s.type == "success") {
        $return = s.data[orderItemId];
      } else {
        UIToastr.init(
          "error",
          "Transaction Details",
          "Error fetching transaction details!! Please retry later."
        );
      }
      return $return;
    }

    function getTransactionHistory(orderItemId, account_id, settlement_type) {
      var $return = "";
      var formData =
        "action=get_difference_details&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id +
        "&type=" +
        settlement_type;
      var s = submitForm(formData, "GET");
      if (s.type == "success") {
        $return = s.data;
      } else {
        UIToastr.init(
          "error",
          "Transaction History",
          "Error fetching transaction history!! Please retry later."
        );
      }
      return $return;
    }

    function updateDifference(element) {
      var orderItemId = $(element).attr("data-itemId");
      var parent_tr = $(element).closest("tr").closest("tr");
      var account_id = $(element).attr("data-accountId");
      var key = $(element).attr("data-key");
      var value = $(element).attr("data-value");
      var formData =
        "action=update_settlement_difference&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id +
        "&key=" +
        key +
        "&value=" +
        value;
      var s = submitForm(formData, "POST");
      if (s.type == "success") {
        UIToastr.init(s.type, "Update Difference", s.msg);
      } else {
        UIToastr.init(
          "error",
          "Update Difference",
          "Error updating difference details!! Please retry later."
        );
      }
    }

    function updateOrderMarketplace(orderItemId, key, value) {
      var $return = "";
      var formData =
        "action=update_settlement_difference&orderItemId=" +
        orderItemId +
        "&key=" +
        key +
        "&value=" +
        value;
      var s = submitForm(formData, "POST");
      if (s.type == "success") {
        $return = s.type;
      } else {
        UIToastr.init(
          "error",
          "Update Order Sales Channel",
          "Error updating sales channel!! Please retry later."
        );
      }
      return $return;
    }

    function markOrderSettled(orderItemId, account_id) {
      var $return = "";
      var formData =
        "action=mark_order_settled&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id;
      var s = submitForm(formData, "POST");
      if (s.type == "success") {
        $return = s.type;
      } else {
        UIToastr.init(
          "error",
          "Mark Order Settled",
          "Error marking order settled!! Please retry later."
        );
      }
      return $return;
    }

    function refreshPayout(orderItemId, account_id) {
      var $return = "";
      var formData =
        "action=refresh_payout&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id;
      var s = submitForm(formData, "GET");
      if (s.type == "success") {
        $return = s.type;
      } else {
        UIToastr.init(
          "error",
          "Refresh Payout Details",
          "Error refreshing payout details!! Please retry later."
        );
      }
      return $return;
    }

    function refetchBillingDetails(orderItemId, account_id) {
      var $return = "";
      var formData =
        "action=refetch_billing_details&orderItemId=" +
        orderItemId +
        "&account_id=" +
        account_id;
      var s = submitForm(formData, "GET");
      if (s.type == "success") {
        UIToastr.init(s.type, "Refresh Billing Details", s.msg);
        $return = s.type;
      } else {
        UIToastr.init(
          "error",
          "Refresh Billing Details",
          "Error refreshing payout details!! Please retry later."
        );
      }
      return $return;
    }

    function update_notes(orderItemId, notes, incidentId, account_id) {
      var formData =
        "action=update_settlement_notes&orderItemId=" +
        orderItemId +
        "&settlementNotes=" +
        notes;
      if (typeof incidentId !== "undefined" || $incidentId != "")
        formData += "&incidentId=" + incidentId + "&accountId=" + account_id;
      var s = submitForm(formData, "POST");
      if (s.type == "success") {
        UIToastr.init(s.type, "Update Notes", s.msg);
      } else {
        UIToastr.init(
          "error",
          "Update Notes",
          "Error updating transaction notes!! Please retry later."
        );
      }
    }

    function order_import_payments_handleValidation() {
      var form1 = $("#payment-import");
      var error1 = $(".alert-danger", form1);
      var success1 = $(".alert-success", form1);

      form1.validate({
        errorElement: "span", //default input error message container
        errorClass: "help-block", // default input error message class
        focusInvalid: false, // do not focus the last invalid input
        ignore: "",
        rules: {
          orders_csv: {
            required: true,
          },
          account_id: {
            required: true,
          },
        },

        invalidHandler: function (event, validator) {
          //display error alert on form submit
          error1.show();
          App.scrollTo(error1, -200);
        },

        highlight: function (element) {
          // hightlight error inputs
          $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
        },

        unhighlight: function (element) {
          // revert the change done by hightlight
          $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
        },

        success: function (label) {
          label.closest(".form-group").removeClass("has-error"); // set success class to the control group
        },

        errorPlacement: function (error, element) {
          if (element.attr("name") == "orders_csv") {
            error.appendTo("#orders_csv_error");
          } else {
            error.appendTo(element.parent("div"));
          }
        },

        submitHandler: function (form) {
          error1.hide();

          $(".form-actions .btn-success", form1).attr("disabled", true);
          $(".form-actions i", form1).addClass("fa fa-sync fa-spin");

          var account_id = $("#account_id option:selected", form1).val();
          var formData = new FormData();
          formData.append("action", "import_payment");
          formData.append("orders_csv", $("#orders_csv")[0].files[0]);
          formData.append("account_id", account_id);

          $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime(),
            cache: false,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            mimeType: "multipart/form-data",
            async: false,
            success: function (s) {
              s = $.parseJSON(s);
              var string = s.total + " Total Orders. ";
              if (s.success != 0) {
                string += s.success + " Orders Successfully added.";
              }
              if (s.existing != 0) {
                string += s.existing + " Orders already exists.";
              }
              if (s.existing != 0) {
                string += s.updated + " Orders Successfully updated.";
              }
              if (s.skipped != 0) {
                string += s.skipped + " Orders skipped.";
              }
              success1.show().text(string);
              setTimeout(function () {
                $("#import_payment_sheet").modal("hide");
                // reset the form and alerts
                $("#account_id", form1).select2("val", "");
                $(form1)[0].reset();
                success1.hide().text("");
                error1.hide().text("");
              }, 2000);
              $(".form-actions .btn-success", form1).attr("disabled", false);
              $(".form-actions i", form1).removeClass("fa fa-sync fa-spin");
            },
            error: function () {
              // NProgress.done(true);
              UIToastr.init(
                "error",
                "Import Payments",
                "Error importing payments details!! Please retry later."
              );
            },
          });
        },
      });
    }

    function export_to_claim_orders() {
      $("#export_to_claim_orders").click(function () {
        var form = document.createElement("form");
        form.setAttribute("method", "post");
        form.setAttribute("action", "ajax_load.php");
        form.setAttribute("target", "_blank");

        // form._submit_function_ = form.submit;

        var params = {
          action: "export_to_claim_orders",
        };

        for (var key in params) {
          if (params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
          }
        }

        // window.open('report.html', 'formresult', 'scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no');

        document.body.appendChild(form);
        // form._submit_function_();
        form.submit();
      });
    }

    function export_unsettled_orders() {
      $("#export_unsettled_orders").click(function () {
        var form = document.createElement("form");
        form.setAttribute("method", "post");
        form.setAttribute("action", "ajax_load.php");
        form.setAttribute("target", "_blank");

        // form._submit_function_ = form.submit;

        var params = {
          action: "export_unsettled_orders",
        };

        for (var key in params) {
          if (params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
          }
        }

        // window.open('report.html', 'formresult', 'scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no');

        document.body.appendChild(form);
        // form._submit_function_();
        form.submit();
      });
    }

    function search_payments() {
      $("#payment_search_payments").addClass("hide");
      $(".search_payments").submit(function (e) {
        e.preventDefault();
        if (
          $(".search_value").val() == "" ||
          $(".search_by :selected").val() == ""
        )
          return;
        var data = $(this).serialize();
        payments_handleTable("search_payments", "&" + data);
        $("#payment_search_payments").removeClass("hide");
      });

      var search_by = App.getURLParameter("search_by");
      var search_value = App.getURLParameter("search_value");
      if (
        typeof search_by !== "undefined" &&
        typeof search_value !== "undefined"
      ) {
        $(".search_payments .search_value").val(search_value);
        if (search_by == "neft_id") {
          $(".search_payments .search_by").val("p.paymentId").trigger("change");
        }
        $(".search_payments").submit();
      }
    }

    var a_options = "<option value=''></option>";
    var accountMenu = $(".account_id");

    // Append Marketplace and Account details
    $.each(accounts, function (account_k, account) {
      if (account_k == "flipkart") {
        $.each(account, function (k, v) {
          a_options +=
            "<option value=" +
            v.account_id +
            ">" +
            v.account_name +
            "</option>";
        });
      }
    });
    accountMenu.empty().append(a_options);

    // IMPORT PAYMENTS SHEET
    order_import_payments_handleValidation();

    // REGISTER TO CLAIM & UNSETTLED ORDERS
    export_to_claim_orders();
    export_unsettled_orders();

    // Init Table
    initTable_payments();
  }

  return {
    //main function to initiate the module
    init: function ($type) {
      switch ($type) {
        case "fk_scan_ship":
          scanShip_handleInit();
          break;

        case "payments":
          payments_handleInit();
      }
    },
  };
})();

var handler = "";
if (window.location.href.indexOf("orders") !== -1) handler = "order";
if (window.location.href.indexOf("returns") !== -1) handler = "return";
if (window.location.href.indexOf("fk_dashboard") !== -1)
  handler = "fk_dashboard";
if (window.location.href.indexOf("fk_wrong_skus") !== -1)
  handler = "fk_wrong_sku";

if (handler == "fk_dashboard") {
  var initTable = function () {
    if (!jQuery().dataTable) {
      return;
    }

    console.log("initTable");
    $type = "reports";
    loadAjaxData($type);

    // Order Status Tabs
    $(".dashboard_type a").click(function () {
      if ($(this).parent().attr("class") == "active") {
        return;
      }
      $tab = $(this).attr("href");
      $type = $tab.substr($tab.indexOf("_") + 1);
      console.log($type);

      var tables = $.fn.dataTable.fnTables(true);
      $(tables).each(function () {
        $(this).dataTable().fnDestroy();
      });

      if ($type == "promotions") {
        loadajaxCalender($type);
      } else {
        loadAjaxData($type);
        // REQUESTS
        // window['request_' + $type]();
        eval("request_" + $type + "()");
      }
    });
  };

  var loadAjaxData = function ($type) {
    if (jQuery().dataTable) {
      var tables = $.fn.dataTable.fnTables(true);
      $(tables).each(function () {
        $(this).dataTable().fnDestroy();
      });
    }

    console.log("loadajaxData");
    // if ($type == 'promotions'){
    // 	$table_format = '<"row" <"col-md-4 col-sm-12" l><"col-md-8 col-sm-12 dataTables_length" <"#filters.navbar-right filter-panel"> <"#reload.navbar-right"> f>>rt<"table-scrollable" <"col-md-5 col-sm-12" i><"col-md-7 col-sm-12" p>>';
    // } else {
    $table_format =
      '<"row" <"col-md-4 col-sm-12" l><"col-md-8 col-sm-12 dataTables_length" <"#filters.navbar-right filter-panel"> <"#reload.navbar-right"> <"#create_report.navbar-right"> f>>rt<"table-scrollable" <"col-md-5 col-sm-12" i><"col-md-7 col-sm-12" p>>';
    // }

    console.log("dashboard_" + $type);
    var table = $("#dashboard_" + $type);
    var oTable = table.DataTable({
      lengthMenu: [
        [20, 50, 100, 200, 500, -1],
        [20, 50, 100, 200, 500, "All"], // change per page values here
      ],
      // set the initial value
      pageLength: 20,
      pagingType: "bootstrap_full_number",
      language: {
        emptyTable: "No Reports Found",
        lengthMenu: "  _MENU_ records per page",
        paginate: {
          previous: "Prev",
          next: "Next",
          last: "Last",
          first: "First",
        },
      },
      processing: true,
      // "bSort": false,
      ordering: false,
      bDestroy: true,
      columnDefs: [
        {
          orderable: false,
          targets: "_all",
        },
      ],
      fixedHeader: {
        headerOffset: 40,
      },
      sDom: $table_format,
      ajax: {
        url:
          "ajax_load.php?action=get_all_" +
          $type +
          "&token=" +
          new Date().getTime(),
        type: "GET",
        cache: false,
      },
      drawCallback: function () {
        $(".dataTables_paginate a").bind("click", function () {
          App.scrollTop();
        });

        if ($type == "reports") {
          get_report_status();
          process_report();
        }

        // if ($type == 'promotions'){
        // 	console.log($type);
        // 	console.log('promo-ajaxLoad');
        // 	promotion_opt_in();
        // 	get_promotion_lid(table);
        // }
      },
      createdRow: function (row, data, dataIndex) {
        // if ($type == 'unsettled' || $type == 'disputed' || $type == 'upcoming'){
        // 	var a = $(row).find('td:eq(1)').html();
        // 	$( row ).attr('id', a).addClass('clickable');
        // 	$( row ).find('td:eq(8)').addClass('return_hide_column');
        // }
      },
      // initComplete: function () {
      // 	console.log($(this));
      // 	$(this).api().columns().every( function () {
      // 		var column = this;
      // 		var select = $('<select><option value=""></option></select>')
      // 			.appendTo( $(column.footer()).empty() )
      // 			.on( 'change', function () {
      // 				var val = $.fn.dataTable.util.escapeRegex(
      // 					$(this).val()
      // 				);

      // 				column
      // 					.search( val ? '^'+val+'$' : '', true, false )
      // 					.draw();
      // 			} );

      // 		column.data().unique().sort().each( function ( d, j ) {
      // 			select.append( '<option value="'+d+'">'+d+'</option>' )
      // 		} );
      // 	} );
      // }
    });

    // if ($type == "settlements"){
    // 	table.order( [ 0, 'desc' ] ).draw();
    // } else {
    // 	table.order( [[ 4, 'asc' ], [ 8, 'asc']] ).draw();
    // }

    $("#create_report").html(
      '<a href="#" data-target="#generate_report" id="generate_report" role="button" class="btn btn-default generate_report" data-toggle="modal"><i class="fa fa-plus"></i></a>'
    );
    $("#reload").html(
      '<a href="#" class="btn btn-default reload"><i class="fa fa-sync"></i></a>'
    );
    $("#reload").on("click", function (e) {
      // off on to restrict multiple occurence of events registration
      e.preventDefault();
      $(".btn").attr("disabled", true);
      $(".get_promotion_lid, .promotion_opt_in").attr("disabled", true);
      oTable.ajax.reload();
      console.log("reload");
      $(".btn").attr("disabled", false);
    });

    var tableWrapper = jQuery("#dashboard_" + $type + "_wrapper");

    var filters = {
      account: {
        type: "select",
        label: "Account",
        class: "account_name",
        search_index: 0, // index of the dataTable to seach for
        options: accounts.flipkart,
      },
      payment_type: {
        type: "select",
        label: "Payments Type",
        class: "payment_type",
        search_index: 2, // index of the dataTable to seach for
        options: ["Settled Transactions", "Unsettled Tranasctions"],
      },
      status: {
        type: "select",
        label: "Status",
        class: "status",
        search_index: 6, // index of the dataTable to seach for
        options: ["Queued", "Generated", "Completed"],
      },
    };

    handleFilters(filters, oTable);
    request_reports();
  };

  var loadajaxCalender = function ($type) {
    console.log("loadajaxCalender");
    $("#calendar").html("<center><i class='fa fa-sync fa-spin'></i></center>");
    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      // cache: false,
      type: "GET",
      data: "action=get_all_promotions",
      success: function (s) {
        $promo = $.parseJSON(s);
        promotion_opt_in();
        promotion_opt_in_manual();
        promotion_re_opt_in_manual();
        promotion_re_opt_in();
        get_promotion_lid();

        $(".pre_sale_starts_picker").datetimepicker({
          isRTL: App.isRTL(),
          format: "M dd, yyyy - HH:ii P", //Feb 10 2021 - 08:00 PM
          // format: "dd.mm.yyyy hh:ii",
          showMeridian: true,
          autoclose: true,
          pickerPosition: App.isRTL() ? "bottom-right" : "bottom-left",
          todayBtn: true,
        });

        var calendarEl = document.getElementById("calendar");
        // var defaultDate = '2019-05-04';
        var c_date = Math.floor(new Date().getTime());
        var cu_date = new Date().getTime() - 18 * 1000;
        console.log(c_date);
        console.log(cu_date);

        var calendar = new FullCalendar.Calendar(calendarEl, {
          schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
          // timeZone: 'local',
          plugins: ["resourceTimeline", "timeGrid", "bootstrap"],
          themeSystem: "bootstrap",
          height: "parent",
          customButtons: {
            refreshButton: {
              bootstrapFontAwesome: "fa-sync",
              click: function () {
                loadajaxCalender();
              },
            },
          },
          header: {
            left: "today prev,next",
            center: "title",
            right: "resourceTimelineDay, resourceTimelineWeek, refreshButton",
          },
          eventRender: function (info) {
            var tooltip = new Tooltip(info.el, {
              title: info.event.extendedProps.description,
              placement: "top",
              trigger: "hover",
              container: "body",
              html: true,
              template:
                '<div class="tooltip fullcalendar_tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
            });
          },
          nowIndicator: true,
          aspectRatio: 1.5,
          defaultView: "resourceTimelineWeek",
          slotDuration: "02:00",
          resourceAreaWidth: "35%",
          filterResourcesWithEvents: true,
          resourceColumns: [
            {
              group: true,
              labelText: "OfferId",
              field: "offerId",
            },
            {
              labelText: "Account",
              field: "accountName",
            },
            {
              labelText: "Label",
              field: "type",
            },
          ],
          resources: $promo.resources,
          events: $promo.events,
          firstHour: cu_date,
        });

        $("#calendar").empty();
        calendar.render();

        var slotDuration = calendar.getOption("firstHour");
        console.log(slotDuration);
      },
      error: function () {
        console.log("Error Processing your Request!!");
      },
    });
  };

  var generate_reports_select = function () {
    console.log("generateReportsSelect");
    var reports = {
      Invoices: [
        "Commission Invoice",
        "Commission Invoice Transaction Details",
      ],
      "Payment Reports": ["Settled Transactions"],
      "Tax Reports": ["GSTR return report", "Sales Report", "TDS"],
    };

    var report_type_option = "<option></option>";
    $.each(reports, function (report_type, report) {
      report_type_option +=
        "<option value='" + report_type + "'>" + report_type + "</option>";
    });
    $(".report_type").select2("destroy");
    $(".report_type").empty().append(report_type_option);
    $(".report_type").select2({
      placeholder: "Select Report Type",
      allowClear: true,
    });

    $(".report_type").change(function () {
      var report_sub_type_option = "<option></option>";
      if (this.value != "") {
        var report_sub_type = reports[this.value];
        $.each(report_sub_type, function (index, sub_report) {
          report_sub_type_option +=
            "<option value='" + sub_report + "'>" + sub_report + "</option>";
        });
      }

      $(".report_sub_type").select2("destroy");
      $(".report_sub_type").empty().append(report_sub_type_option);
      $(".report_sub_type").select2({
        placeholder: "Select Sub Report Type",
        allowClear: true,
      });
    });
  };

  var request_reports = function () {
    console.log("requestReport");
    generate_reports_select();

    var form1 = $("#request-report");
    var error1 = $(".alert-danger", form1);
    var success1 = $(".alert-success", form1);

    form1.validate({
      errorElement: "span", //default input error message container
      errorClass: "help-block", // default input error message class
      focusInvalid: false, // do not focus the last invalid input
      ignore: "",
      rules: {
        report_type: {
          required: true,
        },
        report_sub_type: {
          required: true,
        },
        account_id: {
          required: true,
        },
        report_daterange: {
          required: true,
        },
      },
      messages: {
        // custom messages for radio buttons and checkboxes
        account_id: {
          required: "Select Account",
        },
        report_type: {
          required: "Select Report",
        },
        report_sub_type: {
          required: "Select Sub Report",
        },
        report_daterange: {
          required: "Select Date Range",
        },
      },

      invalidHandler: function (event, validator) {
        //display error alert on form submit
        error1.show();
        App.scrollTo(error1, -200);
      },

      highlight: function (element) {
        // hightlight error inputs
        $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
      },

      unhighlight: function (element) {
        // revert the change done by hightlight
        $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
      },

      success: function (label) {
        label.closest(".form-group").removeClass("has-error"); // set success class to the control group
      },

      errorPlacement: function (error, element) {
        if (element.attr("name") == "report_daterange") {
          error.appendTo(element.parent("div").parent("div"));
        } else {
          error.appendTo(element.parent("div"));
        }
      },

      submitHandler: function (form) {
        error1.hide();

        $(".form-actions .btn-success", form1).attr("disabled", true);
        $(".form-actions i", form1).addClass("fa fa-sync fa-spin");

        var account_id = $("#account_id option:selected", form1).val();
        var report_type = $("#report_type option:selected", form1).val();
        var report_sub_type = $(
          "#report_sub_type option:selected",
          form1
        ).val();
        var report_daterange = $("#report_daterange input").val();

        var formData = new FormData();
        formData.append("action", "request_report");
        formData.append("report_type", report_type);
        formData.append("report_sub_type", report_sub_type);
        formData.append("report_date_range", report_daterange);
        formData.append("account_id", account_id);

        console.log(formData);

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          mimeType: "multipart/form-data",
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            $(".form-actions .btn-success", form1).attr("disabled", false);
            $(".form-actions i", form1).removeClass("fa fa-sync fa-spin");
            setTimeout(function () {
              $("#generate_report").modal("hide");
              // reset the form and alerts
              $("#report_type", form1).select2("val", "");
              $("#report_sub_type", form1).select2("val", "");
              $("#account_id", form1).select2("val", "");
              $(form1)[0].reset();
              success1.hide().text("");
              error1.hide().text("");
              $(".reload").trigger("click");
            }, 500);
            UIToastr.init(s.type, "Request Report", s.message);
          },
          error: function () {
            // NProgress.done(true);
            UIToastr.init(
              "error",
              "Import Payments",
              "Error importing payments details!! Please retry later."
            );
          },
        });
      },
    });
  };

  var handleFilters = function (filters, table) {
    var panel = $(".filter-panel");

    jQuery(".dataTables_wrapper #filters").append(
      '<div class="toggler"><i class="fa fa-filter"></i></div><div class="filter-options"></div>'
    );

    var filter_content = "";
    $.each(filters, function (index, value) {
      filter_content +=
        '<div class="filter-option ' +
        value.class +
        '"><span>' +
        value.label +
        ": </span>";
      if (value.type == "select") {
        var options = '<option value="">';
        $.each(value.options, function (op_index, op_value) {
          if (index == "account") {
            op_value = op_value.account_name;
          }
          options += "<option value=" + op_value + ">" + op_value + "</option>";
        });
        filter_content +=
          "<select class='form-control input-inline input-small'>" +
          options +
          "</select></div>";
      }
    });

    jQuery(".filter-options").append(filter_content);

    if (jQuery().select2) {
      $(".page-content select").select2({
        placeholder: "Select",
        allowClear: true,
      });
    }

    // Bind to table for search
    $.each(filters, function (index, value) {
      if (value.type == "select") {
        jQuery(".dataTables_wrapper ." + value.class + " select").bind(
          "change",
          function () {
            table.columns(value.search_index).search(jQuery(this).val()).draw();
            // table.fnDraw();
          }
        );
      }
    });

    $(".toggler").click(function () {
      $(this).toggleClass("open");
      $(".filter-panel > .filter-options").toggle();
    });
  };

  var get_report_status = function () {
    $(".update")
      .off("click")
      .on("click", function (e) {
        // off on to restrict multiple occurence of events registration
        $return = "";
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");
        $reportId = $($this).data("reportid");
        $accountId = $($this).data("accountid");
        $reportType = $($this).data("reporttype");
        $reportSubType = $($this).data("reportsubtype");

        $.ajax({
          url:
            "ajax_load.php?token=" +
            new Date().getTime() +
            "&action=get_report_status&reportId=" +
            $reportId +
            "&accountId=" +
            $accountId +
            "&reportType=" +
            $reportType +
            "&reportSubType=" +
            $reportSubType,
          cache: false,
          type: "POST",
          processData: false,
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            // console.log(s);
            if (s.type == "success") {
              $this.closest("td").prev("td").text("Generated");
              if ($reportType == "Invoices") {
                $this
                  .closest("td")
                  .html(
                    '<a class="download btn btn-default btn-xs purple" data-reportid="' +
                      $reportId +
                      '" data-accountid="' +
                      $accountId +
                      '" data-reporttype="' +
                      $reportType +
                      '" data-reportsubtype="' +
                      $reportSubType +
                      '" data-reportfileformat="' +
                      s.reportFileFormat +
                      '"><i class=""></i> Download</a>'
                  );
              } else {
                $this
                  .closest("td")
                  .html(
                    '<a class="process btn btn-default btn-xs purple" data-reportid="' +
                      $reportId +
                      '" data-accountid="' +
                      $accountId +
                      '"><i class=""></i> Process</a>'
                  );
              }
            }
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
            $return = s;
            UIToastr.init(s.type, "Report Status Update", s.message);
            process_report();
          },
          error: function () {
            UIToastr.init(
              "error",
              "Report Status Update",
              "Error refreshing status details!! Please retry later."
            );
          },
        });
        return $return;
      });
  };

  var process_report = function () {
    $(".process")
      .off("click")
      .on("click", function (e) {
        // off on to restrict multiple occurence of events registration
        $return = "";
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");
        $reportId = $($this).data("reportid");
        $accountId = $($this).data("accountid");
        $reportType = $($this).data("reporttype");
        $reportSubType = $($this).data("reportsubtype");
        $action = "import_payment";
        $warehouse = "";
        if ($reportType == "Flipkart Fulfilment Orders Report") {
          $action = "import_flipkart_fulfilment_orders";
          $warehouse = "&warehouse_id=" + $reportSubType;
        }

        $.ajax({
          url:
            "ajax_load.php?token=" +
            new Date().getTime() +
            "&action=" +
            $action +
            "&reportId=" +
            $reportId +
            "&account_id=" +
            $accountId +
            $warehouse,
          cache: false,
          type: "POST",
          processData: false,
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              $this.closest("td").prev("td").text("Completed");
              $this
                .closest("td")
                .html(
                  '<a class="process btn btn-default btn-xs purple" data-reportid="' +
                    $reportId +
                    '"" data-accountid="' +
                    $accountId +
                    '" data-reporttype="' +
                    $reportType +
                    '" data-reportsubtype="' +
                    $reportSubType +
                    '"><i class=""></i>Re-Process</a>'
                );
              s.message =
                "Total: " +
                s.total +
                "<br/>Success: " +
                s.success +
                "<br/>Existing: " +
                s.existing +
                "<br/>Updated: " +
                s.updated +
                "<br/>Error: " +
                s.error;
            }
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
            $return = s;
            UIToastr.init(
              s.type,
              "Import " + $reportType + " Report Update",
              s.message
            );
          },
          error: function () {
            UIToastr.init(
              "error",
              "Import " + $reportType + " Report Update",
              "Error importing payment details!! Please retry later."
            );
          },
        });
        return $return;
      });

    $(".download")
      .off("click")
      .on("click", function (e) {
        // off on to restrict multiple occurence of events registration
        $return = "";
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");
        $reportId = $($this).data("reportid");
        $accountId = $($this).data("accountid");
        $reportType = $($this).data("reporttype");
        $reportSubType = $($this).data("reportsubtype");
        $reportFileFormat = $($this).data("reportfileformat");
        $action = "download_report";

        $.ajax({
          url:
            "ajax_load.php?token=" +
            new Date().getTime() +
            "&action=" +
            $action +
            "&reportId=" +
            $reportId +
            "&account_id=" +
            $accountId +
            "&reportType=" +
            $reportType +
            "&reportFileFormat=" +
            $reportFileFormat,
          cache: false,
          type: "GET",
          processData: false,
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              $this
                .closest("td")
                .html(
                  '<a class="download btn btn-default btn-xs purple" data-reportid="' +
                    $reportId +
                    '" data-accountid="' +
                    $accountId +
                    '" data-reporttype="' +
                    $reportType +
                    '" data-reportsubtype="' +
                    $reportSubType +
                    '" data-reportfileformat="' +
                    $reportFileFormat +
                    '"><i class=""></i> Download</a>'
                );
              window.open(s.download_url, "_self");
            }
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
            UIToastr.init(s.type, "Download " + $reportType + "", s.message);
          },
          error: function () {
            UIToastr.init(s.type, "Download " + $reportType + "", s.message);
          },
        });
        return $return;
      });
  };

  var promotion_opt_in = function () {
    $("body")
      .off("click", ".promotion_opt_in")
      .on("click", ".promotion_opt_in", function (e) {
        // off on to restrict multiple occurence of events registration
        e.preventDefault();

        $this = $(this);

        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");
        $startDate = $this.data("startdate");
        $endDate = $this.data("enddate");
        $isMPInc = $this.data("ismpinc");
        $offerType = $this.data("offertype");
        $offerValue = $this.data("offervalue");
        $offerValueType = $this.data("offervaluetype");
        $entityType = $this.data("entitytype");
        $mpIncAmt = "";
        $preSaleStarts = "";
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");

        if ($isMPInc == true) {
          $("#confirm_mp_inc_amount").modal("show");
          $(".confirm_inc_amount")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();

              $btn = $(this);
              $mpIncAmt = $("#mp_inc_amount").val();
              $preSaleStarts = moment(
                $("#pre_sale_starts").val(),
                "lll"
              ).format("X");

              $mp_validation = true;
              $date_validation = true;

              if ($preSaleStarts == "") {
                $("#pre_sale_starts")
                  .closest(".form-group")
                  .addClass("has-error");
                $date_validation = false;
              }

              if ($mpIncAmt == "") {
                $("#mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                $mp_validation = false;
              }

              if ($mp_validation && $date_validation) {
                window.setTimeout(function () {
                  $btn.find("i").addClass("fa fa-sync fa-spin");
                  $btn.attr("disabled", true);
                  send_promotion_optin_request(
                    "promotion_opt_in",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $entityType,
                    $this,
                    false
                  );
                  $("#mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                }, 100);
              }
              $btn.attr("disabled", false);
              $btn.find("i").removeClass("fa fa-sync fa-spin");
            });
          $(".close_inc_amount_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else {
          window.setTimeout(function () {
            send_promotion_optin_request(
              "promotion_opt_in",
              $accountId,
              $offerId,
              $startDate,
              $endDate,
              $isMPInc,
              $mpIncAmt,
              $preSaleStarts,
              $offerType,
              $offerValue,
              $offerValueType,
              $entityType,
              $this,
              true
            );
          }, 500);
        }
      });
  };

  var promotion_opt_in_manual = function () {
    $("body")
      .off("click", ".promotion_opt_in_manual")
      .on("click", ".promotion_opt_in_manual", function (e) {
        // off on to restrict multiple occurence of events registration
        e.preventDefault();

        $this = $(this);

        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");
        $startDate = $this.data("startdate");
        $endDate = $this.data("enddate");
        $isMPInc = $this.data("ismpinc");
        $offerType = $this.data("offertype");
        $offerValue = $this.data("offervalue");
        $offerValueType = $this.data("offervaluetype");
        $isManual = $this.data("ismanual");
        $entityType = $this.data("entitytype");
        $mpIncAmt = "";
        $preSaleStarts = "";
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");

        if ($isManual == true) {
          $("#manual_optin").modal("show");
          if ($isMPInc) {
            $(".mp_inc_group").removeClass("hide");
          }
          $(".submit_manual_optin")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();

              $this1 = $(this);

              $("#optin_csv").closest(".form-group").removeClass("has-error");
              $("#manual_mp_inc_amount, #manual_pre_sale_starts")
                .closest(".form-group")
                .removeClass("has-error");

              $optin_csv = $("#optin_csv")[0].files[0];
              $mpIncAmt = $("#manual_mp_inc_amount").val();
              $preSaleStarts = moment(
                $("#manual_pre_sale_starts").val(),
                "lll"
              ).format("X");
              $file_validation = true;
              $date_validation = true;
              $mp_validation = true;

              if (typeof $optin_csv === "undefined" || $optin_csv == "") {
                $("#optin_csv").closest(".form-group").addClass("has-error");
                $file_validation = false;
              }

              if (
                typeof $preSaleStarts === "undefined" ||
                $preSaleStarts == ""
              ) {
                $("#pre_sale_starts")
                  .closest(".form-group")
                  .addClass("has-error");
                $date_validation = false;
              }

              if ($isMPInc && $mpIncAmt == "") {
                $("#manual_mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                $mp_validation = false;
              }

              if ($file_validation && $mp_validation && $date_validation) {
                window.setTimeout(function () {
                  $this1.find("i").addClass("fa fa-sync fa-spin");
                  $this1.attr("disabled", true);

                  $("#manual_mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                  $("#optin_csv")
                    .closest(".form-group")
                    .removeClass("has-error");
                  $("#pre_sale_starts")
                    .closest(".form-group")
                    .removeClass("has-error");
                  send_promotion_optin_request(
                    "promotion_opt_in_manual",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $entityType,
                    $this,
                    false,
                    "",
                    "multipart/form-data"
                  );
                }, 100);
              }
              $this1.attr("disabled", false);
              $this1.find("i").removeClass("fa fa-sync fa-spin");
            });

          $(".close_manual_optin_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else if ($isMPInc == true && $isManual == false) {
          $("#confirm_mp_inc_amount").modal("show");
          $(".confirm_inc_amount")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();

              $btn = $(this);

              $mpIncAmt = $("#mp_inc_amount").val();
              $preSaleStarts = moment(
                $("#manual_pre_sale_starts").val(),
                "lll"
              ).format("X");
              if ($mpIncAmt == "") {
                $("#mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                return;
              } else {
                window.setTimeout(function () {
                  $btn.find("i").addClass("fa fa-sync fa-spin");
                  $btn.attr("disabled", true);
                  send_promotion_optin_request(
                    "promotion_opt_in",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $this,
                    false
                  );
                  $("#mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                }, 500);
              }
              $btn.attr("disabled", false);
              $btn.find("i").removeClass("fa fa-sync fa-spin");
            });
          $(".close_inc_amount_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else {
          window.setTimeout(function () {
            send_promotion_optin_request(
              "promotion_opt_in",
              $accountId,
              $offerId,
              $startDate,
              $endDate,
              $isMPInc,
              $mpIncAmt,
              $preSaleStarts,
              $offerType,
              $offerValue,
              $offerValueType,
              $this,
              true
            );
          }, 500);
        }
      });
  };

  var promotion_opt_out = function () {
    $("body")
      .off("click", ".promotion_opt_out")
      .on("click", ".promotion_opt_out", function (e) {
        // off on to restrict multiple occurence of events registration
        e.preventDefault();

        $return = "";
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");
        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");

        $.ajax({
          url:
            "ajax_load.php?token=" +
            new Date().getTime() +
            "&action=promotion_opt_out&accountId=" +
            $accountId +
            "&offerId=" +
            $offerId,
          cache: false,
          type: "POST",
          processData: false,
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              UIToastr.init("success", "Promotions Opt out", s.message);
            } else {
              UIToastr.init(
                "error",
                "Promotions Opt out",
                "Error Processing your Request!! " + s.message
              );
            }
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
            UIToastr.init(s.type, "Promotions Opt out", s.message);
          },
          error: function () {
            UIToastr.init(
              "error",
              "Promotions Opt out",
              "Error Processing your Request. Please try again later!!! " +
                s.message
            );
          },
        });
        return $return;
      });
  };

  var promotion_re_opt_in = function () {
    $("body")
      .off("click", ".promotion_re_opt_in")
      .on("click", ".promotion_re_opt_in", function (e) {
        // off on to restrict multiple occurence of events registration
        e.preventDefault();

        $this = $(this);

        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");
        $startDate = $this.data("startdate");
        $endDate = $this.data("enddate");
        $isMPInc = $this.data("ismpinc");
        $offerType = $this.data("offertype");
        $offerValue = $this.data("offervalue");
        $offerValueType = $this.data("offervaluetype");
        $entityType = $this.data("entitytype");
        $mpIncAmt = "";
        $preSaleStarts = "";
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");

        if ($isMPInc == true) {
          $("#confirm_mp_inc_amount").modal("show");
          $(".confirm_inc_amount")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();
              $btn = $(this);
              $mpIncAmt = $("#mp_inc_amount").val();
              $preSaleStarts = moment(
                $("#pre_sale_starts").val(),
                "lll"
              ).format("X");

              $mp_validation = true;
              $date_validation = true;

              if ($preSaleStarts == "") {
                $("#pre_sale_starts")
                  .closest(".form-group")
                  .addClass("has-error");
                $date_validation = false;
              }

              if ($mpIncAmt == "") {
                $("#mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                $mp_validation = false;
              }

              if ($mp_validation && $date_validation) {
                window.setTimeout(function () {
                  $btn.find("i").addClass("fa fa-sync fa-spin");
                  $btn.attr("disabled", true);
                  send_promotion_optin_request(
                    "promotion_update",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $entityType,
                    $this,
                    false,
                    "ADD"
                  );
                  $("#mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                }, 500);
              }
              $btn.attr("disabled", false);
              $btn.find("i").removeClass("fa fa-sync fa-spin");
            });
          $(".close_inc_amount_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else {
          window.setTimeout(function () {
            send_promotion_optin_request(
              "promotion_update",
              $accountId,
              $offerId,
              $startDate,
              $endDate,
              $isMPInc,
              $mpIncAmt,
              $preSaleStarts,
              $offerType,
              $offerValue,
              $offerValueType,
              $entityType,
              $this,
              true,
              "ADD"
            );
          }, 500);
        }
      });
  };

  var promotion_re_opt_in_manual = function () {
    console.log("promotion_re_opt_in_manual");
    $("body")
      .off("click", ".promotion_re_opt_in_manual")
      .on("click", ".promotion_re_opt_in_manual", function (e) {
        // off on to restrict multiple occurence of events registration
        console.log("promotion_re_opt_in_manual_click");
        e.preventDefault();

        $this = $(this);

        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");
        $startDate = $this.data("startdate");
        $endDate = $this.data("enddate");
        $isMPInc = $this.data("ismpinc");
        $offerType = $this.data("offertype");
        $offerValue = $this.data("offervalue");
        $offerValueType = $this.data("offervaluetype");
        $entityType = $this.data("entitytype");
        $isManual = $this.data("ismanual");
        $mpIncAmt = "";
        $preSaleStarts = "";
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");

        if ($isManual == true) {
          $("#manual_optin").modal("show");
          if ($isMPInc) {
            $(".mp_inc_group").removeClass("hide");
          }
          $(".submit_manual_optin")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();

              $this1 = $(this);

              $("#manual_mp_inc_amount, #manual_pre_sale_starts")
                .closest(".form-group")
                .removeClass("has-error");
              $("#optin_csv").closest(".form-group").removeClass("has-error");

              $mpIncAmt = $("#manual_mp_inc_amount").val();
              $optin_csv = $("#optin_csv")[0].files[0];
              $preSaleStarts = moment(
                $("#manual_pre_sale_starts").val(),
                "lll"
              ).format("X");
              $file_validation = true;
              $mp_validation = true;

              if (typeof $optin_csv === "undefined" || $optin_csv == "") {
                $("#optin_csv").closest(".form-group").addClass("has-error");
                $file_validation = false;
              }

              if (
                typeof $preSaleStarts === "undefined" ||
                $preSaleStarts == ""
              ) {
                $("#pre_sale_starts")
                  .closest(".form-group")
                  .addClass("has-error");
                $date_validation = false;
              }

              if ($isMPInc && $mpIncAmt == "") {
                $("#manual_mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                $mp_validation = false;
              }

              if ($file_validation && $mp_validation) {
                console.log("ok");
                window.setTimeout(function () {
                  $this1.find("i").addClass("fa fa-sync fa-spin");
                  $this1.attr("disabled", true);
                  $("#manual_mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                  $("#optin_csv")
                    .closest(".form-group")
                    .removeClass("has-error");
                  send_promotion_optin_request(
                    "promotion_update_manual",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $entityType,
                    $this,
                    false,
                    "ADD",
                    "multipart/form-data"
                  );
                }, 100);
              }
              $(this).attr("disabled", false);
              $(this).find("i").removeClass("fa fa-sync fa-spin");
            });
          $(".close_manual_optin_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else if ($isMPInc == true && $isManual == false) {
          $("#confirm_mp_inc_amount").modal("show");
          $(".confirm_inc_amount")
            .off("click")
            .on("click", function (e) {
              // off on to restrict multiple occurence of events registration
              e.preventDefault();

              $btn = $(this);

              $mpIncAmt = $("#mp_inc_amount").val();
              $preSaleStarts = moment(
                $("#manual_pre_sale_starts").val(),
                "lll"
              ).format("X");
              if ($mpIncAmt == "") {
                $("#mp_inc_amount")
                  .closest(".form-group")
                  .addClass("has-error");
                return;
              } else {
                window.setTimeout(function () {
                  $btn.find("i").addClass("fa fa-sync fa-spin");
                  $btn.attr("disabled", true);
                  send_promotion_optin_request(
                    "promotion_update_manual",
                    $accountId,
                    $offerId,
                    $startDate,
                    $endDate,
                    $isMPInc,
                    $mpIncAmt,
                    $preSaleStarts,
                    $offerType,
                    $offerValue,
                    $offerValueType,
                    $entityType,
                    $this,
                    false,
                    "ADD",
                    "multipart/form-data"
                  );
                  $("#mp_inc_amount")
                    .closest(".form-group")
                    .removeClass("has-error");
                }, 500);
              }
              $btn.attr("disabled", false);
              $btn.find("i").removeClass("fa fa-sync fa-spin");
            });
          $(".close_inc_amount_modal").click(function () {
            $this.find("i").removeClass("fa fa-sync fa-spin");
            $this.attr("disabled", false);
          });
        } else {
          window.setTimeout(function () {
            send_promotion_optin_request(
              "promotion_update_manual",
              $accountId,
              $offerId,
              $startDate,
              $endDate,
              $isMPInc,
              $mpIncAmt,
              $preSaleStarts,
              $offerType,
              $offerValue,
              $offerValueType,
              $this,
              true,
              "ADD"
            );
          }, 500);
        }
      });

    /*$('body').off('click', '.promotion_re_opt_in_manual').on('click', '.promotion_re_opt_in_manual', function(e){ // off on to restrict multiple occurence of events registration
			e.preventDefault();

			$this = $(this);

			$accountId = $this.data('accountid');
			$offerId = $this.data('offerid');
			$startDate = $this.data('startdate');
			$endDate = $this.data('enddate');
			$isMPInc = $this.data('ismpinc');
			$offerType = $this.data('offertype');
			$offerValue = $this.data('offervalue');
			$mpIncAmt = "";
			$this.attr('disabled', true);
			$this.find('i').addClass('fa fa-sync fa-spin');

			if ($isMPInc == true){
				$('#confirm_mp_inc_amount').modal('show');
				$(".confirm_inc_amount").off('click').on('click', function(e){ // off on to restrict multiple occurence of events registration
					e.preventDefault();

					$(this).find('i').addClass('fa fa-sync fa-spin');
					$(this).attr('disabled', true);
					$mpIncAmt = $('#mp_inc_amount').val();
					if ($mpIncAmt == ""){
						$('#mp_inc_amount').closest('.form-group').addClass('has-error');
						return;
					} else {
						send_promotion_optin_request('promotion_update', $accountId, $offerId, $startDate, $endDate, $isMPInc, $mpIncAmt, $offerType, $offerValue, $this, false, 'ADD');
						$('#mp_inc_amount').closest('.form-group').removeClass('has-error');
					}
					$(this).attr('disabled', false);
					$(this).find('i').removeClass('fa fa-sync fa-spin');
				});
				$('.close_inc_amount_modal').click(function(){
					$this.find('i').removeClass('fa fa-sync fa-spin');
					$this.attr('disabled', false);
				});
			} else {
				send_promotion_optin_request('promotion_update', $accountId, $offerId, $startDate, $endDate, $isMPInc, $mpIncAmt, $offerType, $offerValue, $this, true, 'ADD');
			}
		});*/
  };

  var send_promotion_optin_request = function (
    $action,
    $accountId,
    $offerId,
    $startDate,
    $endDate,
    $isMPInc,
    $mpIncAmt,
    $preSaleStarts,
    $offerType,
    $offerValue,
    $offerValueType,
    $entityType,
    $this,
    async,
    $updateType = "",
    $mime = ""
  ) {
    var formData = "";
    // if ($mime == "multipart/form-data"){
    var formData = new FormData();
    formData.append("action", $action);
    formData.append("accountId", $accountId);
    formData.append("offerId", $offerId);
    formData.append("startDate", $startDate);
    formData.append("endDate", $endDate);
    formData.append("isMPInc", $isMPInc);
    formData.append("mpIncAmt", $mpIncAmt);
    formData.append("preSaleStarts", $preSaleStarts);
    formData.append("offerType", $offerType);
    formData.append("offerValue", $offerValue);
    formData.append("offerValueType", $offerValueType);
    formData.append("entityType", $entityType);
    formData.append("updateType", $updateType);
    formData.append("optin_csv", $("#optin_csv")[0].files[0]);
    // } else {
    // formData = "action="+$action+"&accountId="+$accountId+"&offerId="+$offerId+"&startDate="+$startDate+"&endDate="+$endDate+"&isMPInc="+$isMPInc+"&mpIncAmt="+$mpIncAmt+"&preSaleStarts="+$preSaleStarts+"&offerType="+$offerType+"&offerValue="+$offerValue+"&updateType="+$updateType;
    // }
    console.log(formData);

    window.setTimeout(function () {
      console.log("submitForm");
      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        cache: false,
        type: "POST",
        data: formData,
        mimeType: $mime,
        contentType: false,
        processData: false,
        async: async,
        success: function (s) {
          s = $.parseJSON(s);
          console.log(s);
          if (s.type == "success") {
            UIToastr.init(s.type, s.title, s.message);
          } else {
            UIToastr.init(
              s.type,
              s.title,
              "Error Processing your Request!! " + s.message
            );
          }
          $this.find("i").removeClass("fa fa-sync fa-spin");
          $this.attr("disabled", false);
          if (
            $action == "promotion_opt_in" ||
            $action == "promotion_opt_in_manual" ||
            $action == "promotion_update_manual"
          ) {
            $this
              .removeClass("promotion_opt_in btn-default")
              .addClass("promotion_opt_out btn-warning");
            $this.text("Opt Out");
            $tr = $("tr").find(
              "[data-resource-id='" + $offerId + ":" + $accountId + "']"
            );
            $tr
              .find("a")
              .css({
                "background-color": "lightgreen",
                "border-color": "lightgreen",
              });
            if (
              s.type == "success" &&
              ($action == "promotion_opt_in_manual" ||
                $action == "promotion_opt_in" ||
                $action == "promotion_update_manual")
            ) {
              $("#confirm_mp_inc_amount").modal("hide");
              $("#manual_optin").modal("hide");
              $("#mp_inc_amount").val("");
              $(".form-horizontal")
                .find(".btn-submit i")
                .removeClass("fa fa-sync fa-spin");
              $(".form-horizontal").find(".btn-submit").attr("disabled", false);
              $(".form-horizontal")
                .find(
                  "input:text, input:password, input:file, select, textarea"
                )
                .val("");
              $(".form-horizontal")
                .find("input:radio, input:checkbox")
                .removeAttr("checked")
                .removeAttr("selected");
            }
          }
          $this.parent().find(".promotion_response").text(s.message);
        },
        error: function () {
          UIToastr.init(
            "error",
            "Promotions Optin Request",
            "Error Processing your Request. Please try again later!!! " +
              s.message
          );
        },
      });
    }, 500);
  };

  var get_promotion_lid = function () {
    $("body")
      .off("click", ".get_promotion_lid")
      .on("click", ".get_promotion_lid", function () {
        // off on to restrict multiple occurence of events registration
        $this = $(this);
        $accountId = $this.data("accountid");
        $offerId = $this.data("offerid");
        $entityType = $this.data("entitytype");
        $opted = $this.data("opted");
        $this.attr("disabled", true);

        var formData = new FormData();
        formData.append("action", "get_promotion_lid");
        formData.append("accountId", $accountId);
        formData.append("offerId", $offerId);
        formData.append("entityType", $entityType);
        formData.append("opted", $opted);

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
              UIToastr.init("success", "Get Eligible Listings", s.message);
              $("#reload").trigger("click");
            } else {
              UIToastr.init(
                "error",
                "Get Eligible Listings",
                "Error Processing your Request!! " + s.message
              );
            }
            $this.attr("disabled", false);
            $this
              .removeClass("get_promotion_lid")
              .addClass("get_promotion_lid");
            if ($opted) {
              $this.html(
                '<i class="fa fa-download"></i> Download Opted Listings</a>&nbsp;'
              );
            } else {
              $this.html(
                '<i class="fa fa-download"></i> Download Listings</a>&nbsp;'
              );
            }
            $this.attr("href", s.file_path);
            $this.attr("target", "_blank");
          },
          error: function () {
            UIToastr.init(
              "error",
              "Get Eligible Listings",
              "Error Processing your Request. Please try again later!!! " +
                s.message
            );
          },
        });
      });
  };

  var initDateRangePickers = function () {
    if (!jQuery().daterangepicker) {
      return;
    }
    console.log("daterangepicker");

    $("#report_daterange")
      .daterangepicker(
        {
          autoApply: true,
          alwaysShowCalendars: true,
          startDate: moment().subtract(1, "month"),
          endDate: moment(),
          ranges: {
            Today: [moment(), moment()],
            Yesterday: [
              moment().subtract(1, "days"),
              moment().subtract(1, "days"),
            ],
            "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "Last 10 Days": [moment().subtract(9, "days"), moment()],
            "Last 30 Days": [moment().subtract(29, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last Month": [
              moment().subtract(1, "month").startOf("month"),
              moment().subtract(1, "month").endOf("month"),
            ],
          },
          minDate: moment().subtract(1, "year"),
          // maxDate: moment(),
        },
        function (start, end) {
          $("#report_daterange input").val(
            start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY")
          );
        }
      )
      .on("change", function () {
        $(this).valid(); // triggers the validation test
      });
  };

  var a_options = "<option value=''></option>";
  var accountMenu = $(".account_id");

  // Append Marketplace and Account details
  $.each(accounts, function (account_k, account) {
    if (account_k == "flipkart") {
      $.each(account, function (k, v) {
        a_options +=
          "<option value=" + v.account_id + ">" + v.account_name + "</option>";
      });
    }
  });
  accountMenu.empty().append(a_options);

  jQuery(document).ready(function ($) {
    // Init Table
    initTable();

    // Date Range picker init
    initDateRangePickers();
  });
} else if (handler == "fk_wrong_sku") {
  // var wrongSKU = function (){
  var wrongSKU_handleTable = function () {
    function restoreRow(oTable, nRow) {
      var aData = oTable.fnGetData(nRow);
      var jqTds = $(">td", nRow);

      for (var i = 0, iLen = jqTds.length; i < iLen; i++) {
        oTable.fnUpdate(aData[i], nRow, i, false);
      }

      oTable.fnDraw();
    }

    function editRow(oTable, nRow) {
      var aData = oTable.fnGetData(nRow);
      var jqTds = $(">td", nRow);

      // jqTds[0].innerHTML = aData[0];
      jqTds[1].innerHTML =
        '<input type="text" class="form-control input-large" value="' +
        aData[1] +
        '">';
      // jqTds[2].innerHTML = aData[2];
      // jqTds[3].innerHTML = aData[3];
      // jqTds[4].innerHTML = aData[4];
      jqTds[5].innerHTML =
        '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i> Save</a>';
      jqTds[6].innerHTML =
        '<a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i> Cancel</a>';

      // Initialize select2me
      $(".selection").select2({
        placeholder: "Select",
        allowClear: true,
      });
    }

    function saveRow(oTable, nRow) {
      var jqInputs = $("input", nRow);

      var aData = oTable.fnGetData(nRow);
      oTable.fnUpdate(jqInputs[0].value, nRow, 1, false);
      // oTable.fnUpdate(jqInputs[1].value, nRow, 4, false);
      // oTable.fnUpdate(jqInputs[2].value, nRow, 5, false);
      oTable.fnUpdate(
        '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-edit"></i> Edit</a>',
        nRow,
        5,
        false
      );
      oTable.fnUpdate(
        '<a class="delete btn btn-default btn-xs purple" href=""><i class="fa fa-trash-o"></i> Delete</a>',
        nRow,
        6,
        false
      );

      var formData = new FormData();
      formData.append("action", "add_wrong_sku");
      formData.append("mp_id", aData[0]);
      formData.append("correct_sku", aData[1]);
      formData.append("wrong_sku", aData[2]);

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        cache: false,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        async: false,
        success: function (s) {
          s = $.parseJSON(s);
          if (s.type == "success") {
            UIToastr.init("success", "Update Wrong SKU", s.message);
          } else {
            error1.text("Error Processing your Request!! " + s.message).show();
            UIToastr.init(
              "success",
              "Update Wrong SKU",
              "Error Processing your Request!! " + s.message
            );
          }
        },
        error: function () {
          UIToastr.init(
            "error",
            "Update Wrong SKU",
            "Error Processing your Request. Please try again later!!!"
          );
        },
      });

      // oTable.api().ajax.reload();
    }

    function cancelEditRow(oTable, nRow) {
      var jqInputs = $("input", nRow);
      oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
      oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
      oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
      // oTable.fnUpdate(jqInputs[3].value, nRow, 3, false);
      oTable.fnUpdate(
        '<a class="edit btn btn-default btn-xs purple" href="">Edit</a>',
        nRow,
        4,
        false
      );
      oTable.fnDraw();
    }

    if (jQuery().dataTable) {
      var tables = $.fn.dataTable.fnTables(true);
      $(tables).each(function () {
        $(this).dataTable().fnDestroy();
      });
    }

    var table = $("#editable_wrong_sku");

    var oTable = table.dataTable({
      lengthMenu: [
        [20, 25, 50, 100, -1],
        [20, 25, 50, 100, "All"], // change per page values here
      ],
      // set the initial value
      pageLength: 50,
      language: {
        lengthMenu: " _MENU_ records",
      },
      bDestroy: true,
      bSort: false,
      ordering: false,
      processing: true,
      deferRender: true,
      // "serverSide": true,
      ajax: {
        url: "ajax_load.php?action=get_wrong_sku&token=" + new Date().getTime(),
        type: "POST",
        cache: false,
      },
      columnDefs: [
        {
          className: "return_hide_column",
          targets: [0],
        },
      ],
      drawCallback: function () {
        // Initialize checkbos for enable/disable user
        // $("[class='in_stock']").bootstrapSwitch();
        // $("[class='is_active']").bootstrapSwitch();
      },
    });

    // Reload Products
    $("#reload-wrongSKU").bind("click", function () {
      oTable.api().ajax.reload();
    });

    var tableWrapper = $("#editable_products_wrapper");

    tableWrapper.find(".dataTables_length select").select2({
      showSearchInput: true, //hide search box with special css class
    }); // initialize select2 dropdown

    var nEditing = null;
    var nNew = false;

    // Delete draft and created PO's
    table.on("click", ".delete", function (e) {
      e.preventDefault();
      if (confirm("Are you sure to delete this row ?") === true) {
        var alias_id = $(this).attr("class").split(" ").pop().split("_").pop();
        var formData = new FormData();
        formData.append("action", "delete_wrong_sku");
        formData.append("alias_id", alias_id);

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          mimeType: "multipart/form-data",
          async: false,
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              UIToastr.init("success", "Delete Wrong SKU", s.message);
            } else {
              error1
                .text("Error Processing your Request!! " + s.message)
                .show();
              UIToastr.init(
                "success",
                "Delete Wrong SKU",
                "Error Processing your Request!! " + s.message
              );
            }
          },
          error: function () {
            UIToastr.init(
              "error",
              "Delete Wrong SKU",
              "Error Processing your Request. Please try again later!!!"
            );
          },
        });
        oTable.api().ajax.reload();
        return;
      }
    });

    table.on("click", ".cancel", function (e) {
      e.preventDefault();

      if (nNew) {
        oTable.fnDeleteRow(nEditing);
        nNew = false;
      } else {
        restoreRow(oTable, nEditing);
        nEditing = null;
      }
    });

    table.on("click", ".edit", function (e) {
      e.preventDefault();

      /* Get the row as a parent of the link that was clicked on */
      var nRow = $(this).parents("tr")[0];

      if (nEditing !== null && nEditing != nRow) {
        /* Currently editing - but not this row - restore the old before continuing to edit mode */
        restoreRow(oTable, nEditing);
        editRow(oTable, nRow);
        nEditing = nRow;
      } else if (
        nEditing == nRow &&
        this.innerHTML == '<i class="fa fa-check"></i> Save'
      ) {
        /* Editing this row and want to save it */
        saveRow(oTable, nEditing);
        nEditing = null;
        oTable.api().ajax.reload();
        // alert("Updated! Do not forget to do some ajax to sync with backend :)");
      } else {
        /* No edit in progress - let's start one */
        editRow(oTable, nRow);
        nEditing = nRow;
      }
    });

    // Reload Purchase Orders
    $("#reload-purchase-orders").bind("click", function (e) {
      e.preventDefault();
      var el = jQuery(this).closest(".portlet").children(".portlet-body");
      App.blockUI({ target: el });
      oTable.api().ajax.reload();
      window.setTimeout(function () {
        App.unblockUI(el);
      }, 500);
    });
  };

  var wrongSKU_handleValidation = function () {
    var form1 = $("#add-wrong-sku");
    var error1 = $(".alert-danger", form1);

    form1.validate({
      errorElement: "span", //default input error message container
      errorClass: "help-block", // default input error message class
      focusInvalid: false, // do not focus the last invalid input
      ignore: "",
      rules: {
        account_id: {
          required: true,
        },
        mp_id: {
          required: true,
        },
        wrong_sku: {
          required: true,
        },
        correct_sku: {
          required: true,
        },
      },

      invalidHandler: function (event, validator) {
        //display error alert on form submit
        error1.show();
        App.scrollTo(error1, -200);
      },

      highlight: function (element) {
        // hightlight error inputs
        $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
      },

      unhighlight: function (element) {
        // revert the change done by hightlight
        $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
      },

      success: function (label) {
        label.closest(".form-group").removeClass("has-error"); // set success class to the control group
      },

      errorPlacement: function (error, element) {
        error.appendTo(element.parent("div"));
      },

      submitHandler: function (form) {
        error1.hide();
        var wrong_sku = $("#wrong_sku").val();
        var correct_sku = $("#correct_sku").val();
        var mp_id = $("#mp_id").val();

        $(".form-actions .btn-success", form1).attr("disabled", true);
        $(".form-actions i", form1).addClass("fa-sync fa-spin");

        var formData = new FormData();
        formData.append("action", "add_wrong_sku");
        formData.append("wrong_sku", wrong_sku);
        formData.append("correct_sku", correct_sku);
        formData.append("mp_id", mp_id);

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          mimeType: "multipart/form-data",
          async: true,
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              $("#add_wrong_sku").modal("hide");
              wrongSKU_handleTable();
              UIToastr.init("success", "Add Wrong SKU", s.message);
              // Reset all the inputs and select2
              $(form1)[0].reset();
              $("select.mp_id").val("").attr("disabled", true);
              $("select.account_id").val("").trigger("change");
            } else {
              error1
                .text("Error Processing your Request!! " + s.message)
                .show();
              UIToastr.init(
                "error",
                "Add Wrong SKU",
                "Error Processing your Request!! " + s.message
              );
            }
          },
          error: function () {
            UIToastr.init(
              "error",
              "Add Wrong SKU",
              "Error Processing your Request. Please try again later!!!"
            );
          },
        });

        $(".form-actions .btn-success", form1).attr("disabled", false);
        $(".form-actions i", form1).removeClass("fa-sync fa-spin");
      },
    });
  };

  jQuery(document).ready(function ($) {
    // LOAD ACCOUNTS DETAILS FOR ADD NEW
    var accountMenu = $(".account_id");
    var a_options = "<option value=''></option>";
    // var a_options = [];

    // Append Marketplace and Account details
    $.each(accounts, function (account_k, account) {
      if (account_k == "flipkart") {
        $.each(account, function (k, v) {
          a_options +=
            "<option value=" +
            v.account_id +
            ">" +
            v.account_name +
            "</option>";
          // a_options[v.account_id] = v.account_name;
        });
      }
    });

    accountMenu.empty().append(a_options);

    $sel_options = {
      parent: {
        placeholder: "Select Account",
        allowClear: true,
      },
      child: {
        placeholder: "Select FSN",
        allowClear: true,
      },
    };
    cascadLoading = new Select2Cascade(
      $("select.account_id"),
      $("select.mp_id"),
      "ajax_load.php?action=get_account_fsn&account_id=:parentId:",
      $sel_options
    );
    cascadLoading.then(function (parent, child, items) {
      if (items.length != 0) {
        child.select2($sel_options.child).on("change", function () {
          $("#wrong_sku").val("");
          $toFind = $(this).val();
          items.filter(function (el) {
            if (el.key == $toFind) {
              $("#wrong_sku").val(el.sku);
            }
          });
        });

        // Open the child listbox immediately
        child.select2("open");
      } else {
        child.prop("disabled", true);
      }
    });

    // LOAD TABLE DATA
    wrongSKU_handleTable();
    wrongSKU_handleValidation();
  });
} else if (handler == "order" || handler == "return") {
  jQuery(document).ready(function ($) {
    var currentRequest = null;
    var currentRequester = null;
    var count_s = 0;
    var count_e = 0;
    var s_count_s = 0;
    var s_count_e = 0;
    var c_count_s = 0;
    var c_count_e = 0;
    var r_count_s = 0;
    var r_count_e = 0;
    var rtd_trackingIds = [];
    var shipped_trackingIds = [];
    var cancel_trackingIds = [];
    var received_trackingIds = [];
    var return_trackingIds = [];
    var search_return_trackingIds = [];
    var a_options = "<option value=''></option>";
    var accountMenu = $(".account_id");
    var spf_files = [];

    refreshCount();
    loadajaxOrders();
    if (handler == "return") loadReconciliation();

    $(".alert-success").hide();
    $(".alert-danger").hide();

    // Append Marketplace and Account details
    $.each(accounts, function (account_k, account) {
      if (account_k == "flipkart") {
        $.each(account, function (k, v) {
          a_options +=
            "<option value=" +
            v.account_id +
            ">" +
            v.account_name +
            "</option>";
        });
      }
    });
    accountMenu.empty().append(a_options);
    $("select.account_id").select2();

    // Order Status Tabs
    $(".order_type a").click(function () {
      if ($(this).parent().attr("class") == "active") {
        return;
      }
      $(".order_content").empty();
      $tab = $(this).attr("href");
      $type = $tab.substr($tab.indexOf("_") + 1);
      // $(tab+" tbody").html(" ");
      $("#update_status").attr("data-activetab", $tab);
      refreshCount();
      loadajaxOrders($type, $tab);
    });

    // $(".buyer-details").bind('click', function(){
    // 	console.log(this);
    // 	$(this+" .buyer-mobile").show();
    // });

    // GENERATORS & PACKER
    $("#portlet_new a, #portlet_packing a, #portlet_rtd a").click(function (e) {
      $tab = $(this).attr("id");
      $type = $tab.substr($tab.indexOf("_") + 1);
      $location = $(this).closest("div.active").attr("id");
      $location_type = $location.substr($location.indexOf("_") + 1);

      // SKIP IF THIS
      if ($type == "rtd_single" || $type == "shipped_single") {
        return;
      }

      var shipment_ids = [];
      $('input[name="orderItemId"]:checked').each(function () {
        shipment_ids.push($(this).val());
      });
      //($type != "label" || $type != "invoice" || $type != "form" || $type != "invoice-form") &&
      if (
        $type != "packlist" &&
        $type != "packlist-fbf" &&
        shipment_ids.length == 0
      ) {
        alert("No Orders Selected");
        return;
      }

      var orders = shipment_ids.length - 1;

      if ($type == "to_pack") {
        e.preventDefault();
        NProgress.configure({ trickle: false });
        NProgress.start();
        $("#mark_to_pack i").addClass("fa fa-sync fa-spin");
        $(".alert-success").html("");
        $(".alert-danger").html("");

        var timesRun = 0;
        var interval = setInterval(function () {
          timesRun += 1;
          if (timesRun === orders) {
            clearInterval(interval);
          }
          NProgress.inc((1 / orders) * 20);
        }, 4000);

        currentRequest = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: "action=mark_to_pack&shipment_ids=" + shipment_ids,
          success: function (s) {
            s = $.parseJSON(s);
            refreshCount();
            loadajaxOrders($location_type, "#" + $location);
            resetCheckbox();
            $("#mark_to_pack").attr("disabled", false);
            $("#mark_to_pack i").removeClass("fa fa-sync fa-spin");
            if (s.success != 0) {
              // $('.alert-success').show().delay(10000).fadeOut('slow');
              $(".alert-success").append(
                "Moved " + s.success + " orders to Pack."
              );
            }
            if (s.error != 0) {
              $(".alert-danger").show().delay(10000).fadeOut("slow");
              $(".alert-danger").append(
                "Unable to update " + s.error + " orders."
              );
            }
            clearInterval(interval);
            NProgress.done(true);
          },
          error: function () {
            clearInterval(interval);
            NProgress.done(true);
            alert("Error Processing your Request!!");
          },
        });
      } else if ($type == "to_cancel") {
        e.preventDefault();
        $("#mark_to_cancel").attr("disabled", true);
        $("#mark_to_cancel i").addClass("fa fa-sync fa-spin");
        currentRequest = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: "action=mark_to_cancel&shipment_ids=" + shipment_ids,
          success: function (s) {
            s = $.parseJSON(s);
            refreshCount();
            loadajaxOrders($location_type, "#" + $location);
            resetCheckbox();
            $("#mark_to_cancel").attr("disabled", false);
            $("#mark_to_cancel i").removeClass("fa fa-sync fa-spin");
            if (s.success != 0) {
              $(".alert-success").show().delay(10000).fadeOut("slow");
              $(".alert-success").append(
                "Moved " + s.success + " orders to Pack."
              );
            }
            if (s.error != 0) {
              $(".alert-danger").show().delay(10000).fadeOut("slow");
              $(".alert-danger").append(
                "Unable to update " + s.error + " orders."
              );
            }
          },
          error: function () {
            // NProgress.done(true);
            alert("Error Processing your Request!!");
          },
        });
      } else if ($type == "create_labels") {
        e.preventDefault();
        NProgress.configure({ trickle: false });
        NProgress.start();
        $("#mark_create_labels").attr("disabled", true);
        $("#mark_create_labels i").addClass("fa fa-sync fa-spin");
        $(".alert-success").html("");
        $(".alert-danger").html("");

        var tab = "#" + $($location + " table").prop("id");
        var timesRun = 0;
        var interval = setInterval(function () {
          timesRun += 1;
          if (timesRun === orders) {
            clearInterval(interval);
          }
          NProgress.inc((1 / orders) * 20);
        }, 4000);

        currentRequest = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: "action=create_labels&shipment_ids=" + shipment_ids,
          success: function (s) {
            s = $.parseJSON(s);
            // refreshCount();
            // loadajaxOrders($location_type, '#' + $location);
            // resetCheckbox();
            $("#mark_create_labels").attr("disabled", false);
            $("#mark_create_labels i").removeClass("fa fa-sync fa-spin");
            if (s.success != 0) {
              // $('.alert-success').show().delay(10000).fadeOut('slow');
              // $('.alert-success').append('Label Generated for '+s.success+' orders.');
              UIToastr.init(
                "success",
                "Label Generation",
                "Successfully generated labels for " + s.success + " orders."
              );

              $("#mark_to_pack i").addClass("fa fa-sync fa-spin");
              $("#mark_to_pack").attr("disabled", true);
              currentRequest = $.ajax({
                url: "ajax_load.php?token=" + new Date().getTime(),
                cache: false,
                type: "POST",
                data:
                  "action=mark_to_pack&shipment_ids=" + s.success_shipment_ids,
                success: function (s) {
                  s = $.parseJSON(s);
                  // update_checked_c(tab);
                  refreshCount();
                  loadajaxOrders($location_type, "#" + $location);
                  $("#mark_to_pack").attr("disabled", false);
                  $("#mark_to_pack i").removeClass("fa fa-sync fa-spin");
                  if (s.success != 0) {
                    // $('.alert-success').show().delay(10000).fadeOut('slow');
                    // $('.alert-success').append();
                    UIToastr.init(
                      "success",
                      "Mark to Pack",
                      "Moved " + s.success + " orders to Pack."
                    );
                  }
                  if (s.error != 0) {
                    // $('.alert-danger').show().delay(10000).fadeOut('slow');
                    // $('.alert-danger').append('Unable to update '+s.error+' orders.');
                    UIToastr.init(
                      "success",
                      "Mark to Pack",
                      "Unable to update " + s.error + " orders."
                    );
                  }
                  clearInterval(interval);
                  NProgress.done(true);
                },
                error: function () {
                  clearInterval(interval);
                  NProgress.done(true);
                  UIToastr.init(
                    "error",
                    "Mark to Pack",
                    "Error Processing your Request! <br />Please try again."
                  );
                  // alert('Error Processing your Request!!');
                },
              });
            }
            if (s.error != 0) {
              UIToastr.init(
                "error",
                "Label Generation",
                "Unable to generate labels for " +
                  s.error +
                  " orders." +
                  s.error_details
              );
            }
            // update_checked_c(tab);
            clearInterval(interval);
            NProgress.done(true);
          },
          error: function () {
            clearInterval(interval);
            NProgress.done(true);
            console.log("Error Processing your Request!!");
            UIToastr.init(
              "error",
              "Label Generation",
              "Error Processing your Request! <br />Please try again."
            );
          },
        });
      } else if ($type == "tracking") {
        e.preventDefault();
        $("#get_tracking").attr("disabled", true);
        $("#get_tracking i").addClass("fa fa-sync fa-spin");
        currentRequest = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: "action=get_tracking&shipment_ids=" + shipment_ids,
          beforeSend: function () {
            if (currentRequest != null) {
              currentRequest.abort();
            } else {
              currentRequest = currentRequest;
            }
          },
          success: function (s) {
            refreshCount();
            loadajaxOrders($location_type, "#" + $location);
            $("#get_tracking").attr("disabled", false);
            $("#get_tracking i").removeClass("fa fa-sync fa-spin");
          },
          error: function () {
            $("#get_tracking").attr("disabled", false);
            $("#get_tracking i").removeClass("fa fa-sync fa-spin");
            alert("Error Processing your Request!!");
          },
        });
      } else if ($type == "packlist") {
        generate_packlist("NON_FBF", "");
      } else if ($type == "packlist-fbf") {
        generate_packlist("FBF_LITE", "");
      } else if (
        $type == "label" ||
        $type == "invoice" ||
        $type == "form" ||
        $type == "label-invoice"
      ) {
        generate_labels(shipment_ids, $type);
      }
    });

    // ORDER SYNC
    $("#order_sync").click(function () {
      $("#order_sync").attr("disabled", true);
      $("#order_sync i").addClass("fa fa-sync fa-spin");
      sync_orders(currentRequest);
    });

    $("#order_replacements").on("show.bs.modal", function (event) {
      get_replacement_orders();
    });

    $("#order_duplicate").on("show.bs.modal", function (event) {
      get_duplicate_orders();
    });

    $("#return_delivery_breached").on("show.bs.modal", function (event) {
      get_return_delivery_breached_orders();
    });

    // UPDATE ORDERS
    $("#update_status").click(function () {
      var order_item_ids = [];
      $('input[name="orderItemId"]:checked').each(function () {
        order_item_ids.push($(this).val());
      });
      if (order_item_ids.length == 0) {
        alert("No Orders Selected");
        return;
      }
      update_status(order_item_ids);
    });

    // MARK RTD
    $("#mark_rtd")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        // RESET
        count_s = 0;
        count_e = 0;
        rtd_trackingIds = [];
        clear_model();
        $("#trackin_id").val("");
        $("#rtd_account_name").val("");
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        refreshCount();
        loadajaxOrders("packing", "#portlet_packing");
      });

    $("#mark-rtd").submit(function (e) {
      e.preventDefault();
      $(".form-group").removeClass("has-error");

      $trackin_id = $.trim($("#trackin_id").val().toUpperCase());
      $account = $("#rtd_account_name").val();

      if ($account == "") {
        $(".form-group").addClass("has-error");
        return;
      }
      if ($trackin_id == "") {
        return;
      }

      $("#trackin_id").val("");

      if (rtd_trackingIds.indexOf($trackin_id) === 0) {
        return;
      }

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data:
          "action=mark_rtd&trackin_id=" + $trackin_id + "&account=" + $account,
        success: function (s) {
          var as = $.parseJSON(s);
          $(".list-container").show();
          $.each(as, function (k, s) {
            if (s.type == "success") {
              $("ul.success-list").append(s.content);
              count_s = count_s + 1;
              $(".scan-passed").html(
                count_s +
                  '<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>'
              );
              if (rtd_trackingIds.indexOf($trackin_id) === -1) {
                rtd_trackingIds.push($trackin_id);
              }
            } else {
              $("ul.failed-list").append(s.content);
              count_e = count_e + 1;
              $(".scan-failed-count").html(
                count_e +
                  '<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
              );
              if (rtd_trackingIds.indexOf($trackin_id) === -1) {
                rtd_trackingIds.push($trackin_id);
              }
            }
          });
          // console.log(rtd_trackingIds);
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // MARK SHIPPED
    $("#mark_shipped")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        // RESET
        s_count_s = 0;
        s_count_e = 0;
        shipped_trackingIds = [];
        clear_model();
        $("#ship_trackin_id").val("");
        $("#ship_account_name").val("");
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        refreshCount();
        loadajaxOrders("rtd", "#portlet_rtd");
      });

    $("#mark-shipped").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");

      $trackin_id = $.trim($("#ship_trackin_id").val().toUpperCase());
      $account = $("#ship_account_name").val();
      $account = typeof $account !== "undefined" ? $account : "";

      if ($account == "") {
        $(".control-group").addClass("error");
        return;
      }
      if ($trackin_id == "") {
        return;
      }

      $("#ship_trackin_id").val("");

      if (shipped_trackingIds.indexOf($trackin_id) === 0) {
        return;
      } else {
        shipped_trackingIds.push($trackin_id);
      }

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data:
          "action=mark_shipped&trackin_id=" +
          $trackin_id +
          "&account=" +
          $account,
        success: function (s) {
          s = $.parseJSON(s);
          $(".list-container").show();
          if (s.type == "success") {
            $(".success-container ul").append(s.content);
            s_count_s = s_count_s + 1;
            $(".scan-passed").html(
              s_count_s +
                '<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>'
            );
            if (shipped_trackingIds.indexOf($trackin_id) === -1) {
              shipped_trackingIds.push($trackin_id);
            }
          } else {
            $(".failed-container ul").append(s.content);
            s_count_e = s_count_e + 1;
            $(".scan-failed-count").html(
              s_count_e +
                '<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
            );
            if (shipped_trackingIds.indexOf($trackin_id) === -1) {
              shipped_trackingIds.push($trackin_id);
            }
          }
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // MARK CANCELLED
    $("#mark_cancelled")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        // RESET
        c_count_s = 0;
        c_count_e = 0;
        cancel_trackingIds = [];
        clear_model();
        $("#cancel_trackin_id").val("");
        $("#cancel_account_name").val("");
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        refreshCount();
        loadajaxOrders("shipped", "#portlet_shipped");
      });

    $("#mark-cancelled").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");

      $trackin_id = $.trim($("#cancel_trackin_id").val().toUpperCase());
      $account = $("#cancel_account_name").val();
      $account = typeof $account !== "undefined" ? $account : "";

      if ($account == "") {
        $(".control-group").addClass("error");
        return;
      }
      if ($trackin_id == "") {
        return;
      }

      $("#cancel_trackin_id").val("");

      if (cancel_trackingIds.indexOf($trackin_id) === 0) {
        return;
      }

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data:
          "action=mark_cancel&trackin_id=" +
          $trackin_id +
          "&account=" +
          $account,
        success: function (s) {
          s = $.parseJSON(s);
          $(".list-container").show();
          if (s.type == "success") {
            $(".success-container ul").append(s.content);
            c_count_s = c_count_s + 1;
            $(".scan-passed").html(
              c_count_s +
                '<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>'
            );
            if (cancel_trackingIds.indexOf($trackin_id) === -1) {
              cancel_trackingIds.push($trackin_id);
            }
          } else {
            $(".failed-container ul").append(s.content);
            c_count_e = c_count_e + 1;
            $(".scan-failed-count").html(
              c_count_e +
                '<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
            );
            if (cancel_trackingIds.indexOf($trackin_id) === -1) {
              cancel_trackingIds.push($trackin_id);
            }
          }
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // MARK RETURN RECEIVED
    $("#mark_return_received")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        // RESET
        r_count_s = 0;
        r_count_e = 0;
        received_trackingIds = [];
        clear_model();
        $("#return_trackin_id").val("");
        $("#return_account_name").val("");
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        refreshCount();
        loadajaxOrders("delivered", "#portlet_delivered");
      });

    $("#mark-return-received").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");

      $trackin_id = $.trim($("#return_trackin_id").val().toUpperCase());
      /*$account = $('#return_account_name').val();
			$account = typeof $account !== 'undefined' ? $account : "";

			if ($account == ""){
				$(".control-group").addClass("error");
				return;
			}*/
      if ($trackin_id == "") {
        return;
      }

      $("#return_trackin_id").val("");

      // if (return_trackingIds.indexOf($trackin_id) === 0){
      // 	return;
      // }

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data: "action=mark_return_received&trackin_id=" + $trackin_id,
        success: function (s) {
          var as = $.parseJSON(s);
          $(".list-container").show();
          $.each(as, function (k, s) {
            if (s.type == "success") {
              $(".success-container ul").append(s.content);
              r_count_s = r_count_s + 1;
              $(".scan-passed").html(
                r_count_s +
                  '<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>'
              );
              if (return_trackingIds.indexOf($trackin_id) === -1) {
                return_trackingIds.push($trackin_id);
              }
            } else {
              $(".failed-container ul").append(s.content);
              r_count_e = r_count_e + 1;
              $(".scan-failed-count").html(
                r_count_e +
                  '<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
              );
              if (return_trackingIds.indexOf($trackin_id) === -1) {
                return_trackingIds.push($trackin_id);
              }
            }
          });
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // MARK ACKNOWLEDGE
    $("#mark_acknowledge_return")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        // RESET
        r_count_s = 0;
        r_count_e = 0;
        return_trackingIds = [];
        clear_model();
        $("#return_trackin_id").val("");
        $("#return_account_name").val("");
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        refreshCount();
        loadajaxOrders("return_received", "#portlet_return_received");
      });

    $("#mark-acknowledge-return").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");

      $trackin_id = $.trim($("#return_tracking_id").val().toUpperCase());
      /*$account = $('#return_account_name').val();
			$account = typeof $account !== 'undefined' ? $account : "";

			if ($account == ""){
				$(".control-group").addClass("error");
				return;
			}*/
      if ($trackin_id == "") {
        return;
      }

      $("#return_tracking_id").val("");

      // if (return_trackingIds.indexOf($trackin_id) === 0){
      // 	return;
      // }

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data: "action=mark_return_complete&trackin_id=" + $trackin_id,
        success: function (s) {
          var as = $.parseJSON(s);
          $(".list-container").show();
          $.each(as, function (k, s) {
            if (s.type == "success") {
              $(".success-container ul").append(s.content);
              r_count_s = r_count_s + 1;
              $(".scan-passed").html(
                r_count_s +
                  '<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>'
              );
              if (return_trackingIds.indexOf($trackin_id) === -1) {
                return_trackingIds.push($trackin_id);
              }
            } else {
              $(".failed-container ul").append(s.content);
              r_count_e = r_count_e + 1;
              $(".scan-failed-count").html(
                r_count_e +
                  '<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
              );
              if (return_trackingIds.indexOf($trackin_id) === -1) {
                // return_trackingIds.push($trackin_id);
              }
            }
          });
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // SEARCH & CLAIM
    $("#search_claim_return")
      .on("show.bs.modal", function (e) {
        $("body").addClass("modal-open");
        $("#search_claim_return .alert-danger").hide();
        // RESET
        $(".modal-body-claim-details").hide();
      })
      .on("hidden.bs.modal", function (e) {
        $("body").removeClass("modal-open");
        reset_spf_modal_inputs();
        // refreshCount();
        // loadajaxOrders('return_received', '#portlet_return_received');
      });

    $("#search-cliam-returns").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");
      $("#search_claim_return .alert-danger").hide();
      $(".modal-body-claim-details").hide();
      $("#search_claim_return .modal-body-nothing-found").addClass("hide");

      $trackin_id = $.trim($("#search_return_trackin_id").val().toUpperCase());

      if ($trackin_id == "") {
        $(".control-group").addClass("error");
        return;
      }

      // $('#search_key').focus();
      $(".modal-body-loading").removeClass("hide");

      $("#search_return_trackin_id").val("");

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "GET",
        data: "action=search_return&trackin_id=" + $trackin_id,
        success: function (s) {
          s = $.parseJSON(s);
          if (s.type == "error") {
            $("#search_claim_return .modal-body-loading").addClass("hide");
            $("#search_claim_return .modal-body-nothing-found")
              .text(s.message)
              .removeClass("hide");
            UIToastr.init("error", "Search & Claim", s.message);
          } else {
            // RESET FORM INPUTS
            reset_spf_modal_inputs();
            var pid_data = s.data;
            var product_uids = s.product_uids;
            s = s.order;
            $("#search_claim_return .product-image img").attr(
              "src",
              s.productImage
            );
            $("#search_claim_return .article-title").text(s.title);
            $("#search_claim_return .order_id").text(s.orderId);
            $("#search_claim_return .order_item_id").text(s.orderItemId);
            $("#search_claim_return .order_date").text(s.orderDate);
            $("#search_claim_return .qty").text(s.quantity);
            $("#search_claim_return .sku").text(s.sku);
            $("#search_claim_return .fsn").text(s.fsn);
            $("#search_claim_return .delivered_date").text(s.r_deliveredDate);
            $("#search_claim_return .received_date").text(s.r_receivedDate);
            $("#search_claim_return .amount").text(s.totalPrice / s.r_quantity);
            $("#search_claim_return .r_qty").text(s.r_quantity);
            $("#search_claim_return .type").text(s.r_source);
            $("#search_claim_return .reason").text(s.r_reason);
            $("#search_claim_return .sub_reason").text(s.r_subReason);
            $("#search_claim_return .customer_comment").text(
              s.r_comments === "" ? "No comments from buyer" : s.r_comments
            );
            $("#search_claim_return .tracking_id").text(s.r_trackingId);
            $("#claim-spf .return_id").val(s.returnId);
            $("#claim-spf .order_id").val(s.orderId);
            $("#claim-spf .order_item_id").val(s.orderItemId);
            $("#claim-spf .tracking_id").val(s.r_trackingId);
            $("#claim-spf .account_id").val(s.account_id);
            $("#claim-spf .product_claim_details").html(pid_data);
            $("#claim-spf .is_combo").val(s.is_combo);
            $("#claim-spf .return_type").val(s.r_source);
            if (product_uids != "") {
              $("#claim-spf .product_uids").html(product_uids);
              $("#claim-spf .product_claim_details").removeClass("hide");
            }

            spf_claim_order();
            $(".modal-body-loading").addClass("hide");
            $(".modal-body-claim-details").show();

            // LOAD DROPZONE
            FormDropzone.init(spf_files, s.account_id);

            // VALIDATE AND SUBMIT
            SPFValidate.init(spf_files, s.account_id);
          }
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // ON SEARCH SUBMIT CLAIM DETAILS
    FormValidate.init("#search-claim-return-single", "return_received");

    $("#search-claim-return-single [name='claim']").change(function () {
      if ($(this).val() == "yes") {
        $(".claim_id").removeClass("hide");
        $("#search-claim-return-single input[name=claim_id]").rules("add", {
          minlength: 21,
          maxlength: 21,
          required: true,
        });
      } else {
        if (!$(".claim_id").hasClass("hide")) {
          $(".claim_id").addClass("hide");
        }
        $("#search-claim-return-single input[name=claim_id]").val("");
        $("#search-claim-return-single input[name=claim_id]").rules("remove");
      }
    });

    // SEARCH ORDER
    $("#search-orders").submit(function (e) {
      e.preventDefault();
      $(".control-group").removeClass("error");
      $("#search_order .alert-danger").text("Error!").hide();
      $(".modal-body-details").hide();

      $search_key = $.trim($("#search_order_key").val());
      $search_by = $("#search_order_by").val();

      if ($search_by == "") {
        $(".control-group").addClass("error");
        return;
      }

      if ($search_key == "") {
        return;
      }

      // $('#search_key').focus();
      $(".modal-body-loading").removeClass("hide");

      $.ajax({
        url: "ajax_load.php?token=" + new Date().getTime(),
        type: "POST",
        data: "action=search_order&key=" + $search_key + "&by=" + $search_by,
        success: function (s) {
          s = $.parseJSON(s);
          if (s.type == "error") {
            $("#search_order .alert-danger")
              .text("No order details found.")
              .show();
          } else {
            if (s.content != "") {
              $(".modal-body-details").html(s.content);
              $(".tooltips").tooltip();
              // copyToClipboard();
            } else {
              $(".modal-body-details").html(
                '<div class="modal-body-empty text-center">No order found!!!</div>'
              );
            }
            $(".modal-body-details").show();
            $(".modal-body-loading").addClass("hide");
            // $(".modal-body-claim-details").show();
            init_flagging();
          }
        },
        error: function () {
          console.log("Error Processing your Request!!");
        },
      });
    });

    // IMPORT ORDERS MANUALLY
    order_import_handleValidation();

    // IMPORT ORDERS FBF MANUALLY
    order_import_fbf_handleValidation();

    if (handler == "return") {
      get_return_delivery_breached_orders_count();
    }

    // LOAD TOOLTIP DATA
    handleTooltips();

    // $(function(){
    // 	// 10 seconds
    // 	// setTimeout(loadajaxOrders, 10000);
    // });
  });

  function spf_claim_order() {
    var group = "";
    var product_condition = "";
    $("#claim-spf .product_uids .checkboxes")
      .off()
      .on("change", function () {
        var checked = $(this).prop("checked");
        group = $(this).val();

        $(".fieldset_" + group).addClass("hide");
        $(".product_condition_list_group_" + group + " .radio").attr(
          "disabled",
          true
        );
        if (checked) {
          $(".fieldset_" + group).removeClass("hide");
          $(".product_condition_list_group_" + group + " .radio").attr(
            "disabled",
            false
          );
        }
        App.updateUniform(
          $(".product_condition_list_group_" + group + " .radio")
        );
      });

    $("#claim-spf .product_condition_list .radio")
      .off()
      .on("change", function () {
        group = $(this).data("group");
        product_condition = $(this).val();
        $(
          ".issue_product_list_group_" + group + ", .product_rto_claim_details"
        ).addClass("hide");
        $(
          ".issue_product_list_group_" +
            group +
            " .checkboxes, .issue_product_list_group_" +
            group +
            " .radio"
        )
          .attr("disabled", true)
          .attr("required", false);
        $(".rto_urls").attr("required", false);
        App.updateUniform($(".issue_checkboxes"));
        // App.updateUniform($('.issue_radio'));
        if (
          $("#claim-spf .return_type").val() == "courier_return" &&
          (product_condition == "wrong" || product_condition == "missing")
        ) {
          $(".product_rto_claim_details").removeClass("hide");
          $(".rto_urls").attr("required", true);
        }
        $("." + product_condition + "_product_list_group_" + group).removeClass(
          "hide"
        );
        $(
          "." +
            product_condition +
            "_product_list_group_" +
            group +
            " .checkboxes, ." +
            product_condition +
            "_product_list_group_" +
            group +
            " .radio"
        )
          .attr("disabled", false)
          .attr("required", true);
        App.updateUniform(
          $(
            "." +
              product_condition +
              "_product_list_group_" +
              group +
              " .checkboxes, ." +
              product_condition +
              "_product_list_group_" +
              group +
              " .radio"
          )
        );
      });

    $("#claim-spf [name='claim']").change(function () {
      if ($(this).val() == "yes") {
        // if($("#claim-spf .return_type").val() == "courier_return" && (product_condition == "wrong" || product_condition == "missing")){
        // 	$(".claim_images").addClass('hide');
        // 	$(".form-actions .btn-success").attr('disabled', false);
        // } else {
        $(".claim_images").removeClass("hide");
        $(".form-actions .btn-success").attr("disabled", true);
        // }
      } else {
        $(".claim_images").addClass("hide");
        $(".form-actions .btn-success").attr("disabled", false);
      }
    });

    App.initUniform();
  }

  function reset_spf_modal_inputs() {
    $(
      "#claim-spf .product_claim_details, #claim-spf .claim_images, #claim-spf .product_rto_claim_details"
    ).addClass("hide");
    $("#claim-spf .checkboxes").prop("checked", false);
    $("#claim-spf .radio").prop("checked", false);
    $("#claim-spf .dropzone-remove-all").trigger("click");
    App.updateUniform($("#claim-spf .checkboxes"));
    App.updateUniform($("#claim-spf .radio"));
    $(".spf_files").empty();
    // App.initUniform();
  }

  function loadReconciliation() {
    var currentReq = null;
    if (jQuery().datepicker) {
      $(".date-picker").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        daysOfWeekDisabled: [0],
      });
    }

    $(".search_by").change(function () {
      var search_by = $(this).val();
      if (search_by == "r_trackingId" || search_by == "") {
        $(".search_by_date").addClass("hide").prop("disabled", true);
        $(".search_by_value").removeClass("hide").prop("disabled", false);
      } else {
        $(".search_by_date").removeClass("hide").prop("disabled", false);
        $(".search_by_value").addClass("hide").prop("disabled", true);
      }
    });

    var search_returns = function () {
      var currentReq = null;
      var form1 = $(".search_returns");
      var error1 = $(".alert-danger", form1);
      var success1 = $(".alert-success", form1);

      form1.validate({
        errorElement: "span", //default input error message container
        errorClass: "help-block", // default input error message class
        focusInvalid: false, // do not focus the last invalid input
        ignore: "",
        rules: {
          report_type: {
            required: true,
          },
          report_sub_type: {
            required: true,
          },
          account_id: {
            required: true,
          },
          report_daterange: {
            required: true,
          },
        },
        messages: {
          // custom messages for radio buttons and checkboxes
          account_id: {
            required: "Select Account",
          },
          search_by: {
            required: "Select Search By",
          },
          search_value: {
            required: "Select Search Value",
          },
        },

        invalidHandler: function (event, validator) {
          //display error alert on form submit
          error1.show();
          App.scrollTo(error1, -200);
        },

        highlight: function (element) {
          // hightlight error inputs
          $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
        },

        unhighlight: function (element) {
          // revert the change done by hightlight
          $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
        },

        success: function (label) {
          label.closest(".form-group").removeClass("has-error"); // set success class to the control group
        },

        errorPlacement: function (error, element) {
          if (element.attr("name") == "report_daterange") {
            error.appendTo(element.parent("div").parent("div"));
          } else {
            error.appendTo(element.parent("div"));
          }
        },

        submitHandler: function (form) {
          error1.hide();

          $(".btn-success", form1).attr("disabled", true);
          $(".btn-success i", form1).addClass("fa fa-sync fa-spin");
          $(".report_data").html("").addClass("hide");

          var a = form1.serializeArray();
          let query = "";
          $.each(a, function () {
            query +=
              "&" +
              encodeURIComponent(this.name) +
              "=" +
              encodeURIComponent(this.value || "");
          });
          query.slice(0, -1);

          currentReq = $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime() + query,
            cache: true,
            type: "GET",
            contentType: false,
            processData: false,
            async: true,
            beforeSend: function () {
              if (currentReq != null) {
                currentReq.abort();
              } else {
                currentReq = currentReq;
              }
              NProgress.configure({ trickle: false });
              NProgress.start();
            },
            success: function (s) {
              s = $.parseJSON(s);
              $(".btn-success", form1).attr("disabled", false);
              $(".btn-success i", form1).removeClass("fa fa-sync fa-spin");
              if (s.redirectUrl) {
                window.location.href = s.redirectUrl;
              }
              setTimeout(function () {
                $(".report_data").html(s.data).removeClass("hide");
                success1.hide().text("");
                error1.hide().text("");
              }, 500);
              NProgress.done(true);
              UIToastr.init(s.type, "Return Reconciliation", s.message);
            },
            error: function () {
              NProgress.done(true);
              $(".btn-success", form1).attr("disabled", false);
              $(".btn-success i", form1).removeClass("fa fa-sync fa-spin");
              UIToastr.init(
                "error",
                "Return Reconciliation",
                "Error processing request!! Please retry later."
              );
            },
          });
        },
      });
    };

    var returns_pod = function () {
      var form1 = $(".returns_pod");
      var error1 = $(".alert-danger", form1);
      var success1 = $(".alert-success", form1);

      form1.validate({
        errorElement: "span", //default input error message container
        errorClass: "help-block", // default input error message class
        focusInvalid: false, // do not focus the last invalid input
        ignore: "",
        rules: {
          account_id: {
            required: true,
          },
          logistic_name: {
            required: true,
          },
          pod_date: {
            required: true,
          },
        },
        messages: {
          // custom messages for radio buttons and checkboxes
          account_id: {
            required: "Select Account",
          },
          logistic_name: {
            required: "Select Logistic",
          },
          pod_date: {
            required: "Select POD Date",
          },
        },

        invalidHandler: function (event, validator) {
          //display error alert on form submit
          error1.show();
          App.scrollTo(error1, -200);
        },

        highlight: function (element) {
          // hightlight error inputs
          $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
        },

        unhighlight: function (element) {
          // revert the change done by hightlight
          $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
        },

        success: function (label) {
          label.closest(".form-group").removeClass("has-error"); // set success class to the control group
        },

        errorPlacement: function (error, element) {
          if (element.attr("name") == "report_daterange") {
            error.appendTo(element.parent("div").parent("div"));
          } else {
            error.appendTo(element.parent("div"));
          }
        },

        submitHandler: function (form) {
          error1.hide();

          $(".btn-success", form1).attr("disabled", true);
          $(".btn-success i", form1).addClass("fa fa-sync fa-spin");

          var a = form1.serializeArray();
          let query = "";
          $.each(a, function () {
            query +=
              "&" +
              encodeURIComponent(this.name) +
              "=" +
              encodeURIComponent(this.value || "");
          });
          query.slice(0, -1);

          window.open(
            "ajax_load.php?token=" + new Date().getTime() + query,
            "_blank"
          );

          $(".btn-success", form1).attr("disabled", false);
          $(".btn-success i", form1).removeClass("fa fa-sync fa-spin");
        },
      });
    };

    search_returns(), returns_pod();
  }

  function loadajaxOrders(order_type, location) {
    if (jQuery().dataTable) {
      var tables = $.fn.dataTable.fnTables(true);
      $(tables).each(function () {
        $(this).dataTable().fnDestroy();
      });
    }

    handler = typeof handler !== "undefined" ? handler : "order";
    if (handler == "order") {
      order_type = typeof order_type !== "undefined" ? order_type : "new";
      location = typeof location !== "undefined" ? location : "#portlet_new";
    } else if (handler == "return") {
      order_type = typeof order_type !== "undefined" ? order_type : "start";
      location = typeof location !== "undefined" ? location : "#portlet_start";
    }
    currentRequest =
      typeof currentRequest !== "undefined" ? currentRequest : null;
    $(".order_content").empty();
    // add loader icon
    $(location + " tbody").html(
      "<tr><td colspan='3'><center><i class='fa fa-sync fa-spin'></i></center></td></tr>"
    );

    currentRequest = $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      type: "GET",
      data: "action=get_orders&type=" + order_type + "&handler=" + handler,
      beforeSend: function () {
        if (currentRequest != null) {
          currentRequest &&
            currentRequest.readyState != 4 &&
            currentRequest.abort();
          currentRequest = null;
        }
      },
      success: function (s) {
        $(location + " tbody").html("");
        $(location + " tbody").html(s);
        initTable(location, accounts["flipkart"]);
        $tab = "#" + $(location + " table").prop("id");
        update_checked_count($tab);
        resetCheckbox($tab);
        lost_claim();
        return_acknowledge();
        update_claim();
        update_claim_id();
        bindDatePicker("#update_claim .form_datetime");
        redownload_label();
        init_flagging();
        if ($tab == "#orders_upcoming") generate_upcoming_picklist();

        // var test = $("input[type=checkbox]:not(.toggle, .make-switch), input[type=radio]:not(.toggle, .star, .make-switch)");
        // if (test.size() > 0) {
        // 	test.each(function () {
        // 		if ($(this).parents(".checker").size() == 0) {
        // 			$(this).show();
        // 			$(this).uniform();
        // 		}
        // 	});
        // }
        App.initUniform();

        $(".dataTables_length select").select2();

        $(".dataTables_filter input").bind("change", function () {
          console.log("input change");
          lost_claim();
          return_acknowledge();
          update_claim();
          update_claim_id();
          bindDatePicker("#update_claim .form_datetime");
          redownload_label();
          init_flagging();
          resetCheckbox();
          App.updateUniform();
        });

        $(".dataTables_length select").bind("change", function () {
          console.log("select change");
          lost_claim();
          return_acknowledge();
          update_claim();
          update_claim_id();
          bindDatePicker("#update_claim .form_datetime");
          redownload_label();
          init_flagging();
          resetCheckbox();
          App.updateUniform();
        });

        $(".dataTables_paginate a").bind("click", function () {
          console.log("anchor change");
          lost_claim();
          return_acknowledge();
          update_claim();
          update_claim_id();
          bindDatePicker("#update_claim .form_datetime");
          redownload_label();
          init_flagging();
          resetCheckbox();
          App.updateUniform();
        });

        $("#mark-acknowledge-return-single [name='claim']").bind(
          "change",
          function () {
            if ($(this).val() == "yes" || $(this).val() == "re-claim") {
              $(".claim_id").removeClass("hide");
              $("#mark-acknowledge-return-single input[name=claim_id]").rules(
                "add",
                {
                  minlength: 21,
                  maxlength: 21,
                  required: true,
                }
              );
            } else {
              if (!$(".claim_id").hasClass("hide")) {
                $(".claim_id").addClass("hide");
              }
              $("#mark-acknowledge-return-single input[name=claim_id]").val("");
              $("#mark-acknowledge-return-single input[name=claim_id]").rules(
                "remove"
              );
            }
          }
        );

        // CLAIM UPDATE RECEIVED STATUS CHANGE FUNCTION
        $("#update-claim [name='receive_type']").bind("change", function () {
          if ($(this).val() == "pod") {
            $(".received_on").removeClass("hide");
            $("#update-claim input[name=received_on]").rules("add", {
              required: true,
              messages: {
                // optional
                required: "Please select a Received On Date",
              },
            });
          } else {
            if (!$(".received_on").hasClass("hide")) {
              $(".received_on").addClass("hide");
            }
            $("#update-claim input[name=received_on]").val("");
            $("#update-claim input[name=received_on]").rules("remove");

            // $("#update-claim [name='claim_staus']").val('reimbursed').trigger('click');
          }
        });

        // CLAIM UPDATE STATUS CHANGE FUNCTION
        $("#update-claim [name='claim_staus']").bind("change", function () {
          if ($(this).val() == "reimbursed") {
            $(".claim_reimburse").removeClass("hide");
            $("#update-claim input[name=claim_reimbursment]").rules("add", {
              required: true,
              number: true,
            });
          } else {
            if (!$(".claim_reimburse").hasClass("hide")) {
              $(".claim_reimburse").addClass("hide");
            }
            $("#update-claim input[name=claim_reimbursment]").val("");
            $("#update-claim input[name=claim_reimbursment]").rules("remove");
          }
        });

        // CLAIM UPDATE CHECK FOR SPF
        get_spf_amount();

        // LOAD TOOLTIPS
        handleTooltips();
      },
      error: function () {
        console.log("Error Processing your Request!!");
      },
    });
  }

  function refreshCount() {
    handler = typeof handler !== "undefined" ? handler : "order";
    // currentRequester = typeof currentRequester !== 'undefined' ? currentRequester : null;
    // currentRequester =
    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "GET",
      data: "action=get_count&handler=" + handler,
      // beforeSend : function(){
      // 	if( currentRequester != null ) {
      // 		// currentRequest.abort();
      // 		currentRequester && currentRequester.readyState != 4 && currentRequester.abort();
      // 		currentRequester = null;
      // 	}
      // },
      success: function (s) {
        var arr = $.parseJSON(s);
        if (handler == "order") {
          $(".portlet_upcoming.count").text("(" + arr.orders.upcoming + ")");
          $(".portlet_new.count").text("(" + arr.orders.new + ")");
          $(".portlet_packing.count").text("(" + arr.orders.packing + ")");
          $(".portlet_rtd.count").text("(" + arr.orders.rtd + ")");
          $(".portlet_shipped.count").text("(" + arr.orders.shipped + ")");
          $(".portlet_cancelled.count").text("(" + arr.orders.cancelled + ")");
        } else if (handler == "return") {
          $(".portlet_start.count").text("(" + arr.orders.start + ")");
          $(".portlet_in_transit.count").text(
            "(" + arr.orders.in_transit + ")"
          );
          $(".portlet_out_for_delivery.count").text(
            "(" + arr.orders.out_for_delivery + ")"
          );
          $(".portlet_delivered.count").text("(" + arr.orders.delivered + ")");
          $(".portlet_received.count").text(
            "(" + arr.orders.return_received + ")"
          );
          $(".portlet_claimed.count").text(
            "(" + arr.orders.return_claimed + ")"
          );
          $(".portlet_claimed_undelivered.count").text(
            "(" + arr.orders.return_claimed_undelivered + ")"
          );
          $(".portlet_return_completed.count").text(
            "(" + arr.orders.return_completed + ")"
          );
          $(".portlet_return_unexpected.count").text(
            "(" + arr.orders.return_unexpected + ")"
          );
        }
        var dropdowns = "";
        if (arr.logistic == "") {
          dropdowns +=
            '<li class="list-group-item"><a><center>No Orders Yet!</center></a></li>';
        } else {
          for (var account in arr.logistic) {
            var count = 0;
            var inner_dropdowns = "";
            for (var partners in arr.logistic[account]) {
              var lp_count = 0;
              var final_dropdowns = "";
              for (var type in arr.logistic[account][partners]) {
                final_dropdowns +=
                  '<li class="list-group-item d-flex justify-content-between align-items-center"><a>' +
                  type +
                  '<span class="badge badge-default badge-pill">' +
                  arr.logistic[account][partners][type] +
                  "</span></a></li>";
                count += arr.logistic[account][partners][type];
                lp_count += arr.logistic[account][partners][type];
              }
              inner_dropdowns +=
                '<li class="list-group-item group-sub-header"><a>' +
                partners +
                '<span class="badge badge-primary badge-pill">' +
                lp_count +
                "</span></a></li>" +
                final_dropdowns;
            }
            dropdowns +=
              '<li class="list-group-item group-header"><a>' +
              account +
              '<span class="badge badge-primary badge-pill">' +
              count +
              "</span></a></li>" +
              inner_dropdowns;
          }
        }
        $(".logistic_details").html(
          '<div class="btn-group btn-group-solid"><button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><i class="fa fa-ellipsis-horizontal"></i> Pickup Details <i class="fa fa-angle-down"></i></button><ul class="dropdown-menu list-group">' +
            dropdowns +
            "</ul></div>"
        );

        $("#fbf_lite_dates").html("");
        $("#non_fbf_dates").html("");
        if (arr.dispatchByDates != "" && arr.dispatchByDates != null) {
          $opt_non = "";
          $opt_fbf = "";
          $.each(arr.dispatchByDates.FBF_LITE, function (k, v) {
            $opt_fbf +=
              "<li><a class='generate_upcoming_picklist' data-order_type='FBF_LITE' data-dbd='" +
              k +
              "'>" +
              k +
              " (" +
              v +
              ")</a></li>";
          });
          $.each(arr.dispatchByDates.NON_FBF, function (k, v) {
            $opt_non +=
              "<li><a class='generate_upcoming_picklist' data-order_type='NON_FBF' data-dbd='" +
              k +
              "'>" +
              k +
              " (" +
              v +
              ")</a></li>";
          });
          $("#fbf_lite_dates").append($opt_fbf);
          $("#non_fbf_dates").append($opt_non);
          generate_upcoming_picklist();
        }
      },
      error: function () {
        console.log("Error Processing your Request!!");
      },
    });
    resetCheckbox();
  }

  function get_return_delivery_breached_orders_count() {
    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "GET",
      data: "action=get_return_breached_orders&type=count",
      success: function (s) {
        s = $.parseJSON(s);
        if (s.type == "success") {
          $(".return_breach_count").html(s.count);
          // console.log(s);
        } else {
          $(".return_breach_count").html("");
        }
      },
      error: function () {
        $($button).prop("disabled", false);
        alert("Error Processing your Request!!");
      },
    });
  }

  function get_return_delivery_breached_orders() {
    $("#return_delivery_breached .modal-body").html("<center><i></i></center>");
    $("#return_delivery_breached .modal-body i").removeClass("hide");
    $("#return_delivery_breached .modal-body i").addClass("fa fa-sync fa-spin");
    $("#order_replacements_template_content").html("");

    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "GET",
      data: "action=get_return_breached_orders",
      success: function (s) {
        // s = $.parseJSON(s);
        $("#return_delivery_breached .modal-body i").addClass("hide");
        $("#return_delivery_breached .modal-body i").removeClass(
          "fa fa-sync fa-spin"
        );
        $("#return_delivery_breached .modal-body").html(s);
        raise_ticket_return_delivery_breached_orders();
      },
      error: function () {
        $("#return_delivery_breached .modal-body i").addClass("hide");
        $("#return_delivery_breached .modal-body i").removeClass(
          "fa fa-sync fa-spin"
        );
        $("#return_delivery_breached .modal-body").html(
          "Error Processing your Request!!"
        );
      },
    });
  }

  function raise_ticket_return_delivery_breached_orders() {
    $(".raise_ticket")
      .off("click")
      .on("click", function () {
        $this = $(this);
        $returnId = $this.data("returnid");
        $orderId = $this.data("orderid");
        $orderItemId = $this.data("orderitemid");
        $trackingId = $this.data("trackingid");
        $accountId = $this.data("acountid");
        $condition = JSON.stringify($this.data("condition"));
        $content = $this.data("content");
        $subject = "";
        $issueType = $this.data("issuetype");
        $mandatory_fields =
          '{"order_id":"' + $orderId + '","tracking_id":"' + $trackingId + '"}';

        // create new ticket
        $this.attr("disabled", true);
        $this.find("i").removeClass("hide");
        $this.find("i").addClass("fa fa-sync fa-spin");
        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          async: true,
          data:
            "action=create_support_ticket&account=" +
            $accountId +
            "&mandatory_fields=" +
            $mandatory_fields +
            "&issueType=" +
            $issueType +
            "&subject=" +
            $subject +
            "&content=" +
            $content +
            "&insert=false",
          success: function (s) {
            s = $.parseJSON(s);
            if (s.type == "success") {
              // update_claim_status
              $.ajax({
                url: "ajax_load.php?token=" + new Date().getTime(),
                cache: false,
                type: "POST",
                async: true,
                data:
                  "action=update_claim_status&return_id=" +
                  $returnId +
                  "&claim_id=" +
                  s.incidentId +
                  "&condition=" +
                  $condition,
                success: function (xs) {
                  // $this.attr('disabled', false);
                  $this.find("i").addClass("hide");
                  $this.find("i").removeClass("fa fa-sync fa-spin");
                  $this.removeClass("btn-success").addClass("btn-info");
                  $this.text(s.incidentId);
                  UIToastr.init(xs.type, "Incident Creation", xs.message);
                },
                error: function (xhr, textStatus, errorThrown) {
                  $this.attr("disabled", false);
                  $this.find("i").addClass("hide");
                  $this.find("i").removeClass("fa fa-sync fa-spin");
                  console.log(textStatus + ":" + errorThrown);
                  UIToastr.init(
                    xs.type,
                    "Incident Creation",
                    "Error saving support ticket!! Please retry later. <br />ERROR:" +
                      textStatus +
                      ":" +
                      errorThrown
                  );
                },
              });
            }
            UIToastr.init(s.type, "Incident Creation", s.message);
          },
          error: function (xhr, textStatus, errorThrown) {
            $this.attr("disabled", false);
            $this.find("i").addClass("hide");
            $this.find("i").removeClass("fa fa-sync fa-spin");
            console.log(textStatus + ":" + errorThrown);
            UIToastr.init(
              "error",
              "Incident Creation",
              "Error creating support ticket!! Please retry later. <br />ERROR:" +
                textStatus +
                ":" +
                errorThrown
            );
          },
        });
      });
  }

  function clear_model() {
    $(".list-container").hide();
    $(".scan-passed").html(
      '0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span>'
    );
    $(".scan-failed-count").html(
      '0<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>'
    );
    $(".list-container ul").html("");
  }

  function redownload_label() {
    $(".redownload_label")
      .off("click")
      .on("click", function () {
        $button = $(this);
        $shipmentId = $($button).data("shipmentid");
        var currentReq = null;
        $($button).prop("disabled", true);
        $($button).find("i").addClass("fa-spin");
        currentReq = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data:
            "action=generate&type=redownload-label&shipment_ids=" + $shipmentId,
          beforeSend: function () {
            if (currentRequest != null) {
              currentRequest.abort();
            } else {
              currentRequest = currentReq;
            }
          },
          success: function (s) {
            s = $.parseJSON(s);
            $($button).find("i").removeClass("fa-spin");
            $($button).prop("disabled", false);
            if (s.type == "success") {
              $($button)
                .parent()
                .html(
                  "Label: <button class='btn tooltips ok' data-placement='right' data-original-title='Label Ok'><i class='fa fa-xs fa-circle'></i></button>"
                );
              // console.log(s);
            }
            UIToastr.init(s.type, "Re-Label Generation", s.message);
          },
          error: function () {
            $($button).prop("disabled", false);
            alert("Error Processing your Request!!");
          },
        });
      });
  }

  function generate_labels(shipment_ids, type) {
    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", "ajax_load.php");
    form.setAttribute("target", "_blank");

    // form._submit_function_ = form.submit;

    var params = {
      action: "generate",
      type: type,
      shipment_ids: shipment_ids.toString(),
    };

    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", params[key]);

        form.appendChild(hiddenField);
      }
    }
    document.body.appendChild(form);
    // form._submit_function_();
    form.submit();

    /*$.ajax({
			url: "ajax_load.php",
			type: 'POST',
			data: "action=generate&type="+type+"&order_item_ids="+order_item_ids.toString(),
			dataType: "json",
			success: function(response){
				var win = window.open();
				win.document.write(response);
			},
			error: function(){
				alert('Error Processing your Request!!');
			}
		});*/
  }

  function generate_packlist($type, $dbd) {
    var form = document.createElement("form");
    form.setAttribute("method", "post");
    form.setAttribute("action", "ajax_load.php");
    form.setAttribute("target", "_blank");

    // form._submit_function_ = form.submit;

    var params = {
      action: "get-packlist",
      type: $type,
      dbd: $dbd,
    };

    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", params[key]);

        form.appendChild(hiddenField);
      }
    }

    document.body.appendChild(form);
    // form._submit_function_();
    form.submit();

    /*$.ajax({
			url: "ajax_load.php",
			type: 'POST',
			data: "action=generate&type="+type+"&order_item_ids="+order_item_ids.toString(),
			dataType: "json",
			success: function(response){
				var win = window.open();
				win.document.write(response);
			},
			error: function(){
				alert('Error Processing your Request!!');
			}
		});*/
  }

  function generate_upcoming_picklist() {
    $(".generate_upcoming_picklist")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        $order_type = $(this).data("order_type");
        $dbd = $(this).data("dbd");
        generate_packlist($order_type, $dbd);
      });
    $(".upcoming_picklist")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        generate_packlist("all", "");
      });
  }

  function sync_orders() {
    var currentReq = null;
    currentReq = $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "POST",
      data: "action=sync&type=new",
      beforeSend: function () {
        if (currentRequest != null) {
          currentRequest.abort();
        } else {
          currentRequest = currentReq;
        }
      },
      success: function (s) {
        currentReq = $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          type: "POST",
          data: "action=sync&type=packing",
          beforeSend: function () {
            if (currentRequest != null) {
              currentRequest.abort();
            } else {
              currentRequest = currentReq;
            }
          },
          success: function (s) {
            refreshCount();
            loadajaxOrders();
            $("#order_sync i").removeClass("fa fa-sync fa-spin");
            $("#order_sync").removeAttr("disabled");
          },
          error: function () {
            $("#order_sync i").removeClass("fa fa-sync fa-spin");
            alert("Error Processing your Request!!");
          },
        });
      },
      error: function () {
        $("#order_sync i").removeClass("fa fa-sync fa-spin");
        alert("Error Processing your Request!!");
      },
    });
  }

  function get_replacement_orders() {
    $("#order_replacements .modal-body").html("<center><i></i></center>");
    $("#order_replacements .modal-body i").removeClass("hide");
    $("#order_replacements .modal-body i").addClass("fa fa-sync fa-spin");
    $("#order_replacements_template_content").html("");

    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "GET",
      data: "action=get_replacement_orders",
      success: function (s) {
        $("#order_replacements .modal-body i").addClass("hide");
        $("#order_replacements .modal-body i").removeClass(
          "fa fa-sync fa-spin"
        );
        $("#order_replacements .modal-body").html(s);
        doubted_orders_checkbox_selection("order_replacements");
      },
      error: function () {
        $("#order_replacements .modal-body i").addClass("hide");
        $("#order_replacements .modal-body i").removeClass(
          "fa fa-sync fa-spin"
        );
        $("#order_replacements .modal-body").html(
          "Error Processing your Request!!"
        );
      },
    });
  }

  function get_duplicate_orders() {
    $("#order_duplicate .modal-body").html("<center><i></i></center>");
    $("#order_duplicate .modal-body i").removeClass("hide");
    $("#order_duplicate .modal-body i").addClass("fa fa-sync fa-spin");
    $("#order_duplicate_template_content").html("");

    $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "GET",
      data: "action=get_duplicate_orders",
      success: function (s) {
        $("#order_duplicate .modal-body i").addClass("hide");
        $("#order_duplicate .modal-body i").removeClass("fa fa-sync fa-spin");
        $("#order_duplicate .modal-body").html(s);
        doubted_orders_checkbox_selection("order_duplicate");
      },
      error: function () {
        $("#order_duplicate .modal-body i").addClass("hide");
        $("#order_duplicate .modal-body i").removeClass("fa fa-sync fa-spin");
        $("#order_duplicate .modal-body").html(
          "Error Processing your Request!!"
        );
      },
    });
  }

  function doubted_orders_checkbox_selection($div) {
    App.initUniform();

    $("#" + $div + ' input[type="checkbox"]').click(function () {
      $checked = $(this).is(":checked");
      if ($div == "order_duplicate") {
        $dup_orderitemid = $(this).data("dup_orderitemid");
        $set = $dup_orderitemid.split(",");
        $($set).each(function (index, element) {
          if ($checked) {
            $(".checkbox-" + element).attr("checked", true);
          } else {
            $(".checkbox-" + element).attr("checked", false);
          }
        });
      }
      // jQuery.uniform.update($set);
      jQuery.uniform.update("#" + $div + ' input[type="checkbox"]');
    });

    $("#" + $div + ' button[type="submit"]').click(function () {
      $("#" + $div + "_template_content").html("");
      $checkbox = "#" + $div + " input[type='checkbox']";
      var allChecked = $($checkbox + ":checked");
      if (allChecked.length > 0) {
        // $("#"+$div+" button[type='submit']").attr('disabled', true);
        $("#" + $div + " button[type='submit'] i").removeClass("hide");
        $("#" + $div + " button[type='submit'] i").addClass(
          "fa fa-sync fa-spin"
        );

        var orderItemIds = {};
        $(allChecked).each(function (index, element) {
          var account = $(this).data("account");
          var group = $(this).data("group");
          var quantity = $(this).data("quantity");
          var orderItemId = $(this).val();

          if (typeof orderItemIds[account] === "undefined")
            orderItemIds[account] = {};

          if (typeof orderItemIds[account][group] === "undefined") {
            orderItemIds[account][group] = [];
            if (group == "multi_quantity") {
              orderItemIds[account][group][0] = { [orderItemId]: quantity };
            } else {
              orderItemIds[account][group][0] = orderItemId;
            }
          } else {
            var glength = orderItemIds[account][group].length;
            if (group == "multi_quantity") {
              orderItemIds[account][group][glength] = {
                [orderItemId]: quantity,
              };
            } else {
              orderItemIds[account][group][glength] = orderItemId;
            }
          }
        });
        var orders = JSON.stringify(orderItemIds);

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: "action=generate&type=ticket_content&orders=" + orders,
          success: function (s) {
            s = $.parseJSON(s);
            $("#" + $div + " button[type='submit']").attr("disabled", false);
            $("#" + $div + " button[type='submit'] i").addClass("hide");
            $("#" + $div + " button[type='submit'] i").removeClass(
              "fa fa-sync fa-spin"
            );

            $templateContent = "";
            $.each(s.accounts, function (i, account) {
              $.each(account, function (k, group) {
                $templateContent +=
                  "<div class='col-md-4 text-left'><div class='well'><span>" +
                  group.html +
                  "</span><br /><br /><span class='create_ticket_content'><button class='btn btn-success create_ticket' type='button' value='Create Ticket' data-account='" +
                  i +
                  "' data-orderid='" +
                  group.orderId +
                  "' data-orderitemids='" +
                  group.orderItemIds +
                  "' data-issuetype='" +
                  group.issueType +
                  "' data-subject='" +
                  group.subject +
                  "' data-content='" +
                  group.ticket_content +
                  "'><i></i> Create Ticket</button></span></div></div>";
              });
            });

            $("#" + $div + "_template_content").html($templateContent);
            create_support_ticket();
          },
          error: function () {
            $("#" + $div + " button[type='submit']").attr("disabled", false);
            $("#" + $div + " button[type='submit'] i").addClass("hide");
            $("#" + $div + " button[type='submit'] i").removeClass(
              "fa fa-sync fa-spin"
            );

            // $('#order_replacements .modal-body').html('Error Processing your Request!!');
          },
        });
      }
    });
  }

  function create_support_ticket() {
    $(".create_ticket")
      .off("click")
      .on("click", function () {
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").removeClass("hide");
        $this.find("i").addClass("fa fa-sync fa-spin");
        $account = $this.data("account");
        $subject = $this.data("subject");
        $content = $this.data("content");
        $issueType = $this.data("issuetype");
        $orderId = $this.data("orderid");
        $orderItemIds = "";
        $orderItemIds = $this.data("orderitemids");
        $mandatory_fields =
          '{"order_id":"' +
          $orderId +
          '","order_item_id":"' +
          $orderItemIds +
          '"}';

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          async: true,
          data:
            "action=create_support_ticket&account=" +
            $account +
            "&mandatory_fields=" +
            $mandatory_fields +
            "&issueType=" +
            $issueType +
            "&subject=" +
            $subject +
            "&content=" +
            $content,
          success: function (s) {
            s = $.parseJSON(s);
            $this.attr("disabled", false);
            $this.find("i").addClass("hide");
            $this.find("i").removeClass("fa fa-sync fa-spin");
            if (s.type == "success") {
              $this
                .parent()
                .html(
                  '<div class="alert alert-success"><strong>Success!</strong> ' +
                    s.message +
                    "</div>"
                );
              $orderItemIds += ",";
              // if ($orderItemIds.indexOf(",") >= 0)
              var odr_array = $orderItemIds.split(",");
              // else
              // 	var odr_array = [$orderItemIds];
              // console.log(odr_array);
              $.each(odr_array, function (i, odr) {
                // console.log('#'+odr);
                $("#" + odr).remove();
              });
            }
            UIToastr.init(s.type, $subject, s.message);
          },
          error: function (xhr, textStatus, errorThrown) {
            $this.attr("disabled", false);
            $this.find("i").addClass("hide");
            $this.find("i").removeClass("fa fa-sync fa-spin");
            console.log(textStatus + ":" + errorThrown);
            UIToastr.init(
              "error",
              $subject,
              "Error creating support ticket!! Please retry later. <br />ERROR:" +
                textStatus +
                ":" +
                errorThrown
            );
          },
        });
      });
  }

  function get_spf_amount() {
    $("#update-claim .get_spf")
      .off("click")
      .on("click", function () {
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").addClass("fa fa-sync fa-spin");

        $orderItemId = $this.attr("data-order-item-id");
        $accountName = $this.attr("data-account-name");
        $item_amount = $this.attr("data-item_amount");
        $total_amount = $this.attr("data-total_amount");
        $retrun_type = $this.attr("data-return_type");

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "GET",
          data:
            "action=get_spf_amount&orderItemId=" +
            $orderItemId +
            "&accountName=" +
            $accountName,
          success: function (s) {
            s = $.parseJSON(s);
            $this.attr("disabled", false);
            $this.find("i").removeClass("fa fa-sync fa-spin");
            // $('#order_replacements .modal-body').html(s);
            // $(".claim_reimbursment")
            if (s.type == "success") {
              $(".claim_reimbursment").val(s.amount);
              if ($retrun_type == "COURIER_RETURN") {
                var percent = (s.amount / $total_amount) * 100;
              } else {
                var percent = (s.amount / $item_amount) * 100;
              }
              $(".sellement_percentage")
                .removeClass("hide")
                .text(percent.toFixed(2) + "%");
              $(".spf_reclaim").removeClass("hide");
              var content = $this.prev(".spf_reclaim").data("content");
              content = content.replace("##APPROVED_SPF##", s.amount);
              $this.prev(".spf_reclaim").removeData("content");
              $this.prev(".spf_reclaim").attr("data-content", content);
            }
            UIToastr.init(s.type, "SPF Details", s.message);
          },
          error: function () {
            $this.attr("disabled", false);
            $this.find("i").removeClass("fa fa-sync fa-spin");
            UIToastr.init(
              "error",
              "SPF Details",
              "Error fetching SPF details!! Please retry later."
            );
            // $('#order_replacements .modal-body').html('Error Processing your Request!!');
          },
        });
      });

    $("#update-claim .spf_reclaim")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        $this = $(this);
        $this.attr("disabled", true);
        $this.find("i").removeClass("hide");
        $this.find("i").addClass("fa fa-sync fa-spin");
        $returnid = $this.data("returnid");
        $account = $this.data("account");
        $subject = $this.data("subject");
        $content = $this.data("content");
        $issueType = $this.data("issuetype");
        $orderItemId = $this.data("orderitemid");
        $base_incident = $this.data("base_incident");
        $approved_spf = $this.data("approved_spf");
        if ($approved_spf == "") $approved_spf = $base_incident;
        $mandatory_fields = '{"base_incident":"' + $approved_spf + '"}';

        window.setTimeout(function () {
          $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime(),
            cache: false,
            type: "POST",
            data:
              "action=create_support_ticket&account=" +
              $account +
              "&mandatory_fields=" +
              $mandatory_fields +
              "&issueType=" +
              $issueType +
              "&subject=" +
              $subject +
              "&content=" +
              $content +
              "&insert=false",
            success: function (s) {
              s = $.parseJSON(s);
              UIToastr.init(s.type, "Reclaim SPF", s.message);
              if (s.type == "success") {
                $.ajax({
                  url: "ajax_load.php?token=" + new Date().getTime(),
                  cache: false,
                  type: "POST",
                  data:
                    "action=update_claim_id&pk=" +
                    $returnid +
                    "&value=" +
                    s.incidentId +
                    "&approved_spf=" +
                    $approved_spf,
                  success: function (r) {
                    r = $.parseJSON(r);
                    if (r.type == "success")
                      UIToastr.init(
                        r.type,
                        "Update Incident ID",
                        "Succesfully updated Incident ID"
                      );
                    else UIToastr.init(r.type, "Update Incident ID", r.msg);

                    $this.attr("disabled", false);
                    $this.find("i").addClass("hide");
                    $this.find("i").removeClass("fa fa-sync fa-spin");
                    $this.addClass("hide");
                    $(".claim_reimbursment").val("");
                    $(".sellement_percentage").addClass("hide");
                    $("#" + $returnid + " .claim_staus").text("PENDING");
                    $("#update_claim").modal("hide");
                  },
                });
              } else {
                $this.attr("disabled", false);
                $this.find("i").addClass("hide");
                $this.find("i").removeClass("fa fa-sync fa-spin");
              }
            },
            error: function (xhr, textStatus, errorThrown) {
              $this.attr("disabled", false);
              $this.find("i").addClass("hide");
              $this.find("i").removeClass("fa fa-sync fa-spin");
              console.log(textStatus + ":" + errorThrown);
              UIToastr.init(
                "error",
                $subject,
                "Error creating support ticket!! Please retry later. <br />ERROR:" +
                  textStatus +
                  ":" +
                  errorThrown
              );
            },
          });
        }, 50);
      });
  }

  function update_status(shipment_ids) {
    $("#update_status").attr("disabled", true);
    $("#update_status i").addClass("fa fa-sync fa-spin");

    $location = $("#update_status").data("activetab");
    $location_type = $location.substr($location.indexOf("_") + 1);
    console.log($location);
    console.log($location_type);

    var currentReq = null;
    currentReq = $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "POST",
      data: "action=update_status&shipment_ids=" + shipment_ids,
      beforeSend: function () {
        if (currentRequest != null) {
          currentRequest.abort();
        } else {
          currentRequest = currentReq;
        }
      },
      success: function (s) {
        refreshCount();
        loadajaxOrders($location_type, $location);
        $("#update_status i").removeClass("fa fa-sync fa-spin");
        $("#update_status").removeAttr("disabled");
      },
      error: function () {
        $("#update_status i").removeClass("fa fa-sync fa-spin");
        alert("Error Processing your Request!!");
      },
    });
  }

  // CHECKBOX COUNT FUNCTION
  function update_checked_count() {
    resetCheckbox();
    $(".checkboxes").click(function () {
      var checked = jQuery(this).is(":checked");
      $(".group-checkable").prop("checked", false);
      update_checked_chckbox(false, checked);
    });

    // Update selected count
    $(".group-checkable").click(function () {
      var tab = $(this).attr("data-set");
      var checked = $(this).is(":checked");
      update_checked_chckbox(true, checked);
    });
  }

  var last = 0;
  function update_checked_chckbox(all, checked) {
    all &&
      (checked
        ? $(".checkboxes").prop("checked", true)
        : $(".checkboxes").prop("checked", false));

    var totalCheckboxes = $(".checkboxes").length;
    var numberOfChecked = $(".checkboxes:checked").length;
    var numberNotChecked = totalCheckboxes - numberOfChecked;
    numberOfChecked == totalCheckboxes
      ? $(".group-checkable").prop("checked", true)
      : "";

    App.updateUniform($(".checkboxes"));

    if (numberOfChecked != 0) {
      $(".selected_checkbox").removeClass("hide");
      // if (!is_queued)
      // 	$('.btn-select').attr('disabled', false);
      $(".btn-process .btn-select").attr("disabled", false);
      $("#update_status").attr("disabled", false);
    } else {
      $(".selected_checkbox").addClass("hide");
      $(".btn-select").attr("disabled", true);
      $(".btn-process .btn-select").attr("disabled", true);
      $("#update_status").attr("disabled", true);
    }
    $(".selected_checkbox_count").text(numberOfChecked);
  }

  function resetCheckbox() {
    $(".selected_checkbox_count").empty();
    $(".selected_checkbox").addClass("hide");
    $(".group-checkable").prop("checked", false);
    $(".checkboxes").prop("checked", false);
    App.initUniform();
  }

  function lost_claim() {
    $("#mark-acknowledge-return-single").trigger("reset");
    // $('input[name=product_condition]').removeAttr('checked');
    $id = "";
    $(".file_lost_claim").bind("click", function () {
      // $(document).on('click', '.acknowledge_return', function(){
      $id = $(this).data("id");
      $("#lost_claim .product-image img").attr(
        "src",
        $("#" + $id + " .ordered-product-image img").attr("src")
      );
      $("#lost_claim .article-title").text(
        $("#" + $id + " .product_name").text()
      );
      $("#lost_claim .sku").text($("#" + $id + " .sku").text());
      $("#lost_claim .order_item_id").text(
        $("#" + $id + " .order_item_id").text()
      );
      $("#lost_claim .fsn").text($("#" + $id + " .fsn").text());
      $("#lost_claim .order_id").text($("#" + $id + " .order_id").text());
      $("#lost_claim .order_date").text($("#" + $id + " .order_date").text());
      $("#lost_claim .amount").text($("#" + $id + " .amount").text());
      $("#lost_claim .qty").text($("#" + $id + " .qty").text());
      $("#lost_claim .type").text($("#" + $id + " .type").text());
      $("#lost_claim .reason").text($("#" + $id + " .return_reason").text());
      $("#lost_claim .sub_reason").text(
        $("#" + $id + " .return_sub_reason").text()
      );
      $("#lost_claim .customer_comment").text(
        $("#" + $id + " .comments").text() === ""
          ? "No comments from buyer"
          : $("#" + $id + " .comments").text()
      );
      $("#lost_claim .tracking_id").text($("#" + $id + " .tracking_id").text());
      $("#lost-claim .return_id").val($id);
      $combo_ids = $("#" + $id + " .combo_ids").text();
      $ids = $.parseJSON($combo_ids);

      $(".products_condition").html("");
      $pid = [];

      $($ids).each(function (id, idv) {
        $(idv).each(function (k, v) {
          $(".products_condition").append(
            '<div class="form-group" id="product_condition_' +
              v.pid +
              '"><label class="col-md-3 control-label">Product condition for ' +
              v.sku +
              '<span class="required" aria-required="true">*</span></label><div class="col-md-9 radio-list"><label class="radio-inline"><div class="radio"><span><input class="product_condition_' +
              v.pid +
              '" name="product_condition[' +
              v.pid +
              ']" value="undelivered" type="radio" checked></span></div>Undelivered</label><div class="form_product_condition_error"></div></div></div>'
          );
          $pid.push(v.pid);
        });
      });

      // Lets get the functionlaity back of dynmically added radio list
      $("#lost-claim .form-group").on(
        "click",
        "input[type=radio]",
        function () {
          $(this)
            .closest(".form-group")
            .find(".radio, span")
            .removeClass("checked");
          $(this).closest(".radio, span ").addClass("checked");
        }
      );

      $("#lost-claim .pids").val($pid.join(","));

      $("#lost_claim").on("hidden.bs.modal", function (e) {
        $("#lost-claim").trigger("reset");
        // $(this).find('form')[0].reset();
      });

      LostValidate.init("#lost-claim");
    });
  }

  function return_acknowledge() {
    $("#mark-acknowledge-return-single").trigger("reset");
    // $('input[name=product_condition]').removeAttr('checked');
    $id = "";
    $(".acknowledge_return").bind("click", function () {
      // $(document).on('click', '.acknowledge_return', function(){
      $id = $(this).data("id");
      $type = $(this).data("type");
      $("#mark_acknowledge_return_single .product-image img").attr(
        "src",
        $("#" + $id + " .ordered-product-image img").attr("src")
      );
      $("#mark_acknowledge_return_single .article-title").text(
        $("#" + $id + " .product_name").text()
      );
      $("#mark_acknowledge_return_single .sku").text(
        $("#" + $id + " .sku").text()
      );
      $("#mark_acknowledge_return_single .order_item_id").text(
        $("#" + $id + " .order_item_id").text()
      );
      $("#mark_acknowledge_return_single .fsn").text(
        $("#" + $id + " .fsn").text()
      );
      $("#mark_acknowledge_return_single .order_id").text(
        $("#" + $id + " .order_id").text()
      );
      $("#mark_acknowledge_return_single .order_date").text(
        $("#" + $id + " .order_date").text()
      );
      $("#mark_acknowledge_return_single .amount").text(
        $("#" + $id + " .amount").text()
      );
      $("#mark_acknowledge_return_single .qty").text(
        $("#" + $id + " .qty").text()
      );
      $("#mark_acknowledge_return_single .return_qty").text(
        $("#" + $id + " .return_qty").text()
      );
      $("#mark_acknowledge_return_single .type").text(
        $("#" + $id + " .type").text()
      );
      $("#mark_acknowledge_return_single .reason").text(
        $("#" + $id + " .return_reason").text()
      );
      $("#mark_acknowledge_return_single .sub_reason").text(
        $("#" + $id + " .return_sub_reason").text()
      );
      $("#mark_acknowledge_return_single .customer_comment").text(
        $("#" + $id + " .comments").text() === ""
          ? "No comments from buyer"
          : $("#" + $id + " .comments").text()
      );
      $("#mark_acknowledge_return_single .tracking_id").text(
        $("#" + $id + " .tracking_id").text()
      );
      $("#mark-acknowledge-return-single .return_id").val($id);
      $combo_ids = $("#" + $id + " .combo_ids").text();
      $ids = $.parseJSON($combo_ids);

      $(".products_condition").html("");
      $pid = [];
      var location = "";
      $($ids).each(function (id, idv) {
        $(idv).each(function (k, v) {
          if ($type == "unexpected") {
            $(".products_condition")
              .append(
                '<div class="form-group" id="product_condition_' +
                  v.pid +
                  '"><label class="col-md-3 control-label">Product condition for ' +
                  v.sku +
                  '<span class="required" aria-required="true">*</span></label><div class="col-md-9 radio-list"><label class="radio-inline"><div class="radio"><span><input class="product_condition_' +
                  v.pid +
                  '" name="product_condition[' +
                  v.pid +
                  ']" value="missing" type="radio" checked></span></div>Missing</label><div class="form_product_condition_error"></div></div></div>'
              )
              .hide();
            location = "return_unexpected";
          } else {
            $(".products_condition").append(
              '<div class="form-group" id="product_condition_' +
                v.pid +
                '"><label class="col-md-3 control-label">Product condition for ' +
                v.sku +
                '<span class="required" aria-required="true">*</span></label><div class="col-md-9 radio-list"><label class="radio-inline"><div class="radio"><span><input class="product_condition_' +
                v.pid +
                '" name="product_condition[' +
                v.pid +
                ']" value="saleable" type="radio"></span></div>Saleable</label><label class="radio-inline"><div class="radio"><span><input class="product_condition_' +
                v.pid +
                '" name="product_condition[' +
                v.pid +
                ']" value="damaged" type="radio"></span></div>Damaged</label><label class="radio-inline"><div class="radio"><span><input class="product_condition_' +
                v.pid +
                '" name="product_condition[' +
                v.pid +
                ']" value="wrong" type="radio"></span></div>Wrong</label><div class="form_product_condition_error"></div></div></div>'
            );
            location = "return_received";
          }

          $pid.push(v.pid);
        });
      });

      $(".products_uid").html("");
      $products_uid =
        '<div class="form-group"><label class="col-md-3 control-label">Product UID<span class="required" aria-required="true">*</span></label><div class="col-md-9 radio-list">';
      $uids = $("#" + $id + " .uids").text();
      if ($uids != "") {
        $uids = $.parseJSON($uids);
        $($uids).each(function (k, uid) {
          $products_uid +=
            '<label class="radio-inline"><div class="radio"><span><input class="products_uid" name="uid" value="' +
            uid +
            '" type="radio"></span></div>' +
            uid +
            "</label>";
        });
        if ($uids.length > 1)
          $products_uid +=
            '<label class="radio-inline"><div class="radio"><span><input class="products_uid" name="uid" value="' +
            $uids.join(",") +
            '" type="radio"></span></div>All</label>';
        $products_uid += '<div class="form_uid_error"></div></div></div>';
        $(".products_uid").append($products_uid);
      }

      // Lets get the functionlaity back of dynmically added radio list
      $("#mark-acknowledge-return-single .form-group").on(
        "click",
        "input[type=radio]",
        function () {
          $(this)
            .closest(".form-group")
            .find(".radio, span")
            .removeClass("checked");
          $(this).closest(".radio, span ").addClass("checked");
        }
      );

      $("#mark-acknowledge-return-single .pids").val($pid.join(","));

      $("#mark_acknowledge_return_single").on("hidden.bs.modal", function (e) {
        $("#mark-acknowledge-return-single").trigger("reset");
        // $(this).find('form')[0].reset();
      });

      FormValidate.init("#mark-acknowledge-return-single", location);

      // Let add the dynamic rule after the validate form is initiated
      $('#mark-acknowledge-return-single [name^="product_condition').each(
        function () {
          $(this).rules("add", {
            required: true,
            messages: {
              // optional
              required: "Please select a Product Condition",
            },
          });
        }
      );
    });
  }

  function update_claim() {
    $(".update_claim")
      .off("click")
      .on("click", function () {
        $id = $(this).data("id");
        $("#update_claim .product-image img").attr(
          "src",
          $("#" + $id + " .ordered-product-image img").attr("src")
        );
        $("#update_claim .article-title").text(
          $("#" + $id + " .product_name").text()
        );
        $("#update_claim .sku").text($("#" + $id + " .sku").text());
        $("#update_claim .order_item_id").text(
          $("#" + $id + " .order_item_id").text()
        );
        $("#update_claim .fsn").text($("#" + $id + " .fsn").text());
        $("#update_claim .return_id").val($("#" + $id + " .return_id").text());
        $("#update_claim .claim_date").val(
          $("#" + $id + " .claim_date").text()
        );
        $("#update_claim .claim_staus").val(
          $("#" + $id + " .claim_staus").text()
        );
        $("#update_claim .claim_id").val($("#" + $id + " .claim_id").text());
        $("#update_claim .product_condition").val(
          $("#" + $id + " .product_condition").text()
        );
        $("#update_claim .order_id").text($("#" + $id + " .order_id").text());
        $("#update_claim .order_date").text(
          $("#" + $id + " .order_date").text()
        );
        $("#update_claim .item_amount").text(
          $("#" + $id + " .item_amount").text()
        );
        $("#update_claim .shipping_amount").text(
          $("#" + $id + " .shipping_amount").text()
        );
        $("#update_claim .amount").text($("#" + $id + " .amount").text());
        $("#update_claim .qty").text($("#" + $id + " .qty").text());
        $("#update_claim .type").text($("#" + $id + " .type").text());
        $("#update_claim .reason").text(
          $("#" + $id + " .return_reason").text()
        );
        $("#update_claim .sub_reason").text(
          $("#" + $id + " .return_sub_reason").text()
        );
        $("#update_claim .customer_comment").text(
          $("#" + $id + " .comments").text() === ""
            ? "No comments from buyer"
            : $("#" + $id + " .comments").text()
        );
        $("#update_claim .tracking_id").text(
          $("#" + $id + " .tracking_id").text()
        );
        $("#update_claim .get_spf").attr(
          "data-order-item-id",
          $("#" + $id + " .order_item_id").text()
        );
        $("#update_claim .get_spf").attr(
          "data-account-name",
          $("#" + $id + " .account_name").text()
        );
        $("#update_claim .get_spf").attr(
          "data-item_amount",
          $("#" + $id + " .item_amount").data("item_amount")
        );
        $("#update_claim .get_spf").attr(
          "data-shipping_amount",
          $("#" + $id + " .shipping_amount").data("shipping_amount")
        );
        $("#update_claim .get_spf").attr(
          "data-total_amount",
          $("#" + $id + " .amount").data("amount")
        );
        $("#update_claim .get_spf").attr(
          "data-return_type",
          $("#" + $id + " .type").text()
        );
        $("#update_claim .sellement_percentage").html("").addClass("hide");

        $("#update_claim .spf_reclaim").removeData([
          "base_incident",
          "returnid",
          "orderItemId",
          "account",
          "content",
          "approved_spf",
        ]);
        $("#update_claim .spf_reclaim").attr(
          "data-base_incident",
          $("#" + $id + " .claim_id").text()
        );
        $("#update_claim .spf_reclaim").attr(
          "data-returnid",
          $("#" + $id + " .return_id").text()
        );
        $("#update_claim .spf_reclaim").attr(
          "data-orderItemId",
          $("#" + $id + " .order_item_id").text()
        );
        $("#update_claim .spf_reclaim").attr(
          "data-account",
          $(".reclaim_details_" + $id).data("account")
        );
        $("#update_claim .spf_reclaim").attr(
          "data-content",
          $(".reclaim_details_" + $id).data("content")
        );
        $("#update_claim .spf_reclaim").attr(
          "data-approved_spf",
          $(".reclaim_details_" + $id).data("approved_spf")
        );

        ClaimValidate.init($id);

        console.log($("#" + $id + " .product_condition").text());
        // console.log($("#"+$id+" input[name=receive_type]").val());
        $("#update_claim .receive_type").removeClass("hide");

        // Let add the dynamic rule after the validate form is initiated
        if ($("#" + $id + " .product_condition").text() == "Undelivered") {
          $("#update_claim .receive_type").removeClass("hide");

          if ($("#" + $id + " input[name=receive_type]").val() == "pod") {
            $("#update_claim .received_on").removeClass("hide");
            // bindDatePicker("#update_claim .form_datetime");

            $("#update-claim input[name=received_on]").rules("add", {
              required: true,
              messages: {
                // optional
                required: "Please select a Received On Date",
              },
            });
          }
        } else {
          $("#update_claim .receive_type").addClass("hide");
          $("#update_claim .received_on").addClass("hide");
          // bindDatePicker("#update_claim .form_datetime");

          $("#update-claim input[name=received_on]").rules("add", {
            required: false,
          });
        }
      });
  }

  function update_claim_id() {
    $(".claim_id").bind("click", function (e) {
      e.preventDefault();
      $id = $(this).closest("div.order-content").attr("id");
      // $.fn.editable.defaults.mode = 'popup';
      $("#" + $id + " .claim_id").editable({
        url: "ajax_load.php?action=update_claim_id",
        type: "text",
        name: "claim_id",
        ajaxOptions: {
          type: "post",
        },
        validate: function (value) {
          if ($.trim(value) == "") return "This field is required";
        },
        error: function (data) {
          return "Unable to update Claim ID. Please try after some time.";
        },
      });
    });
  }

  var LostValidate = (function () {
    return {
      //main function to initiate the module
      init: function (id) {
        var form = $(id);
        var error = $(".alert-danger", form);
        error.hide();

        form.validate({
          // debug: true,
          errorElement: "span", //default input error message container
          errorClass: "help-inline", // default input error message class
          focusInvalid: false, // do not focus the last invalid input
          ignore: "",
          rules: {
            claim_id: {
              minlength: 21,
              maxlength: 21,
              required: true,
            },
          },

          messages: {
            // custom messages for radio buttons and checkboxes
            claim_id: {
              required: "Please enter claim reference number.",
            },
          },

          errorPlacement: function (error, element) {
            // render error placement for each input type
            if (element.attr("name").indexOf("product_condition") >= 0) {
              // for uniform radio buttons, insert the after the given container
              $this_element = element[Object.keys(element)[0]];
              $div_id = $($this_element).attr("class");
              error
                .addClass("no-left-padding")
                .insertAfter("#" + $div_id + " .form_product_condition_error");
            } else if (element.attr("name") == "claim") {
              error
                .addClass("no-left-padding")
                .insertAfter(id + " .form_claim_error");
            } else {
              error.insertAfter(element); // for other inputs, just perform default behavoir
            }
          },

          invalidHandler: function (event, validator) {
            //display error alert on form submit
            error.show();
          },

          highlight: function (element) {
            // hightlight error inputs
            $(element).closest(".help-inline").removeClass("ok"); // display OK icon
            $(element)
              .closest(".control-group")
              .removeClass("success")
              .addClass("error"); // set error class to the control group
          },

          unhighlight: function (element) {
            // revert the change dony by hightlight
            $(element).closest(".control-group").removeClass("error"); // set error class to the control group
          },

          success: function (label) {
            if (
              label.attr("for") == "product_condition" ||
              label.attr("for") == "claim"
            ) {
              // for checkboxes and radio buttons, no need to show OK icon
              label
                .closest(".control-group")
                .removeClass("error")
                .addClass("success");
              label.remove(); // remove error label here
            } else {
              // display success icon for other inputs
              label
                .addClass("valid")
                .addClass("help-inline ok") // mark the current input as valid and display OK icon
                .closest(".control-group")
                .removeClass("error")
                .addClass("success"); // set success class to the control group
            }
          },

          submitHandler: function (form) {
            error.hide();
            $claim_id = $(id + " input[name='claim_id']").val();
            $return_id = $(id + " input[name='return_id']").val();
            $pids = $(id + " input[name='pids']")
              .val()
              .split(",");
            var $condition = {};
            $($pids).each(function (k, v) {
              $condition[v] = $(
                id + " input[name='product_condition[" + v + "]']:checked"
              ).val();
            });

            $(id + " .form-actions .btn-success").attr("disabled", true);
            $(id + " .form-actions i").addClass("icon-refresh");
            $(id + " .re_error").text("");

            $.ajax({
              url: "ajax_load.php?token=" + new Date().getTime(),
              cache: false,
              type: "POST",
              data:
                "action=update_claim_status&return_id=" +
                $return_id +
                "&claim_id=" +
                $claim_id +
                "&condition=" +
                JSON.stringify($condition),
              success: function (s) {
                s = $.parseJSON(s);
                $(id + " btn-success").attr("disabled", false);
                if (s.type == "success") {
                  // save and close
                  setTimeout(function () {
                    $(id).closest("div.modal").modal("hide");
                    $(id + " .form-actions .btn-success").attr(
                      "disabled",
                      false
                    );
                    $(id + " .form-actions i").removeClass("icon-refresh");
                  }, 500);
                  $(form)[0].reset();
                  refreshCount();
                  loadajaxOrders("delivered", "#portlet_delivered");
                }
                if (s.type == "info") {
                  $(id + " .form-actions .btn-success").attr("disabled", false);
                  $(id + " .form-actions i").removeClass("icon-refresh");
                  $(id + " .re_error").text("Claim ID already exists");
                }
                if (s.type == "error") {
                  $(id + " .form-actions .btn-success").attr("disabled", false);
                  $(id + " .form-actions i").removeClass("icon-refresh");
                  $(id + " .re_error").text(
                    "Unable to process request. Please retry"
                  );
                }
                UIToastr.init(s.type, "Claim Update", s.message);
              },
              error: function () {
                // NProgress.done(true);
                alert("Error Processing your Request!!");
              },
            });
          },
        });

        //apply validation on chosen dropdown value change, this only needed for chosen dropdown integration.
        $(".chosen, .chosen-with-diselect", form).change(function () {
          form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
        });
      },
    };
  })();

  var FormValidate = (function () {
    return {
      //main function to initiate the module
      init: function (id, location) {
        var form = $(id);
        var error = $(".alert-danger", form);
        error.hide();

        form.validate({
          errorElement: "span", //default input error message container
          errorClass: "help-inline", // default input error message class
          focusInvalid: false, // do not focus the last invalid input
          ignore: "",
          rules: {
            "product_condition[]": {
              required: true,
            },
            uid: {
              required: true,
            },
            claim: {
              required: true,
            },
          },

          messages: {
            // custom messages for radio buttons and checkboxes
            product_condition: {
              required: "Please select a Product Condition",
            },
            uid: {
              required: "Please select atleast one Product UID",
            },
            claim: {
              required: "Please select a Claim Decision",
            },
          },

          errorPlacement: function (error, element) {
            // render error placement for each input type
            if (element.attr("name").indexOf("product_condition") >= 0) {
              // for uniform radio buttons, insert the after the given container
              $this_element = element[Object.keys(element)[0]];
              $div_id = $($this_element).attr("class");
              error
                .addClass("no-left-padding")
                .insertAfter("#" + $div_id + " .form_product_condition_error");
            } else if (element.attr("name") == "uid") {
              error
                .addClass("no-left-padding")
                .insertAfter(id + " .form_uid_error");
            } else if (element.attr("name") == "claim") {
              error
                .addClass("no-left-padding")
                .insertAfter(id + " .form_claim_error");
            } else {
              error.insertAfter(element); // for other inputs, just perform default behavoir
            }
          },

          invalidHandler: function (event, validator) {
            //display error alert on form submit
            error.show();
          },

          highlight: function (element) {
            // hightlight error inputs
            $(element).closest(".help-inline").removeClass("ok"); // display OK icon
            $(element)
              .closest(".control-group")
              .removeClass("success")
              .addClass("error"); // set error class to the control group
          },

          unhighlight: function (element) {
            // revert the change dony by hightlight
            $(element).closest(".control-group").removeClass("error"); // set error class to the control group
          },

          success: function (label) {
            if (
              label.attr("for") == "product_condition" ||
              label.attr("for") == "claim"
            ) {
              // for checkboxes and radio buttons, no need to show OK icon
              label
                .closest(".control-group")
                .removeClass("error")
                .addClass("success");
              label.remove(); // remove error label here
            } else {
              // display success icon for other inputs
              label
                .addClass("valid")
                .addClass("help-inline ok") // mark the current input as valid and display OK icon
                .closest(".control-group")
                .removeClass("error")
                .addClass("success"); // set success class to the control group
            }
          },

          submitHandler: function (form) {
            error.hide();
            $claim_id = "";
            $re_claim = $(id + " input[name='claim']:checked").val();
            if ($re_claim == "yes" || $re_claim == "re-claim") {
              $claim_id = $(id + " input[name='claim_id']").val();
            }

            $return_id = $(id + " input[name='return_id']").val();
            $pids = $(id + " input[name='pids']")
              .val()
              .split(",");
            var $condition = {};
            $($pids).each(function (k, v) {
              $condition[v] = $(
                id + " input[name='product_condition[" + v + "]']:checked"
              ).val();
            });

            $uid = $(id + " input[name='uid']:checked").val();
            $(id + " .form-actions .btn-success").attr("disabled", true);
            $(id + " .form-actions i").addClass("icon-refresh");
            $(id + " .re_error").text("");

            $.ajax({
              url: "ajax_load.php?token=" + new Date().getTime(),
              cache: false,
              type: "POST",
              data:
                "action=update_claim_status&return_id=" +
                $return_id +
                "&claim_id=" +
                $claim_id +
                "&condition=" +
                JSON.stringify($condition) +
                "&re_claim=" +
                $re_claim +
                "&uid=" +
                $uid,
              success: function (s) {
                s = $.parseJSON(s);
                $(id + " btn-success").attr("disabled", false);
                if (s.type == "success") {
                  // save and close
                  setTimeout(function () {
                    $(id).closest("div.modal").modal("hide");
                    $(id + " .form-actions .btn-success").attr(
                      "disabled",
                      false
                    );
                    $(id + " .form-actions i").removeClass("icon-refresh");
                  }, 500);
                  $(form)[0].reset();
                  refreshCount();
                  loadajaxOrders(location, "#portlet_" + location);
                  console.log("in");
                }
                if (s.type == "existing") {
                  $(id + " .form-actions .btn-success").attr("disabled", false);
                  $(id + " .form-actions i").removeClass("icon-refresh");
                  $(id + " .re_error").text("Claim ID already exists");
                }
                if (s.type == "error") {
                  $(id + " .form-actions .btn-success").attr("disabled", false);
                  $(id + " .form-actions i").removeClass("icon-refresh");
                  $(id + " .re_error").text(
                    "Unable to process request. Please retry"
                  );
                }
              },
              error: function () {
                // NProgress.done(true);
                alert("Error Processing your Request!!");
              },
            });
          },
        });

        //apply validation on chosen dropdown value change, this only needed for chosen dropdown integration.
        $(".chosen, .chosen-with-diselect", form).change(function () {
          form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
        });
      },
    };
  })();

  var ClaimValidate = (function () {
    return {
      init: function (id) {
        var form = $("#update-claim");
        var error = $(".alert-danger", form);

        form.validate({
          errorElement: "span", //default input error message container
          errorClass: "help-inline", // default input error message class
          focusInvalid: false, // do not focus the last invalid input
          ignore: "",
          rules: {
            claim_staus: {
              required: true,
            },
            claim_comments: {
              required: true,
            },
          },

          messages: {
            // custom messages for radio buttons and checkboxes
            claim_staus: {
              required: "Please select a Claim Status",
            },
            claim_comments: {
              required: "Please add Claim Comments",
            },
            claim_reimbursment: {
              required: "Please add Claim Reimbursment Amount",
            },
          },

          errorPlacement: function (error, element) {
            // render error placement for each input type
            // if (element.attr("name") == "claim_staus") { // for uniform radio buttons, insert the after the given container
            // 	error.addClass("no-left-padding").insertAfter("#form_product_condition_error");
            if (element.parent(".input-group").size() > 0) {
              error.insertAfter(element.parent(".input-group"));
            } else if (element.parents(".radio-inline").size() > 0) {
              error.appendTo(
                element.parents(".radio-inline").attr("data-error-container")
              );
              error
                .addClass("no-left-padding")
                .insertAfter("#form_product_condition_error");
            } else {
              error.insertAfter(element); // for other inputs, just perform default behavoir
            }
          },

          invalidHandler: function (event, validator) {
            //display error alert on form submit
            error.show();
          },

          highlight: function (element) {
            // hightlight error inputs
            $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
          },

          unhighlight: function (element) {
            // revert the change dony by hightlight
            $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
          },

          success: function (label) {
            if (label.attr("for") == "claim_staus") {
              // for checkboxes and radio buttons, no need to show OK icon
              label.closest(".form-group").removeClass("has-error");
              label.remove(); // remove error label here
            } else {
              // display success icon for other inputs
              label.closest(".form-group").removeClass("error"); // set success class to the control group
            }
          },

          submitHandler: function (form) {
            error.hide();
            // submit_update_claim(id, $('#'+form2.id+ " input[name='claim_staus']:checked").val(), $('#'+form2.id+ " textarea[name='claim_comments']").val(), $('#'+form2.id+ " input[name='claim_reimbursment']").val() );
            var return_id = $("#" + form.id + " input[name='return_id']").val();
            var claim_status = $(
              "#" + form.id + " input[name='claim_staus']:checked"
            ).val();
            var claim_comments = $(
              "#" + form.id + " textarea[name='claim_comments']"
            ).val();
            var claim_amount = $(
              "#" + form.id + " input[name='claim_reimbursment']"
            ).val();
            var product_condition = $(
              "#" + form.id + " input[name='product_condition']"
            ).val();

            console.log(product_condition);

            var received_on = "";
            if (product_condition == "Undelivered") {
              received_on = $(
                "#" + form.id + " input[name='received_on']"
              ).val();
              received_on = "&received_on=" + received_on;
            }

            if (claim_amount == "") {
              claim_amount = 0.0;
            }

            $(".form-actions .btn-success").attr("disabled", true);
            $(".form-actions i").addClass("icon-refresh");
            $(".re_error").text("");

            $.ajax({
              url: "ajax_load.php?token=" + new Date().getTime(),
              cache: false,
              type: "POST",
              data:
                "action=update_claim_details&return_id=" +
                return_id +
                "&claim_status=" +
                claim_status +
                "&claim_comments=" +
                claim_comments +
                "&claim_amount=" +
                claim_amount +
                received_on,
              async: true,
              success: function (s) {
                s = $.parseJSON(s);
                if (s.type == "success") {
                  // save and close
                  setTimeout(function () {
                    $("#update_claim .close").trigger("click");
                    // $('#update_claim').modal('close');
                    $(".form-actions .btn-success").attr("disabled", false);
                    $(".form-actions i").removeClass("icon-refresh");
                    $(".claim_reimbursment").val("");
                    $(".spf_reclaim").addClass("hide");
                    $(".sellement_percentage").addClass("hide");
                  }, 2000);
                  $(form)[0].reset();
                  refreshCount();
                  loadajaxOrders("return_claimed", "#portlet_return_claimed");
                }
                if (s.type == "error") {
                  console.log("in error");
                  $(".form-actions .btn-success").attr("disabled", false);
                  $(".form-actions i").removeClass("icon-refresh");
                  $(".re_error").text(
                    "Unable to process request. Please retry"
                  );
                }
                $("#" + id + " btn-success").attr("disabled", false);
                UIToastr.init(s.type, "Claim Update", s.message);
              },
              error: function () {
                // NProgress.done(true);
                alert("Error Processing your Request!!");
              },
            });
          },
        });

        //apply validation on chosen dropdown value change, this only needed for chosen dropdown integration.
        $(".chosen, .chosen-with-diselect", form).change(function () {
          form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
        });
      },
    };
  })();

  var SPFValidate = (function () {
    return {
      init: function (spf_files) {
        var form = $("#claim-spf");
        var error = $(".alert-danger", form);

        form.validate({
          errorElement: "span", //default input error message container
          errorClass: "help-inline", // default input error message class
          focusInvalid: false, // do not focus the last invalid input
          ignore: "",

          messages: {
            // custom messages for radio buttons and checkboxes
            claim: {
              required: "Please select a Claim Decision",
            },
          },

          errorPlacement: function (error, element) {
            // render error placement for each input type
            if (element.parent(".input-group").size() > 0) {
              error.insertAfter(element.parent(".input-group"));
            } else if (element.attr("data-error-container")) {
              error.appendTo(element.attr("data-error-container"));
            } else if (element.parents(".radio-list").size() > 0) {
              error.appendTo(
                element.parents(".radio-list").attr("data-error-container")
              );
            } else if (element.parents(".radio-inline").size() > 0) {
              error.appendTo(
                element.parents(".radio-inline").attr("data-error-container")
              );
            } else if (element.parents(".checkbox-list").size() > 0) {
              error.appendTo(
                element.parents(".checkbox-list").attr("data-error-container")
              );
            } else if (element.parents(".checkbox-inline").size() > 0) {
              error.appendTo(
                element.parents(".checkbox-inline").attr("data-error-container")
              );
            } else {
              error.insertAfter(element); // for other inputs, just perform default behavior
            }
          },

          invalidHandler: function (event, validator) {
            //display error alert on form submit
            error.show();
          },

          highlight: function (element) {
            // hightlight error inputs
            $(element).closest(".help-inline").removeClass("ok"); // display OK icon
            $(element)
              .closest(".control-group")
              .removeClass("success")
              .addClass("error"); // set error class to the control group
          },

          unhighlight: function (element) {
            // revert the change dony by hightlight
            $(element).closest(".control-group").removeClass("error"); // set error class to the control group
          },

          success: function (label) {
            if (
              label.attr("for") == "product_condition" ||
              label.attr("for") == "claim"
            ) {
              // for checkboxes and radio buttons, no need to show OK icon
              label
                .closest(".control-group")
                .removeClass("error")
                .addClass("success");
              label.remove(); // remove error label here
            } else {
              // display success icon for other inputs
              label
                .addClass("valid")
                .addClass("help-inline ok") // mark the current input as valid and display OK icon
                .closest(".control-group")
                .removeClass("error")
                .addClass("success"); // set success class to the control group
            }
          },

          submitHandler: function (form) {
            error.hide();
            $(".spf_files").val(JSON.stringify(spf_files));
            var data = $(form).serialize();
            $(".form-actions .btn-success", form)
              .attr("disabled", true)
              .find("i")
              .addClass("fa fa-sync fa-spin");
            $(".re_error").text("");

            $.ajax({
              url: "ajax_load.php?token=" + new Date().getTime(),
              cache: false,
              type: "POST",
              data: "action=spf_generate&type=create_spf&" + data,
              async: true,
              success: function (s) {
                s = $.parseJSON(s);
                if (s.type == "success") {
                  // save and close
                  setTimeout(function () {
                    $("#search_claim_return .close").trigger("click");
                    reset_spf_modal_inputs();
                  }, 100);
                  $(form)[0].reset();
                  refreshCount();
                  loadajaxOrders("return_received", "#portlet_return_received");
                } else {
                  $(".re_error").text(s.message);
                }
                $(".form-actions .btn-success", form)
                  .attr("disabled", false)
                  .find("i")
                  .removeClass("fa fa-sync fa-spin");
                UIToastr.init(s.type, "Claim Update", s.message);
              },
              error: function () {
                // NProgress.done(true);
                alert("Error Processing your Request!!");
              },
            });
          },
        });

        // //apply validation on chosen dropdown value change, this only needed for chosen dropdown integration.
        // $('.chosen, .chosen-with-diselect', form).change(function () {
        // 	form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
        // });
      },
    };
  })();

  var initTable = function (location, fk_accounts) {
    var t_options =
      '<option value=""><option value="">&nbsp;</option><option value="NON_FBF">NON FBF</option><option value="FBF_LITE">FBF LITE</option>';
    var options = '<option value="">&nbsp;</option>';
    for (i = 0; i < fk_accounts.length; i++) {
      options +=
        '<option value="' +
        fk_accounts[i].account_name +
        '">' +
        fk_accounts[i].account_name +
        "</option>";
    }

    $rows = [
      { orderable: false },
      { orderable: false },
      { orderable: false },
      { orderable: false },
      { orderable: false },
    ];
    $diff = "";
    if (handler == "return") {
      $rows = [
        { orderable: false },
        { orderable: false },
        null,
        { orderable: false },
        { orderable: false },
      ];
      $diff = { orderable: true, targets: 2 };
    }

    var sDome =
      '<"row" <"col-md-4 col-sm-12" l><"col-md-8 col-sm-12 dataTables_length" <"filters" <"#order_type.dt_filter"> <"#account_name.dt_filter"> > f>>rt<"table-scrollable" <"col-md-5 col-sm-12" i><"col-md-7 col-sm-12" p>>';

    var table = $(location + " table");
    table.dataTable({
      lengthMenu: [
        [20, 50, 100, 200, 500, -1],
        [20, 50, 100, 200, 500, "All"], // change per page values here
      ],
      // set the initial value
      pageLength: 20,
      language: {
        lengthMenu:
          "  _MENU_ records per page | <span class='selected_checkbox_count'>0</span> selected",
      },
      bDestroy: true,
      order: [[2, "asc"]],
      bSort: false,
      ordering: true,
      sProcessing: "<i class='fa fa-sync fa-spin'></i>",
      processing: true,
      deferRender: true,

      sDom: sDome,
      fnDrawCallback: function () {
        $(".dataTables_paginate a").bind("click", function () {
          App.scrollTop();
        });
        // App.initUniform();
      },
      /*initComplete: function () {
				var index = 0;
				this.api().columns().every( function () {
					if (index == 3 || index == 4){
						if (index == 3){
							var appendTo = "#account_name";
							var select_type = "Account";
						} else {
							var appendTo = "#order_type";
							var select_type = "Type";
						}

						var column = this;
						var select = $("<label>"+select_type+" : <select class='form-control input-inline'></select></label>")
							.appendTo( appendTo )
							.on( 'change', function () {
								var val = $.fn.dataTable.util.escapeRegex(
									$(this).val()
								);

								column
									.search( val ? '^'+val+'$' : '', true, false )
									.draw();
							} );

						column.data().unique().sort().each( function ( d, j ) {
							select.append( '<option value="'+d+'">'+d+'</option>' )
						} );

						index++;
					}
				} );
			},*/
      columns: $rows,
      columnDefs: [
        {
          className: "return_hide_column",
          targets: [2, 3, 4],
        },
        $diff,
      ],
    });

    // portlet_return_received
    var tableWrapper = $(location + "_wrapper");

    table.find(".group-checkable").change(function () {
      var set = jQuery(this).attr("data-set");
      var checked = jQuery(this).is(":checked");
      jQuery(set).each(function () {
        if (checked) {
          $(this).attr("checked", true);
          $(this).parents("tr").addClass("active");
        } else {
          $(this).attr("checked", false);
          $(this).parents("tr").removeClass("active");
        }
      });
      jQuery.uniform.update(set);
    });

    table.on("change", "tbody tr .checkboxes", function () {
      $(this).parents("tr").toggleClass("active");
    });

    tableWrapper
      .find(".dataTables_length select")
      .addClass("form-control input-xsmall input-inline"); // modify table per page dropdown

    $("#order_type").append(
      "<label>Type: <select class='form-control input-inline'>" +
        t_options +
        "</select></label>"
    );
    $("#order_type select").addClass("input-small");

    $("#account_name").append(
      "<label>Account: <select class='form-control input-inline'>" +
        options +
        "</select></label>"
    );
    $("#account_name select").addClass("input-small");

    $("#order_type select").bind("change", function () {
      console.log("order_type change");
      var val = $(this).val();
      table
        .api()
        .columns(4)
        .search(val ? "^" + val + "$" : "", true, false)
        .draw();
    });
    $("#account_name select").bind("change", function () {
      console.log("account_name change");
      var val = $(this).val();
      table
        .api()
        .columns(3)
        .search(val ? "^" + val + "$" : "", true, false)
        .draw();
    });
  };

  var order_import_handleValidation = function () {
    var form1 = $("#order-import");
    var error1 = $(".alert-danger", form1);
    var success1 = $(".alert-success", form1);

    form1.validate({
      errorElement: "span", //default input error message container
      errorClass: "help-block", // default input error message class
      focusInvalid: false, // do not focus the last invalid input
      ignore: "",
      rules: {
        order_item_ids: {
          required: true,
        },
        account_id: {
          required: true,
        },
      },

      invalidHandler: function (event, validator) {
        //display error alert on form submit
        error1.show();
        App.scrollTo(error1, -200);
      },

      highlight: function (element) {
        // hightlight error inputs
        $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
      },

      unhighlight: function (element) {
        // revert the change done by hightlight
        $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
      },

      success: function (label) {
        label.closest(".form-group").removeClass("has-error"); // set success class to the control group
      },

      errorPlacement: function (error, element) {
        if (element.attr("name") == "orders_csv") {
          error.appendTo("#orders_csv_error");
        } else {
          error.appendTo(element.parent("div"));
        }
      },

      submitHandler: function (form) {
        error1.hide();

        $(".btn-submit").attr("disabled", true);
        $(".btn-submit i").addClass("fa fa-sync fa-spin");

        $order_item_id = $("#order_item_id", form1).val().replace(/ /g, "");
        var order_item_ids = $order_item_id.split(",");
        var account_id = $("#account_id option:selected", form1).val();
        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          type: "POST",
          data:
            "action=order_import&account_id=" +
            account_id +
            "&order_item_ids=" +
            order_item_ids,
          success: function (s) {
            s = $.parseJSON(s);
            $string = s.total + " Total Orders Imported. ";
            if (s.success != 0) {
              $string += s.success + " Orders Successfully added.";
            }
            if (s.existing != 0) {
              $string += s.existing + " Orders already exists.";
            }
            success1.show().text($string);
            setTimeout(function () {
              $("#order_import").modal("hide");
            }, 2000);

            refreshCount();
            loadajaxOrders("new", "#portlet_new");
            $(".btn-submit i").removeClass("fa fa-sync fa-spin");
            $(".btn-submit").attr("disabled", false);
          },
          error: function () {
            console.log("Error Processing your Request!!");
          },
        });
      },
    });
  };

  var order_import_fbf_handleValidation = function () {
    var form1 = $("#order-import-fbf");
    var error1 = $(".alert-danger", form1);
    var success1 = $(".alert-success", form1);

    form1.validate({
      errorElement: "span", //default input error message container
      errorClass: "help-block", // default input error message class
      focusInvalid: false, // do not focus the last invalid input
      ignore: "",
      rules: {
        orders_csv: {
          required: true,
        },
        account_id: {
          required: true,
        },
      },

      invalidHandler: function (event, validator) {
        //display error alert on form submit
        error1.show();
        App.scrollTo(error1, -200);
      },

      highlight: function (element) {
        // hightlight error inputs
        $(element).closest(".form-group").addClass("has-error"); // set error class to the control group
      },

      unhighlight: function (element) {
        // revert the change done by hightlight
        $(element).closest(".form-group").removeClass("has-error"); // set error class to the control group
      },

      success: function (label) {
        label.closest(".form-group").removeClass("has-error"); // set success class to the control group
      },

      errorPlacement: function (error, element) {
        if (element.attr("name") == "orders_csv") {
          error.appendTo("#orders_csv_error");
        } else {
          error.appendTo(element.parent("div"));
        }
      },

      submitHandler: function (form) {
        error1.hide();

        $(".form-actions .btn-success", form1).attr("disabled", true);
        $(".form-actions i", form1).addClass("fa fa-sync fa-spin");

        var account_id = $("#account_id option:selected", form1).val();
        var formData = new FormData();
        formData.append("action", "order_import_fbf");
        formData.append("orders_csv", $("#orders_csv")[0].files[0]);
        formData.append("account_id", account_id);

        $.ajax({
          url: "ajax_load.php?token=" + new Date().getTime(),
          cache: false,
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          mimeType: "multipart/form-data",
          async: false,
          success: function (s) {
            s = $.parseJSON(s);
            $string = s.total + " Total Orders. ";
            if (s.success != 0) {
              $string += s.success + " Orders Successfully added.";
            }
            if (s.existing != 0) {
              $string += s.existing + " Orders already exists.";
            }
            if (s.skipped != 0) {
              $string += s.skipped + " Orders skipped.";
            }
            success1.show().text($string);
            setTimeout(function () {
              $("#order_import_fbf").modal("hide");
              // reset the form and alerts
              $("#account_id", form1).select2("val", "");
              $(form1)[0].reset();
              success1.hide().text("");
              error1.hide().text("");
            }, 2000);
            // refresh the grid
            refreshCount();
            loadajaxOrders();
            $(".form-actions .btn-success", form1).attr("disabled", false);
            $(".form-actions i", form1).removeClass("fa fa-sync fa-spin");
          },
          error: function () {
            // NProgress.done(true);
            alert("Error Processing your Request!!");
          },
        });
      },
    });
  };

  var bindDatePicker = function (element) {
    $(element).datepicker({
      opens: "left",
      format: "M dd, yyyy",
      rtl: App.isRTL(),
      autoclose: true,
    });
  };

  var getUrlParam = function getUrlParam(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
      sURLVariables = sPageURL.split("&"),
      sParameterName,
      i;

    for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split("=");

      if (sParameterName[0] === sParam) {
        return sParameterName[1] === undefined ? true : sParameterName[1];
      }
    }
  };
}

function init_flagging() {
  $(".flag").bind("click", function (e) {
    e.preventDefault();
    var flag = $(this);
    var is_active = flag.hasClass("active");
    var order_item_id = flag.data("itemid");
    var currentReq = null;
    flag.prop("disabled", true);
    currentReq = $.ajax({
      url: "ajax_load.php?token=" + new Date().getTime(),
      cache: false,
      type: "POST",
      data:
        "action=set_flag&order_item_id=" +
        order_item_id +
        "&flag=" +
        !is_active,
      beforeSend: function () {
        if (currentRequest != null) {
          currentRequest.abort();
        } else {
          currentRequest = currentReq;
        }
      },
      success: function (s) {
        s = $.parseJSON(s);
        if (s.type == "success") {
          if (!is_active) {
            flag.addClass("active");
          } else {
            flag.removeClass("active");
          }
        }
        flag.prop("disabled", false);
      },
      error: function () {
        flag.prop("disabled", false);
        alert("Error Processing your Request!!");
      },
    });
  });
}

// Handles Bootstrap Tooltips.
var handleTooltips = function () {
  jQuery(".tooltips").tooltip();
};

var FormDropzone = (function () {
  return {
    //main function to initiate the module
    init: function (spf_files, account_id) {
      // destroy if already attached
      if (Dropzone.instances.length > 0)
        Dropzone.instances.forEach((bz) => bz.destroy());
      // set the dropzone container id
      var id = "#claim_images_dropzone";
      $(id).addClass("dropzone");
      // var error = false;

      // set the preview element template
      var previewNode = $(id + " .dropzone-item");
      previewNode.id = "";
      var previewTemplate =
        '<div class="dropzone-item col-md-6" style="display:none"><div class="dz-image"><img data-dz-thumbnail=""></div><div class="dropzone-file"><div class="dropzone-filename" title="some_image_file_name.jpg"><span data-dz-name="">some_image_file_name.jpg</span><strong>(<span data-dz-size="">340kb</span>)</strong></div><div class="dropzone-error" data-dz-errormessage=""></div></div><div class="dropzone-progress"><div class="progress"><div class="progress-bar bg-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-dz-uploadprogress=""></div></div></div><div class="dropzone-toolbar"><span class="dropzone-start"><i class="fa fa-upload"></i></span><span class="dropzone-cancel" data-dz-remove="" style="display: none;"><i class="fa fa-times"></i></span><span class="dropzone-delete" data-dz-remove=""><i class="fa fa-trash-alt"></i></span></div></div>';
      previewNode.remove();

      var myDropzone4 = new Dropzone(id, {
        // Make the whole body a dropzone
        url:
          "ajax_load.php?action=spf_generate&type=upload_image_attachements&account_id=" +
          account_id, // Set the url for your upload script location
        paramName: "file", // The name that will be used to transfer the file
        minFiles: 3,
        maxFiles: 10,
        maxFilesize: 10, // MB
        acceptedFiles: "image/*",
        timeout: 180000,
        parallelUploads: 2,
        previewTemplate: previewTemplate,
        autoQueue: false, // Make sure the files aren't queued until manually added
        previewsContainer: id + " .dropzone-items", // Define the container to display the previews
        clickable: id + " .dropzone-select", // Define the element that should be used as click trigger to select files.
      });

      myDropzone4.on("addedfile", function (file) {
        // Hookup the start button
        file.previewElement.querySelector(id + " .dropzone-start").onclick =
          function () {
            myDropzone4.enqueueFile(file);
          };
        $(document)
          .find(id + " .dropzone-item")
          .css("display", "");
        $(id + " .dropzone-upload, " + id + " .dropzone-remove-all").css(
          "display",
          "inline-block"
        );
        $(".form-actions .btn-success").attr("disabled", true);
        var file_data = {
          name: file.name,
          size: file.size,
          type: null,
          fileUploadUrl: null,
          path: null,
          fileName: file.name,
        };
        spf_files.push(file_data);
        $(".dropzone-start").attr("disabled", false);
      });

      // Update the total progress bar
      myDropzone4.on("totaluploadprogress", function (progress) {
        $(this)
          .find(id + " .progress-bar")
          .css("width", progress + "%");
      });

      myDropzone4.on("sending", function (file) {
        // Show the total progress bar when upload starts
        $(id + " .progress-bar").css("opacity", "1");
        // And disable the start button
        file.previewElement
          .querySelector(id + " .dropzone-start")
          .setAttribute("disabled", "disabled");
        // Disable the updload button to avoid multi upload request
        $(".dropzone-start").attr("disabled", true);
      });

      // Hide the total progress bar when nothing's uploading anymore
      myDropzone4.on("complete", function (progress) {
        var thisProgressBar = id + " .dz-complete";
        setTimeout(function () {
          $(
            thisProgressBar +
              " .progress-bar, " +
              thisProgressBar +
              " .progress, " +
              thisProgressBar +
              " .dropzone-start"
          ).css("opacity", "0");
        }, 300);
      });

      // Setup the buttons for all transfers
      document.querySelector(id + " .dropzone-upload").onclick = function () {
        $(id + " .dropzone-upload i").addClass("fa fa-sync fa-spin");
        this.setAttribute("disabled", true);
        $(".dropzone-error").text("");
        myDropzone4.enqueueFiles(
          myDropzone4.getFilesWithStatus(Dropzone.ADDED)
        );
      };

      // Setup the button for remove all files
      document.querySelector(id + " .dropzone-remove-all").onclick =
        function () {
          $(id + " .dropzone-upload, " + id + " .dropzone-remove-all").css(
            "display",
            "none"
          );
          myDropzone4.removeAllFiles(true);
          spf_files = [];
          $(".form-actions .btn-success").attr("disabled", true);
        };

      // On all files completed upload
      myDropzone4.on("queuecomplete", function (progress) {
        // if (!error){
        $(id + " .dropzone-upload")
          .attr("disabled", false)
          .css("display", "none")
          .find("i")
          .removeClass("fa fa-sync fa-spin");
        $(".form-actions .btn-success").attr("disabled", false);
        // }
      });

      // On all files removed
      myDropzone4.on("removedfile", function (file) {
        if (myDropzone4.files.length < 1) {
          $(id + " .dropzone-upload, " + id + " .dropzone-remove-all").css(
            "display",
            "none"
          );
        }

        spf_files.find((o, i) => {
          if (o.name === file.name) {
            spf_files.splice(i, 1);
            return true; // stop searching
          }
        });
      });

      myDropzone4.on("success", function (file, response) {
        response = $.parseJSON(response);
        if (response.type == "success") {
          spf_files.find((o, i) => {
            if (o.name === file.name) {
              spf_files[i].fileUploadUrl = response.url;
              spf_files[i].path = response.url;
              $(".dropzone-error").text("");
              return true; // stop searching
            }
          });
        }
      });

      myDropzone4.on("error", function (file, response) {
        file.status = Dropzone.ADDED;
        response = $.parseJSON(response);
        $(file.previewElement)
          .find(".dropzone-error")
          .text("Error: " + response.message);
        setTimeout(function () {
          $(
            id +
              " .progress-bar, " +
              id +
              " .progress, " +
              id +
              " .dropzone-start"
          ).css("opacity", "1");
        }, 350);
        return false;
      });
    },
  };
})();
