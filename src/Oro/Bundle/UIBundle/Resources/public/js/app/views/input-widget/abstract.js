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

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.resolveOptions(options);

            if (this.initializeOptions) {
                this.widgetFunction(this.initializeOptions);
            } else {
                this.widgetFunction();
            }

            if (this.isInitialized()) {
                this.findContainer();
                this.getContainer().addClass(this.containerClass);
            }
        },

        delegateEvents: function() {
            AbstractInputWidget.__super__.delegateEvents.apply(this, arguments);
            if (this.refreshOnChange) {
                this.$el.on('change' + this.eventNamespace(), _.bind(this.refresh, this));
            }
        },

        /**
         * Implement this method in child class if widget can not be initialized for some reason
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

            this.$el.data('inputWidget', this);
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

            if (this.destroyOptions) {
                this.widgetFunction(this.destroyOptions);
            }

            this.$el.removeData('inputWidget');
            delete this.$container;

            return AbstractInputWidget.__super__.dispose.apply(this, arguments);
        },

        /**
         * Find widget root element
         */
        findContainer: function() {},

        /**
         * Get widget root element
         *
         * @returns {jQuery}
         */
        getContainer: function() {
            return this.$container;
        },

        /**
         * Resize widget
         *
         * @param {mixed} width
         */
        setWidth: function(width) {
            if (this.getContainer()) {
                this.getContainer().width(width);
            }
        },

        /**
         * Refresh widget, by example after input value change
         */
        refresh: function() {
            if (this.refreshOptions) {
                this.widgetFunction(this.refreshOptions);
            }
        }
    });

    return AbstractInputWidget;
});
