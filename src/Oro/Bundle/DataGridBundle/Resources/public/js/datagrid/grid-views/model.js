define([
    'backbone',
    'underscore',
    'routing',
    'orotranslation/js/translator'
], function(Backbone, _, routing, __) {
    'use strict';

    var GridViewsModel;

    GridViewsModel = Backbone.Model.extend({
        route: 'oro_datagrid_api_rest_gridview_post',
        urlRoot: null,
        sharedByLabel: 'oro.datagrid.grid_views.shared_by.label',

        /** @property */
        idAttribute: 'name',

        /** @property */
        defaults: {
            filters: [],
            sorters: [],
            columns: {},
            deletable: false,
            editable:  false,
            is_default: false,
            shared_by: null
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

            if (_.isArray(data.appearanceData) && _.isEmpty(data.appearanceData)) {
                this.set('appearanceData', {});
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
                filters:    this.get('filters'),
                sorters:    this.get('sorters'),
                columns:    this.get('columns'),
                gridView:   this.get('name'),
                appearanceType: this.get('appearanceType') !== '' ? this.get('appearanceType') : void 0,
                appearanceData: this.get('appearanceData')
            };
        },

        /**
         * @returns {Array}
         */
        toJSON: function() {
            return _.omit(this.attributes, ['editable', 'deletable', 'shared_by', 'icon']);
        },

        /**
         * @returns {string}
         */
        getLabel: function() {
            var label = this.get('label');
            var sharedBy = this.get('shared_by');
            return null === sharedBy ? label : label + '(' + __(this.sharedByLabel, {name: sharedBy}) + ')';
        }
    });

    return GridViewsModel;
});
