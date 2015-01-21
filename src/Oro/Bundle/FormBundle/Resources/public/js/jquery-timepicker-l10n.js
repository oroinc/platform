/* global define */
define(function (require) {
    'use strict';

    var decimal, timeFormat,
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        localeSettings = require('orolocale/js/locale-settings');
    require('oroform/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    decimal = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;
    timeFormat = localeSettings.getVendorDateTimeFormat('php', 'time', $.fn.timepicker.defaults.timeFormat);

    $.extend($.fn.timepicker.defaults, {
        timeFormat: timeFormat,
        lang: {
            am: __('oro.form.datepicker.am'),
            pm: __('oro.form.datepicker.pm'),
            AM: __('oro.form.datepicker.AM'),
            PM: __('oro.form.datepicker.PM'),
            decimal: decimal,
            mins: __('oro.form.datepicker.mins'),
            hr: __('oro.form.datepicker.hr'),
            hrs: __('oro.form.datepicker.hrs')
        }
    });
});
