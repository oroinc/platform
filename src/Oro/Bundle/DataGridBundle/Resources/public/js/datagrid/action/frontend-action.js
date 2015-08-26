define([
    'oroui/js/mediator',
    './model-action'
], function(mediator, ModelAction) {
    'use strict';

    var FrontendAction;

    /**
     * Action triggers frontend event
     *
     * @export oro/datagrid/action/Frontend-action
     * @class oro.datagrid.action.FrontendAction
     * @extends oro.datagrid.action.ModelAction
     */
    FrontendAction = ModelAction.extend({
        /**
         * @inheritDoc
         */
        execute: function() {
            mediator.trigger('datagrid:frontend:execute:' + this.datagrid.name, this);
            this.$el.dropdown('toggle');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('datagrid:frontend:execute:' + this.datagrid.name);

            ModelAction.__super__.dispose.call(this);
        }
    });

    return FrontendAction;
});
