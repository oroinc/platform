/** @lends RelatedIdSelectEditorView */
define(function(require) {
    'use strict';

    /**
     * Select-like cell content editor. This view is applicable when cell value contains label (not the value).
     * Editor will use provided `choices` map and `value_field_name`. Server will be updated with value only.
     *
     * ### Column configuration sample:
     *
     * Please note the value_field_name registration in query and properties in the provided sample yml configuration
     *
     * ``` yml
     * datagrid:
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
     *             view: orodatagrid/js/app/views/editor/related-id-select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               value_field_name: {column-name-value}
     *               # choices: @choiceProvider->getAll
     *               choices: # required
     *                 key-1: First
     *                 key-2: Second
     *           validationRules:
     *             # jQuery.validate configuration
     *             required: true
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
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
     * inline_editing.editor.validationRules               | Optional. Client side validation rules
     *
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for empty element
     * @param {Object} options.validationRules - Validation rules in form applicable to jQuery.validate
     * @param {Object} options.choices - Key-value set of available choices
     * @param {Object} options.value_field_name - Related value field name
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports RelatedIdSelectEditorView
     */
    var RelatedIdSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    require('jquery.select2');

    RelatedIdSelectEditorView = SelectEditorView.extend(/** @exports RelatedIdSelectEditorView.prototype */{

        initialize: function(options) {
            if (options.value_field_name) {
                this.valueFieldName = options.value_field_name;
            } else {
                throw new Error('`value_field_name` option is required');
            }
            RelatedIdSelectEditorView.__super__.initialize.apply(this, arguments);
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

        getModelValue: function() {
            return this.model.get(this.valueFieldName) || '';
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
            data[this.column.get('name')] = this.getChoiceLabel(this.getValue());
            return data;
        }
    });

    return RelatedIdSelectEditorView;
});
