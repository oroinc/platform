define([
    'backbone',
    './model'
], function(Backbone, Model) {
    'use strict';

    var DashboardItemsCollection;
    /**
     * @class   orodashboard.items.Collection
     * @extends Backbone.Collection
     */
    DashboardItemsCollection = Backbone.Collection.extend({
        model: Model,

        /**
         * @inheritDoc
         */
        constructor: function DashboardItemsCollection() {
            DashboardItemsCollection.__super__.constructor.apply(this, arguments);
        }
    });

    return DashboardItemsCollection;
});
