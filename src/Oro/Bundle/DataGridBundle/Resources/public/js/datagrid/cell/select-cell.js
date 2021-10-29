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

        /**
         * @inheritdoc
         */
        constructor: function SelectCell(options) {
            SelectCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
         * @inheritdoc
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
         * @inheritdoc
         */
        enterEditMode: function() {
            if (this.isEditableColumn()) {
                SelectCell.__super__.enterEditMode.call(this);
            }
        },

        /**
         * @inheritdoc
         */
        exitEditMode: function() {
            this.$el.removeClass('error');
            this.stopListening(this.currentEditor);
            delete this.currentEditor;
        }
    });

    return SelectCell;
});
