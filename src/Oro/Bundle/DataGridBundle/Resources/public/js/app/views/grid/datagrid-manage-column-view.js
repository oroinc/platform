define(function(require) {
    'use strict';

    var DatagridManageColumnView;
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var DatagridModuleManagerView = require('orodatagrid/js/app/views/grid/datagrid-module-manager-view');
    var DatagridSettingsListCollection = require('orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-collection');

    /**
     * @class DatagridManageColumnView
     * @extends DatagridModuleManagerView
     */
    DatagridManageColumnView = DatagridModuleManagerView.extend({
        /**
         * Contains a snapshot of columns state which is created when grid.collection is loaded.
         * Used in _onDatagridSettingsHide() to detect whether it is needed to refresh grid to fetch new columns.
         *
         * @type {Object|null}
         */
        defaultState: null,

        /**
         * @inheritDoc
         */
        listen: {
            'dropdown-launcher:hide mediator': '_onDatagridSettingsHide'
        },

        /**
         * @inheritDoc
         */
        constructor: function DatagridManageColumnView() {
            DatagridManageColumnView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            this._createManagedCollection();
            this.defaultState = tools.deepClone(this.grid.collection.state);

            this._onDatagridSettingsHide = _.debounce(this._onDatagridSettingsHide, 100);

            DatagridManageColumnView.__super__.initialize.apply(this, arguments);

            this._applyState(this.grid.collection, this.grid.collection.state);
        },

        /**
         * @inheritDoc
         */
        delegateListeners: function() {
            this.listenTo(this.grid.collection, 'updateState', this._applyState);
            this.listenTo(this.grid.collection, 'sync', this._onSync);
            this.listenTo(this.collection, 'change:renderable', _.debounce(this._pushState, this.pushStateTimeout));
            this.listenTo(this.collection, 'sort', function() {
                this.columns.sort();
            });

            return DatagridModuleManagerView.__super__.delegateListeners.apply(this, arguments);
        },

        /**
         * Create collection with manageable columns
         *
         * @param {Object} options
         * @protected
         */
        _createManagedCollection: function() {
            var managedColumns = [];

            this.collection.each(function(column, i) {
                var isManageable = column.get('manageable') !== false;

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
            if (this._applyingState) {
                return;
            }

            var columnsState = this._createState();
            this.grid.collection.updateState({
                columns: columnsState
            });
        },

        /**
         * Handles state update
         *
         * @protected
         */
        _applyState: function(collection, state) {
            state = state || collection.state;
            var columnsState = state.columns;
            var attrs;

            if (tools.isEqualsLoosely(this._createState(), columnsState)) {
                // nothing to apply, state is the same
                return;
            }

            this._applyingState = true;

            this.collection.each(function(column, i) {
                var name = column.get('name');
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
         * Create state according to column parameters
         * (iterates manageable columns and collects their state)
         *
         * @return {Object}
         * @protected
         */
        _createState: function(collection) {
            var state = {};

            this.collection.each(function(column) {
                var name = column.get('name');
                var order = column.get('order');

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
            return _.filter(this._createState(), function(columnState, columnName) {
                return columnState.renderable && !previousState.columns[columnName].renderable;
            }).length > 0;
        },

        /**
         * @protected
         */
        _refreshCollection: function() {
            this.grid.setAdditionalParameter('refresh', true);
            this.grid.collection.fetch({reset: true});
            this.grid.removeAdditionalParameter('refresh');
        }
    });

    return DatagridManageColumnView;
});
