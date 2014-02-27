/*global define*/
define(['underscore', 'backbone'
    ], function (_, Backbone) {
    'use strict';

    /**
     * Panel with action buttons
     *
     * @export  orodatagrid/js/datagrid/actions-panel
     * @class   orodatagrid.datagrid.ActionsPanel
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property String */
        className: 'btn-group',

        /** @property {Array.<oro.datagrid.AbstractAction>} */
        actions: [],

        /** @property {Array.<oro.datagrid.ActionLauncher>} */
        launchers: [],

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Array} [options.actions] List of actions
         */
        initialize: function (options) {
            var opts = options || {};

            if (opts.actions) {
                this.setActions(opts.actions);
            }

            Backbone.View.prototype.initialize.apply(this, arguments);
        },

        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function () {
            this.$el.empty();

            _.each(this.launchers, function (launcher) {
                this.$el.append(launcher.render().$el);
            }, this);

            return this;
        },

        /**
         * Checks if there is at least one action in this panel
         */
        haveActions: function () {
            return !_.isEmpty(this.actions);
        },

        /**
         * Set actions
         *
         * @param {Array.<oro.datagrid.AbstractAction>} actions
         */
        setActions: function (actions) {
            this.actions = [];
            this.launchers = [];
            _.each(actions, function (action) {
                this.addAction(action);
            }, this);
        },

        /**
         * Adds action to toolbar
         *
         * @param {oro.datagrid.AbstractAction} action
         */
        addAction: function (action) {
            this.actions.push(action);
            this.launchers.push(action.createLauncher());
        },

        /**
         * Disable
         *
         * @return {*}
         */
        disable: function () {
            _.each(this.launchers, function (launcher) {
                launcher.disable();
            });

            return this;
        },

        /**
         * Enable
         *
         * @return {*}
         */
        enable: function () {
            _.each(this.launchers, function (launcher) {
                launcher.enable();
            });

            return this;
        }
    });
});
