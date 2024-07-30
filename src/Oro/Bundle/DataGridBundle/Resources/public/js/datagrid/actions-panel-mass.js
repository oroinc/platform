define(function(require) {
    const ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');

    const ActionsPanelMass = ActionsPanel.extend({
        constructor: function ActionsPanelMass(...args) {
            ActionsPanelMass.__super__.constructor.apply(this, args);
        }
    });

    return ActionsPanelMass;
});
