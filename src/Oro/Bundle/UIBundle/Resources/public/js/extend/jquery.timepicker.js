define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mask = require('oroui/js/dropdown-mask');
    require('oroui/js/jquery-timepicker-l10n');
    require('jquery.timepicker');

    const origTimepicker = $.fn.timepicker;
    $.fn.timepicker = function(method, ...args) {
        let options;
        let result;
        if (typeof method === 'object' || !method || method === 'init') {
            options = method === 'init' ? args[1] : method;
            options = $.extend(true, {}, origTimepicker.defaults, options);
            result = origTimepicker.call(this, options);
        } else {
            args.unshift(method);
            result = origTimepicker.apply(this, args);
        }
        return result;
    };

    $(document)
        .on('showTimepicker', function(e) {
            const $input = $(e.target);
            const zIndex = $input.data('timepicker-list').css('zIndex');
            mask.show(zIndex - 1)
                .onhide(function() {
                    $input.timepicker('hide');
                });
        })
        .on('hideTimepicker', function(e) {
            mask.hide();
        });
});
