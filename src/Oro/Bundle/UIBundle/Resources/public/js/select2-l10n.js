define(function(require) {
    'use strict';

    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    require('jquery.select2');

    $.fn.select2.defaults = $.extend($.fn.select2.defaults, {
        formatNoMatches: function() {
            return __('No matches found');
        },
        formatInputTooShort: function(input, min) {
            var number = min - input.length;
            return __('oro.ui.select2.input_too_short', {number: number}, number);
        },
        formatInputTooLong: function(input, max) {
            var number = input.length - max;
            return __('oro.ui.select2.input_too_long', {number: number}, number);
        },
        formatSelectionTooBig: function(limit) {
            return __('oro.ui.select2.selection_too_big', {limit: limit}, limit);
        },
        formatLoadMore: function() {
            return __('oro.ui.select2.load_more');
        },
        formatSearching: function() {
            return __('Searching...');
        }
    });
});
