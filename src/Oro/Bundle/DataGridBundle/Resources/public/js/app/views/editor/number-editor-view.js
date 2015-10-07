/** @lends NumberEditorView */
define(function(require) {
    'use strict';

    /**
     * Number cell content editor
     *
     * @class
     * @param {Object} options - Options container.
     * @augments (TextEditorView)[./text-editor-view.md]
     * @exports NumberEditorView
     */
    var NumberEditorView;
    var TextEditorView = require('./text-editor-view');
    var NumberFormatter = require('orofilter/js/formatter/number-formatter');

    NumberEditorView = TextEditorView.extend(/** @exports NumberEditorView.prototype */{
        className: 'number-editor',

        initialize: function(options) {
            this.formatter = new NumberFormatter(options);
            NumberEditorView.__super__.initialize.apply(this, arguments);
        },

        getValue: function() {
            return this.formatter.toRaw(this.$('input[name=value]').val());
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
