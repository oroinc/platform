define([
    'backbone',
    'underscore',
    'routing'
], function(Backbone, _, routing) {
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
            sorters: [],
            columns: {},
            deletable: false,
            editable:  false
        },

        /** @property */
        directions: {
            ASC: '-1',
            DESC: '1'
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
        initialize: function(data) {
            this.urlRoot = routing.generate(this.route);

            if (_.isArray(data.filters) && _.isEmpty(data.filters)) {
                this.set('filters', {});
            }

            if (_.isArray(data.sorters) && _.isEmpty(data.sorters)) {
                this.set('sorters', {});
            }

            _.each(data.sorters, function(direction, key) {
                if (typeof this.directions[direction] !== 'undefined') {
                    data.sorters[key] = this.directions[direction];
                } else {
                    data.sorters[key] = String(direction);
                }
            }, this);
        },

        /**
         * Convert model to format needed for applying greed state
         *
         * @returns {}
         */
        toGridState: function() {
            return {
                filters:  this.get('filters'),
                sorters:  this.get('sorters'),
                columns:  this.get('columns'),
                gridView: this.get('name')
            };
        },

        /**
         * @returns {Array}
         */
        toJSON: function() {
            return _.omit(this.attributes, ['editable', 'deletable']);
        }
    });

    return GridViewsModel;
});
