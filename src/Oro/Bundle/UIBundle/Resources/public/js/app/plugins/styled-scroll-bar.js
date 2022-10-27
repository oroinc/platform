define(function(require, exports, module) {
    'use strict';

    const NAME = 'styledScrollBar';
    const DATA_KEY = 'oro.' + NAME;

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseClass = require('oroui/js/base-class');
    const error = require('oroui/js/error');
    const OverlayScrollBars = require('overlayScrollbars');
    const config = require('module-config').default(module.id);

    const allowedConfig = _.omit(config, 'callbacks');

    const ScrollBar = BaseClass.extend({
        scrollBar: null,

        /**
         * @inheritdoc
         */
        cidPrefix: 'scrollBar',

        /**
         * @inheritdoc
         */
        listen: function() {
            const listenTo = {};

            listenTo['layout:reposition mediator'] = _.debounce(this.update.bind(this),
                this.options('autoUpdateInterval'));
            return listenTo;
        },

        /**
         * @inheritdoc
         */
        constructor: function ScrollBar(options, element) {
            this.element = element;
            ScrollBar.__super__.constructor.call(this, options, element);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.defaults = $.extend(true, {}, $.fn[NAME].defaults, allowedConfig, options || {});
            this.scrollBar = new OverlayScrollBars(this.element, this.defaults);
        },

        /**
         * Destroy plugin
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.scrollBar) {
                this.scrollBar.destroy();
                delete this.scrollBar;
            }

            $.removeData(this.element, DATA_KEY);

            delete this.defaults;
            ScrollBar.__super__.dispose.call(this);
        },

        /**
         * Proxy for OverlayScrollBars.options method
         * @returns {Object|undefined}
         */
        options: function(...args) {
            return this.scrollBar.options(...args);
        },

        /**
         * Proxy for OverlayScrollBars.update method
         */
        update: function(...args) {
            if (this.disposed) {
                return;
            }
            this.scrollBar.update(...args);
        },

        /**
         * Proxy for OverlayScrollBars.sleep method
         */
        sleep: function() {
            this.scrollBar.sleep();
        },

        /**
         * Proxy for OverlayScrollBars.scroll method
         * @returns {Object|undefined}
         */
        scroll: function(...args) {
            return this.scrollBar.scroll(...args);
        },

        /**
         * Proxy for OverlayScrollBars.scrollStop method
         * @returns {Object}
         */
        scrollStop: function() {
            return this.scrollBar.scrollStop();
        },

        /**
         * Proxy for OverlayScrollBars.getElements method
         * @returns {Object}
         */
        getElements: function() {
            return this.scrollBar.getElements();
        },

        /**
         * Proxy for OverlayScrollBars.getState method
         * @returns {Object}
         */
        getState: function() {
            return this.scrollBar.getState();
        },

        /**
         * @returns {Object}
         */
        getDefaults: function() {
            return $.extend(true, {}, $.fn[NAME].defaults);
        }
    });

    $.fn[NAME] = function(options, ...args) {
        const isMethodCall = typeof options === 'string';
        let response = this;

        this.each(function(index) {
            const $element = $(this);
            const instance = $element.data(DATA_KEY);

            if (!instance) {
                $element.data(DATA_KEY, new ScrollBar(options, this));
                return;
            }

            if (isMethodCall) {
                if (options === 'instance') {
                    response = instance;
                    return response;
                }

                if (!_.isFunction(instance[options]) || options.charAt(0) === '_') {
                    error.showErrorInConsole(new Error('Instance ' + NAME + ' doesn\'t support method ' + options ));
                    return false;
                }

                const result = instance[options](...args);

                if (result !== void 0 && index === 0) {
                    response = result;
                }
            }
        });

        return response;
    };

    $.fn[NAME].constructor = ScrollBar;
    $.fn[NAME].defaults = {
        className: 'os-theme-dark',
        autoUpdateInterval: 50
    };
});
