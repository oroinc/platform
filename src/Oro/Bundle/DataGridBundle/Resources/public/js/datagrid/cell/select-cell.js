/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backgrid'
], function (_, Backgrid) {
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
        initialize: function (options) {
            if (this.choices) {
                this.optionValues = [];
                _.each(this.choices, function (value, key) {
                    this.optionValues.push([value, key]);
                }, this);
            }
            SelectCell.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function (e) {
            if (this.column.get("editable")) {
                e.stopPropagation();
            }
            return SelectCell.__super__.enterEditMode.apply(this, arguments);
        }
    });

    return SelectCell;
});
