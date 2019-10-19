define(function(require, exports, module) {
    'use strict';

    var NAME = 'styledScrollBar';
    var DATA_KEY = 'oro.' + NAME;

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');
    var error = require('oroui/js/error');
    var OverlayScrollBars = require('overlayScrollbars');
    var config = require('module-config').default(module.id);

    var allowedConfig = _.omit(config, 'callbacks');

    var ScrollBar = BaseClass.extend({
        scrollBar: null,

        /**
         * @inheritDoc
         */
        cidPrefix: 'scrollBar',

        /**
         * @inheritDoc
         */
        listen: function() {
            var listenTo = {};

            listenTo['layout:reposition mediator'] = _.debounce(this.update.bind(this),
                this.options('autoUpdateInterval'));
            return listenTo;
        },

        /**
         * @inheritDoc
         */
        constructor: function ScrollBar(options, element) {
            this.element = element;
            ScrollBar.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
        options: function() {
            return this.scrollBar.options.apply(this.scrollBar, arguments);
        },

        /**
         * Proxy for OverlayScrollBars.update method
         */
        update: function() {
            if (this.disposed) {
                return;
            }
            this.scrollBar.update.apply(this.scrollBar, arguments);
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
        scroll: function() {
            return this.scrollBar.scroll.apply(this.scrollBar, arguments);
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

    $.fn[NAME] = function(options) {
        var args = _.rest(arguments);
        var isMethodCall = typeof options === 'string';
        var response = this;

        this.each(function(index) {
            var $element = $(this);
            var instance = $element.data(DATA_KEY);

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

                var result = instance[options].apply(instance, args);

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
