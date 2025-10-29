import AbstractAction from './abstract-action';
import ActionComponentDropDownLauncher from 'orodatagrid/js/datagrid/action-component-dropdown-launcher';

const ShowComponentAction = AbstractAction.extend({
    launcher: ActionComponentDropDownLauncher,

    /**
     * @inheritdoc
     */
    constructor: function ShowComponentAction(options) {
        ShowComponentAction.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    execute: function() {
        // do nothing
    }
});

export default ShowComponentAction;
