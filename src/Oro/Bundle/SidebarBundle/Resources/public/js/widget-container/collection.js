/*jslint nomen: true, vars: true*/
/*global define*/

define(function (require) {
    'use strict';

    var Backbone = require('backbone');
    var WidgetContainerModel = require('./model');

    /**
     * @export  orosidebar/js/widget-container/collection
     * @class   orosidebar.widgetContainer.Collection
     * @extends Backbone.Collection
     */
    var WidgetContainerCollection = Backbone.Collection.extend({
        model: WidgetContainerModel,

        comparator: 'position'
    });

    return WidgetContainerCollection;
});
