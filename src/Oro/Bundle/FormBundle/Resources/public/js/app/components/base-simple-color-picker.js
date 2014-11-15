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

                this.$picker.siblings('.minicolors').css({'position': 'static', 'display': 'block'});

                this.$parent.on('click', 'span.color', _.bind(function (e) {
                    if (!this.$element.is(':disabled')) {
                        this.$current = $(e.currentTarget);
                    }
                }, this));
                this.$picker.on('click', _.bind(function (e) {
                    if (!this.$element.is(':disabled')) {
                        this.$parent.find('.minicolors-panel').css(this._getPickerPos());
                        this.$picker.minicolors('show');
                    }
                }, this));
            }
        },

        /**
         * @param {Object} options
         * @private
         */
        _processOptions: function (options) {
            var selectedVal = options._sourceElement.val(),
                selectedIndex = null,
                customIndex = null;

            options = _.defaults(options, this.defaults);

            // set custom color
            if (selectedVal) {
                _.each(options.data, function (value, index) {
                    if (value.class) {
                        if (value.class === 'custom-color') {
                            customIndex = index;
                        }
                    } else if (value.id === selectedVal) {
                        selectedIndex = index;
                    }
                });
                if (customIndex !== null && selectedIndex === null) {
                    options.data[customIndex].id = selectedVal;
                }
            }
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
            return _.defaults(_.omit(options, ['_sourceElement', 'pickerActionsTemplate']), {
                emptyColor: '#FFFFFF'
            });
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
                        this.$element.val(this.$picker.minicolors('value'));
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
         * @returns {{left: int, top: int}}
         * @private
         */
        _getPickerPos: function () {
            var $panel = this.$parent.find('.minicolors-panel'),
                pos = this.$picker.position(),
                x = pos.left + this.$picker.offsetParent().scrollLeft() + 5,
                y = pos.top + this.$picker.offsetParent().scrollTop() + this.$picker.outerHeight() + 3,
                w = $panel.outerWidth(),
                h = $panel.outerHeight() + 39,
                width = this.$picker.offsetParent().width(),
                height = this.$picker.offsetParent().height();
            if (x > width - w) {
                x -= w;
            }
            if (y > height - h) {
                y -= h + this.$picker.outerHeight() + 6;
            }
            return {left: x, top: y};
        }
    });

    return BaseSimpleColorPicker;
});
