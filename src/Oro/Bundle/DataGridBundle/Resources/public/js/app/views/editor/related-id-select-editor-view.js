define(function(require) {
    'use strict';

    var RelatedIdSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    require('jquery.select2');

    RelatedIdSelectEditorView = SelectEditorView.extend({

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

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== ('' + this.getModelValue());
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
