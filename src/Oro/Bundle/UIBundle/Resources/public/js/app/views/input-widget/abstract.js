define(function(require) {
    'use strict';

    var AbstractInputWidgetView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * AbstractInputWidgetView is the base class for all input widgets.
     * InputWidget is used to provide a common API for all input widgets.
     * By using this API you provide ability to change input widget to any other or remove it.
     */
    AbstractInputWidgetView = BaseView.extend({
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
        constructor: function AbstractInputWidgetView() {
            AbstractInputWidgetView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.resolveOptions(options);
            this.initializeWidget();

            if (this.isInitialized()) {
                this.getContainer().addClass(this.containerClass);
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
            AbstractInputWidgetView.__super__.delegateEvents.apply(this, arguments);
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
            if (!this.widgetFunction && this.widgetFunctionName) {
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

            return AbstractInputWidgetView.__super__.dispose.apply(this, arguments);
        },

        disposeWidget: function() {
            this.$container = null;
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
        getContainer: function() {
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
            this.getContainer().width(width);
        },

        /**
         * Refresh widget, by example after input value change
         */
        refresh: function() {
            if (this.refreshOptions) {
                this.widgetFunction(this.refreshOptions);
                this.$container = this.findContainer();
            } else {
                this.disposeWidget();
                this.initializeWidget();
            }
        },

        hide: function() {
            this.getContainer().hide();
        },

        show: function() {
            this.getContainer().show();
        },

        _addEvent: function(eventName, callback) {
            this.$el.on(eventName + this.eventNamespace(), callback);
        },

        disable: function(state) {
            this.$el.attr('disabled', state);
            this.refresh();
        }
    });

    return AbstractInputWidgetView;
});
