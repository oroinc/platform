import viewportManager from 'oroui/js/viewport-manager';
import BaseComponent from 'oroui/js/app/components/base/component';
import loadModules from 'oroui/js/app/services/load-modules';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';

const ViewportComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        viewport: 'all',
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

        // Bind context for callback
        this.onMediaQueryListChange = this.onMediaQueryListChange.bind(this);

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

    onComponentLoaded: function(Component) {
        this.Component = Component;

        this.listenTo(mediator, 'viewport:change', this.onMediaQueryListChange);
        this.onMediaQueryListChange();
    },

    onMediaQueryListChange() {
        if (viewportManager.isApplicable(this.options.viewport)) {
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

export default ViewportComponent;
