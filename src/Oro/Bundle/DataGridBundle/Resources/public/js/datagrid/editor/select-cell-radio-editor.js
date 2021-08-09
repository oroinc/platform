define([
    'underscore',
    'backgrid'
], function(_, Backgrid) {
    'use strict';

    /**
     * @export  orodatagrid/js/datagrid/editor/select-cell-radio-editor
     * @class   orodatagrid.datagrid.editor.SelectCellRadioEditor
     * @extends Backgrid.SelectCellEditor
     */
    const SelectCellRadioEditor = Backgrid.SelectCellEditor.extend({
        /**
         * @inheritdoc
         */
        tagName: 'ul',

        /** @property */
        className: 'radio-ul',

        /**
         * @inheritdoc
         */
        template: _.template('<li><input id="<%- this.model.cid + \'_\' + this.cid + \'_\' + value %>" ' +
            'name="<%- this.model.cid + \'_\' + this.cid %>" type="radio" value="<%- value %>" ' +
            '<%= selected ? "checked" : "" %>><label for="<%- this.model.cid + \'_\' + this.cid + \'_\' + value %>">' +
            '<%- text %></label></li>', null, {variable: null}),

        /**
         * @inheritdoc
         */
        constructor: function SelectCellRadioEditor(options) {
            SelectCellRadioEditor.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        save: function() {
            const model = this.model;
            const column = this.column;
            model.set(column.get('name'), this.formatter.toRaw(this.$el.find(':checked').val(), model));
        }
    });

    return SelectCellRadioEditor;
});
