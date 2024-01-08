var IdleTimeout = function () {

    return {
        //main function to initiate the module
        init: function (base_url, cur_url) {

            (function ($) {

                $(document).idleTimeout({
                    redirectUrl: base_url + "/logout.php?return=" + cur_url, // redirect to this url. Set this value to YOUR site's logout page.

                    // idle settings
                    idleTimeLimit: 900,           // 'No activity' time limit in seconds. 1200 = 20 Minutes
                    idleCheckHeartbeat: 30,       // Frequency to check for idle timeouts in seconds

                    // optional custom callback to perform before logout
                    customCallback: false,       // set to false for no customCallback
                    // customCallback:    function () {    // define optional custom js function
                    // perform custom action before logout
                    // },

                    // customCallbackKeepAlive: true,
                    customCallbackKeepAlive: function () {
                        console.log('keepalive');
                        /*var currentReq = null;
                        var ret = "";
                        currentReq = $.ajax({
                            url: base_url+"/login/ajax_load.php?token="+ new Date().getTime(),
                            cache: true,
                            type: 'POST',
                            data: JSON.stringify({"action":"keepalive"}),
                            // contentType: "application/json",
                            dataType: "json",
                            async: false,
                            showLoader: true,
                            beforeSend : function(){
                                if(currentRequest != null) {
                                    currentRequest.abort();
                                } else {
                                    currentRequest = currentReq;
                                }
                            },
                            success: function(s){
                                if (s != ""){
                                    ret = $.parseJSON(s);

                                    if(ret.redirectUrl){
                                        window.location.href = ret.redirectUrl;
                                    }
                                }
                            },
                            error: function(e){
                                UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
                            }
                        });*/
                    },

                    // configure which activity events to detect
                    // http://www.quirksmode.org/dom/events/
                    // https://developer.mozilla.org/en-US/docs/Web/Reference/Events
                    activityEvents: 'click keypress scroll wheel mousewheel mousemove', // separate each event with a space

                    // warning dialog box configuration
                    enableDialog: true,           // set to false for logout without warning dialog
                    dialogDisplayLimit: 30,       // 20 seconds for testing. Time to display the warning dialog before logout (and optional callback) in seconds. 180 = 3 Minutes
                    dialogTitle: 'Session Expiration Warning', // also displays on browser title bar
                    dialogText: 'Because you have been inactive, your session will expire in ',
                    dialogStayLoggedInButton: 'Yes, Keep Working',
                    dialogLogOutNowButton: 'No, Logout',

                    // error message if https://github.com/marcuswestin/store.js not enabled
                    errorAlertMessage: 'Please disable "Private Mode", or upgrade to a modern browser. Or perhaps a dependent file missing. Please see: https://github.com/marcuswestin/store.js',

                    // server-side session keep-alive timer
                    sessionKeepAliveTimer: 30,   // ping the server at this interval in seconds. 600 = 10 Minutes. Set to false to disable pings
                    sessionKeepAliveUrl: base_url + '/keepalive.php' // set URL to ping - does not apply if sessionKeepAliveTimer: false
                });

            })(jQuery);

        }

    };

}();