define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');

    /**
     * @class ToolbarMassActionComponent
     * @extends BaseComponent
     */
    const ToolbarMassActionComponent = BaseComponent.extend({
        /**
         * Instance of grid
         * @type {Backgrid.Grid}
         */
        grid: null,

        /**
         * @inheritdoc
         */
        constructor: function ToolbarMassActionComponent(options) {
            ToolbarMassActionComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['collection', 'actions', 'grid']));

            const actions = [];
            const grid = this.grid;

            this.actions.each(function(Action) {
                const ActionModule = Action.get('module');
                const action = new ActionModule({datagrid: grid});

                this.listenTo(action, 'preExecute', this.onActionRun.bind(this));
                actions.push(action);
            }, this);

            this.actionsPanel = new ActionsPanel({actions: actions, el: options._sourceElement});
            this.actionsPanel.render();

            this.listenTo(this.grid.collection, 'backgrid:refresh', function() {
                if (this.actionsPanel.$el.is(':visible')) {
                    this.actionsPanel.$el.dropdown('toggle');
                }
            });
            ToolbarMassActionComponent.__super__.initialize.call(this, options);
        },

        onActionRun: function(action) {
            if (action && action.disposed) {
                return;
            }

            action.launcherInstance.$el.trigger('tohide.bs.dropdown');
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            // remove properties to prevent disposing them with the columns manager
            delete this.collection;
            delete this.actions;
            delete this.grid;

            ToolbarMassActionComponent.__super__.dispose.call(this);
        }
    });

    return ToolbarMassActionComponent;
});
