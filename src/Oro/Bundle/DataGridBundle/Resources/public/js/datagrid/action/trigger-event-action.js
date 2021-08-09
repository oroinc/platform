define([
    'underscore',
    './model-action'
], function(_, ModelAction) {
    'use strict';

    const mediator = require('oroui/js/mediator');

    /**
     * Trigger Event action. Trigger event from options.
     *
     * @export  oro/datagrid/action/trigger-event-action
     * @class   oro.datagrid.action.TriggerEventAction
     * @extends oro.datagrid.action.ModelAction
     */
    const TriggerEventAction = ModelAction.extend({
        /**
         * @inheritdoc
         */
        constructor: function TriggerEventAction(options) {
            TriggerEventAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        execute: function() {
            const scope = this.datagrid.getGridScope();
            if (scope) {
                mediator.trigger(this.event_name + ':' + scope, [this.model.id]);
            }
            mediator.trigger(this.event_name, [this.model.id]);
        }
    });

    return TriggerEventAction;
});
