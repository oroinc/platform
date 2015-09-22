define(function(require) {
    'use strict';

    var NumberEditorView;
    var TextEditorView = require('./text-editor-view');

    NumberEditorView = TextEditorView.extend({
        className: 'number-editor',

        getValue: function() {
            var data = {};
            data[this.column.get('name')] = parseFloat(this.$('input[name=value]').val());
            return data;
        },

        getValidationRules: function() {
            var rules = NumberEditorView.__super__.getValidationRules.call(this);
            rules.number = true;
            return rules;
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            raw = parseFloat(raw);
            if (isNaN(raw)) {
                return '';
            }
            return this.options.decimalPlaces !== void 0 ? raw.toFixed(this.options.decimalPlaces) : raw;
        }
    });

    return NumberEditorView;
});
