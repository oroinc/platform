define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mask = require('oroui/js/dropdown-mask');
    require('oroui/js/jquery-timepicker-l10n');
    require('jquery.timepicker');

    var origTimepicker = $.fn.timepicker;
    $.fn.timepicker = function(method) {
        var options;
        var result;
        if (typeof method === 'object' || !method || method === 'init') {
            options = method === 'init' ? arguments[1] : method;
            options = $.extend(true, {}, origTimepicker.defaults, options);
            result = origTimepicker.call(this, options);
        } else {
            result = origTimepicker.apply(this, arguments);
        }
        return result;
    };

    $(document)
        .on('showTimepicker', function(e) {
            var $input = $(e.target);
            var zIndex = $input.data('timepicker-list').css('zIndex');
            mask.show(zIndex - 1)
                .onhide(function() {
                    $input.timepicker('hide');
                });
        })
        .on('hideTimepicker', function(e) {
            mask.hide();
        });
});
