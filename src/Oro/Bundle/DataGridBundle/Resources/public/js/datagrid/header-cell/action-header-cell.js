define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const Backgrid = require('backgrid');
    const ActionsPanel = require('../actions-panel');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!orodatagrid/templates/datagrid/action-header-cell.html');

    /**
     *
     *
     * @export  orodatagrid/js/datagrid/header-cell/action-header-cell
     * @class   orodatagrid.datagrid.headerCell.ActionHeaderCell
     * @extends oroui/js/app/views/base/view
     */
    const ActionHeaderCell = BaseView.extend({
        optionNames: ['column'],

        _attributes() {
            const attrs = Backgrid.Cell.prototype._attributes.call(this);

            attrs['aria-label'] = __('oro.datagrid.cell.action_header.aria_label');

            return attrs;
        },

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
         * @inheritdoc
         */
        constructor: function ActionHeaderCell(options) {
            ActionHeaderCell.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            ActionHeaderCell.__super__.initialize.call(this, options);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.createActionsPanel();

            const datagrid = this.column.get('datagrid');
            this.listenTo(datagrid, 'enable', this.enable);
            this.listenTo(datagrid, 'disable', this.disable);
            this.listenTo(datagrid.massActions, 'reset', this.rebuildAndRender);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.column;
            ActionHeaderCell.__super__.dispose.call(this);
            delete this.actionsPanel;
        },

        createActionsPanel: function() {
            const actions = [];
            const datagrid = this.column.get('datagrid');

            this.column.get('massActions').each(function(Action) {
                const ActionModule = Action.get('module');

                actions.push(
                    new ActionModule({
                        datagrid: datagrid
                    })
                );
            });

            this.subview('actionsPanel', new this.actionsPanel({actions: actions}));
        },

        render: function() {
            const panel = this.subview('actionsPanel');
            this.$el.empty();
            if (panel.haveActions()) {
                this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
                panel.$el.children().wrap('<li/>');
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
            this.$(this.options.controls)
                .attr({
                    'tabindex': null,
                    'aria-disabled': null
                })
                .removeClass('disabled');
        },

        disable: function() {
            this.subview('actionsPanel').disable();
            this.$(this.options.controls)
                .attr({
                    'tabindex': -1,
                    'aria-disabled': true
                })
                .addClass('disabled');
        }
    });

    return ActionHeaderCell;
});
