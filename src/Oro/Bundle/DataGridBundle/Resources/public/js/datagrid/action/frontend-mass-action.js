define([
    'underscore',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    './mass-action'
], function(_, mediator, messenger, __, MassAction) {
    'use strict';

    /**
     * Frontend mass action class.
     *
     * @export  oro/datagrid/action/frontend-mass-action
     * @class   oro.datagrid.action.FrontendMassAction
     * @extends oro.datagrid.action.MassAction
     */
    const FrontendMassAction = MassAction.extend({
        /**
         * @inheritdoc
         */
        constructor: function FrontendMassAction(options) {
            FrontendMassAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        execute: function() {
            const selectionState = this.datagrid.getSelectionState();
            if (selectionState.selectedIds.length === 0 && selectionState.inset) {
                messenger.notificationFlashMessage('warning', __(this.messages.empty_selection));
            } else {
                mediator.trigger('datagrid:mass:frontend:execute:' + this.datagrid.name, this);
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('datagrid:mass:frontend:execute:' + this.datagrid.name);

            MassAction.__super__.dispose.call(this);
        }
    });

    return FrontendMassAction;
});
