define([
    'oro/datagrid/action/ajax-action',
    'oroui/js/mediator'
], function(AjaxAction, mediator) {
    'use strict';

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

