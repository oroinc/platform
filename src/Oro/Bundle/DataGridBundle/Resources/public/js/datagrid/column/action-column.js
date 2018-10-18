define(function(require) {
    'use strict';

    var ActionColumn;
    var Backbone = require('backbone');
    var _ = require('underscore');
    var Backgrid = require('backgrid');
    var ActionCell = require('oro/datagrid/cell/action-cell');
    var ActionHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');

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
            massActions: new Backbone.Collection()
        }, Backgrid.Column.prototype.defaults),

        /**
         * @inheritDoc
         */
        constructor: function ActionColumn() {
            ActionColumn.__super__.constructor.apply(this, arguments);
        },

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
            if (_.isEmpty(attrs.actions) && attrs.massActions.length) {
                this.set('renderable', false);
            }
            ActionColumn.__super__.initialize.apply(this, arguments);
        }
    });

    return ActionColumn;
});
