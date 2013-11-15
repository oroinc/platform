define(function (require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');
    var WidgetContainerModel = require('oro/sidebar/widget-container/model');

    /**
     * @export  oro/sidebar/widget-controller/collection
     * @class oro.sidebar.widget-controller.Collection
     * @extends Backbone.Collection
     */
    var WidgetContainerCollection = Backbone.Collection.extend({
        url: 'bundles/orosidebar/api/widget-container',

        model: WidgetContainerModel,

        comparator: 'order'
    });

    return WidgetContainerCollection;
});
