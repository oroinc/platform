define(function(require) {
    'use strict';

    const Backbone = require('backbone');
    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const ActionCell = require('oro/datagrid/cell/action-cell');
    const ActionHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');

    /**
     * Column of grid that contains row actions
     *
     * @export  orodatagrid/js/datagrid/column/action-column
     * @class   orodatagrid.datagrid.column.ActionColumn
     * @extends Backgrid.Column
     */
    const ActionColumn = Backgrid.Column.extend({
        /** @property {Object} */
        defaults: _.defaults({
            name: '',
            label: '',
            sortable: false,
            editable: false,
            cell: ActionCell,
            headerCell: ActionHeaderCell,
            datagrid: null,
            actions: [],
            massActions: new Backbone.Collection()
        }, Backgrid.Column.prototype.defaults),

        /**
         * @inheritdoc
         */
        constructor: function ActionColumn(...attrs) {
            ActionColumn.__super__.constructor.call(this, ...attrs);
        },

        /**
         * @inheritdoc
         */
        initialize: function(attributes, options) {
            const attrs = attributes || {};
            if (!attrs.cell) {
                attrs.cell = this.defaults.cell;
            }
            if (!attrs.name) {
                attrs.name = this.defaults.name;
            }
            if (_.isEmpty(attrs.actions) && attrs.massActions.length) {
                this.set('renderable', false);
            }
            ActionColumn.__super__.initialize.call(this, attributes, options);
        }
    });

    return ActionColumn;
});
