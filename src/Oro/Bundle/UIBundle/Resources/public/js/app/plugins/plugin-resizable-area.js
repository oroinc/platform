define(function(require) {
    'use strict';

    var ResizableArea;
    var persistentStorage = require('oroui/js/persistent-storage');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var _ = require('underscore');
    var $ = require('jquery');
    require('jquery-ui');

    ResizableArea = BasePlugin.extend({
        /**
         * @property {Options}
         */
        defaults: {
            useResizable: !_.isMobile(),
            cashedStateToDOM: false
        },

        /**
         * @property {String}
         */
        uniqueStorageKey: 'resizableAreaID',

        /**
         * @property {Object}
         */
        resizableOptions: {
            // 'n, e, s, w, ne, se, sw, nw, all' */
            handles: 'e',
            zIndex: null,
            maxWidth: 600,
            minWidth: 320,
            // Selector or Element or String
            containment: 'parent',
            classes: {
                'ui-resizable': 'resizable',
                'ui-resizable-e': 'resizable-area'
            }
        },

        /**
         * @property {jQuery}
         */
        $resizableEl: $([]),

        /**
         * @property {jQuery}
         */
        $extraEl: $([]),

        /**
         * @inheritDoc
         */
        initialize: function(main, options) {
            this.options = _.extend(this.defaults, options);

            if (!this.options.useResizable) {
                return;
            }

            if (_.isObject(this.options.resizableOptions)) {
                this.resizableOptions = _.extend(this.resizableOptions, this.options.resizableOptions);
            }

            if (_.isString(this.options.uniqueStorageKey)) {
                this.uniqueStorageKey = this.options.uniqueStorageKey;
            }

            if (this.main.$(this.options.$extraEl).length) {
                this.$extraEl = this.main.$(this.options.$extraEl);
            }

            if (this.main.$(this.options.$resizableEl).length) {
                this.$resizableEl = this.main.$(this.options.$resizableEl);

                this._applyResizable();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this._destroyResizable();

            ResizableArea.__super__.dispose.apply(this, arguments);
        },

        /**
         * Apply the resizable functionality
         * @private
         */
        _applyResizable: function() {
            if (this.$resizableEl.data('uiResizable')) {
                this._destroyResizable();
            }

            this.$resizableEl
                .resizable(
                    _.extend(
                        this.resizableOptions,
                        {
                            resize: _.bind(function(event, ui) {
                                this._onResize(event, ui);
                            }, this),
                            stop: _.bind(function(event, ui) {
                                this._onResizeEnd(event, ui);
                            }, this)
                        }
                    )
                );
        },

        /**
         * Remove the resizable functionality
         * @private
         */
        _destroyResizable: function() {
            this.$resizableEl
                .removeClass('resizable-enable')
                .resizable('destroy');
        },

        /**
         * {Boolean} [removeSize]
         * Disable the resizable functionality
         */
        disable: function(removeSize) {
            var restore = _.isUndefined(removeSize) ? true : removeSize;

            this.$resizableEl
                .removeClass('resizable-enable')
                .resizable('disable');

            if (_.isBoolean(restore)) {
                this.removeCalculatedSize();
            }

            ResizableArea.__super__.disable.call(this);
        },

        /**
         * {Boolean} [restoreSize]
         * Enable the resizable functionality
         */
        enable: function(restoreSize) {
            var restore = _.isUndefined(restoreSize) ? true : restoreSize;

            this.$resizableEl
                .addClass('resizable-enable')
                .resizable('enable');

            if (_.isBoolean(restore)) {
                this.setPreviousSize();
            }

            ResizableArea.__super__.enable.call(this);
        },

        /**
         * @param {Event} event
         * @param {Object} ui
         * @private
         */
        _onResize: function(event, ui) {
            this.$extraEl.css({
                width: this.calculateSize(ui.size.width)
            });
        },

        /**
         * @param {Event} event
         * @param {Object} ui
         * @private
         */
        _onResizeEnd: function(event, ui) {
            this._savePreviousSize(ui.size.width);
        },

        /**
         * @param {Number} size
         * @private
         */
        _savePreviousSize: function(size) {
            persistentStorage.setItem(
                this.uniqueStorageKey,
                JSON.stringify({
                    resizeSize: size
                })
            );

            if ($(this.options.cashedStateToDOM).length) {
                var selectors = {};
                selectors[this.options.$resizableEl] = size;
                selectors[this.options.$extraEl] = this.calculateSize(size);
                $(this.options.cashedStateToDOM).data('resizable-area-cache', {
                    apply: _.bind(function() {
                        _.each(selectors, function(key, item) {
                            $(this).find(item).css({
                                width: key
                            });
                        }, this);
                    }, $(this.options.cashedStateToDOM))
                });
            }
        },

        setPreviousSize: function() {
            var state = JSON.parse(persistentStorage.getItem(this.uniqueStorageKey));

            if (_.isObject(state)) {
                this.$resizableEl.css({
                    width: state.resizeSize
                });
                this.$extraEl.css({
                    width: this.calculateSize(state.resizeSize)
                });
            }
        },

        removePreviusState: function() {
            persistentStorage.removeItem(this.uniqueStorageKey);
        },

        removeCalculatedSize: function() {
            this.$extraEl
                .add(this.$resizableEl)
                .css({
                    width: ''
                });
        },

        /**
         * @param {Number} size
         * @returns {string}
         */
        calculateSize: function(size) {
            return _.isNumber(size) ? 'calc(100% - ' + size + 'px)' : '';
        }
    });

    return ResizableArea;
});
