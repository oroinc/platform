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
            useResizable: true
        },

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
        $resizableEl: null,

        /**
         * @inheritDoc
         */
        initialize: function(main, options) {
            this.options = _.extend(this.defaults, options);

            if (!this.options.useResizable) {
                return;
            }

            if (_.isObject(this.options.resizableOptions)) {
                this.resizableOptions = _.defaults({}, this.options.resizableOptions, this.resizableOptions);
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
                        {},
                        this.resizableOptions,
                        {
                            stop: this._onResizeEnd.bind(this)
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
                this.setPreviousState();
            }

            ResizableArea.__super__.enable.call(this);
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
            var oldValue = persistentStorage.getItem(ResizableArea.STORAGE_KEY);
            var newValue = {};

            oldValue = oldValue ? JSON.parse(oldValue) : {};

            newValue[this.options.$resizableEl] = {width: size};

            persistentStorage.setItem(ResizableArea.STORAGE_KEY,
                JSON.stringify(_.extend({}, oldValue, newValue))
            );
        },

        setPreviousState: function() {
            ResizableArea.setPreviousState(this.main.$el);
        },

        removePreviousState: function() {
            var state = JSON.parse(persistentStorage.getItem(ResizableArea.STORAGE_KEY));

            if (_.isObject(state)) {
                if (_.has(state, this.options.$resizableEl)) {
                    delete state[this.options.$resizableEl];
                }

                if (_.isEmpty(state)) {
                    persistentStorage.removeItem(ResizableArea.STORAGE_KEY);
                }
            }
        },

        removeCalculatedSize: function() {
            this.$resizableEl.css({width: ''});
        }
    });

    /**
     * @static
     */
    ResizableArea.STORAGE_KEY = 'custom-style-elements-cache';

    /**
     * @static
     */
    ResizableArea.setPreviousState = function($container) {
        var state = JSON.parse(persistentStorage.getItem(ResizableArea.STORAGE_KEY));

        if (_.isObject(state)) {
            _.each(state, function(value, key) {
                var $el = $container.find(key);

                if ($.contains($container[0], $el[0])) {
                    $el.css(value);
                }
            }, this);
        }
    };

    return ResizableArea;
});
