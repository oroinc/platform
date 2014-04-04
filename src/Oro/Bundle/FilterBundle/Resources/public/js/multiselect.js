/*global define*/
/*jslint nomen:true, browser:true*/
define(['jquery', 'jquery-ui', 'jquery.multiselect'], function ($) {
    'use strict';

    $.widget('orofilter.multiselect', $.ech.multiselect, {

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open: function () {
            this._superApply(arguments);

            var events = this._getUpdatePosEvents(),
                handler = $.proxy(this.updatePos, this);

            this.element.parents().add(window).each(function () {
                $(this).on(events, handler);
            });
        },

        /**
         * Remove all handlers before closing menu
         * @override
         */
        close: function () {
            var events = this._getUpdatePosEvents();

            this.element.parents().add(window).each(function () {
                $(this).off(events);
            });

            this._superApply(arguments);
        },

        /**
         * Process position update for menu element
         */
        updatePos: function () {
            var isShown = this.menu.is(':visible');
            this.position();
            if (isShown) {
                this.menu.show();
            }
        },

        /**
         * Combines space-separated line of events with widget's namespace
         *  for updating menu's position
         *
         * @returns {string}
         * @private
         */
        _getUpdatePosEvents: function () {
            var events = ['scroll'],
                ns = 'multiselect-' + this.uuid;

            events = $.map(events, function (eventName) {
                return eventName + '.' + ns;
            });

            return events.join(' ');
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget("ech.multiselect", $.orofilter.multiselect, {});
});
