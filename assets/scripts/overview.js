var Overview = function () {
	var start_date = '',
		end_date = '',
		o_type = '',
		o_sel_account = [],
		o_sel_brand = [],
		r_type = 'orders',
		r_sel_account = [],
		r_sel_brand = [],
		s_sel_account = [],
		s_sel_brand = [],
		rt_sel_account = [],
		rt_sel_brand = [];

	var flipkartStartup = function () {
		// Append Marketplace and Account details 
		var a_options = '';
		$.each(accounts, function (account_k, account) {
			if (account_k == 'flipkart') {
				$.each(account, function (k, v) {
					a_options += "<label><input type='checkbox' name='accounts' value=" + v.account_id + " checked >" + v.account_name + "</label>";
				});
			}
		});
		$('.flipkart_accounts').empty().append(a_options);

		var a_options = '';
		$.each(brands.opt, function (k, v) {
			a_options += "<label><input type='checkbox' name='accounts' value=" + v.brandid + " checked >" + v.brandName + "</label>";
		});
		$('.product_brands').empty().append(a_options);


		App.addResponsiveHandler(function () {
			jQuery('.vmaps').each(function () {
				var map = jQuery(this);
				map.width(map.parent().width());
			});
		});
	};

	var flipkartToday = function () {
	};

	var flipkartOverall = function () {

		$(".stats-overview.overview_loading").show();
		$(".stats-overview.overview_details").hide();

		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime() + "&action=overview_get_overall&start_date=" + start_date + "&end_date=" + end_date,
			cache: false,
			type: 'POST',
			// processData: false,
			async: true,
			success: function (s) {
				$count = $.parseJSON(s);

				$(".previous .previous_dates").text($count.dates.previous_start_date + ' - ' + $count.dates.previous_end_date + ' : ');
				$.each($count.current, function (key, value) {
					if ($count.difference[key] < 0) {
						$(".overview_" + key + " .display").addClass('bad');
					} else if ($count.difference[key] > 0 && $count.difference[key] < 10) {
						$(".overview_" + key + " .display").addClass('ok');
					} else {
						$(".overview_" + key + " .display").addClass('good');
					}
					$deno = "";
					if (key == "average_cp" || key == "average_sp" || key == "sales") {
						$deno = "₹";
					}
					$(".overview_" + key + " .display .percent").text($count.difference[key] + '%');
					$(".overview_" + key + " .details .numbers").text($deno + value);
					$(".overview_" + key + " .previous .previous_value").text($count.previous[key]);
				});

				// Hide the spinner and show the data
				$(".stats-overview.overview_loading").hide();
				$(".stats-overview.overview_details").show();
			},
			error: function (e) {
				alert('Error Processing your Request!!');
			}
		});
	};

	var flipkartCharts = function () {

		if (!jQuery.plot) {
			return;
		}

		function showTooltip(x, y, contents) {
			$('<div id="tooltip" class="chart-tooltip">' + contents + '<\/div>').css({
				position: 'absolute',
				display: 'none',
				top: y - 115,
				width: 90,
				left: x - 40,
				border: '0px solid #ccc',
				padding: '2px 6px',
				'background-color': '#fff',
				'text-align': 'center',
			}).appendTo("body").fadeIn(200);
		}

		var draw_bar_chart = function (order_type, account_id, brand_id, start_date, end_date) {
			// $count = overview_get_orders_count(order_type, account_id, brand_id, start_date, end_date);
			start_date = typeof start_date !== 'undefined' ? start_date : "";
			end_date = typeof end_date !== 'undefined' ? end_date : "";
			var order_type = typeof order_type !== 'undefined' ? order_type : "";
			var account_id = typeof account_id !== 'undefined' ? account_id : "";
			var brand_id = typeof brand_id !== 'undefined' ? brand_id : "";

			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime() + "&action=overview_get_orders_count&type=" + order_type + "&account_id=" + account_id + "&brand_id=" + brand_id + "&start_date=" + start_date + "&end_date=" + end_date,
				cache: false,
				type: 'POST',
				// processData: false,
				async: true,
				success: function (s) {
					$count = $.parseJSON(s);

					var orders = Object.entries($count.orders),
						returns = Object.entries($count.returns),
						replacements = Object.entries($count.replacements),
						average = Object.entries($count.average),
						start_date = $count.dates[0],
						end_date = $count.dates[1]
					tick_size = $count.dates[2];

					if ($('#fk_orders_statistics').size() != 0) {

						$('#orders_statistics_loading').hide();
						$('#orders_statistics_content').show();

						data = [orders, returns, replacements, average];

						var plot_statistics = $.plot($("#fk_orders_statistics"), [{
							data: orders,
							label: "Orders"
						}, {
							data: returns,
							label: "Returns"
						}, {
							data: replacements,
							label: "Replacements"
						}, {
							data: average,
							label: "Average"
						}
						], {
							series: {
								lines: {
									show: true,
									lineWidth: 2,
									fill: true,
									fillColor: {
										colors: [{
											opacity: 0.1
										}, {
											opacity: 0.05
										}
										]
									}
								},
								points: {
									show: true
								},
								shadowSize: 2
							},
							grid: {
								hoverable: true,
								clickable: true,
								tickColor: "#eee",
								borderWidth: 0,
								mouseActiveRadius: 50,
							},
							colors: ["#37b7f3", "#d12610", "#52e136", "#bbbbbb"],
							xaxis: {
								mode: "time",
								tickSize: [tick_size, "day"],
								axisLabel: "Date",
								min: (new Date(start_date * 1000)).getTime(),
								max: (new Date(end_date * 1000)).getTime(),
								timeformat: "%d %b",
							},
							yaxis: {
								min: 0,
								ticks: 10,
							},
						});

						var previousPoint = null;
						var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
						$("#fk_orders_statistics").bind("plothover", function (event, pos, item) {
							$("#x").text(pos.x.toFixed(2));
							$("#y").text(pos.y.toFixed(2));
							if (item) {
								if (previousPoint != item.dataIndex) {
									previousPoint = item.dataIndex;
									$("#tooltip").remove();

									var d = new Date(item.datapoint[0]);
									n = d.toISOString();
									var curr_date = d.getDate(),
										curr_month = monthNames[d.getMonth()],
										curr_year = d.getFullYear();

									y1 = data[0][item.dataIndex][1];
									y2 = data[1][item.dataIndex][1];
									y3 = data[2][item.dataIndex][1];
									y4 = data[3][item.dataIndex][1];

									$content = '<div class="date">' + curr_date + ' ' + curr_month + ' ' + curr_year + '</div><div class="label label-info">Orders: ' + y1 + ' </div><div class="label label-danger">Returns: ' + y2 + ' </div><div class="label label-success">Replaces: ' + y3 + ' </div><div class="label label-warning">Average: ' + y4 + ' </div>';
									showTooltip(item.pageX, item.pageY, $content);
								}
							} else {
								$("#tooltip").remove();
								previousPoint = null;
							}
						});
					}
				},
				error: function (e) {
					alert('Error Processing your Request!!');
				}
			});
		};

		draw_bar_chart(o_type, o_sel_account, o_sel_brand, start_date, end_date);

		$('#overview_order_type input:radio').bind('change', function () {
			$('#orders_statistics_loading').show();
			$('#orders_statistics_content').hide();
			o_type = $(this).val();
			draw_bar_chart(o_type, o_sel_account, o_sel_brand, start_date, end_date);
		});

		$('.orders .flipkart_accounts_group').on('hide.bs.dropdown', function () {
			$('#orders_statistics_loading').show();
			$('#orders_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				o_sel_account = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					o_sel_account.push($(this).val());
				});
				o_sel_account = o_sel_account.join(",");
				draw_bar_chart(o_type, o_sel_account, o_sel_brand, start_date, end_date);
			}
		});

		$('.orders .flipkart_brands_group').on('hide.bs.dropdown', function () {
			$('#orders_statistics_loading').show();
			$('#orders_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				o_sel_brand = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					o_sel_brand.push($(this).val());
				});
				o_sel_brand = o_sel_brand.join(",");
				draw_bar_chart(o_type, o_sel_account, o_sel_brand, start_date, end_date);
			}
		});
	};

	var flipkartJQVMAP = function () {

		var sample_data = "";
		var overview_get_region_count = function (order_type, account_id, brand_id, start_date, end_date) {
			var content = "";
		};

		var showMap = function (type) {
			jQuery('.vmaps').hide();
			jQuery('#vmap_' + type).show();
		}

		var destroyMap = function (type) {
			$('#vmap_' + type).html("");
		}

		var setMap = function (order_type, account_id, brand_id, start_date, end_date) {
			destroyMap(order_type);
			var max = 0,
				min = Number.MAX_VALUE,
				cc,
				startColor = [200, 238, 255],
				endColor = [0, 100, 145],
				colors = {},
				hex;

			start_date = typeof start_date !== 'undefined' ? start_date : "";
			end_date = typeof end_date !== 'undefined' ? end_date : "";
			var order_type = typeof order_type !== 'undefined' ? order_type : "orders";
			var account_id = typeof account_id !== 'undefined' ? account_id : "";
			var brand_id = typeof brand_id !== 'undefined' ? brand_id : "";

			$.ajax({
				// url: "ajax_load.php?token="+ new Date().getTime(),
				url: "ajax_load.php?token=" + new Date().getTime() + "&action=overview_get_region_count&type=" + order_type + "&account_id=" + account_id + "&brand_id=" + brand_id + "&start_date=" + start_date + "&end_date=" + end_date,
				cache: false,
				type: 'POST',
				// processData: false,
				// async: false,
				success: function (s) {
					sample_data = $.parseJSON(s);

					//find maximum and minimum values
					for (cc in sample_data) {
						if (parseFloat(sample_data[cc]) > max) {
							max = parseFloat(sample_data[cc]);
						}
						if (parseFloat(sample_data[cc]) < min) {
							min = parseFloat(sample_data[cc]);
						}
					}

					//set colors according to values
					for (cc in sample_data) {
						if (sample_data[cc] > 0) {
							colors[cc] = '#';
							for (var i = 0; i < 3; i++) {
								hex = Math.round(startColor[i]
									+ (endColor[i]
										- startColor[i])
									* (sample_data[cc] / (max - min))).toString(16);

								if (hex.length == 1) {
									hex = '0' + hex;
								}

								colors[cc] += (hex.length == 1 ? '0' : '') + hex;
							}
						}
					}

					var data = {
						map: 'india_en',
						backgroundColor: null,
						borderColor: '#ffffff',
						borderOpacity: 0.5,
						borderWidth: 1,
						enableZoom: true,
						normalizeFunction: 'linear',
						selectedColor: null,
						selectedRegion: null,
						showTooltip: true,
						colors: colors,
						hoverOpacity: 0.7,
						hoverColor: null,
						onLabelShow: function (event, label, code) {
							label_content = label.html();

							sample_data[code] = typeof sample_data[code] !== 'undefined' ? sample_data[code] : 0;
							remove_content = '';
							if (label_content.indexOf("-") > 0) {
								remove_content = label_content.substr(label_content.indexOf("-"));
								label.html(label_content.replace(remove_content, ""));
							}
							label.append(' - ' + sample_data[code]);
						},
					};

					var map = jQuery('#vmap_' + order_type);
					if (!map) {
						$('#regions_statistics_loading').hide();
						return;
					}
					$('#regions_statistics_loading').hide();
					$('#regions_statistics_content').show();
					map.width(map.parent().parent().width());
					map.show();
					map.vectorMap(data);
				},
				error: function (e) {
					alert('Error Processing your Request!!');
				}
			});
		}

		setMap(r_type, r_sel_account, r_sel_brand, start_date, end_date);
		showMap(r_type);

		$('#regions_statistics_content input:radio').bind('change', function () {
			$('#regions_statistics_loading').show();
			$('#regions_statistics_content').hide();
			r_type = $(this).val();
			setMap(r_type, r_sel_account, r_sel_brand, start_date, end_date);
			showMap(r_type);
			$('#regions_statistics_loading').hide();
		});

		$('.regions .flipkart_accounts_group').on('hide.bs.dropdown', function () {
			$('#regions_statistics_loading').show();
			$('#regions_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				r_sel_account = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					r_sel_account.push($(this).val());
				});
				setMap(r_type, r_sel_account, r_sel_brand, start_date, end_date);
				showMap(r_type);
			}
		});

		$('.regions .flipkart_brands_group').on('hide.bs.dropdown', function () {
			$('#regions_statistics_loading').show();
			$('#regions_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				r_sel_brand = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					r_sel_brand.push($(this).val());
				});
				setMap(r_type, r_sel_account, r_sel_brand, start_date, end_date);
				showMap(r_type);
				$('#regions_statistics_loading').hide();
			}
		});
	};

	var flipkartSales = function () {
		var getSalesOverview = function (account_id, brand, start_date, end_date) {
			start_date = typeof start_date !== 'undefined' ? start_date : "";
			end_date = typeof end_date !== 'undefined' ? end_date : "";
			var account_id = typeof account_id !== 'undefined' ? account_id : "";
			var brand = typeof brand !== 'undefined' ? brand : "";
			var content = "";

			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime() + "&action=overview_get_top_selling_count&account_id=" + account_id + "&brand=" + brand + "&start_date=" + start_date + "&end_date=" + end_date,
				cache: false,
				type: 'POST',
				// processData: false,
				// async: false,
				success: function (s) {
					s = $.parseJSON(s);
					$.each(s, function (index, value) {
						content += '<li class="sku-' + index + '"><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc">' + index + '</div></div></div></div><div class="col2"><div class="date qty">' + value.quantity + '</div></div>';
						$.each(value, function (f_index, f_value) {
							if (f_index == 'brand' || f_index == 'quantity') {
								return;
							}
							content += '<ul class="inner-feeds feeds fsn_details" data-sku="' + index + '">';
							if (f_value != null) {
								content += '<li class="fsn-' + f_index + '"><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc">' + f_index + '</div></div></div></div><div class="col2"><div class="date qty">' + f_value.fsn_quantity + '</div></div>';
								content += '<ul class="inner-feeds feeds account_details" data-fsn="' + f_index + '">';
								content += '<li><div class="col1"><div class="cont"><div class="cont-col2"><div class="date">Account Name</div></div></div></div><div class="col3"><div class="date">NON_FBF</div></div><div class="col2"><div class="date">FBF_LITE</div></div></li>';
								$.each(f_value, function (a_index, a_value) {
									if (a_index == 'fsn_quantity') {
										return;
									}
									content += '<li><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc"><a href="https://www.flipkart.com/product/p/itme?pid=' + f_index + '" target="_blank">' + a_value.sku + '</a> <span class="ac_name">' + a_index + '</span></div></div></div></div><div class="col3"><div class="date qty">' + a_value.NON_FBF + '</div></div><div class="col2"><div class="date qty">' + a_value.FBF_LITE + '</div></div></li>';
								});
								content += '</ul>';
								content += '</li>';
							}
							content += '</ul>';
						});
						content += '</li>';
					});
					$('#sales_statistics_loading').hide();
					$('#sales_statistics_content .sales-feeds').html(content);
					$('#sales_statistics_content').show();
					bindSalesTreeView();
				},
				error: function (e) {
					alert('Error Processing your Request!!');
				}
			});
		}

		var bindSalesTreeView = function () {
			$('.sales-feeds li, .sales-feeds .fsn_details li').bind('click', function (e) {
				// Prevent Parent getting clicked
				e.stopPropagation();

				$class = $(this).attr('class');
				$type = $class.substring(0, 3);
				$data = $class.substring(4, $class.length);
				$(this).find('*[data-' + $type + '="' + $data + '"]').toggleClass('active');
				$(this).find('*[data-' + $type + '="' + $data + '"]').toggle();
			});
		}

		getSalesOverview(s_sel_account, s_sel_brand, start_date, end_date);

		$('.sales .flipkart_accounts_group').on('hide.bs.dropdown', function () {
			$('#sales_statistics_content .sales-feeds').html("");
			$('#sales_statistics_loading').show();
			$('#sales_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				s_sel_account = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					s_sel_account.push($(this).val());
				});
				s_sel_account = s_sel_account.join(",");
				getSalesOverview(s_sel_account, s_sel_brand, start_date, end_date);
				bindSalesTreeView();

			}
		});

		$('.sales .flipkart_brands_group').on('hide.bs.dropdown', function () {
			$('#sales_statistics_content .sales-feeds').html("");
			$('#sales_statistics_loading').show();
			$('#sales_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				s_sel_brand = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					s_sel_brand.push($(this).val());
				});
				s_sel_brand = s_sel_brand.join(",");
				getSalesOverview(s_sel_account, s_sel_brand, start_date, end_date);
				bindSalesTreeView();
			}
		});
	};

	var flipkartReturns = function () {
		var getReturnsOverview = function (account_id, brand, start_date, end_date) {
			start_date = typeof start_date !== 'undefined' ? start_date : "";
			end_date = typeof end_date !== 'undefined' ? end_date : "";
			var account_id = typeof account_id !== 'undefined' ? account_id : "";
			var brand = typeof brand !== 'undefined' ? brand : "";
			var content = "";

			$.ajax({
				// url: "ajax_load.php?token="+ new Date().getTime(),
				url: "ajax_load.php?token=" + new Date().getTime() + "&action=overview_get_top_returning_count&account_id=" + account_id + "&brand=" + brand + "&start_date=" + start_date + "&end_date=" + end_date,
				cache: false,
				type: 'POST',
				// processData: false,
				// async: false,
				success: function (s) {
					s = $.parseJSON(s);
					$.each(s, function (index, value) {
						content += '<li class="sku-' + index + '"><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc">' + index + '</div></div></div></div><div class="col2"><div class="date qty">' + value.quantity + '</div></div>';
						$.each(value, function (f_index, f_value) {
							if (f_index == 'brand' || f_index == 'quantity') {
								return;
							}
							content += '<ul class="inner-feeds feeds fsn_details" data-sku="' + index + '">';
							if (f_value != null) {
								content += '<li class="fsn-' + f_index + '"><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc">' + f_index + '</div></div></div></div><div class="col2"><div class="date qty">' + f_value.fsn_quantity + '</div></div>';
								content += '<ul class="inner-feeds feeds account_details" data-fsn="' + f_index + '">';
								content += '<li><div class="col1"><div class="cont"><div class="cont-col2"><div class="date">Account Name</div></div></div></div><div class="col3"><div class="date">NON_FBF</div></div><div class="col2"><div class="date">FBF_LITE</div></div></li>';
								$.each(f_value, function (a_index, a_value) {
									if (a_index == 'fsn_quantity') {
										return;
									}
									content += '<li><div class="col1"><div class="cont"><div class="cont-col2"><div class="desc"><a href="https://www.flipkart.com/product/p/itme?pid=' + f_index + '" target="_blank">' + a_value.sku + '</a> <span class="ac_name">' + a_index + '</span></div></div></div></div><div class="col3"><div class="date qty">' + a_value.NON_FBF + '</div></div><div class="col2"><div class="date qty">' + a_value.FBF_LITE + '</div></div></li>';
								});
								content += '</ul>';
								content += '</li>';
							}
							content += '</ul>';
						});
						content += '</li>';
					});
					$('#returns_statistics_loading').hide();
					$('#returns_statistics_content .returns-feeds').html(content);
					$('#returns_statistics_content').show();
					bindReturnsTreeView();
				},
				error: function (e) {
					alert('Error Processing your Request!!');
				}
			});
		}

		var bindReturnsTreeView = function () {
			$('.returns-feeds li, .returns-feeds .fsn_details li').bind('click', function (e) {
				// Prevent Parent getting clicked
				e.stopPropagation();

				$class = $(this).attr('class');
				$type = $class.substring(0, 3);
				$data = $class.substring(4, $class.length);
				$(this).find('*[data-' + $type + '="' + $data + '"]').toggleClass('active');
				$(this).find('*[data-' + $type + '="' + $data + '"]').toggle();
			});
		}

		getReturnsOverview(s_sel_account, s_sel_brand, start_date, end_date);

		$('.returns .flipkart_accounts_group').on('hide.bs.dropdown', function () {
			$('#returns_statistics_content .returns-feeds').html("");
			$('#returns_statistics_loading').show();
			$('#returns_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				rt_sel_account = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					rt_sel_account.push($(this).val());
				});
				rt_sel_account = rt_sel_account.join(",");
				getReturnsOverview(s_sel_account, s_sel_brand, start_date, end_date);
				bindReturnsTreeView();

			}
		});

		$('.returns .flipkart_brands_group').on('hide.bs.dropdown', function () {
			$('#returns_statistics_content .returns-feeds').html("");
			$('#returns_statistics_loading').show();
			$('#returns_statistics_content').hide();
			$this = $(this);
			if ($this.hasClass('open')) { // Trigger if current state has open class else its closed dropdown
				// Get all checked checkboxes
				s_sel_brand = [];
				$.each($("input[name='accounts']:checked", $this), function () {
					s_sel_brand.push($(this).val());
				});
				s_sel_brand = s_sel_brand.join(",");
				getReturnsOverview(s_sel_account, s_sel_brand, start_date, end_date);
				bindReturnsTreeView();
			}
		});
	};

	var flipkartDashboardDaterange = function () {

		$('#dashboard-report-range').daterangepicker({
			autoApply: true,
			opens: (App.isRTL() ? 'right' : 'left'),
			minDate: moment().subtract('days', 365),
			maxDate: moment(),
			dateLimit: {
				days: 120
			},
			ranges: {
				'Last 7 Days': [moment().subtract('days', 6), moment()],
				'Last 30 Days': [moment().subtract('days', 30), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
			},
		},

			function (start, end) {
				console.log("Date change Callback initiated!");
				$('#dashboard-report-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
				start_date = start.format('YYYY-MM-DD');
				end_date = end.format('YYYY-MM-DD');

				$('#orders_statistics_loading').show();
				$('#orders_statistics_content').hide();
				$('#regions_statistics_loading').show();
				$('#regions_statistics_content').hide();
				$('#sales_statistics_loading').show();
				$('#sales_statistics_content').hide();
				$('#returns_statistics_loading').show();
				$('#returns_statistics_content').hide();

				flipkartOverall();
				flipkartCharts();
				flipkartJQVMAP();
				flipkartSales();
				flipkartReturns();
			});


		$('#dashboard-report-range span').html(moment().subtract('days', 30).format('MMMM D, YYYY') + ' - ' + moment().format('MMMM D, YYYY'));
		$('#dashboard-report-range').show();
	};

	return {
		//main function
		init: function (type) {
			switch (type) {
				case 'flipkart':
					flipkartStartup();
					flipkartOverall();
					flipkartDashboardDaterange();
					// flipkartToday();
					flipkartCharts();
					flipkartJQVMAP();
					flipkartSales();
					flipkartReturns();
					break;

				case 'shopify':
					break;
			}
		}
	}
}();