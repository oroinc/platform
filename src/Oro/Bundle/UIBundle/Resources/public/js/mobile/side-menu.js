/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
/*jslint nomen: true*/
define(['../side-menu', '../mediator'], function ($, mediator) {
    'use strict';

    $.widget('oroui.mobileSideMenu', $.oroui.sideMenu, {
        /**
         * Creates side menu
         *
         * @private
         */
        _create: function () {
            this._super();

            this.listener.listenTo(mediator, 'page:request', $.proxy(this._hide, this));

            // handler for hiding menu on outside click
            this._onOutsideClick = $.proxy(function (e) {
                if (!$.contains(this.element.get(0), e.target)) {
                    this._hide();
                }
            }, this);
        },

        /**
         * Adds accordion's styles for HTML of menu
         *
         * @private
         */
        _init: function () {
            this._convertToAccordion();
        },

        /**
         * Performs show menu action
         *
         * @private
         */
        _show: function () {
            this.$toggle.addClass('open');
            $('#main-menu').show();
            $(document).on('click', this._onOutsideClick);
        },

        /**
         * Performs hide menu action
         *
         * @private
         */
        _hide: function () {
            $('#main-menu').hide();
            this.$toggle.removeClass('open');
            $(document).off('click', this._onOutsideClick);
        },

        /**
         * Handles open/close side menu
         *
         * @private
         */
        _toggle: function (e) {
            if (!this.$toggle.hasClass('open')) {
                this._show();
            } else {
                this._hide();
            }
        }
    });
});
