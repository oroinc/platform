define(function(require) {
    'use strict';

    var ToolbarMassActionComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');

    /**
     * @class ToolbarMassActionComponent
     * @extends BaseComponent
     */
    ToolbarMassActionComponent = BaseComponent.extend({
        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['collection', 'actions', 'grid']));

            var actions = [];
            var grid = this.grid;

            this.actions.each(function(Action) {
                var ActionModule = Action.get('module');

                actions.push(
                    new ActionModule({
                        datagrid: grid
                    })
                );
            });

            this.actionsPanel = new ActionsPanel({'actions': actions, el: options._sourceElement});
            this.actionsPanel.render();

            this.listenTo(this.grid.collection, 'backgrid:refresh', function() {
                if (this.actionsPanel.$el.is(':visible')) {
                    this.actionsPanel.$el.dropdown('toggle');
                }
            });
            ToolbarMassActionComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            // remove properties to prevent disposing them with the columns manager
            delete this.collection;
            delete this.actions;
            delete this.grid;

            ToolbarMassActionComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return ToolbarMassActionComponent;
});
