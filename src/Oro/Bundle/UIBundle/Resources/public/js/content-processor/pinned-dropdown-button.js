define(['./dropdown-button', 'oroui/js/mediator', 'oroui/js/persistent-storage'
], function($, mediator, persistentStorage) {
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
            const $buttons = this._super($element);
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
            const key = this._getStorageKey();
            const index = key ? persistentStorage.getItem(key) || 0 : 0;
            const result = $buttons.get(index);

            return result ? $(result) : this._super($buttons);
        },

        /**
         * Stores index of used button
         *
         * @param e
         * @private
         */
        _onButtonClick: function(e) {
            const key = this._getStorageKey();
            if (key) {
                persistentStorage.setItem(key, $(e.target).data('button-index') || 0);
            }
            mediator.trigger('dropdown-button:click');
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
