/*global define*/
define(['underscore', 'backgrid', '../cell/action-cell', '../header-cell/action-header-cell'
    ], function (_, Backgrid, ActionCell, ActionHeaderCell) {
    'use strict';

    /**
     * Column of grid that contains row actions
     *
     * @export  orodatagrid/js/datagrid/column/action-column
     * @class   orodatagrid.datagrid.column.ActionColumn
     * @extends Backgrid.Column
     */
    return Backgrid.Column.extend({

        /** @property {Object} */
        defaults: _.extend({}, Backgrid.Column.prototype.defaults, {
            name: '',
            label: '',
            sortable: false,
            editable: false,
            cell: ActionCell,
            headerCell: ActionHeaderCell,
            datagrid: null,
            actions: [],
            massActions: []
        }),

        /**
         * {@inheritDoc}
         */
        initialize: function (attributes) {
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
            Backgrid.Column.prototype.initialize.apply(this, arguments);
        }
    });
});
