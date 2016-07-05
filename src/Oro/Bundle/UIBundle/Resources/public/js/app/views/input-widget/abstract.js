define(function(require) {
    'use strict';

    var AbstractInputWidget;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * AbstractInputWidget is the base class for all input widgets.
     * InputWidget is used to provide a common API for all input widgets.
     * By using this API you provide ability to change input widget to any other or remove it.
     */
    AbstractInputWidget = BaseView.extend({
        /** @property {jQuery} */
        $container: null,

        /** @property {String} */
        widgetFunctionName: '',

        /** @property {Function} */
        widgetFunction: null,

        /** @property {mixed} */
        initializeOptions: null,

        /** @property {mixed} */
        destroyOptions: null,

        /** @property {mixed} */
        refreshOptions: null,

        /** @property {string} */
        containerClass: 'input-widget',

        /** @property {string} */
        containerClassSuffix: '',

        /** @property {Boolean} */
        keepElement: true,

        refreshOnChange: false,

        overrideJqueryMethods: ['val', 'hide', 'show', 'focus', 'width'],

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.resolveOptions(options);
            this.initializeWidget();

            if (this.isInitialized()) {
                this.container().addClass(this.containerClass);
            }

            this.$el.trigger('input-widget:init');
        },

        initializeWidget: function() {
            if (this.initializeOptions) {
                this.widgetFunction(this.initializeOptions);
            } else {
                this.widgetFunction();
            }
        },

        delegateEvents: function() {
            AbstractInputWidget.__super__.delegateEvents.apply(this, arguments);
            if (this.refreshOnChange) {
                this._addEvent('change', _.bind(this.refresh, this));
            }
        },

        /**
         * Implement this method in child class if widget can not be initialized for some reason
         *
         * @returns {boolean}
         */
        isInitialized: function() {
            return true;
        },

        /**
         * @param {Object} options
         */
        resolveOptions: function(options) {
            _.extend(this, options || {});

            this.$el.data('inputWidget', this)
                .attr('data-bound-input-widget', this.widgetFunctionName || 'no-name');
            if (!this.widgetFunction) {
                this.widgetFunction = _.bind(this.$el[this.widgetFunctionName], this.$el);
            }

            if (this.containerClassSuffix) {
                this.containerClass += '-' + this.containerClassSuffix;
            }
        },

        /**
         * Destroy widget
         *
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disposeWidget();

            this.$el.removeData('inputWidget')
                .removeAttr('data-bound-input-widget');
            delete this.$container;

            return AbstractInputWidget.__super__.dispose.apply(this, arguments);
        },

        disposeWidget: function() {
            if (this.destroyOptions) {
                this.widgetFunction(this.destroyOptions);
            }
        },

        /**
         * Find widget root element
         */
        findContainer: function() {
            throw Error('"findContainer" method have to be defined in the child view');
        },

        /**
         * Get widget root element
         *
         * @returns {jQuery}
         */
        container: function() {
            return this.$container || (this.$container = this.findContainer());
        },

        applyWidgetFunction: function(command, args) {
            args = Array.prototype.slice.apply(args);
            args.unshift(command);
            return this.widgetFunction.apply(this, args);
        },

        /**
         * Resize widget
         *
         * @param {mixed} width
         */
        width: function(width) {
            this.container().width(width);
        },

        /**
         * Refresh widget, by example after input value change
         */
        refresh: function() {
            if (this.refreshOptions) {
                this.widgetFunction(this.refreshOptions);
            } else {
                this.disposeWidget();
                this.initializeWidget();
            }
        },

        hide: function() {
            this.container().hide();
        },

        show: function() {
            this.container().show();
        },

        _addEvent: function(eventName, callback) {
            this.$el.on(eventName + this.eventNamespace(), callback);
        }
    });

    return AbstractInputWidget;
});
