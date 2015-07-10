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

            if (this.choices) {
                this.optionValues = [];
                _.each(this.choices, function(value, key) {
                    this.optionValues.push([value, key]);
                }, this);
            }
            SelectCell.__super__.initialize.apply(this, arguments);

            this.listenTo(this.model, 'change:' + this.column.get('name'), function() {
                this.enterEditMode();
            });
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var render = SelectCell.__super__.render.apply(this, arguments);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function() {
            if (this.column.get('editable')) {
                SelectCell.__super__.enterEditMode.apply(this, arguments);

                this.$el.find('select').uniform();
            }
        },

        /**
         * @inheritDoc
         */
        exitEditMode: function() {
            this.$el.removeClass('error');
            this.stopListening(this.currentEditor);
            delete this.currentEditor;
        }
    });

    return SelectCell;
});
