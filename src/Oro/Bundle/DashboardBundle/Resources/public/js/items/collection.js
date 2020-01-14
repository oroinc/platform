define([
    'backbone',
    './model'
], function(Backbone, Model) {
    'use strict';

    /**
     * @class   orodashboard.items.Collection
     * @extends Backbone.Collection
     */
    const DashboardItemsCollection = Backbone.Collection.extend({
        model: Model,

        /**
         * @inheritDoc
         */
        constructor: function DashboardItemsCollection(...args) {
            DashboardItemsCollection.__super__.constructor.apply(this, args);
        }
    });

    return DashboardItemsCollection;
});
