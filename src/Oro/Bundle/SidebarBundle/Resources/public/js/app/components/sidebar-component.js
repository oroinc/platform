define(function(require) {
    'use strict';

    var SidebarComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var SidebarModel = require('orosidebar/js/model');
    var SidebarView = require('orosidebar/js/view');
    var WidgetContainerCollection = require('orosidebar/js/widget-container/collection');
    var BaseComponent = require('oroui/js/app/components/base/component');

    SidebarComponent = BaseComponent.extend({
        initialize: function(options) {
            SidebarComponent.__super__.initialize.call(this, options);

            this.sidebarModel = new SidebarModel(JSON.parse(options.sidebarData), {
                urlRoot: options.urlRoot
            });

            var widgetsData = JSON.parse(options.widgetsData);
            _.each(widgetsData, function(widget) {
                // extend widgets data with defaults
                _.defaults(widget, options.availableWidgets[widget.widgetName]);
            });
            this.widgetsCollection = new WidgetContainerCollection(widgetsData, {
                url: options.url
            });

            this.view = new SidebarView({
                model: this.sidebarModel,
                availableWidgets: options.availableWidgets,
                widgets: this.widgetsCollection,
                el: options._sourceElement,
                $main: $('#main')
            });

            this.view.render();
        }
    });

    return SidebarComponent;
});
