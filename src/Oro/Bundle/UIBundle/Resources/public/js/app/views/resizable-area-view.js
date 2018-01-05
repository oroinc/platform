define(function(require) {
    'use strict';

    var ResizableAreaView;
    var BaseView = require('oroui/js/app/views/base/view');
    var persistentStorage = require('oroui/js/persistent-storage');
    var _ = require('underscore');
    var $ = require('jquery');
    require('jquery-ui');

    ResizableAreaView = BaseView.extend({
        /**
         * @inheritDoc
         * @property {Object}
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'uniqueStorageKey',
            'resizableOptions',
            '$resizableEl',
            '$extraEl',
            'useResizable'
        ]),

        /**
         * @property {Options}
         */
        options: {
            useResizable: !_.isMobile()
        },

        /**
         * @property {String}
         */
        uniqueStorageKey: 'resizableAreaID',

        /**
         * @property {String}
         */
        resizableOptions: {
            // 'n, e, s, w, ne, se, sw, nw, all' */
            handles: 'e',
            zIndex: null,
            maxWidth: 600,
            minWidth: 320,
            // Selector or Element or String
            containment: 'parent'
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
        initialize: function(options) {
            this.options = _.extend(this.options, options);

            ResizableAreaView.__super__.initialize.call(this, arguments);

            if (!this.options.useResizable) {
                return;
            }

            if (_.isObject(this.options.resizableOptions)) {
                this.resizableOptions = _.extend(this.resizableOptions, this.options.resizableOptions);
            }

            if (_.isString(this.options.uniqueStorageKey)) {
                this.uniqueStorageKey = this.options.uniqueStorageKey;
            }

            if (this.$(this.options.$resizableEl).length) {
                this.$resizableEl = this.$(this.options.$resizableEl);
            }

            if (this.$(this.options.$extraEl).length) {
                this.$extraEl = this.$(this.options.$extraEl);
            }

            this._applyResizable();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this._destroyResizable();

            ResizableAreaView.__super__.initialize.apply(this, arguments);
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
                            classes: {
                                'ui-resizable': 'resizable',
                                'ui-resizable-e': 'resizable-area'
                            },
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
                .removeClass('resizable-disable')
                .resizable('destroy');
        },

        /**
         * {Boolean} [removeSize]
         * Disable the resizable functionality
         */
        disableResizable: function(removeSize) {
            this.$resizableEl
                .addClass('resizable-disable')
                .resizable('disable');

            if (_.isBoolean(removeSize)) {
                this.removeCalculatedSize();
            }
        },

        /**
         * {Boolean} [restoreSize]
         * Enable the resizable functionality
         */
        enableResizable: function(restoreSize) {
            this.$resizableEl
                .removeClass('resizable-disable')
                .resizable('enable');

            if (_.isBoolean(restoreSize)) {
                this.setPreviousSize();
            }
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

    return ResizableAreaView;
});
