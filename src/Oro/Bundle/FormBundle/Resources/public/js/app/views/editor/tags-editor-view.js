/** @lends TagsEditorView */
define(function(require) {
    'use strict';

    /**
     * Tags-select content editor. Please note that it requires column data format
     * corresponding to tags-cell.
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
     *             view: orodatagrid/js/app/views/editor/tags-editor-view
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
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.metadata - Editor metadata
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports TagsEditorView
     */
    var TagsEditorView;
    var RelatedIdRelationEditorView = require('./related-id-relation-editor-view');
    var SelectEditorView = require('./select-editor-view');
    var tools = require('oroui/js/tools');
    var _ = require('underscore');

    TagsEditorView = SelectEditorView.extend(/** @exports TagsEditorView.prototype */{
        className: 'tags-select-editor',
        initialize: function(options) {
            this.options = options;
            TagsEditorView.__super__.initialize.apply(this, arguments);
        },
        getAvailableOptions: function() {
            var value = this.model.get(this.fieldName);
            if (!_.isArray(value)) {
                return [];
            }
            return value;
        },

        getSelect2Options: function() {
            var options = {
                tags: true,
                allowClear: false,
                tokenSeparators:  [',', ' '],
                data: {results: this.availableChoices},
                createSearchChoice: function(term) {
                    return {id: term, text: term, isNew: true};
                }
            };
            return options;
        },

        getModelValue: function() {
            var value = this.model.get(this.fieldName);

            if (!_.isArray(value)) {
                return {data: []};
            }

            return {
                data: value.map(function(value) {
                    return {id: value.id};
                })
            };
        },

        getValue: function() {
            var selections = this.$('input[name=value]').select2('data');
            return {
                data: selections.map(function(v) {
                    return {id: v.id, text: v.text, locked: v.locked};
                }),
                count: selections.length
            };
        },
        getInitialResultItem: function() {
            var modelValue = this.getModelValue();
            if (modelValue !== null && modelValue && modelValue.data) {
                return modelValue.data;
            } else {
                return [];
            }
        },

        getFormattedValue: function() {
            return this.getInitialResultItem()
                .map(function(item) {return item.id;})
                .join(',');
        },

        filterInitialResultItem: function(choices) {
            choices = _.clone(choices);
            return choices;
        },
        getServerUpdateData: function() {
            var data = {};
            data[this.fieldName] = this.getValue();
            return data;
        },

        getModelUpdateData: function() {
            return this.getServerUpdateData();
        }
    }, {
        DEFAULT_ACCESSOR_CLASS: 'oroentity/js/tools/entity-select-search-api-accessor',
        processColumnMetadata: function(columnMetadata) {
            var apiSpec = columnMetadata.inline_editing.autocomplete_api_accessor;
            if (!_.isObject(apiSpec)) {
                throw new Error('`autocomplete_api_accessor` is required option');
            }
            if (!apiSpec.class) {
                apiSpec.class = RelatedIdRelationEditorView.DEFAULT_ACCESSOR_CLASS;
            }
            return tools.loadModuleAndReplace(apiSpec, 'class');
        }
    });

    return TagsEditorView;
});
