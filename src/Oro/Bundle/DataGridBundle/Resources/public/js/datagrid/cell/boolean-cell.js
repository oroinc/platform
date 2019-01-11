define(function(require) {
    'use strict';

    var BooleanCell;
    var Backgrid = require('backgrid');
    var __ = require('orotranslation/js/translator');

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

        events: {
            // no need for enterEditMode on click, boolean cell already in edit mode
        },

        /**
         * @inheritDoc
         */
        constructor: function BooleanCell() {
            BooleanCell.__super__.constructor.apply(this, arguments);
            this.listenTo(this.model, 'change:' + this.column.get('name'), this.onModelChange);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            if (this.isEditableColumn()) {
                // render a checkbox for editable cell
                this.enterEditMode();
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
        enterEditMode: function() {
            BooleanCell.__super__.enterEditMode.call(this);
            if (this.currentEditor) {
                this.currentEditor.$el.inputWidget('create');
            }
        },

        /**
         * @param {Backgrid.Row} row
         * @param {Event} e
         */
        onRowClicked: function(row, e) {
            if (this.currentEditor && !this.currentEditor.$el.is(e.target)) {
                // click on the row, but outside of currentEditor
                var columnName = this.column.get('name');
                var currentValue = this.model.get(columnName);
                this.model.set(columnName, !currentValue);
            }
        },

        /**
         * Handles model change and updates editor
         * @param {Backbone.Model} model
         */
        onModelChange: function(model) {
            if (this.currentEditor) {
                var val = this.currentEditor.formatter.fromRaw(model.get(this.column.get('name')), model);
                this.currentEditor.$el.prop('checked', val);
            }
        }
    });

    return BooleanCell;
});
