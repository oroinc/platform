/*global define*/
define([
    'underscore',
    'backgrid'
], function (_, Backgrid) {
    'use strict';

    var SelectCellRadioEditor;

    SelectCellRadioEditor = Backgrid.SelectCellEditor.extend({
        /**
         * @inheritDoc
         */
        tagName: "ul class='icons-ul'",

        /**
         * @inheritDoc
         */
        events: {
            "change": "save",
            "blur": "close",
            "keydown": "close",
            "click": "onClick"
        },

        /**
         * @inheritDoc
         */
        template: _.template('<li><input id="<%- this.model.cid + \'_\' + this.cid + \'_\' + value %>" name="<%- this.model.cid + \'_\' + this.cid %>" type="radio" value="<%- value %>" <%= selected ? "checked" : "" %>><label for="<%- this.model.cid + \'_\' + this.cid + \'_\' + value %>"><%- text %></label></li>', null, {variable: null}),

        /**
         * @inheritDoc
         */
        save: function () {
            var model = this.model;
            var column = this.column;
            model.set(column.get("name"), this.formatter.toRaw(this.$el.find(':checked').val(), model));
        },

        /**
         * @param {Object} event
         */
        onClick: function (event) {
            event.stopPropagation();
        }
    });

    return SelectCellRadioEditor;
});
