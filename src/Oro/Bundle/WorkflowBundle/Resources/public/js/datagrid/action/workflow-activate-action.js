/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'orodatagrid/js/datagrid/action/model-action'
],
function (_, __, messenger, ModelAction) {
    'use strict';

    /**
     * Activate AJAX action, triggers confirmation dialog and activate workflow definition
     *
     * @export  orodatagrid/js/datagrid/action/workflow-activate-action
     * @class   orodatagrid.datagrid.action.WorkflowActivateAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({
        /** @property {Boolean} */
        confirmation: true,

        /** @property {Array} */
        defaultMessages: {
            confirm_title: __('Workflow reset'),
            confirm_content: __('Attention: This action will reset all workflow data for this entity.'),
            confirm_ok: __('Yes, Reset')
        }
    });
});
