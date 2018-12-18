define(function(require) {
    'use strict';

    var DatagridSettingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var ShowComponentAction = require('oro/datagrid/action/show-component-action');
    var DatagridManageColumnView = require('orodatagrid/js/app/views/grid/datagrid-manage-column-view');
    var DatagridManageFilterView = require('orodatagrid/js/app/views/grid/datagrid-manage-filter-view');
    var DatagridSettingView = require('orodatagrid/js/app/views/grid/datagrid-settings-view');

    var config = require('module').config();
    config = _.extend({
        icon: 'cog',
        wrapperClassName: 'datagrid-settings dropleft',
        label: __('oro.datagrid.settings.title')
    }, config);

    DatagridSettingPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
            DatagridSettingPlugin.__super__.enable.call(this);
        },

        onBeforeToolbarInit: function(toolbarOptions) {
            var options = {
                datagrid: this.main,
                launcherOptions: _.extend(config, {
                    componentConstructor: toolbarOptions.componentConstructor || DatagridSettingView,
                    viewConstructors: toolbarOptions.viewConstructors || [
                        {
                            id: 'grid',
                            label: __('oro.datagrid.settings.tab.grid'),
                            view: DatagridManageColumnView,
                            options: {
                                collection: this.main.columns
                            }
                        },
                        {
                            id: 'filters',
                            label: __('oro.datagrid.settings.tab.filters'),
                            view: DatagridManageFilterView,
                            options: {
                                collection: this.main.metadata.filters,
                                addSorting: false
                            }
                        }
                    ],
                    columns: this.main.columns
                }, toolbarOptions.datagridSettings),
                order: 600
            };

            toolbarOptions.addToolbarAction(new ShowComponentAction(options));
        }
    });

    return DatagridSettingPlugin;
});
