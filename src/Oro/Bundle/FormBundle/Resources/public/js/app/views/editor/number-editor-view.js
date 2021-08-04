define(function(require) {
    'use strict';

    const TextEditorView = require('./text-editor-view');
    const NumberFormatter = require('orofilter/js/formatter/number-formatter');

    /**
     * Number cell content editor.
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
     *         frontend_type: <number/integer/decimal/currency>
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/number-editor-view
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
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports NumberEditorView
     */
    const NumberEditorView = TextEditorView.extend(/** @lends NumberEditorView.prototype */{
        className: 'number-editor',

        /**
         * @inheritdoc
         */
        constructor: function NumberEditorView(options) {
            NumberEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const formatOptions = {};
            if (options.metadata) {
                const cellMetadata = options.metadata;
                if (cellMetadata.style) {
                    formatOptions.style = cellMetadata.style;
                }
            }

            this.formatter = new NumberFormatter(formatOptions);
            NumberEditorView.__super__.initialize.call(this, options);
        },

        getValue: function() {
            const userInput = this.$('input[name=value]').val();
            const parsed = this.formatter.toRaw(userInput);

            return parsed ? parsed : void 0;
        },

        getValidationRules: function() {
            const rules = NumberEditorView.__super__.getValidationRules.call(this);
            rules.Number = true;
            return rules;
        },

        formatRawValue: function(value) {
            if (isNaN(value)) {
                return '';
            }
            return this.formatter.fromRaw(value);
        },

        isChanged: function() {
            const valueChanged = this.getValue() !== this.getModelValue();
            return isNaN(this.getModelValue())
                ? this.$('input[name=value]').val() !== ''
                : valueChanged;
        },

        getServerUpdateData: function() {
            const data = {};
            const value = this.getValue();
            data[this.fieldName] = isNaN(value) ? null : value;
            return data;
        },

        getModelUpdateData: function() {
            const data = {};
            const value = this.getValue();
            data[this.fieldName] = isNaN(value) ? null : value;
            return data;
        }
    });

    return NumberEditorView;
});
