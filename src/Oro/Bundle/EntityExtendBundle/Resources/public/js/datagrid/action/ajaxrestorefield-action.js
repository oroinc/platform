define(function(require) {
    'use strict';

    const AjaxAction = require('oro/datagrid/action/ajax-action').default;
    const mediator = require('oroui/js/mediator');

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

    return AjaxrestorefieldAction;
});
