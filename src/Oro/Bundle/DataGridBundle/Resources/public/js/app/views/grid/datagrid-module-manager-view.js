define(function(require, exports, module) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const DatagridSettingsListFilterModel =
        require('orodatagrid/js/app/models/datagrid-settings-list/datagrid-settings-list-filter-model');
    const DatagridSettingsListFilterView =
        require('orodatagrid/js/app/views/datagrid-settings-list/datagrid-settings-list-filter-view');
    const DatagridSettingsListCollectionView =
        require('orodatagrid/js/app/views/datagrid-settings-list/datagrid-settings-list-collection-view');
    const DatagridSettingsListView =
        require('orodatagrid/js/app/views/datagrid-settings-list/datagrid-settings-list-view');
    let config = require('module-config').default(module.id);

    config = _.extend({
        enableFilters: true
    }, config);

    /**
     * @class DatagridModuleManagerView
     * @extends BaseView
     */
    const DatagridModuleManagerView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'columns', 'grid', 'collection', 'addSorting', 'enableFilters', 'datagridSettingsListView'
        ]),
        /**
         * Full collection of columns
         * @type {Backgrid.Columns}
         */
        columns: null,

        /**
         * Collection of manageable items
         * @type {Backgrid.Columns}
         */
        collection: null,

        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

        /**
         * Check if sorting enabled
         * @type {boolean}
         */
        addSorting: true,

        /**
         * Check if filters enabled
         * @type {boolean}
         */
        enableFilters: config.enableFilters,

        /**
         * Settings view constructor
         * @property {Constructor.View}
         */
        datagridSettingsListView: DatagridSettingsListView,

        collectionFilterModel: null,

        /**
         * Timeout of update collection state
         * Used for _pushState()
         */
        pushStateTimeout: 200,

        /**
         * Contains a snapshot of columns state which is created when grid.collection is loaded.
         * Used in _onDatagridSettingsHide() to detect whether it is needed to refresh grid to fetch new columns.
         *
         * @type {Object|null}
         */
        _defaultState: null,

        /**
         * @inheritdoc
         */
        constructor: function DatagridModuleManagerView(options) {
            DatagridModuleManagerView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!(options.columns instanceof Backgrid.Columns)) {
                throw new TypeError('The "columns" option have to be instance of Backgrid.Columns');
            }

            if (!(options.grid instanceof Backgrid.Grid)) {
                throw new TypeError('The "grid" option have to be instance of Backgrid.Grid');
            }

            this.collection.comparator = 'order';

            this.collectionFilterModel = new DatagridSettingsListFilterModel();

            this.render = this.render.bind(this, options);

            DatagridModuleManagerView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            // remove properties to prevent disposing them with the columns manager
            delete this.columns;
            delete this.grid;
            DatagridModuleManagerView.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        render: function(options) {
            // index of first manageable column
            const orderShift = this.collection[0] ? this.collection[0].get('order') : 0;
            this.subview('datagridSettingsListView', new this.datagridSettingsListView({
                el: options._sourceElement,
                collection: this.collection,
                columnFilterModel: this.collectionFilterModel
            }));

            if (this.enableFilters) {
                this.subview('datagridSettingsListFilterView', new DatagridSettingsListFilterView({
                    el: this.subview('datagridSettingsListView').$('[data-role="datagrid-settings-filter"]').get(0),
                    model: this.collectionFilterModel
                }));
            }

            this.subview('datagridSettingsListCollectionView', new DatagridSettingsListCollectionView({
                el: this.subview('datagridSettingsListView').$('[data-role="datagrid-settings-table"]').get(0),
                collection: this.collection,
                filterModel: this.collectionFilterModel,
                addSorting: this.addSorting,
                orderShift: orderShift
            }));

            this.listenTo(this.subview('datagridSettingsListCollectionView'), 'reordered', this._pushState);
        },

        /**
         * Handles bootstrap dropdown show event
         *
         * @param {jQuery.Event} showEvent
         */
        beforeOpen: function(showEvent) {
            if (!this.subview('datagridSettingsListCollectionView')) {
                this.render();
            }
            _.invoke(this.subviews, 'beforeOpen', showEvent);
        },

        /**
         * Update subviews
         * Fit header width for collection view
         * Update height for list view
         */
        updateViews: function() {
            this.subview('datagridSettingsListCollectionView').updateHeaderWidths();
            this.subview('datagridSettingsListView').updateStateView();
        }
    });

    return DatagridModuleManagerView;
});
