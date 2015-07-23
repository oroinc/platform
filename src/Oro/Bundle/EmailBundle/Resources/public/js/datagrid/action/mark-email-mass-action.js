define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oro/datagrid/action/mass-action'
], function(_, messenger, __, MassAction) {
    'use strict';

    var MarkAction;

    /**
     * Mark email as read/unread
     *
     * @export  oroemail/js/datagrid/action/mark-email-mass-action
     * @class   oro.datagrid.action.MarkAction
     * @extends oro.datagrid.action.MassAction
     */
    MarkAction = MassAction.extend({

    });

    return MarkAction;
});
