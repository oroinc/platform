define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const SelectCellRadioEditor = require('orodatagrid/js/datagrid/editor/select-cell-radio-editor');
    const textUtil = require('oroui/js/tools/text-util');

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

        notMarkAsBlank: true,

        /**
         * @inheritdoc
         */
        constructor: function SelectCell(options) {
            SelectCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            if (this.expanded && !this.multiple) {
                this.editor = SelectCellRadioEditor;
            }

            const choices = options.column.get('metadata').choices;
            if (choices) {
                this.optionValues = Object.entries(choices)
                    .map(([label, value]) => [_.escape(textUtil.prepareText(label)), value]);
            } else {
                throw new Error('Column metadata must have choices specified');
            }
            SelectCell.__super__.initialize.call(this, options);
            this.listenTo(this.model, 'change:' + this.column.get('name'), this.enterEditMode);
        },

        /**
         * @inheritdoc
         */
        render() {
            if (_.isEmpty(this.optionValues)) {
                return;
            }

            const render = SelectCell.__super__.render.call(this);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritdoc
         */
        enterEditMode() {
            if (!this.isEditableColumn()) {
                return;
            }
            const modelValue = this.model.get(this.column.get('name'));
            if (!this.currentEditor || !_.isEqual(this.currentEditor.$el.val(), modelValue)) {
                SelectCell.__super__.enterEditMode.call(this);
                this.$el.find('select').inputWidget('create');
            }
        },

        /**
         * @inheritdoc
         */
        exitEditMode() {
            this.$el.removeClass('error');
            this.stopListening(this.currentEditor);
            delete this.currentEditor;
        }
    });

    return SelectCell;
});
