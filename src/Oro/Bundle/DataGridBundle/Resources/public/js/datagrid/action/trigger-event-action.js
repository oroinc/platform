define([
    'underscore',
    './model-action'
], function(_, ModelAction) {
    'use strict';

    var TriggerEventAction;

    /**
     * Trigger Event action. Trigger event from options.
     *
     * @export  oro/datagrid/action/trigger-event-action
     * @class   oro.datagrid.action.TriggerEventAction
     * @extends oro.datagrid.action.ModelAction
     */
    TriggerEventAction = ModelAction.extend({
        /**
         * @inheritDoc
         */
        initialize: function() {
            TriggerEventAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        execute: function() {
            this.datagrid.trigger(this.event_name, [this.model.id]);
            // @TODO: BB-9443
            // If there are no possibility to get datagrid instance in js component from BB-9443
            // please add mediator for this script and change triggering to code below
            // mediator.trigger(this.event_name, this.datagrid, [this.model.id]);
        }
    });

    return TriggerEventAction;
});
