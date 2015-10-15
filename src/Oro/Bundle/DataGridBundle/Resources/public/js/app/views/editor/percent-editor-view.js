/** @lends NumberEditorView */
define(function(require) {
    'use strict';

    /**
     * Percent cell content editor.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by number frontend type
     *       {column-name-1}:
     *         frontend_type: percent
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/percent-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *           validationRules:
     *             # jQuery.validate configuration
     *             required: true
     *             min: 0
     *             max: 100
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.validationRules               | Optional. The client side validation rules
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules in a form applicable for jQuery.validate
     *
     * @augments [NumberEditorView](./number-editor-view.md)
     * @exports PercentEditorView
     */
    var PercentEditorView;
    var NumberEditorView = require('./number-editor-view');

    PercentEditorView = NumberEditorView.extend(/** @exports PercentEditorView.prototype */{
        className: 'number-editor',

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return parseFloat(raw) * 100;
        },

        getModelUpdateData: function() {
            var data = {};
            data[this.column.get('name')] = this.getValue() / 100;
            return data;
        },

        getFormattedValue: function() {
            if (isNaN(this.getModelValue())) {
                return '';
            }
            return String(this.getModelValue());
        }

    });

    return PercentEditorView;
});
