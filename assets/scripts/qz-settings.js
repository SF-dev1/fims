"use strict";

var Qz = function () {
	var _return = [];
	var init_qzTray = function () {
		// qz.api.security.setPromiseType(function(e) {
		// 	return new i.Promise(e);
		// });

		qz.security.setCertificatePromise(function (resolve, reject) {
			if (window.location.href.includes('skmienterprise.com')) {
				$.ajax({ url: "../assets/certificates-live/server.crt", cache: false, dataType: "text" }).then(resolve, reject);
			} else {
				$.ajax({ url: "../assets/certificates/server.crt", cache: false, dataType: "text" }).then(resolve, reject);
			}
		});

		qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1
		qz.security.setSignaturePromise(function (toSign) {
			return function (resolve, reject) {
				$.ajax("../includes/qz-sign-message.php?request=" + toSign).then(resolve, reject);
			};
		});

		// qz.api.showDebug(true);
		// var promise = qz.api.setPromiseType(function promise(resolver) { 
		// 	return new Promise(resolver); 
		// });
		// console.log(promise);
		var connection = qz.websocket.connect()
			.then(function () {
				UIToastr.init('success', 'QZ Tray', "Connection Successfull");
				return qz.printers.find()
			}).then(function (connection) {
				_return = connection;
			})
			.catch(handleConnectionError);
		// .catch(function(err) {
		//    console.error(err);
		//    process.exit(1);
		// });

		return connection;
	};

	var handleConnectionError = function (err) {
		if (!qz.websocket.isActive()) {
			window.location.assign("qz:launch");
		} else {
			if (err.target != undefined) {
				if (err.target.readyState >= 2) { //if CLOSING or CLOSED
					displayError("Connection to QZ Tray was closed");
					console.log(err);
					return err;
				} else {
					displayError("A connection error occurred, check log for details");
					console.error(err);
					return err;
				}
			} else {
				displayError(err);
				console.log(err);
				return err;
			}
		}
	};

	var displayError = function (err) {
		// UIToastr('error', 'QZ Tray', err)
	};

	var find_printers = function () {
		return _return;
	};

	var endConnection = function () {
		if (qz.websocket.isActive()) {
			qz.websocket.disconnect().catch(handleConnectionError);
		} else {
			displayError('No active connection with QZ exists.');
		}
	}

	var print_v1 = function (printer_name, data, copies, size, jobName) {
		var config = qz.configs.create(printer_name, {
			copies: parseInt(copies),
			size: { width: size.width, height: size.height },
			margins: 0,//{ top: 0.25, right: 0.25, bottom: 0.25, left: 0.25 }
			units: 'mm',
			colorType: 'grayscale',
			jobName: jobName
		});

		var err = "";
		var _return = qz.print(config, data).catch(function (e) {
			// console.error(e);
			// displayError(e);
			err = e;
		});

		console.log(_return);
		return _return;
	};

	var print = function (printer_name, data, copies, size, jobName) {
		// console.log(printer_name);
		// var p_width = size.width*0.0393701;
		// var p_height = size.height*0.0393701;
		var p_width = size.width;
		var p_height = size.height;
		var config = qz.configs.create(printer_name, {
			copies: parseInt(copies),
			size: { width: p_width, height: p_height },
			margins: 0, //{ top: 0, right: 0, bottom: 0, left: 0 },
			units: 'mm',
			colorType: 'grayscale',
			jobName: jobName,
			orientation: "portrait",
			scaleContent: true,
			// rotation: 90,
			// density: 203,
			// rasterize: false,
		});

		// return qz.printers.find(printer_name).then(function() {
		return qz.print(config, data)
			// .then(qz.websocket.disconnect)
			.catch(function (err) {
				return handleConnectionError(err);
			});
		// });
	};

	return {
		// //main function to initiate the module
		init: init_qzTray,
		getAvailablePrinters: find_printers,
		printWithPrinter: print,
		disconnect: endConnection,
	}
}();