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
         * @inheritdoc
         */
        constructor: function FrontendAction(options) {
            FrontendAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
