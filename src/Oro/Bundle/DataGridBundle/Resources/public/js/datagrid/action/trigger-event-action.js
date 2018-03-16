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
        constructor: function TriggerEventAction() {
            TriggerEventAction.__super__.constructor.apply(this, arguments);
        },

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
            var scope = this.datagrid.getGridScope();
            if (scope) {
                mediator.trigger(this.event_name + ':' + scope, [this.model.id]);
            }
            mediator.trigger(this.event_name, [this.model.id]);
        }
    });

    return TriggerEventAction;
});
