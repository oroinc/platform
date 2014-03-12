/*global define*/
define(['backbone', 'backgrid', '../actions-panel'
    ], function (Backbone, Backgrid, ActionsPanel) {
    "use strict";

    var $ = Backbone.$;

    /**
     *
     *
     * @export  orodatagrid/js/datagrid/header-cell/action-header-cell
     * @class   orodatagrid.datagrid.headerCell.ActionHeaderCell
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property */
        className: 'action-column',

        /** @property */
        tagName: "th",

        /** @property */
        template: '#template-datagrid-action-header-cell',

        options: {
            controls: '[data-toggle=dropdown]'
        },

        initialize: function (options) {
            var datagrid;
            Backgrid.requireOptions(options, ["column", "collection"]);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.createActionsPanel();

            datagrid = this.column.get('datagrid');
            this.listenTo(datagrid, 'enable', this.enable);
            this.listenTo(datagrid, 'disable', this.disable);
        },

        createActionsPanel: function () {
            var actions = [],
                datagrid = this.column.get('datagrid');

            _.each(this.column.get('massActions'), function (Action) {
                var action = new Action({
                    datagrid: datagrid
                });
                actions.push(action);
            });

            this.actionsPanel = new ActionsPanel();
            this.actionsPanel.setActions(actions);
        },

        render: function () {
            var panel = this.actionsPanel;

            this.$el.empty().append($(this.template).text());
            if (panel.haveActions()) {
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
                panel.$el.children().wrap('<li/>');
            }

            return this;
        },

        enable: function () {
            this.actionsPanel.enable();
            this.$(this.options.controls).removeClass('disabled');
        },

        disable: function () {
            this.actionsPanel.disable();
            this.$(this.options.controls).addClass('disabled');
        }
    });
});
