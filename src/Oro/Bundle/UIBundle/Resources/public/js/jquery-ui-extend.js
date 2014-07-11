/*global define*/
/*jslint nomen:true, browser:true*/
define(['jquery', 'jquery-ui'], function ($) {
    'use strict';

    /* datepicker extend:start */
    (function () {

        /**
         * Combines space-separated line of events with widget's namespace
         *  for handling datepicker's position change
         *
         * @returns {string}
         * @private
         */
        function getEvents(uuid) {
            var events = ['scroll', 'resize'],
                ns = 'datepicker-' + uuid;

            events = $.map(events, function (eventName) {
                return eventName + '.' + ns;
            });

            return events.join(' ');
        }

        var _isEventsAdded  = false,
            _showDatepicker = $.datepicker.constructor.prototype._showDatepicker,
            _hideDatepicker = $.datepicker.constructor.prototype._hideDatepicker;

        /**
         * Bind update position method after datepicker is opened
         *
         * @param elem
         * @override
         * @private
         */
        $.datepicker.constructor.prototype._showDatepicker = function (elem) {
            var events, input;

            _showDatepicker.apply(this, arguments);

            input = elem.target || elem;
            events = getEvents(input.id);

            $(input).parents().add(window).each(function () {
                $(this).on(events, function () {
                    // just close datepicker
                    $.datepicker._hideDatepicker();
                    input.blur();
                });
            });

            _isEventsAdded = true;
        };

        /**
         * Remove all handlers before closing datepicker
         *
         * @param elem
         * @override
         * @private
         */
        $.datepicker.constructor.prototype._hideDatepicker = function (elem) {
            if (!_isEventsAdded) {
                return;
            }

            var events, input = elem;

            if (!elem) {
                input = $.datepicker._curInst.input.get(0);
            }
            events = getEvents(input.id);

            $(input).parents().add(window).each(function () {
                $(this).off(events);
            });

            _hideDatepicker.apply(this, arguments);
            _isEventsAdded = false;
        };
    }());
    /* datepicker extend:end */
});
