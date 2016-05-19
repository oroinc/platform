define([
    'backbone',
    'underscore',
    'backgrid',
    'oro/datagrid/cell/action-cell',
    '../header-cell/action-header-cell',
    '../simplified-cell-event-binding/move-event-binding-to-row-cell-class-processor'
], function(Backbone, _, Backgrid, ActionCell, ActionHeaderCell, moveEventBindingToRowProcessor) {
    'use strict';

    var Column;

    /**
     * Column of grid that contains row actions
     *
     * @export  orodatagrid/js/datagrid/column/action-column
     * @class   orodatagrid.datagrid.column.ActionColumn
     * @extends Backgrid.Column
     */
    Column = Backgrid.Column.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function(attributes) {
            Column.__super__.initialize.apply(this, arguments);
            this.set(moveEventBindingToRowProcessor.applyTo(this.get('cell')));
        }
    });

    return Column;
});
