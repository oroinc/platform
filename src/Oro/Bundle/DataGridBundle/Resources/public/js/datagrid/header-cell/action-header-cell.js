define(function(require) {
    'use strict';

    var ActionHeaderCell;
    var Backgrid = require('backgrid');
    var ActionsPanel = require('../actions-panel');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!orodatagrid/templates/datagrid/action-header-cell.html');

    /**
     *
     *
     * @export  orodatagrid/js/datagrid/header-cell/action-header-cell
     * @class   orodatagrid.datagrid.headerCell.ActionHeaderCell
     * @extends oroui/js/app/views/base/view
     */
    ActionHeaderCell = BaseView.extend({
        keepElement: false,
        /** @property */
        className: 'action-column renderable',

        /** @property */
        tagName: 'th',

        /** @property */
        template: template,

        /** @property */
        actionsPanel: ActionsPanel,

        /** @property */
        options: {
            controls: '[data-toggle=dropdown]'
        },

        /**
         * @inheritDoc
         */
        constructor: function ActionHeaderCell() {
            ActionHeaderCell.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            ActionHeaderCell.__super__.initialize.apply(this, arguments);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.createActionsPanel();

            var datagrid = this.column.get('datagrid');
            this.listenTo(datagrid, 'enable', this.enable);
            this.listenTo(datagrid, 'disable', this.disable);
            this.listenTo(datagrid.massActions, 'reset', this.rebuildAndRender);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.column;
            ActionHeaderCell.__super__.dispose.apply(this, arguments);
            delete this.actionsPanel;
        },

        createActionsPanel: function() {
            var actions = [];
            var datagrid = this.column.get('datagrid');

            this.column.get('massActions').each(function(Action) {
                var ActionModule = Action.get('module');

                actions.push(
                    new ActionModule({
                        datagrid: datagrid
                    })
                );
            });

            this.subview('actionsPanel', new this.actionsPanel({actions: actions}));
        },

        render: function() {
            var panel = this.subview('actionsPanel');
            this.$el.empty();
            if (panel.haveActions()) {
                this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
                panel.$el.children().addClass('dropdown-item').wrap('<li/>');
            }
            return this;
        },

        rebuildAndRender: function(massActions) {
            this.column.set('massActions', massActions);

            this.createActionsPanel();
            this.render();
        },

        enable: function() {
            this.subview('actionsPanel').enable();
            this.$(this.options.controls).removeClass('disabled');
        },

        disable: function() {
            this.subview('actionsPanel').disable();
            this.$(this.options.controls).addClass('disabled');
        }
    });

    return ActionHeaderCell;
});
