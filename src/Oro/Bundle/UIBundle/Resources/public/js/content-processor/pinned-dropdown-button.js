/*global define, localStorage*/
define(['./dropdown-button'], function ($) {
    'use strict';

    $.widget('oroui.pinnedDropdownButtonProcessor', $.oroui.dropdownButtonProcessor, {
        options: {
            mainButtons: '',
            minItemQuantity: 0,
            groupKey: ''
        },

        keyPreffix: 'pinned-dropdown-button-processor-',

        _create: function () {
            this._super();
            this._on({
                'click [button-index]': this._onButtonClick
            });
        },

        /**
         * Fetches buttons and creates index for them
         *
         * @returns {*}
         * @private
         */
        _collectButtons: function () {
            var $buttons = this._super();
            $buttons.filter(':not(.divider)').each(function (i) {
                $(this).attr('button-index', '').data('button-index', i);
            });
            return $buttons;
        },

        /**
         * Fetches main buttons
         *
         * @param {jQuery} $buttons
         * @returns {jQuery}
         * @private
         */
        _mainButtons: function ($buttons) {
            var index = localStorage.getItem(this.keyPreffix + this.options.groupKey) || 0;
            return $buttons.get(index) || this._superApply(arguments);
        },

        /**
         * Stores index of used button
         *
         * @param e
         * @private
         */
        _onButtonClick: function (e) {
            localStorage.setItem(this.keyPreffix + this.options.groupKey, $(e.target).data('button-index'));
        }
    });

    return $;
});
