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
        tagName: "div",

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
        template: _.template('<input name="<%- this.model.cid + \'_\' + this.cid %>" type="radio" value="<%- value %>" <%= selected ? "checked" : "" %>><%- text %>', null, {variable: null}),

        /**
         * @param {Object} event
         */
        onClick: function (event) {
            event.stopPropagation();
        }
    });

    return SelectCellRadioEditor;
});
