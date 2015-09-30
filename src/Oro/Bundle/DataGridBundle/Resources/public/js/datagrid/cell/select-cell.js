define([
    'underscore',
    'backgrid',
    'orodatagrid/js/datagrid/editor/select-cell-radio-editor'
], function(_, Backgrid, SelectCellRadioEditor) {
    'use strict';

    var SelectCell;

    /**
     * Select column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/select-cell
     * @class   oro.datagrid.cell.SelectCell
     * @extends Backgrid.SelectCell
     */
    SelectCell = Backgrid.SelectCell.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.expanded && !this.multiple) {
                this.editor = SelectCellRadioEditor;
            }

            if (options.column.get('metadata').choices) {
                this.optionValues = [];
                _.each(options.column.get('metadata').choices, function(value, key) {
                    this.optionValues.push([value, key]);
                }, this);
            } else {
                throw new Error('Column metadata must have choices specified');
            }
            SelectCell.__super__.initialize.apply(this, arguments);
        }
    });

    return SelectCell;
});
