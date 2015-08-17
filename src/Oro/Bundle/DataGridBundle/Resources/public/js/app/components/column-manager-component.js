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
        collection: null,

        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

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

            this.collection.each(function(column) {
                var name = column.get('name');
                if (columnsState[name]) {
                    attrs = _.defaults(_.pick(columnsState[name], ['renderable', 'order']), {renderable: true});
                    column.set(attrs);
                } else {
                    column.set({
                        renderable: true,
                        order: void 0
                    });
                }
            });
            this.collection.sort();

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
