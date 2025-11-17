import ModelAction from './model-action';

/**
 * Ajax action, triggers REST AJAX request
 *
 * @export  oro/datagrid/action/ajax-action
 * @class   oro.datagrid.action.AjaxAction
 * @extends oro.datagrid.action.ModelAction
 */
const AjaxAction = ModelAction.extend({
    /** @property {String} */
    requestType: 'POST',

    /**
     * @inheritdoc
     */
    constructor: function AjaxAction(options) {
        AjaxAction.__super__.constructor.call(this, options);
    }
});

export default AjaxAction;
