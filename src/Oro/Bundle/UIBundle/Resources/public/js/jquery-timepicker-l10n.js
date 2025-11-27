import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import localeSettings from 'orolocale/js/locale-settings';
import 'jquery.timepicker';

const decimal = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;
const timeFormat = localeSettings.getVendorDateTimeFormat('php', 'time', 'g:i A');

$.fn.timepicker.defaults = {
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
};
