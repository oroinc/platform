define([
    'underscore',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    './mass-action'
], function(_, mediator, messenger, __, MassAction) {
    'use strict';

    var FrontendMassAction;

    /**
     * Frontend mass action class.
     *
     * @export  oro/datagrid/action/frontend-mass-action
     * @class   oro.datagrid.action.FrontendMassAction
     * @extends oro.datagrid.action.MassAction
     */
    FrontendMassAction = MassAction.extend({
        /**
         * @inheritDoc
         */
        execute: function() {
            var selectionState = this.datagrid.getSelectionState();
            if (selectionState.selectedModels.length === 0 && selectionState.inset) {
                messenger.notificationFlashMessage('warning', __(this.messages.empty_selection));
            } else {
                mediator.trigger('datagrid:mass:frontend:execute:' + this.datagrid.name, this);
                this.$el.dropdown('toggle');
            }
        },

        /**
         * @inheritDoc
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
