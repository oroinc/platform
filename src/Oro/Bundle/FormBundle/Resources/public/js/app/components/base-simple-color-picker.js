/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/app/components/base/component',
    'oroui/js/tools/color-util', 'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function ($, _, __, BaseComponent, colorUtil) {
    'use strict';

    var BaseSimpleColorPicker = BaseComponent.extend({
        defaults: {
            pickerActionsTemplate: '<div class="form-actions">' +
                '<button class="btn pull-right" data-action="cancel" type="button"><%= __("Close") %></button>' +
            '</div>'
        },

        /** @property */
        pickerActionsTemplate: null,

        /** @property {jQuery} */
        $element: null,

        /** @property {jQuery} */
        $parent: null,

        /** @property {jQuery} */
        $picker: null,

        /** @property {jQuery} */
        $current: null,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._processOptions(options);
            this.pickerActionsTemplate = _.template(options.pickerActionsTemplate);
            this.$element = options._sourceElement;
            this.$parent = options._sourceElement.parent();
            this.$element.simplecolorpicker(this._getSimpleColorPickerOptions(options));
            this.$picker = this._getPicker();
            if (this.$picker.length) {
                this.$picker.minicolors(this._getPickerOptions(options));
                this._addPickerActions();
                this._preparePicker();
                this._addPickerHandlers();
            }
        },

        /**
         * @param {Object} options
         * @private
         */
        _processOptions: function (options) {
            options = _.defaults(options, this.defaults);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed && this.$element) {
                this.$element.off('.' + this.cid);
            }
            BaseSimpleColorPicker.__super__.dispose.call(this);
        },

        /**
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _getSimpleColorPickerOptions: function (options) {
            return _.omit(options, ['_sourceElement', 'pickerActionsTemplate']);
        },

        /**
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _getPickerOptions: function (options) {
            return _.defaults(options, {
                control: 'wheel',
                letterCase: 'uppercase',
                defaultValue: this.$picker.data('color') || '#FFFFFF',
                change: _.bind(function (hex, opacity) {
                    if (this.$current) {
                        this.$current.data('color', hex);
                        this.$current.css('color', colorUtil.getContrastColor(hex));
                        this.$element.val(hex);
                    }
                }, this),
                hide: _.bind(function () {
                    this.$current = null;
                }, this)
            });
        },

        /**
         * @private
         * @abstract
         */
        _getPicker: function () {
            throw new Error('Method _getPicker is abstract and must be implemented');
        },

        /**
         * @private
         */
        _preparePicker: function () {
            $(document).off('mousedown.minicolors touchstart.minicolors', '.minicolors-swatch');
            $(document).off('focus.minicolors', '.minicolors-input');
            this.$picker.siblings('.minicolors').css({'position': 'static', 'display': 'block'});
        },

        /**
         * @private
         */
        _addPickerActions: function () {
            this.$picker.parent().find('.minicolors-panel').append(this.pickerActionsTemplate({__: __}));
            this.$picker.parent().find('button[data-action=cancel]').on('click', _.bind(function (e) {
                e.preventDefault();
                this.$picker.minicolors('hide');
            }, this));
        },

        /**
         * @private
         */
        _addPickerHandlers: function () {
            this.$parent.on('click', 'span.color', _.bind(function (e) {
                if (!this.$element.is(':disabled')) {
                    this.$current = $(e.currentTarget);
                }
            }, this));
            this.$picker.on('click', _.bind(function (e) {
                if (!this.$element.is(':disabled')) {
                    this.$parent.find('.minicolors-panel').css(this._getPickerPos(this.$picker));
                    this.$picker.minicolors('show');
                }
            }, this));
        },

        /**
         * @param {jQuery} $swatch
         * @returns {{left: int, top: int}}
         * @private
         */
        _getPickerPos: function ($swatch) {
            var $panel = this.$parent.find('.minicolors-panel'),
                pos = $swatch.position(),
                x = pos.left + $swatch.offsetParent().scrollLeft() + 5,
                y = pos.top + $swatch.offsetParent().scrollTop() + $swatch.outerHeight() + 3,
                w = $panel.outerWidth(),
                h = $panel.outerHeight() + 39,
                width = $swatch.offsetParent().width(),
                height = $swatch.offsetParent().height();
            if (x > width - w) {
                x -= w;
            }
            if (y > height - h) {
                y -= h + $swatch.outerHeight() + 6;
            }
            return {left: x, top: y};
        }
    });

    return BaseSimpleColorPicker;
});
