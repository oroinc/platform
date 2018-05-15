define(function(require) {
    'use strict';

    var SidebarComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var SidebarModel = require('orosidebar/js/app/models/sidebar-model');
    var SidebarView = require('orosidebar/js/app/views/sidebar-view');
    var SidebarWidgetContainerCollection = require('orosidebar/js/app/models/sidebar-widget-container-collection');
    var BaseComponent = require('oroui/js/app/components/base/component');

    SidebarComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function SidebarComponent(options) {
            SidebarComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            SidebarComponent.__super__.initialize.call(this, options);

            this.sidebarModel = new SidebarModel(JSON.parse(options.sidebarData), {
                urlRoot: options.urlRoot
            });

            var availableWidgets = _.mapObject(options.availableWidgets, function(defaults) {
                return _.defaults({description: __(defaults.description)}, defaults);
            });

            var widgetsData = JSON.parse(options.widgetsData);
            _.each(widgetsData, function(data) {
                // extend widgets data with defaults
                _.defaults(data, availableWidgets[data.widgetName]);
            });
            this.widgetsCollection = new SidebarWidgetContainerCollection(widgetsData, {
                url: options.url
            });

            var loadPromises = this.widgetsCollection.map(function(widgetModel) {
                return widgetModel.loadModule();
            });

            this._deferredInit();
            $.when.apply($, loadPromises).then(function() {
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

    return SidebarComponent;
});
