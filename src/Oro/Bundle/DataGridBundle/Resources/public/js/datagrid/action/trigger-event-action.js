define([
    'underscore',
    './model-action'
], function(_, ModelAction) {
    'use strict';

    var TriggerEventAction;
    var mediator = require('oroui/js/mediator');

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
            mediator.trigger(
                this.event_name + ':' + this.datagrid.$el.closest(this.container).prop('id'),
                [this.model.id]
            );
        }
    });

    return TriggerEventAction;
});
