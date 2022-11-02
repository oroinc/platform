define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');

    /**
     * Panel with action buttons
     *
     * @export  orodatagrid/js/datagrid/actions-panel
     * @class   orodatagrid.datagrid.ActionsPanel
     * @extends Backbone.View
     */
    const ActionsPanel = Backbone.View.extend({
        /** @property String */
        className: 'btn-group',

        /** @property {Array.<oro.datagrid.action.AbstractAction>} */
        actions: [],

        /** @property {Array.<orodatagrid.datagrid.ActionLauncher>} */
        launchers: [],

        /**
         * @inheritdoc
         */
        constructor: function ActionsPanel(options) {
            ActionsPanel.__super__.constructor.call(this, options);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Array} [options.actions] List of actions
         */
        initialize: function(options) {
            const opts = options || {};

            this.subviews = [];
            if (opts.actions) {
                this.setActions(opts.actions);
            }

            ActionsPanel.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.launchers;
            delete this.actions;
            ActionsPanel.__super__.dispose.call(this);
        },

        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();

            const isDropdown = this.$el.is('.dropdown-menu');

            this.launchers.forEach(launcher => {
                launcher.setOptions({withinDropdown: isDropdown});
                this.$el.append(launcher.render().$el);
                launcher.trigger('appended');
            });

            return this;
        },

        /**
         * Checks if there is at least one action in this panel
         */
        haveActions: function() {
            return !_.isEmpty(this.actions);
        },

        /**
         * Set actions
         *
         * @param {Array.<oro.datagrid.action.AbstractAction>} actions
         */
        setActions: function(actions) {
            this.actions = [];
            this.launchers = [];
            _.each(actions, function(action) {
                this.actions.push(action);
                this.launchers.push(action.createLauncher());
            }, this);
            this.subviews.push(...this.actions);
        },

        /**
         * Disable
         *
         * @return {*}
         */
        disable: function() {
            _.each(this.launchers, function(launcher) {
                launcher.disable();
            });

            return this;
        },

        /**
         * Enable
         *
         * @return {*}
         */
        enable: function() {
            _.each(this.launchers, function(launcher) {
                launcher.enable();
            });

            return this;
        }
    });

    return ActionsPanel;
});
