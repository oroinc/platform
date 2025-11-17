import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import ShowComponentAction from 'oro/datagrid/action/show-component-action';
import ToolbarMassActionComponent from 'orodatagrid/js/app/components/toolbar-mass-action-component';
import moduleConfig from 'module-config';

const config = {
    icon: 'ellipsis-h',
    wrapperClassName: 'toolbar-mass-actions',
    label: __('oro.datagrid.mass_action.title'),
    attributes: {'data-placement': 'bottom-end'},
    ...moduleConfig(module.id)
};

const ToolbarMassActionPlugin = BasePlugin.extend({
    enable: function() {
        this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
        ToolbarMassActionPlugin.__super__.enable.call(this);
    },

    onBeforeToolbarInit: function(toolbarOptions) {
        const options = {
            datagrid: this.main,
            launcherOptions: _.extend(config, {
                componentConstructor: ToolbarMassActionComponent,
                collection: toolbarOptions.collection,
                actions: this.main.massActions
            })
        };

        toolbarOptions.actions.push(new ShowComponentAction(options));
    }
});

export default ToolbarMassActionPlugin;
