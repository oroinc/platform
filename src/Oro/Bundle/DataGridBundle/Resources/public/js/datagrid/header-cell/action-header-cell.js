/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'backgrid',
    '../actions-panel'
], function(_, Backbone, Backgrid, ActionsPanel) {
    "use strict";

    var $, ActionHeaderCell;
    $ = Backbone.$;

    /**
     *
     *
     * @export  orodatagrid/js/datagrid/header-cell/action-header-cell
     * @class   orodatagrid.datagrid.headerCell.ActionHeaderCell
     * @extends Backbone.View
     */
    ActionHeaderCell = Backbone.View.extend({
        /** @property */
        className: 'action-column',

        /** @property */
        tagName: "th",

        /** @property */
        template: '#template-datagrid-action-header-cell',

        options: {
            controls: '[data-toggle=dropdown]'
        },

        initialize: function(options) {
            var datagrid;

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.subviews = [];
            this.createActionsPanel();

            datagrid = this.column.get('datagrid');
            this.listenTo(datagrid, 'enable', this.enable);
            this.listenTo(datagrid, 'disable', this.disable);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.actionsPanel;
            delete this.column;
            ActionHeaderCell.__super__.dispose.apply(this, arguments);
        },

        createActionsPanel: function() {
            var actions = [],
                datagrid = this.column.get('datagrid');

            _.each(this.column.get('massActions'), function(Action) {
                var action = new Action({
                    datagrid: datagrid
                });
                actions.push(action);
            });

            this.actionsPanel = new ActionsPanel();
            this.actionsPanel.setActions(actions);

            this.subviews.push(this.actionsPanel);
        },

        render: function() {
            var panel = this.actionsPanel;
            this.$el.empty();
            if (panel.haveActions()) {
                this.$el.append($(this.template).text());
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
                panel.$el.children().wrap('<li/>');
            }
            return this;
        },

        enable: function() {
            this.actionsPanel.enable();
            this.$(this.options.controls).removeClass('disabled');
        },

        disable: function() {
            this.actionsPanel.disable();
            this.$(this.options.controls).addClass('disabled');
        }
    });

    return ActionHeaderCell;
});
