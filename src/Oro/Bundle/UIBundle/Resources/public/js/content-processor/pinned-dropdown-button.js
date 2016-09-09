define(['./dropdown-button', 'oroui/js/persistent-storage'], function($, persistentStorage) {
    'use strict';

    $.widget('oroui.pinnedDropdownButtonProcessor', $.oroui.dropdownButtonProcessor, {
        options: {
            mainButtons: '',
            minItemQuantity: 0,
            groupKey: ''
        },

        keyPreffix: 'pinned-dropdown-button-processor-',

        _create: function() {
            this._super();
            this._on({
                'click [data-button-index]': this._onButtonClick
            });
        },

        /**
         * Fetches buttons and creates index for them
         *
         * @param {jQuery|null} $element
         * @returns {*}
         * @private
         */
        _collectButtons: function($element) {
            var $buttons = this._super($element);
            $buttons.filter(':not(.divider)').each(function(i) {
                $(this).attr('data-button-index', '').data('button-index', i);
            });
            return $buttons;
        },

        /**
         * Defines main buttons
         *
         * @param {jQuery} $buttons
         * @returns {jQuery}
         * @private
         */
        _mainButtons: function($buttons) {
            var key = this._getStorageKey();
            var index = key ? persistentStorage.getItem(key) || 0 : 0;
            var result = $buttons.get(index);

            return result ? $(result) : this._superApply(arguments);
        },

        /**
         * Stores index of used button
         *
         * @param e
         * @private
         */
        _onButtonClick: function(e) {
            var key = this._getStorageKey();
            if (key) {
                persistentStorage.setItem(key, $(e.target).data('button-index') || 0);
            }
        },

        /**
         * Defines storage key
         *
         * @returns {string}
         * @private
         */
        _getStorageKey: function() {
            return this.options.groupKey ? this.keyPreffix + this.options.groupKey : '';
        }
    });

    return $;
});
