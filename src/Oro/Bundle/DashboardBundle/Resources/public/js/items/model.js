define([
    'backbone'
], function(Backbone) {
    'use strict';

    /**
     * @class   orodashboard.items.Model
     * @extends Backbone.Model
     */
    const DashboardItemsModel = Backbone.Model.extend({
        defaults: {
            id: null,
            label: null,
            show: true,
            order: 1,
            namePrefix: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function DashboardItemsModel(attrs, options) {
            DashboardItemsModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return DashboardItemsModel;
});
