define([
    'underscore',
    'backgrid',
    'orodatagrid/js/datagrid/editor/select-cell-radio-editor',
    'oroui/js/tools/text-util'
], function(_, Backgrid, SelectCellRadioEditor, textUtil) {
    'use strict';

    /**
     * Select column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/select-cell
     * @class   oro.datagrid.cell.SelectCell
     * @extends Backgrid.SelectCell
     */
    const SelectCell = Backgrid.SelectCell.extend({
        events: {},

        optionValues: [],

        /**
         * @inheritDoc
         */
        constructor: function SelectCell(options) {
            SelectCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.expanded && !this.multiple) {
                this.editor = SelectCellRadioEditor;
            }

            const choices = options.column.get('metadata').choices;
            if (choices) {
                this.optionValues = [];
                _.each(choices, function(value, label) {
                    this.optionValues.push([_.escape(textUtil.prepareText(label)), value]);
                }, this);
            } else {
                throw new Error('Column metadata must have choices specified');
            }
            SelectCell.__super__.initialize.call(this, options);

            this.listenTo(this.model, 'change:' + this.column.get('name'), function() {
                this.enterEditMode();

                this.$el.find('select').inputWidget('create');
            });
        },

        /**
         * @inheritDoc
         */
        render: function() {
            if (_.isEmpty(this.optionValues)) {
                return;
            }

            const render = SelectCell.__super__.render.call(this);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function() {
            if (this.isEditableColumn()) {
                SelectCell.__super__.enterEditMode.call(this);
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
