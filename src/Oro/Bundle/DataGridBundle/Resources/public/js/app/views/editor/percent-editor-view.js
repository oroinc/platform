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
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
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

            var raw = this.getModelValue();
            if (isNaN(raw)) {
                return '';
            }
            return String(raw);
        }

    });

    return PercentEditorView;
});
