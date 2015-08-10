define(function(require) {
    'use strict';

    var ColumnManagerComponent;
    var _ = require('underscore');
    var Backgrid = require('backgrid');
    var BaseCollection = require('oroui/js/app/models/base/collection');
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
         * @inheritDoc
         */
        initialize: function(options) {
            if (!(options.columns instanceof Backgrid.Columns)) {
                throw new TypeError('The "columns" option have to be instance of Backgrid.Columns');
            }

            _.extend(this, _.pick(options, ['columns']));

            var manageableColumns = this.columns.filter(function(columns) {
                return columns.get('manageable') !== false;
            });

            this.collection = new BaseCollection(manageableColumns);

            ColumnManagerComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.columns;

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
            });

            return columnManagerView;
        }
    });

    return ColumnManagerComponent;
});
