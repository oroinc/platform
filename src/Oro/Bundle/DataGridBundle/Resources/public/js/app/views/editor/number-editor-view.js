define(function(require) {
    'use strict';

    var NumberEditorView;
    var TextEditorView = require('./text-editor-view');

    NumberEditorView = TextEditorView.extend({
        className: 'number-editor',

        getValue: function() {
            return parseFloat(this.$('input[name=value]').val());
        },

        getValidationRules: function() {
            var rules = NumberEditorView.__super__.getValidationRules.call(this);
            rules.number = true;
            return rules;
        },

        getFormattedValue: function() {
            var raw = this.getModelValue();
            if (isNaN(raw)) {
                return '';
            }
            return this.options.decimalPlaces !== void 0 ? raw.toFixed(this.options.decimalPlaces) : raw;
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return parseFloat(raw);
        }
    });

    return NumberEditorView;
});
