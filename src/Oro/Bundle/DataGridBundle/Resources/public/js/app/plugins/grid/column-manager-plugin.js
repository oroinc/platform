define(function(require) {
    'use strict';

    var ColumnManagerPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var ShowComponentAction = require('oro/datagrid/action/show-component-action');
    var ColumnsCollection = require('orodatagrid/js/app/models/column-manager/columns-collection');
    var ColumnManagerComponent = require('orodatagrid/js/app/components/column-manager-component');

    var config = require('module').config();
    config = _.extend({
        icon: 'cog',
        wrapperClassName: 'column-manager',
        label: __('oro.datagrid.column_manager.title')
    }, config);

    ColumnManagerPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
            ColumnManagerPlugin.__super__.enable.call(this);
        },

        onBeforeToolbarInit: function(toolbarOptions) {
            this._createManagedCollection();
            var options = {
                datagrid: this.main,
                launcherOptions: _.extend(config, {
                    componentConstructor: ColumnManagerComponent,
                    columns: this.main.columns,
                    managedColumns: this.managedColumns
                }, toolbarOptions.columnManager)
            };

            toolbarOptions.actions.push(new ShowComponentAction(options));
        },

        /**
         * Create collection with manageable columns
         *
         * @param {Object} options
         * @protected
         */
        _createManagedCollection: function(options) {
            var managedColumns = [];

            this.main.columns.each(function(column, i) {
                // set initial order
                column.set('order', i, {silent: true});
                // collect manageable columns
                if (column.get('manageable') !== false) {
                    managedColumns.push(column);
                }
            });

            this.managedColumns = new ColumnsCollection(managedColumns,
                _.pick(options, ['minVisibleColumnsQuantity']));
        }
    });

    return ColumnManagerPlugin;
});
