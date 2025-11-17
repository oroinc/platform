import $ from 'jquery';
import _ from 'underscore';
import BaseSimpleColorPickerView from 'oroform/js/app/views/base-simple-color-picker-view';
import colorUtil from 'oroui/js/tools/color-util';

const ColorTableView = BaseSimpleColorPickerView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ColorTableView(options) {
        ColorTableView.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     * @param {object} options
     */
    initialize: function(options) {
        ColorTableView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    _getSimpleColorPickerOptions: function(options) {
        options = ColorTableView.__super__._getSimpleColorPickerOptions.call(this, options);
        return _.omit(options, ['picker']);
    },

    /**
     * @inheritdoc
     */
    _getPickerOptions: function(options) {
        options = ColorTableView.__super__._getPickerOptions.call(this, options.picker);
        return _.extend(options, {
            change: (hex, opacity) => {
                if (this.$current && this.$current.data('color') !== hex) {
                    this.$el.simplecolorpicker('replaceColor', this.$current.data('color'), hex, this.$current);
                    this.$current.data('color', hex);
                    this.$current.css('color', colorUtil.getContrastColor(hex));
                }
            }
        });
    },

    /**
     * @inheritdoc
     */
    _getPicker: function() {
        const pickerId = this.$el.prop('id') + '_picker';
        this.$parent.append('<span id="' + pickerId + '" style="display: none;"></span>');
        return this.$parent.find('#' + pickerId);
    },

    /**
     * @inheritdoc
     */
    _addPickerHandlers: function() {
        this.$parent.on('click.' + this.cid, 'span.color', e => {
            e.preventDefault();
            if (!this.$el.is(':disabled') && !$(e.currentTarget).is(this.$current)) {
                this.$current = $(e.currentTarget);
                this.$picker.parent().find('.minicolors-panel').css(this._getPickerPos(this.$current));
                this.$parent.removeClass('minicolors-focus');
                this.$picker.minicolors('value', this.$current.data('color'));
                this.$picker.minicolors('show');
            }
        });
    }
});

export default ColorTableView;
