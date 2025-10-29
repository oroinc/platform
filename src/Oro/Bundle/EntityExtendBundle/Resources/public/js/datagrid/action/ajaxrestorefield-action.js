import AjaxAction from 'oro/datagrid/action/ajax-action';
import mediator from 'oroui/js/mediator';

/**
 * Ajax restore field action.
 * Triggers REST AJAX request and on success - refresh current page
 *
 * @export  oro/datagrid/action/ajaxrestorefield-action
 * @class   oro.datagrid.action.AjaxrestorefieldAction
 * @extends oro.datagrid.action.AjaxAction
 */
const AjaxrestorefieldAction = AjaxAction.extend({
    _onAjaxSuccess: function() {
        mediator.execute('refreshPage');
    }
});

export default AjaxrestorefieldAction;
