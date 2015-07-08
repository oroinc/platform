define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/app/views/base/view',
    'oroui/js/tools/color-util', 'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function($, _, __, BaseView, colorUtil) {
    'use strict';

    var BaseSimpleColorPickerView = BaseView.extend({
        defaults: {
            pickerActionsTemplate: '<div class="form-actions">' +
                '<button class="btn pull-right" data-action="cancel" type="button"><%= __("Close") %></button>' +
            '</div>'
        },

        events: {
            'enable': 'enable',
            'disable': 'disable'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this._processOptions(options);
            this.pickerActionsTemplate = _.template(options.pickerActionsTemplate);
            this.$parent = this.$el.parent();
            this.$el.simplecolorpicker(this._getSimpleColorPickerOptions(options));
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
        _processOptions: function(options) {
            _.defaults(options, this.defaults);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (!this.disposed) {
                if (this.$el && this.$el.data('simplecolorpicker')) {
                    this.$el.simplecolorpicker('destroy');
                }
                if (this.$parent) {
                    this.$parent.off('.' + this.cid);
                    delete this.$parent;
                }
                if (this.$picker) {
                    this.$picker.minicolors('destroy');
                    this.$picker.off('.' + this.cid);
                    delete this.$picker;
                }
            }
            BaseSimpleColorPickerView.__super__.dispose.call(this);
        },

        enable: function() {
            this.$el.simplecolorpicker('enable');
        },

        disable: function() {
            this.$el.simplecolorpicker('enable', false);
        },

        /**
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _getSimpleColorPickerOptions: function(options) {
            return _.omit(options, ['el', 'pickerActionsTemplate']);
        },

        /**
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _getPickerOptions: function(options) {
            return _.defaults(options, {
                control: 'wheel',
                letterCase: 'uppercase',
                defaultValue: this.$picker.data('color') || '#FFFFFF',
                change: _.bind(function(hex, opacity) {
                    if (this.$current) {
                        this.$current.data('color', hex);
                        this.$current.css('color', colorUtil.getContrastColor(hex));
                        this.$el.val(hex);
                    }
                }, this),
                hide: _.bind(function() {
                    if (this.$current) {
                        delete this.$current;
                    }
                }, this)
            });
        },

        /**
         * @private
         * @abstract
         */
        _getPicker: function() {
            throw new Error('Method _getPicker is abstract and must be implemented');
        },

        /**
         * @private
         */
        _preparePicker: function() {
            this.$picker.siblings('.minicolors').css({'position': 'static', 'display': 'block'});
        },

        /**
         * @private
         */
        _addPickerActions: function() {
            this.$picker.parent().find('.minicolors-panel').append(this.pickerActionsTemplate({__: __}));
            this.$parent.on('click.' + this.cid, 'button[data-action=cancel]', _.bind(function(e) {
                e.preventDefault();
                this.$picker.minicolors('hide');
            }, this));
        },

        /**
         * @private
         */
        _addPickerHandlers: function() {
            this.$parent.on('click.' + this.cid, 'span.color', _.bind(function(e) {
                if (!this.$el.is(':disabled')) {
                    this.$current = $(e.currentTarget);
                }
            }, this));
            this.$picker.on('click.' + this.cid, _.bind(function(e) {
                if (!this.$el.is(':disabled')) {
                    this.$picker.parent().find('.minicolors-panel').css(this._getPickerPos(this.$picker));
                    this.$picker.minicolors('show');
                }
            }, this));
            // stop propagation of some events to avoid showing a minicolors picker before a user clicks on a swatch
            // it is required because we need to set a picker position before show it
            this.$picker.on('mousedown.' + this.cid + ' touchstart.' + this.cid + ' focus.' + this.cid,
                _.bind(function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, this));
        },

        /**
         * @param {jQuery} $swatch
         * @returns {{left: int, top: int}}
         * @private
         */
        _getPickerPos: function($swatch) {
            var $panel = this.$picker.parent().find('.minicolors-panel');
            var pos = $swatch.position();
            var x = pos.left + $swatch.offsetParent().scrollLeft() + 5;
            var y = pos.top + $swatch.offsetParent().scrollTop() + $swatch.outerHeight() + 3;
            var w = $panel.outerWidth();
            var h = $panel.outerHeight() + 39;
            var width = $swatch.offsetParent().width();
            var height = $swatch.offsetParent().height();
            if (x > width - w) {
                x -= w;
            }
            if (y > height - h) {
                y -= h + $swatch.outerHeight() + 6;
            }
            return {left: x, top: y};
        }
    });

    return BaseSimpleColorPickerView;
});
