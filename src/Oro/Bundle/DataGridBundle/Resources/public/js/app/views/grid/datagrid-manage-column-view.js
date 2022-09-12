define(function(require) {
    'use strict';

    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const DatagridModuleManagerView = require('orodatagrid/js/app/views/grid/datagrid-module-manager-view');
    const DatagridSettingsListCollection =
        require('orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-collection');

    /**
     * @class DatagridManageColumnView
     * @extends DatagridModuleManagerView
     */
    const DatagridManageColumnView = DatagridModuleManagerView.extend({
        /**
         * Contains a snapshot of columns state which is created when grid.collection is loaded.
         * Used in _onDatagridSettingsHide() to detect whether it is needed to refresh grid to fetch new columns.
         *
         * @type {Object|null}
         */
        defaultState: null,

        /**
         * @inheritdoc
         */
        listen: {
            'dropdown-launcher:hide mediator': '_onDatagridSettingsHide'
        },

        /**
         * @inheritdoc
         */
        constructor: function DatagridManageColumnView(options) {
            DatagridManageColumnView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         * @param options
         */
        initialize: function(options) {
            this._createManagedCollection();
            this.defaultState = tools.deepClone(this.grid.collection.state);

            DatagridManageColumnView.__super__.initialize.call(this, options);

            this._applyState(this.grid.collection, this.grid.collection.state);
        },

        /**
         * @inheritdoc
         */
        delegateListeners: function() {
            this.listenTo(this.grid.collection, 'updateState', this._applyState);
            this.listenTo(this.grid.collection, 'sync', this._onSync);
            this.listenTo(this.collection, 'change:renderable', _.debounce(this._pushState, this.pushStateTimeout));
            this.listenTo(this.collection, 'sort', function() {
                this.columns.sort();
            });

            return DatagridModuleManagerView.__super__.delegateListeners.call(this);
        },

        /**
         * Create collection with manageable columns
         *
         * @param {Object} options
         * @protected
         */
        _createManagedCollection: function() {
            const managedColumns = [];

            this.collection.each(function(column, i) {
                const isManageable = column.get('manageable') !== false;

                // set initial order
                if (_.isUndefined(column.get('order')) || isManageable) {
                    column.set('order', i, {silent: true});
                }

                // collect manageable columns
                if (isManageable) {
                    managedColumns.push(column);
                }
            });

            this.collection = new DatagridSettingsListCollection(managedColumns);
        },

        /**
         * Updated columns state in grid collection
         *
         * @protected
         */
        _pushState: function() {
            if (this._applyingState || this._isStateSynced()) {
                return;
            }

            const columnsState = this._createState();
            this.grid.collection.updateState({
                columns: columnsState
            });
        },

        /**
         * Handles state update
         *
         * @protected
         */
        _applyState: function(collection, gridCollectionState) {
            const {columns: columnsState} = gridCollectionState || this.grid.collection.state;

            if (this._isStateSynced(gridCollectionState)) {
                // nothing to apply, state is the same
                return;
            }

            this._applyingState = true;

            this.collection.each(function(column, i) {
                let attrs;
                const name = column.get('name');
                if (columnsState[name]) {
                    attrs = _.defaults(_.pick(columnsState[name], ['renderable', 'order']), {renderable: true});
                } else {
                    attrs = {renderable: true, order: i};
                }
                column.set(attrs);
            });
            this.collection.sort();

            this._applyingState = false;
        },

        /**
         * Check if state in columns collection complies to the columns state in grid collection
         *
         * @param {Object=} gridCollectionState preserved in grid collection
         * @return {boolean}
         * @protected
         */
        _isStateSynced(gridCollectionState) {
            const {columns: preservedColumnsState} = gridCollectionState || this.grid.collection.state;
            const currentColumnsState = this._createState();

            return tools.isEqualsLoosely(currentColumnsState, preservedColumnsState);
        },

        /**
         * Create state according to column parameters
         * (iterates manageable columns and collects their state)
         *
         * @return {Object}
         * @protected
         */
        _createState: function() {
            const state = {};

            this.collection.each(function(column) {
                const name = column.get('name');
                const order = column.get('order');

                state[name] = {
                    renderable: column.get('renderable')
                };

                if (order !== void 0) {
                    state[name].order = order;
                }
            }, this);

            return state;
        },

        /**
         * Creates a snapshot of grid.collection state.
         *
         * @protected
         */
        _onSync: function() {
            this.defaultState = tools.deepClone(this.grid.collection.state);
        },

        /**
         * Makes the datagrid collection to refresh if new columns were added.
         *
         * @protected
         */
        _onDatagridSettingsHide: function() {
            this._pushState();

            if (!this._isRefreshNeeded(this.defaultState)) {
                // do not refresh collection if no new columns were added.
                return;
            }

            this._refreshCollection();
        },

        /**
         * Compares previous and new state and returns true if new columns are found in new state.
         *
         * @param {Object} previousState State object to compare current state with.
         * @returns {boolean}
         * @protected
         */
        _isRefreshNeeded: function(previousState) {
            const {refresh} = this.grid.collection.state.parameters || {};

            if (refresh) {
                return false;
            }

            return _.filter(this._createState(), function(columnState, columnName) {
                return columnState.renderable && !previousState.columns[columnName].renderable;
            }).length > 0;
        },

        /**
         * @protected
         */
        _refreshCollection: function() {
            this.grid.setAdditionalParameter('refresh', true);
            this.grid.collection.fetch({reset: true}).then(() => {
                this.grid.removeAdditionalParameter('refresh');
            });
        }
    });

    return DatagridManageColumnView;
});
