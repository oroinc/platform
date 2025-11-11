import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import ToggleFiltersAction from 'orofilter/js/actions/toggle-filters-action';
import moduleConfig from 'module-config';

const config = moduleConfig(module.id);

const launcherOptions = _.extend({
    className: 'toggle-filters-action btn btn-primary-light',
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

export default FiltersTogglePlugin;
