/** @lends MultiRelationEditorView */
define(function(require) {
    'use strict';

    /**
     * Multi-relation content editor. Please note that it requires column data format
     * corresponding to multi-relation-cell.
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
     *             view: oroform/js/app/views/editor/multi-relation-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               maximumSelectionLength: 3
     *           validation_rules:
     *             NotBlank: true
     *         autocomplete_api_accessor:
     *           # class: oroentity/js/tools/entity-select-search-api-accessor
     *           # entity_select is default search api
     *           # following options are specific only for entity-select-search-api-accessor
     *           # please place here an options corresponding to specified class
     *           entity_name: {corresponding-entity}
     *           field_name: {corresponding-entity-field-name}
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
     * inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available choices
     * inline_editing.editor.autocomplete_api_accessor.class | One of the [list of search APIs](../search-apis.md)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../search-apis.md)
     *
     * @augments [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)
     * @exports MultiRelationEditorView
     */
    var MultiRelationEditorView;
    var RelatedIdRelationEditorView = require('./related-id-relation-editor-view');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var select2autosizer = require('../../../utils/select2-autosizer');

    MultiRelationEditorView = RelatedIdRelationEditorView.extend(/** @exports MultiRelationEditorView.prototype */{
        className: 'multi-relation-editor',
        initialize: function(options) {
            options.ignore_value_field_name = true;
            this.maximumSelectionLength = options.maximumSelectionLength;
            MultiRelationEditorView.__super__.initialize.apply(this, arguments);
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

        addInitialResultItem: function(choices) {
            return this.filterInitialResultItem(choices);
        },

        getSelect2Options: function() {
            var options = MultiRelationEditorView.__super__.getSelect2Options.apply(this, arguments);
            options.multiple = true;
            options.maximumSelectionLength = this.maximumSelectionLength;
            return options;
        },

        getModelValue: function() {
            var value = this.model.get(this.column.get('name'));
            if (_.isString(value)) {
                value = JSON.parse(value);
            }
            if (value === null || value === void 0) {
                // assume empty
                value = {
                    count: 0,
                    data: []
                };
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
            return {
                data: ids.map(function(id) {
                    return {
                        id: id
                    };
                }),
                count: ids.length
            };
        },

        getServerUpdateData: function() {
            var data = {};
            data[this.column.get('name')] = this.getValue();
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

    return MultiRelationEditorView;
});
