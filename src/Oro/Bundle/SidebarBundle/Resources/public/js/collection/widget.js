define(function (require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');
    var WidgetModel = require('oro/model/widget');

    var WidgetCollection = Backbone.Collection.extend({
        model: WidgetModel,

        comparator: 'order'
    });

    return WidgetCollection;
});