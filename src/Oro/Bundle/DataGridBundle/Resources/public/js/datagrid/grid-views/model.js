/*jslint nomen:true*/
/*global define*/
define([
    'backbone',
    'underscore',
    'routing'
], function (Backbone, _, routing) {
    'use strict';

    var GridViewsModel;

    GridViewsModel = Backbone.Model.extend({
        route: 'oro_datagrid_api_rest_gridview_post',
        urlRoot: null,

        /** @property */
        idAttribute: 'name',

        /** @property */
        defaults: {
            filters: [],
            sorters: []
        },

        /** @property */
        directions: {
            "ASC": "-1",
            "DESC": "1"
        },

        /**
         * Initializer.
         *
         * @param {Object} data
         * @param {String} data.name
         * @param {String} data.label
         * @param {String} data.type
         * @param {Array}  data.sorters
         * @param {Array}  data.filters
         */
        initialize: function (data) {
            this.urlRoot = routing.generate(this.route);
            _.each(data.sorters, _.bind(function (direction, key) {
                data.sorters[key] = this.directions[direction];
            }, this));
        },

        /**
         * Convert model to format needed for applying greed state
         *
         * @returns {}
         */
        toGridState: function () {
            return {
                filters:  this.get('filters'),
                sorters:  this.get('sorters'),
                gridView: this.get('name')
            };
        }
    });

    return GridViewsModel;
});
