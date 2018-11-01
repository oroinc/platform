define(function(require) {
    'use strict';

    var DatagridManageFilterView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var DatagridModuleManagerView = require('orodatagrid/js/app/views/grid/datagrid-module-manager-view');
    var DatagridSettingsListCollection = require('orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-collection');

    /**
     * @class DatagridManageFilterView
     * @extends DatagridModuleManagerView
     */
    DatagridManageFilterView = DatagridModuleManagerView.extend({
        /**
         * @inheritDoc
         */
        constructor: function DatagridManageFilterView() {
            DatagridManageFilterView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            var filterCollection = this._applyState(options.collection, this.grid.collection.state.filters);

            this.collection = new DatagridSettingsListCollection(filterCollection);

            this.defaultState = this._getEnabledState();
            DatagridManageFilterView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        delegateListeners: function() {
            this.listenTo(this.grid.collection, 'sync', this._onSync);
            this.listenTo(this.collection, 'change:renderable', _.debounce(this._pushState, this.pushStateTimeout));

            return DatagridModuleManagerView.__super__.delegateListeners.apply(this, arguments);
        },

        /**
         * Update filters visibility if new filters were added
         * @private
         */
        _pushState: function() {
            var enabledState = this._getEnabledState();
            if (_.haveEqualSet(this.defaultState, enabledState)) {
                return;
            }

            mediator.trigger('filters:update', enabledState);
        },

        /**
         * Creates a snapshot of grid.collection state.
         *
         * @protected
         */
        _onSync: function() {
            this.defaultState = this._getEnabledState();
        },

        /**
         * Create state according to column parameters
         * (iterates manageable columns and collects their state)
         *
         * @return {Object}
         * @protected
         */
        _getEnabledState: function() {
            var state = [];

            this.collection.each(function(filter) {
                if (filter.get('renderable')) {
                    state.push(filter.get('name'));
                }
            }, this);

            return state;
        },

        /**
         * Apply save filter state initial
         * @param collectionFilters
         * @param stateFilters
         * @returns {*}
         * @private
         */
        _applyState: function(collectionFilters, stateFilters) {
            _.each(collectionFilters, function(filter) {
                var stateKey = '__' + filter['name'];
                if (_.has(stateFilters, stateKey)) {
                    filter['enabled'] = stateFilters[stateKey] !== '0';
                }
                filter['renderable'] = filter['enabled'];
                filter['metadata'] = {
                    renderable: filter['enabled']
                };
            });

            return collectionFilters;
        }
    });

    return DatagridManageFilterView;
});
