define(['../side-menu', '../mediator', 'oroui/js/tools/scroll-helper'], function($, mediator, scrollHelper) {
    'use strict';

    $.widget('oroui.mobileSideMenu', $.oroui.sideMenu, {
        /**
         * Creates side menu
         *
         * @private
         */
        _create: function() {
            this._super();

            this.listener.listenTo(mediator, 'page:request', this._hide.bind(this));

            // handler for hiding menu on outside click
            this._onOutsideClick = function(e) {
                if (!$.contains(this.element.get(0), e.target)) {
                    this._hide();
                }
            }.bind(this);
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
            scrollHelper.disableBodyTouchScroll();
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
            scrollHelper.enableBodyTouchScroll();
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
