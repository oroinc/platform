/** @lends MultiselectEditorView */
define(function(require) {
    'use strict';

    /**
     * Multi-select content editor. Please note that it requires column data format
     * corresponding to multi-select-cell.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Full configuration
     *       {column-name-1}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/multi-relation-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               maximumSelectionLength: 3
     *           validation_rules:
     *             NotBlank: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)
     * @exports MultiRelationEditorView
     *
     * @augments [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)
     * @exports MultiSelectEditorView
     */
    var MultiSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var select2autosizer = require('../../../utils/select2-autosizer');

    MultiSelectEditorView = SelectEditorView.extend(/** @exports MultiSelectEditorView.prototype */{
        className: 'multi-select-editor',
        initialize: function(options) {
            options.ignore_value_field_name = true;
            this.maximumSelectionLength = options.maximumSelectionLength;
            MultiSelectEditorView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'change input[name=value]': 'autoSize'
        },

        listen: {
            'change:visibility': 'autoSize'
        },

        autoSize: function() {
            select2autosizer.applyTo(this.$el, this);
        },

        getSelect2Options: function() {
            var options = MultiSelectEditorView.__super__.getSelect2Options.apply(this, arguments);
            options.multiple = true;
            options.maximumSelectionLength = this.maximumSelectionLength;
            return options;
        },

        getFormattedValue: function() {
            return this.getModelValue().join(',');
        },

        getModelValue: function() {
            var value = this.model.get(this.column.get('name'));
            if (_.isString(value)) {
                value = JSON.parse(value);
            }
            if (_.isNull(value) || value === void 0) {
                // assume empty
                return [];
            }
            return value;
        },

        getValue: function() {
            var select2Value = this.$('input[name=value]').val();
            var ids;
            if (select2Value !== '') {
                ids = select2Value.split(',').map(function(id) {return parseInt(id);});
            } else {
                ids = [];
            }
            return ids;
        }
    });

    return MultiSelectEditorView;
});
