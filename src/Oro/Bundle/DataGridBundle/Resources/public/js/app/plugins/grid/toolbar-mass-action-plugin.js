define(function(require) {
    'use strict';

    var ToolbarMassActionPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var ShowComponentAction = require('oro/datagrid/action/show-component-action');
    var ToolbarMassActionComponent = require('orodatagrid/js/app/components/toolbar-mass-action-component');

    var config = require('module').config();
    config = _.extend({
        icon: 'ellipsis-horizontal',
        wrapperClassName: 'toolbar-mass-actions',
        label: __('oro.datagrid.mass_action.title')
    }, config);

    ToolbarMassActionPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'beforeToolbarInit', this.onBeforeToolbarInit);
            ToolbarMassActionPlugin.__super__.enable.call(this);
        },

        onBeforeToolbarInit: function(toolbarOptions) {
            var options = {
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

    return ToolbarMassActionPlugin;
});
