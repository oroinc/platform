/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'oroform/js/app/components/base-simple-color-picker', 'oroui/js/tools/color-util'
    ], function ($, _, BaseSimpleColorPicker, colorUtil) {
    'use strict';

    var ColorTable = BaseSimpleColorPicker.extend({
        /**
         * @constructor
         * @param {object} options
         */
        initialize: function (options) {
            ColorTable.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        _getSimpleColorPickerOptions: function (options) {
            options = ColorTable.__super__._getSimpleColorPickerOptions.call(this, options);
            return _.omit(options, ['picker']);
        },

        /**
         * @inheritDoc
         */
        _getPickerOptions: function (options) {
            options = ColorTable.__super__._getPickerOptions.call(this, options.picker);
            return _.extend(options, {
                change: _.bind(function (hex, opacity) {
                    if (this.$current && this.$current.data('color') !== hex) {
                        this.$element.simplecolorpicker('replaceColor', this.$current.data('color'), hex);
                        this.$current.data('color', hex);
                        this.$current.css('color', colorUtil.getContrastColor(hex));
                    }
                }, this)
            });
        },

        /**
         * @inheritDoc
         */
        _getPicker: function () {
            var pickerId = this.$element.prop('id') + '_picker';
            this.$parent.append('<span id="' + pickerId + '" style="display: none;"></span>');
            return this.$parent.find('#' + pickerId);
        },

        /**
         * @inheritDoc
         */
        _addPickerHandlers: function () {
            this.$parent.on('click', 'span.color', _.bind(function (e) {
                e.preventDefault();
                if (!this.$element.is(':disabled')) {
                    this.$current = $(e.currentTarget);
                    this.$picker.parent().find('.minicolors-panel').css(this._getPickerPos(this.$current));
                    this.$parent.removeClass('minicolors-focus');
                    this.$picker.minicolors('value', this.$current.data('color'));
                    this.$picker.minicolors('show');
                }
            }, this));
        }
    });

    return ColorTable;
});
