define(function(require) {
    'use strict';

    /**
     * Select-like cell content editor. This view is applicable when the cell value contains label (not the value).
     * The editor will use provided `choices` map and `value_field_name`. The server will be updated with value only.
     *
     * ### Column configuration sample:
     *
     * Please pay attention to the registration of the `value_field_name` in `query` and `properties` sections of the
     * sample yml configuration below
     *
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     source:
     *       query:
     *         select:
     *           # please note that both fields(value and label) are required for valid work
     *           - {entity}.id as {column-name-value}
     *           - {entity}.name as {column-name-label}
     *           # query continues here
     *     columns:
     *       {column-name-label}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/related-id-select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               value_field_name: {column-name-value}
     *               # choices: @choiceProvider->getAll
     *               choices: # required
     *                 key-1: First
     *                 key-2: Second
     *           validation_rules:
     *             NotBlank: ~
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
     *     properties:
     *       # this line is required to add {column-name-value} to data sent to client
     *       {column-name-value}: ~
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * inline_editing.editor.view_options.choices          | Key-value set of available choices
     * inline_editing.editor.view_options.value_field_name | Related value field name.
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
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
     * @param {Object} options.choices - Key-value set of available choices
     * @param {Object} options.value_field_name - Related value field name
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports RelatedIdSelectEditorView
     */
    var RelatedIdSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    require('jquery.select2');

    RelatedIdSelectEditorView = SelectEditorView.extend(/** @lends RelatedIdSelectEditorView.prototype */{
        /**
         * @inheritDoc
         */
        constructor: function RelatedIdSelectEditorView() {
            RelatedIdSelectEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            RelatedIdSelectEditorView.__super__.initialize.apply(this, arguments);
            if (options.value_field_name) {
                this.valueFieldName = options.value_field_name;
            } else {
                throw new Error('`value_field_name` option is required');
            }
        },

        getAvailableOptions: function(options) {
            if (!options.choices) {
                throw new Error('`choices` option is required');
            }
            var choices = options.choices;
            var result = [];
            for (var id in choices) {
                if (choices.hasOwnProperty(id)) {
                    result.push({
                        id: id,
                        text: choices[id]
                    });
                }
            }
            return result;
        },

        getRawModelValue: function() {
            return this.model.get(this.valueFieldName);
        },

        parseRawValue: function(value) {
            return value || '';
        },

        getChoiceLabel: function(choiceId) {
            for (var i = 0; i < this.availableChoices.length; i++) {
                var option = this.availableChoices[i];
                if (option.id === choiceId) {
                    return option.text;
                }
            }
        },

        getServerUpdateData: function() {
            var data = {};
            data[this.valueFieldName] = this.getValue();
            return data;
        },

        getModelUpdateData: function() {
            var data = this.getServerUpdateData();
            data[this.fieldName] = this.getChoiceLabel(this.getValue());
            return data;
        }
    });

    return RelatedIdSelectEditorView;
});
