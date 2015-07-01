/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backgrid'
], function (_, Backgrid) {
    'use strict';

    var SelectCellEditor;

    /**
     * Select column cell editor
     *
     * @export  oro/datagrid/editor/select-cell-editor
     * @class   oro.datagrid.editor.SelectCellEditor
     * @extends Backgrid.SelectCellEditor
     */
    SelectCellEditor = Backgrid.SelectCellEditor.extend({
        /**
         * @inheritDoc
         */
        events: {
            'change':  'save',
            'blur':    'close',
            'keydown': 'close',
            'click':   'onClick'
        },

        /**
         * @param {Object} event
         */
        onClick: function (event) {
            event.stopPropagation();
        }
    });

    return SelectCellEditor;
});
