define(function(require) {
    'use strict';

    var ColumnManagerComponent;
    var _ = require('underscore');
    var Backgrid = require('backgrid');
    var tools = require('oroui/js/tools');
    var ColumnsCollection = require('orodatagrid/js/app/models/column-manager/columns-collection');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ColumnManagerView = require('orodatagrid/js/app/views/column-manager/column-manager-view');

    /**
     * @class ColumnManagerComponent
     * @extends BaseComponent
     */
    ColumnManagerComponent = BaseComponent.extend({
        /**
         * Full collection of columns
         * @type {Backgrid.Columns}
         */
        columns: null,

        /**
         * Collection of manageable columns
         * @type {Backgrid.Columns}
         */
        collection: null,

        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

        /**
         * Preserved initial state of columns
         * @type {Object}
         */
        _initialState: null,

        listen: {
            'change:renderable collection': '_pushState'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!(options.columns instanceof Backgrid.Columns)) {
                throw new TypeError('The "columns" option have to be instance of Backgrid.Columns');
            }

            if (!(options.grid instanceof Backgrid.Grid)) {
                throw new TypeError('The "grid" option have to be instance of Backgrid.Grid');
            }

            _.extend(this, _.pick(options, ['columns', 'grid']));

            var manageableColumns = this.columns.filter(function(columns) {
                return columns.get('manageable') !== false;
            });

            this.collection = new ColumnsCollection(
                manageableColumns,
                _.pick(options, ['minVisibleColumnsQuantity'])
            );

            this._initialState = this._createState();
            this._applyState(this.grid.collection, this.grid.collection.state);
            this.listenTo(this.grid.collection, 'updateState', this._applyState);

            ColumnManagerComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            // remove properties to prevent disposing them with the columns manager
            delete this.columns;
            delete this.grid;

            ColumnManagerComponent.__super__.dispose.apply(this, arguments);
        },

        /**
         * Implements ActionInterface
         *
         * @returns {ColumnManagerView}
         */
        createLauncher: function() {
            var columnManagerView = new ColumnManagerView({
                collection: this.collection
            });

            this.listenTo(columnManagerView, 'reordered', function() {
                this.columns.sort();
                this._pushState();
            });

            return columnManagerView;
        },

        /**
         * Updated columns state in grid collection
         *
         * @protected
         */
        _pushState: function() {
            var columnsState = this._createState();

            if (tools.isEqualsLoosely(columnsState, this._initialState)) {
                columnsState = {};
            }

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
            var columnsState = state.columns;

            this.collection.each(function(column) {
                var name = column.get('name');
                if (columnsState[name]) {
                    column.set(columnsState[name]);
                }
            });
            this.collection.sort();

            this.columns.sort();
        },

        /**
         * Create state according to column parameters
         * (iterates manageable columns and collects their state)
         *
         * @return {Object}
         * @protected
         */
        _createState: function() {
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
        }
    });

    return ColumnManagerComponent;
});
