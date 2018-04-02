define(function(require) {
    'use strict';

    /**
     * Multi-select content editor. Please note that it requires column data format
     * corresponding to multi-select-cell.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Full configuration
     *       {column-name-1}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/multi-select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               maximumSelectionLength: 3
     *           validation_rules:
     *             NotBlank: true
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
     * inline_editing.validation_rules          | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {string} options.value - initial value of edited field
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports MultiSelectEditorView
     */
    var MultiSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var select2autosizer = require('oroui/js/tools/select2-autosizer');

    MultiSelectEditorView = SelectEditorView.extend(/** @lends MultiSelectEditorView.prototype */{
        className: 'multi-select-editor',

        /**
         * @inheritDoc
         */
        constructor: function MultiSelectEditorView() {
            MultiSelectEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
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

        formatRawValue: function(value) {
            return this.parseRawValue(value).join(',');
        },

        parseRawValue: function(value) {
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
                ids = select2Value.split(',').map(function(id) {
                    return id;
                });
            } else {
                ids = [];
            }
            return ids;
        }
    });

    return MultiSelectEditorView;
});
