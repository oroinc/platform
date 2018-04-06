define([
    'backbone'
], function(Backbone) {
    'use strict';

    var DashboardItemsModel;
    /**
     * @class   orodashboard.items.Model
     * @extends Backbone.Model
     */
    DashboardItemsModel = Backbone.Model.extend({
        defaults: {
            id: null,
            label: null,
            show: true,
            order: 1,
            namePrefix: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function DashboardItemsModel() {
            DashboardItemsModel.__super__.constructor.apply(this, arguments);
        }
    });

    return DashboardItemsModel;
});
