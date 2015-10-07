/** @lends RelatedIdSelectEditorView */
define(function(require) {
    'use strict';

    /**
     * Text cell content editor
     *
     * @class
     * @param {Object} options - Options container.
     * @param {Object} options.model - current row model
     * @param {Backgrid.Cell} options.cell - current datagrid cell
     * @param {Backgrid.Column} options.column - current datagrid column
     * @param {string} options.placeholder - placeholder for empty element
     * @param {Object} options.validationRules - validation rules in form applicable to jQuery.validate
     *
     * @augments (SelectEditorView)[./select-editor-view.md]
     * @exports RelatedIdSelectEditorView
     */
    var RelatedIdSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    require('jquery.select2');

    RelatedIdSelectEditorView = SelectEditorView.extend(/** @exports RelatedIdSelectEditorView.prototype */{

        initialize: function(options) {
            if (options.id_field_name) {
                this.idFieldName = options.id_field_name;
            } else {
                throw new Error('`id_field_name` option is required');
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
            return this.model.get(this.idFieldName) || '';
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
            data[this.idFieldName] = this.getValue();
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
