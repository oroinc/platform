define(function(require) {
    'use strict';

    const viewportManager = require('oroui/js/viewport-manager');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const loadModules = require('oroui/js/app/services/load-modules');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');

    const ViewportComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            viewport: {},
            component: null,
            componentOptions: {}
        },

        /**
         * @property {Function} Component constructor
         */
        Component: null,

        /**
         * @property {Object} Component instance
         */
        component: null,

        /**
         * @inheritdoc
         */
        constructor: function ViewportComponent(options) {
            ViewportComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, _.pick(options, ['viewport', 'component']));
            this.options.componentOptions = _.omit(options, _.keys(this.options));

            this.resolveComponent();

            loadModules(this.options.component, this.onComponentLoaded, this);
        },

        resolveComponent: function() {
            if (this.options.component) {
                return;
            }
            if (this.options.componentOptions.view) {
                this.options.component = 'oroui/js/app/components/view-component';
            } else if (this.options.componentOptions.widgetModule) {
                this.options.component = 'oroui/js/app/components/jquery-widget-component';
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            mediator.off(null, null, this);
            return ViewportComponent.__super__.dispose.call(this);
        },

        onComponentLoaded: function(Component) {
            this.Component = Component;
            mediator.on('viewport:change', this.onViewportChange, this);
            this.onViewportChange(viewportManager.getViewport());
        },

        onViewportChange: function(viewport) {
            if (viewport.isApplicable(this.options.viewport)) {
                this.initializeComponent();
            } else {
                this.disposeComponent();
            }
        },

        initializeComponent: function() {
            if (this.component && !this.component.disposed) {
                return;
            }
            this.component = new this.Component(this.options.componentOptions);
        },

        disposeComponent: function() {
            if (!this.component || this.component.disposed) {
                return;
            }
            this.component.dispose();
        }
    });

    return ViewportComponent;
});
