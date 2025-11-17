import ActionsPanel from 'orodatagrid/js/datagrid/actions-panel';

const ActionsPanelMass = ActionsPanel.extend({
    constructor: function ActionsPanelMass(...args) {
        ActionsPanelMass.__super__.constructor.apply(this, args);
    }
});

export default ActionsPanelMass;
