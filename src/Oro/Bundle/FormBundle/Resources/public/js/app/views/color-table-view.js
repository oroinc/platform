define(['jquery', 'underscore', 'oroform/js/app/views/base-simple-color-picker-view', 'oroui/js/tools/color-util'
    ], function($, _, BaseSimpleColorPickerView, colorUtil) {
    'use strict';

    var ColorTableView = BaseSimpleColorPickerView.extend({
        /**
         * @constructor
         * @param {object} options
         */
        initialize: function(options) {
            ColorTableView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        _getSimpleColorPickerOptions: function(options) {
            options = ColorTableView.__super__._getSimpleColorPickerOptions.call(this, options);
            return _.omit(options, ['picker']);
        },

        /**
         * @inheritDoc
         */
        _getPickerOptions: function(options) {
            options = ColorTableView.__super__._getPickerOptions.call(this, options.picker);
            return _.extend(options, {
                change: _.bind(function(hex, opacity) {
                    if (this.$current && this.$current.data('color') !== hex) {
                        this.$el.simplecolorpicker('replaceColor', this.$current.data('color'), hex, this.$current);
                        this.$current.data('color', hex);
                        this.$current.css('color', colorUtil.getContrastColor(hex));
                    }
                }, this)
            });
        },

        /**
         * @inheritDoc
         */
        _getPicker: function() {
            var pickerId = this.$el.prop('id') + '_picker';
            this.$parent.append('<span id="' + pickerId + '" style="display: none;"></span>');
            return this.$parent.find('#' + pickerId);
        },

        /**
         * @inheritDoc
         */
        _addPickerHandlers: function() {
            this.$parent.on('click.' + this.cid, 'span.color', _.bind(function(e) {
                e.preventDefault();
                if (!this.$el.is(':disabled') && !$(e.currentTarget).is(this.$current)) {
                    this.$current = $(e.currentTarget);
                    this.$picker.parent().find('.minicolors-panel').css(this._getPickerPos(this.$current));
                    this.$parent.removeClass('minicolors-focus');
                    this.$picker.minicolors('value', this.$current.data('color'));
                    this.$picker.minicolors('show');
                }
            }, this));
        }
    });

    return ColorTableView;
});
