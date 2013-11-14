define(function (require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');
    var WidgetContainerModel = require('oro/sidebar/model/widget-container');

    var WidgetContainerCollection = Backbone.Collection.extend({
        url: 'bundles/orosidebar/api/widget-container',

        model: WidgetContainerModel,

        comparator: 'order'
    });

    return WidgetContainerCollection;
});