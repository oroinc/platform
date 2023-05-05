define(function(require) {
    'use strict';

    const Backgrid = require('backgrid');
    const __ = require('orotranslation/js/translator');

    /**
     * Boolean column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/boolean-cell
     * @class   oro.datagrid.cell.BooleanCell
     * @extends Backgrid.BooleanCell
     */
    const BooleanCell = Backgrid.BooleanCell.extend({
        /** @property {Boolean} */
        listenRowClick: true,

        events: {
            // no need for enterEditMode on click, boolean cell already in edit mode
        },

        /**
         * @inheritdoc
         */
        constructor: function BooleanCell(options) {
            BooleanCell.__super__.constructor.call(this, options);
            this.listenTo(this.model, 'change:' + this.column.get('name'), this.onModelChange);
        },

        /**
         * @inheritdoc
         */
        render() {
            if (this.isEditableColumn()) {
                // render a checkbox for editable cell
                this.enterEditMode();
            } else {
                // render a yes/no text for non editable cell
                this.$el.empty();
                let text = '';
                const columnData = this.model.get(this.column.get('name'));
                if (columnData !== null) {
                    text = this.formatter.fromRaw(columnData) ? __('Yes') : __('No');
                }
                this.$el.append('<span>').text(text);
                this.delegateEvents();
            }

            return this;
        },

        /**
         * @inheritdoc
         */
        enterEditMode() {
            if (!this.currentEditor) {
                BooleanCell.__super__.enterEditMode.call(this);
                if (this.currentEditor) {
                    this.currentEditor.$el.inputWidget('create');
                }
            }
        },

        /**
         * @param {Backgrid.Row} row
         * @param {Event} e
         */
        onRowClicked(row, e) {
            if (this.currentEditor && !this.currentEditor.$el.is(e.target)) {
                // click on the row, but outside currentEditor
                const columnName = this.column.get('name');
                const currentValue = this.model.get(columnName);
                this.model.set(columnName, !currentValue);
            }
        },

        /**
         * Handles model change and updates editor
         * @param {Backbone.Model} model
         */
        onModelChange(model) {
            if (this.currentEditor) {
                const val = this.currentEditor.formatter.fromRaw(model.get(this.column.get('name')), model);
                this.currentEditor.$el.prop('checked', val);
            }
        }
    });

    return BooleanCell;
});
