define([
    'underscore',
    'backgrid',
    'oro/datagrid/cell/action-cell',
    '../header-cell/action-header-cell'
], function(_, Backgrid, ActionCell, ActionHeaderCell) {
    'use strict';

    var ActionColumn;

    /**
     * Column of grid that contains row actions
     *
     * @export  orodatagrid/js/datagrid/column/action-column
     * @class   orodatagrid.datagrid.column.ActionColumn
     * @extends Backgrid.Column
     */
    ActionColumn = Backgrid.Column.extend({
        /** @property {Object} */
        defaults: _.defaults({
            name: '',
            label: 'test',
            sortable: false,
            editable: false,
            cell: ActionCell,
            headerCell: ActionHeaderCell,
            datagrid: null,
            actions: [],
            massActions: []
        }, Backgrid.Column.prototype.defaults),

        /**
         * {@inheritDoc}
         */
        initialize: function(attributes) {
            var attrs = attributes || {};
            if (!attrs.cell) {
                attrs.cell = this.defaults.cell;
            }
            if (!attrs.name) {
                attrs.name = this.defaults.name;
            }
            if (_.isEmpty(attrs.actions) && _.isEmpty(attrs.massActions)) {
                this.set('renderable', false);
            }
            ActionColumn.__super__.initialize.apply(this, arguments);
        }
    });

    return ActionColumn;
});
