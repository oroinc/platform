/*global define*/
/*jslint nomen: true*/
define(['../side-menu', '../mediator'], function ($, mediator) {
    'use strict';

    $.widget('oroui.desktopSideMenu', $.oroui.sideMenu, {
        /**
         * Do initial changes
         *
         * @private
         */
        _init: function () {
            var minimized = this.element.hasClass('minimized');
            if (minimized) {
                this._convertToDropdown();
            } else {
                this._convertToAccordion();
            }
        },

        /**
         * Handles menu toggle state action
         */
        _toggle: function () {
            this.element.toggleClass('minimized');
            $('#main').toggleClass('main-menu-maximized');
            this._init();
            mediator.trigger('layout:adjustHeight');
        }
    });

    return $;
});
