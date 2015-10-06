define([
    'backgrid',
    'orotranslation/js/translator'
], function(Backgrid, __) {
    'use strict';

    var BooleanCell;

    /**
     * Boolean column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/boolean-cell
     * @class   oro.datagrid.cell.BooleanCell
     * @extends Backgrid.BooleanCell
     */
    BooleanCell = Backgrid.BooleanCell.extend({
        /** @property {Boolean} */
        listenRowClick: true,

        /**
         * @inheritDoc
         */
        render: function() {
            if (this.column.get('editable')) {
                // render a checkbox for editable cell
                BooleanCell.__super__.render.apply(this, arguments);
            } else {
                // render a yes/no text for non editable cell
                this.$el.empty();
                var text = this.formatter.fromRaw(this.model.get(this.column.get('name'))) ? __('Yes') : __('No');
                this.$el.append('<span>').text(text);
                this.delegateEvents();
            }

            return this;
        },

        /**
         * @param {Backgrid.Row} row
         * @param {Event} e
         */
        onRowClicked: function(row, e) {
            if (!this.$el.is(e.target) && !this.$el.has(e.target).length) {
                // click on another cell of a row
                this.enterEditMode(e);
            }
        }
    });

    return BooleanCell;
});
