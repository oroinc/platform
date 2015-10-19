/** @lends NumberEditorView */
define(function(require) {
    'use strict';

    /**
     * Number cell content editor.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
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
     *             view: orodatagrid/js/app/views/editor/number-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validationRules:
     *             # jQuery.validate configuration
     *             required: true
     *             min: 5
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.validationRules               | Optional. The client side validation rules
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules in a form applicable for jQuery.validate
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports NumberEditorView
     */
    var NumberEditorView;
    var TextEditorView = require('./text-editor-view');
    var _ = require('underscore');
    var NumberFormatter = require('orofilter/js/formatter/number-formatter');

    NumberEditorView = TextEditorView.extend(/** @exports NumberEditorView.prototype */{
        className: 'number-editor',

        initialize: function(options) {
            this.formatter = new NumberFormatter(options);
            NumberEditorView.__super__.initialize.apply(this, arguments);
        },

        getValue: function() {
            var userInput = this.$('input[name=value]').val();
            var parsed = this.formatter.toRaw(userInput);
            return _.isNumber(parsed) ? parsed : NaN;
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
