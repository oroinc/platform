import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import DatagridModuleManagerView from 'orodatagrid/js/app/views/grid/datagrid-module-manager-view';
import DatagridSettingsListCollection
    from 'orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-collection';

/**
 * @class DatagridManageFilterView
 * @extends DatagridModuleManagerView
 */
const DatagridManageFilterView = DatagridModuleManagerView.extend({
    /**
     * @inheritdoc
     */
    constructor: function DatagridManageFilterView(options) {
        DatagridManageFilterView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param options
     */
    initialize: function(options) {
        const filterCollection = this._applyState(options.collection, this.grid.collection.state.filters);

        this.collection = new DatagridSettingsListCollection(filterCollection);

        this.defaultState = this._getEnabledState();
        DatagridManageFilterView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    delegateListeners: function() {
        this.listenTo(this.grid.collection, 'sync', this._onSync);
        this.listenTo(this.collection, 'change:renderable', _.debounce(this._pushState, this.pushStateTimeout));

        return DatagridModuleManagerView.__super__.delegateListeners.call(this);
    },

    /**
     * Update filters visibility if new filters were added
     * @private
     */
    _pushState: function() {
        const enabledState = this._getEnabledState();

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
        const state = [];

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
            const stateKey = '__' + filter['name'];
            if (_.has(stateFilters, stateKey)) {
                filter.renderable = stateFilters[stateKey] !== '0';
            }
            filter.metadata = {
                renderable: filter.renderable
            };
        });

        return collectionFilters;
    }
});

export default DatagridManageFilterView;
