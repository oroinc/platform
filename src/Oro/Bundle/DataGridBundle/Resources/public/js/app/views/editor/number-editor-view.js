define(function(require) {
    'use strict';

    var NumberEditorView;
    var TextEditorView = require('./text-editor-view');
    var NumberFormatter = require('orofilter/js/formatter/number-formatter');

    NumberEditorView = TextEditorView.extend({
        className: 'number-editor',

        initialize: function(options) {
            this.formatter = new NumberFormatter(options);
            NumberEditorView.__super__.initialize.apply(this, arguments);
        },

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

            return this.formatter.fromRaw(raw);
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return parseFloat(raw);
        },

        isChanged: function() {
            var valueChanged = this.getValue() !== this.getModelValue();
            return isNaN(this.getValue()) ?
                this.$('input[name=value]').val() !== '' :
                valueChanged;
        }
    });

    return NumberEditorView;
});
