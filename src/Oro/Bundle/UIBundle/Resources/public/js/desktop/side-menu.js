define(['../side-menu', '../mediator', 'oroui/js/persistent-storage'], function($, mediator, persistentStorage) {
    'use strict';

    var STATE_STORAGE_KEY = 'main-menu-state';
    var MAXIMIZED_STATE = 'maximized';
    var MINIMIZED_STATE = 'minimized';

    $.widget('oroui.desktopSideMenu', $.oroui.sideMenu, {
        /**
         * Do initial changes
         */
        _init: function() {
            this._update();
        },

        /**
         * Updates menu's minimized/maximized view
         */
        _update: function() {
            var isMinimized = persistentStorage.getItem(STATE_STORAGE_KEY) !== MAXIMIZED_STATE;
            this.element.toggleClass('minimized', isMinimized);
            $('#main').toggleClass('main-menu-maximized', isMinimized);
            if (isMinimized) {
                this._convertToDropdown();
            } else {
                this._convertToAccordion();
            }
        },

        /**
         * Handles menu toggle state action
         */
        _toggle: function() {
            persistentStorage.setItem(
                STATE_STORAGE_KEY,
                this.element.hasClass('minimized') ? MAXIMIZED_STATE : MINIMIZED_STATE
            );
            this._update();
            mediator.trigger('layout:adjustHeight');
        }
    });

    return $;
});
