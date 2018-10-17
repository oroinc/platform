define(['../side-menu', '../mediator'], function($, mediator) {
    'use strict';

    $.widget('oroui.mobileSideMenu', $.oroui.sideMenu, {
        /**
         * Creates side menu
         *
         * @private
         */
        _create: function() {
            this._super();

            this.listener.listenTo(mediator, 'page:request', $.proxy(this._hide, this));

            // handler for hiding menu on outside click
            this._onOutsideClick = $.proxy(function(e) {
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
        _init: function() {
            this._convertToAccordion();
        },

        /**
         * Performs show menu action
         *
         * @private
         */
        _show: function() {
            this.$toggle.addClass('open');
            $(document).trigger('clearMenus'); // hides all opened dropdown menus
            $('#main-menu').show();
            $(document).on('click shown.bs.dropdown', this._onOutsideClick);
            $('html').addClass('modal-dropdown-shown');
        },

        /**
         * Performs hide menu action
         *
         * @private
         */
        _hide: function() {
            this.$toggle.removeClass('open');
            $('#main-menu').hide();
            $(document).off('click shown.bs.dropdown', this._onOutsideClick);
            $('html').removeClass('modal-dropdown-shown');
        },

        /**
         * Handles open/close side menu
         *
         * @private
         */
        _toggle: function(e) {
            if (!this.$toggle.hasClass('open')) {
                this._show();
            } else {
                this._hide();
            }
        }
    });
});
