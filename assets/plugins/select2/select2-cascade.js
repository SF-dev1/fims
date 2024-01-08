/**
 * A Javascript module to loadeding/refreshing options of a select2 list box using ajax based on selection of another select2 list box.
 * 
 * @url : https://gist.github.com/ajaxray/187e7c9a00666a7ffff52a8a69b8bf31
 * @auther : Anis Uddin Ahmad <anis.programmer@gmail.com>
 * 
 * Live demo - https://codepen.io/ajaxray/full/oBPbQe/
 * w: http://ajaxray.com | t: @ajaxray
 */
var Select2Cascade = ( function(window, $) {

    function Select2Cascade(parent, child, url, select2Options) {
        var afterActions = [];
        var handleData = [];
        var options = select2Options || {};
        child.prop("disabled", true);

        // Register functions to be called after cascading data loading done
        this.then = function(callback) {
            afterActions.push(callback);
            return this;
        };

        parent.select2(options.parent).on("change", function (e) {

            child.prop("disabled", true);
            var _this = this;
            
            $.getJSON(url.replace(':parentId:', $(this).val()), function(response) {
                var newOptions = '<option value=""></option>';
                var newData = response;
                    handleData.forEach(function(callback) {
                    newData = callback(child, response);
                });

                newData.forEach(function(item) {
                    newOptions += '<option value="'+ item.key +'">'+ item.value +'</option>';
                });
                
                child.select2('destroy').html(newOptions).prop("disabled", false).select2(options.child);

                afterActions.forEach(function (callback) {
                    callback(parent, child, response);
                });
            });
        });
    }

    return Select2Cascade;

})( window, $);
