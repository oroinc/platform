/*global define*/
define([
    './model-action'
], function(ModelAction) {
    'use strict';

    var AjaxAction;

    /**
     * Ajax action, triggers REST AJAX request
     *
     * @export  oro/datagrid/action/ajax-action
     * @class   oro.datagrid.action.AjaxAction
     * @extends oro.datagrid.action.ModelAction
     */
    AjaxAction = ModelAction.extend();

    return AjaxAction;
});
