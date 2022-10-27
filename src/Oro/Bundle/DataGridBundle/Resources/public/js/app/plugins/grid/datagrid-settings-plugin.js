define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const ShowComponentAction = require('oro/datagrid/action/show-component-action');
    const DatagridManageColumnView = require('orodatagrid/js/app/views/grid/datagrid-manage-column-view');
    const DatagridManageFilterView = require('orodatagrid/js/app/views/grid/datagrid-manage-filter-view');
    const DatagridSettingView = require('orodatagrid/js/app/views/grid/datagrid-settings-view');

    let config = require('module-config').default(module.id);
    config = _.extend({
        icon: 'cog',
        wrapperClassName: `datagrid-settings ${_.isRTL() ? 'dropright' : 'dropleft'}`,
        label: __('oro.datagrid.settings.title'),
        ariaLabel: __('oro.datagrid.settings.title_aria_label')
    }, config);

    const DatagridSettingPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
            DatagridSettingPlugin.__super__.enable.call(this);
        },

        onBeforeToolbarInit: function(toolbarOptions) {
            const options = {
                datagrid: this.main,
                launcherOptions: _.extend(config, {
                    allowDialog: _.isMobile(),
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
                                collection: _.filter(this.main.metadata.filters, function(filter) {
                                    // Do not render filters with visible=false setting
                                    return filter.visible;
                                }),
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
