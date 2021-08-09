define(function(require, exports, module) {
    'use strict';

    const Backbone = require('backbone');
    const _ = require('underscore');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    let config = require('module-config').default(module.id);

    config = _.extend({
        route: 'oro_datagrid_api_rest_gridview_post'
    }, config);

    const GridViewsModel = Backbone.Model.extend({
        /** @property */
        route: config.route,

        /** @property */
        urlRoot: null,

        /** @property */
        sharedByLabel: 'oro.datagrid.grid_views.shared_by.label',

        /** @property */
        idAttribute: 'name',

        /** @property */
        defaults: {
            filters: [],
            sorters: [],
            columns: {},
            deletable: false,
            editable: false,
            is_default: false,
            shared_by: null,
            freezeName: ''
        },

        /** @property */
        directions: {
            ASC: '-1',
            DESC: '1'
        },

        /**
         * @inheritdoc
         */
        constructor: function GridViewsModel(attrs, options) {
            GridViewsModel.__super__.constructor.call(this, attrs, options);
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
                filters: this.get('filters'),
                sorters: this.get('sorters'),
                columns: this.get('columns'),
                gridView: this.get('name'),
                appearanceType: this.get('appearanceType') !== '' ? this.get('appearanceType') : void 0,
                appearanceData: this.get('appearanceData')
            };
        },

        /**
         * @returns {Array}
         */
        toJSON: function() {
            return _.omit(this.attributes, ['editable', 'deletable', 'shared_by', 'icon', 'freezeName']);
        },

        /**
         * @returns {string}
         */
        getLabel: function() {
            const label = this.get('label');
            const sharedBy = this.get('shared_by');
            return null === sharedBy ? label : label + '(' + __(this.sharedByLabel, {name: sharedBy}) + ')';
        },

        validate: function(attrs, options) {
            const freezeName = this.get('freezeName').replace(/\s+/g, ' ');
            const errors = [];

            if (_.trim(attrs.label) === '') {
                errors.push(__('oro.datagrid.gridview.notBlank'));
            }

            if (_.trim(attrs.label) === _.trim(freezeName)) {
                errors.push(__('oro.datagrid.gridview.unique'));
            }

            if (errors.length) {
                mediator.trigger(this.get('grid_name') + ':grid-views-model:invalid', errors);

                return true;
            }
        }
    });

    return GridViewsModel;
});
