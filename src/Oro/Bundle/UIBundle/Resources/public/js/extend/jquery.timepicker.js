define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
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
            if (_.isRTL()) {
                options.orientation = 'r';
            }
            result = origTimepicker.call(this, options);
        } else {
            args.unshift(method);
            result = origTimepicker.apply(this, args);
        }
        return result;
    };

    $(document)
        .on('showTimepicker', function(e) {
            const zIndex = e.target.timepickerObj.list.css('zIndex');
            if (e.target.closest('.ui-dialog-content')) {
                e.target.timepickerObj.list.addClass('input-in-dialog-widget');
            }

            $(document).on('click.timepickerOutgoing', event => {
                if (!e.target.contains(event.target)) {
                    $(e.target).timepicker('hide');
                }
            });

            mask.show(zIndex - 1)
                .onhide(function() {
                    $(e.target).timepicker('hide');
                });
        })
        .on('hideTimepicker', function(e) {
            mask.hide();
            $(document).off('click.timepickerOutgoing');
        });
});
