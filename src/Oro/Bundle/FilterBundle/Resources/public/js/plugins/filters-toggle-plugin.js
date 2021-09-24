define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const ToggleFiltersAction = require('orofilter/js/actions/toggle-filters-action');

    const config = require('module-config').default(module.id);
    const launcherOptions = _.extend({
        className: 'btn',
        icon: 'filter',
        label: __('oro.filter.datagrid-toolbar.filters'),
        ariaLabel: __('oro.filter.datagrid-toolbar.aria_label')
    }, config.launcherOptions || {});

    const FiltersTogglePlugin = BasePlugin.extend({
        /**
         * @inheritdoc
         */
        enable: function() {
            this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
            FiltersTogglePlugin.__super__.enable.call(this);
        },

        /**
         * @inheritdoc
         */
        onBeforeToolbarInit: function(toolbarOptions) {
            const options = {
                datagrid: this.main,
                launcherOptions: launcherOptions,
                order: config.order || 50
            };

            toolbarOptions.addToolbarAction(new ToggleFiltersAction(options));
        }
    });

    return FiltersTogglePlugin;
});
