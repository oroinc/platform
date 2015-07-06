define([
    'underscore',
    'backbone'
], function(_, Backbone) {
    'use strict';

    var ActionsPanel;

    /**
     * Panel with action buttons
     *
     * @export  orodatagrid/js/datagrid/actions-panel
     * @class   orodatagrid.datagrid.ActionsPanel
     * @extends Backbone.View
     */
    ActionsPanel = Backbone.View.extend({
        /** @property String */
        className: 'btn-group',

        /** @property {Array.<oro.datagrid.action.AbstractAction>} */
        actions: [],

        /** @property {Array.<orodatagrid.datagrid.ActionLauncher>} */
        launchers: [],

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Array} [options.actions] List of actions
         */
        initialize: function(options) {
            var opts = options || {};

            this.subviews = [];
            if (opts.actions) {
                this.setActions(opts.actions);
            }

            ActionsPanel.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.launchers;
            delete this.actions;
            ActionsPanel.__super__.dispose.apply(this, arguments);
        },

        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();

            _.each(this.launchers, function(launcher) {
                this.$el.append(launcher.render().$el);
            }, this);

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
            this.subviews.push.apply(this.subviews, this.actions);
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
