define(function(require) {
    'use strict';

    var ColumnManagerComponent;
    var _ = require('underscore');
    var Backgrid = require('backgrid');
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
        manageableColumns: null,

        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

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

            this._createManageableCollection(options);

            this._applyState(this.grid.collection, this.grid.collection.state);

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
         * @inheritDoc
         */
        delegateListeners: function() {
            this.listenTo(this.grid.collection, 'updateState', this._applyState);

            return ColumnManagerComponent.__super__.delegateListeners.apply(this, arguments);
        },

        /**
         * Implements ActionInterface
         *
         * @returns {ColumnManagerView}
         */
        createLauncher: function() {
            // index of first manageable column
            var orderShift = this.manageableColumns[0] ? this.manageableColumns[0].get('order') : 0;

            var columnManagerView = new ColumnManagerView({
                collection: this.manageableColumns,
                orderShift: orderShift
            });

            this.listenTo(columnManagerView, 'reordered', this._pushState);
            this.listenTo(this.manageableColumns, 'change:renderable', this._pushState);

            return columnManagerView;
        },

        /**
         * Create collection with manageable columns
         *
         * @param {Object} options
         * @protected
         */
        _createManageableCollection: function(options) {
            var manageableColumns = [];

            this.columns.each(function(column, i) {
                // set initial order
                column.set('order', i, {silent: true});
                // collect manageable columns
                if (column.get('manageable') !== false) {
                    manageableColumns.push(column);
                }
            });

            this.manageableColumns = new ColumnsCollection(manageableColumns,
                _.pick(options, ['minVisibleColumnsQuantity']));
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
            var columnsState = state.columns;
            var attrs;

            this._applyingState = true;

            this.manageableColumns.each(function(column, i) {
                var name = column.get('name');
                if (columnsState[name]) {
                    attrs = _.defaults(_.pick(columnsState[name], ['renderable', 'order']), {renderable: true});
                    column.set(attrs);
                } else {
                    column.set({
                        renderable: true,
                        order: i
                    });
                }
            });
            this.manageableColumns.sort();

            this.columns.sort();

            this._applyingState = false;
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

            this.manageableColumns.each(function(column) {
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
