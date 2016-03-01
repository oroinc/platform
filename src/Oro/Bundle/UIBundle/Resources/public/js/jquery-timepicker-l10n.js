define(function(require) {
    'use strict';

    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var localeSettings = require('orolocale/js/locale-settings');
    require('oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    var decimal = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;
    var timeFormat = localeSettings.getVendorDateTimeFormat('php', 'time', $.fn.timepicker.defaults.timeFormat);

    $.extend($.fn.timepicker.defaults, {
        timeFormat: timeFormat,
        lang: {
            am: __('oro.ui.timepicker.am'),
            pm: __('oro.ui.timepicker.pm'),
            AM: __('oro.ui.timepicker.AM'),
            PM: __('oro.ui.timepicker.PM'),
            decimal: decimal,
            mins: __('oro.ui.timepicker.mins'),
            hr: __('oro.ui.timepicker.hr'),
            hrs: __('oro.ui.timepicker.hrs')
        }
    });
});
