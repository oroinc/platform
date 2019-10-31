define([
    'oroui/js/mediator',
    './model-action'
], function(mediator, ModelAction) {
    'use strict';

    /**
     * Action triggers frontend event
     *
     * @export oro/datagrid/action/Frontend-action
     * @class oro.datagrid.action.FrontendAction
     * @extends oro.datagrid.action.ModelAction
     */
    const FrontendAction = ModelAction.extend({
        /**
         * @inheritDoc
         */
        constructor: function FrontendAction(options) {
            FrontendAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        execute: function() {
            mediator.trigger('datagrid:frontend:execute:' + this.datagrid.name, this);
            if (!this.disposed) {
                this.$el.dropdown('toggle');
            }
        }
    });

    return FrontendAction;
});
