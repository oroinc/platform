define(function(require) {
    'use strict';

    var ColumnManagerComponent;
    var _ = require('underscore');
    var Backgrid = require('backgrid');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ColumnFilterModel = require('orodatagrid/js/app/models/column-manager/column-filter-model');
    var ColumnFilterView = require('orodatagrid/js/app/views/column-manager/column-manager-filter-view');
    var ColumnManagerCollectionView = require('orodatagrid/js/app/views/column-manager/column-manager-collection-view');
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
        managedColumns: null,

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

            this.managedColumns = options.managedColumns;

            this.columnFilterModel = new ColumnFilterModel();

            this.filterer = _.bind(this.columnFilterModel.filterer, this.columnFilterModel);

            this._createViews(options);

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
            this.listenTo(this.columnManagerCollectionView, 'reordered', this._pushState);
            this.listenTo(this.columnFilterModel, 'change', this.applyFilter);
            this.listenTo(this.managedColumns, 'change', this.applyFilter);
            this.listenTo(this.managedColumns, 'change:renderable', this._pushState);
            this.listenTo(this.managedColumns, 'sort', function() {
                this.columns.sort();
            });

            return ColumnManagerComponent.__super__.delegateListeners.apply(this, arguments);
        },

        /**
         * Creates views for column manager
         *
         * @param {Object} options
         * @protected
         */
        _createViews: function(options) {
            // index of first manageable column
            var orderShift = this.managedColumns[0] ? this.managedColumns[0].get('order') : 0;
            this.columnManagerView = new ColumnManagerView({
                el: options._sourceElement,
                collection: this.managedColumns,
                columnFilterModel: this.columnFilterModel
            });
            this.columnFilterView = new ColumnFilterView({
                el: this.columnManagerView.$('.column-manager-filter').get(0),
                model: this.columnFilterModel
            });
            this.columnManagerCollectionView = new ColumnManagerCollectionView({
                el: this.columnManagerView.$('.column-manager-table').get(0),
                collection: this.managedColumns,
                filterModel: this.columnFilterModel,
                orderShift: orderShift
            });
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

            if (tools.isEqualsLoosely(this._createState(), columnsState)) {
                // nothing to apply, state is the same
                return;
            }

            this._applyingState = true;

            this.managedColumns.each(function(column, i) {
                var name = column.get('name');
                if (columnsState[name]) {
                    attrs = _.defaults(_.pick(columnsState[name], ['renderable', 'order']), {renderable: true});
                } else {
                    attrs = {renderable: true, order: i};
                }
                column.set(attrs);
            });
            this.managedColumns.sort();

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

            this.managedColumns.each(function(column) {
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

        applyFilter: function() {
            this.columnManagerCollectionView.filter(this.filterer);
        }
    });

    return ColumnManagerComponent;
});
