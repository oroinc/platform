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
        template: _.template('<input name="<%- this.model.cid + \'_\' + this.cid %>" type="radio" value="<%- value %>" <%= selected ? checked : "" %>><%- text %>', null, {variable: null})
    });

    return SelectCellRadioEditor;
});
