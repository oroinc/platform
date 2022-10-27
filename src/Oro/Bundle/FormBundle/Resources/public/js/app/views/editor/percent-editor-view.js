define(function(require) {
    'use strict';

    const NumberEditorView = require('./number-editor-view');

    /**
     * Percent cell content editor.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
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
     *             view: oroform/js/app/views/editor/percent-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.className - CSS class name for editor element
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {string} options.value - initial value of edited field
     *
     * @augments [NumberEditorView](./number-editor-view.md)
     * @exports PercentEditorView
     */
    const PercentEditorView = NumberEditorView.extend(/** @lends PercentEditorView.prototype */{
        className: 'number-editor',

        /**
         * @inheritdoc
         */
        constructor: function PercentEditorView(options) {
            PercentEditorView.__super__.constructor.call(this, options);
        },

        parseRawValue: function(value) {
            return this._roundValue(parseFloat(value) * 100);
        },

        getModelUpdateData: function() {
            const data = {};
            const value = this.getValue();
            data[this.fieldName] = isNaN(value) ? null : this._roundValue(value / 100);
            return data;
        },

        formatRawValue: function(value) {
            const raw = this.parseRawValue(value);
            if (isNaN(raw)) {
                return '';
            }
            return String(raw);
        },

        /**
         * Removes insignificant fractional part of a float value that may occurs in result of math operations.
         * For example, the string representation of the result of 1.11 * 100 is 111.00000000000001,
         * but we need to show 111 in this case.
         *
         * @param {Float} value
         * @returns {Float}
         * @private
         */
        _roundValue: function(value) {
            return parseFloat(Math.round(value + 'e12') + 'e-12');
        }
    });

    return PercentEditorView;
});
