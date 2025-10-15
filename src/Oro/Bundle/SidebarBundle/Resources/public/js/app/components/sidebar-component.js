import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import SidebarModel from 'orosidebar/js/app/models/sidebar-model';
import SidebarView from 'orosidebar/js/app/views/sidebar-view';
import SidebarWidgetContainerCollection from 'orosidebar/js/app/models/sidebar-widget-container-collection';
import BaseComponent from 'oroui/js/app/components/base/component';

const SidebarComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function SidebarComponent(options) {
        SidebarComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        SidebarComponent.__super__.initialize.call(this, options);

        this.sidebarModel = new SidebarModel(JSON.parse(options.sidebarData), {
            urlRoot: options.urlRoot
        });

        const availableWidgets = _.mapObject(options.availableWidgets, function(defaults) {
            return _.defaults({description: __(defaults.description)}, defaults);
        });

        const widgetsData = JSON.parse(options.widgetsData);
        _.each(widgetsData, function(data) {
            // extend widgets data with defaults
            _.defaults(data, availableWidgets[data.widgetName]);
        });
        this.widgetsCollection = new SidebarWidgetContainerCollection(widgetsData, {
            url: options.url
        });

        const loadPromises = this.widgetsCollection.map(function(widgetModel) {
            return widgetModel.loadModule();
        });

        this._deferredInit();
        $.when(...loadPromises).then(function() {
            this.view = new SidebarView({
                el: options._sourceElement,
                autoRender: true,
                animationDuration: 0,
                model: this.sidebarModel,
                collection: this.widgetsCollection,
                availableWidgets: availableWidgets
            });
            this._resolveDeferredInit();
        }.bind(this));
    }
});

export default SidebarComponent;
