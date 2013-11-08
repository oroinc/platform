define(['backbone', 'oro/model/widget'], function (Backbone, WidgetModel) {
    'use strict';

    var WidgetCollection = Backbone.Collection.extend({
        model: WidgetModel
    });

    return WidgetCollection;
});