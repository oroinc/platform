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
        constructor: function BooleanCell() {
            BooleanCell.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            if (this.isEditableColumn()) {
                // render a checkbox for editable cell
                BooleanCell.__super__.render.apply(this, arguments);
            } else {
                // render a yes/no text for non editable cell
                this.$el.empty();
                var text = '';
                var columnData = this.model.get(this.column.get('name'));
                if (columnData !== null) {
                    text = this.formatter.fromRaw(columnData) ? __('Yes') : __('No');
                }
                this.$el.append('<span>').text(text);
                this.delegateEvents();
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function(e) {
            BooleanCell.__super__.enterEditMode.apply(this, arguments);
            if (this.isEditableColumn()) {
                var $editor = this.currentEditor.$el;
                $editor.prop('checked', !$editor.prop('checked')).change();
                e.stopPropagation();
                $editor.inputWidget('isInitialized')
                    ? $editor.inputWidget('refresh')
                    : $editor.inputWidget('create');
            }
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
