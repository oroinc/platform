/** @lends RelatedIdRelationEditorView */
define(function(require) {
    'use strict';

    /**
     * Select-like cell content editor. This view is applicable when the cell value contains label (not the value).
     * The editor will use `autocomplete_api_accessor` and `value_field_name`. The server will be updated with the value
     * only.
     *
     * ### Column configuration sample:
     *
     * Please pay attention to the registration of the `value_field_name` in `query` and `properties` sections of the
     * sample yml configuration below
     *
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     source:
     *       query:
     *         select:
     *           # please note that both fields(value and label) are required for valid work
     *           - {entity}.id as {column-name-value}
     *           - {entity}.name as {column-name-label}
     *           # query continues here
     *     columns:
     *       {column-name-label}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/related-id-select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               value_field_name: {column-name-value}
     *           validation_rules:
     *             NotBlank: ~
     *         autocomplete_api_accessor:
     *           # class: oroentity/js/tools/entity-select-search-api-accessor
     *           # entity_select is default search api
     *           # following options are specific only for entity-select-search-api-accessor
     *           # please place here an options corresponding to specified class
     *           entity_name: {corresponding-entity}
     *           field_name: {corresponding-entity-field-name}
     *     properties:
     *       # this line is required to add {column-name-value} to data sent to client
     *       {column-name-value}: ~
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * inline_editing.editor.view_options.value_field_name | Related value field name.
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.input_delay      | Delay before user finished input and request sent to server
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     * inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available choices
     * inline_editing.editor.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Object} options.input_delay - Delay before user finished input and request sent to server
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     * @param {Object} options.value_field_name - Related value field name
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../reference/search-apis.md)
     *
     * @augments [AbstractRelationEditorView](./abstract-relation-editor-view.md)
     * @exports RelatedIdRelationEditorView
     */
    var RelatedIdRelationEditorView;
    var AbstractRelationEditorView = require('./abstract-relation-editor-view');
    var _ = require('underscore');
    require('jquery.select2');

    RelatedIdRelationEditorView =
        AbstractRelationEditorView.extend(/** @exports RelatedIdRelationEditorView.prototype */{
        DEFAULT_ID_PROPERTY: 'id',
        DEFAULT_TEXT_PROPERTY: 'text',
        initialize: function(options) {
            RelatedIdRelationEditorView.__super__.initialize.apply(this, arguments);
            if (options.value_field_name || options.ignore_value_field_name) {
                this.valueFieldName = options.value_field_name;
            } else {
                throw new Error('`value_field_name` option is required');
            }
        },

        getAvailableOptions: function(options) {
            return [];
        },

        getInitialResultItem: function() {
            return {
                id: this.getModelValue(),
                label: this.model.get(this.fieldName)
            };
        },

        filterInitialResultItem: function(choices) {
            choices = _.clone(choices);
            var id = String(this.getModelValue());
            for (var i = 0; i < choices.length; i++) {
                if (String(choices[i].id) === id) {
                    choices.splice(i, 1);
                    break;
                }
            }
            return choices;
        },

        addInitialResultItem: function(choices) {
            choices = this.filterInitialResultItem(choices);
            choices.unshift(this.getInitialResultItem());
            return choices;
        },

        getSelect2Options: function() {
            var _this = this;
            var options = _.omit(RelatedIdRelationEditorView.__super__.getSelect2Options.call(this), 'data');

            return _.extend(options, {
                allowClear: true,
                noFocus: true,
                formatSelection: function(item) {
                    return item.label;
                },
                formatResult: function(item) {
                    return item.label;
                },
                initSelection: function(element, callback) {
                    callback(_this.getInitialResultItem());
                },
                query: function(options) {
                    _this.currentTerm = options.term;
                    if (_this.currentRequest && _this.currentRequest.term !== '' &&
                        _this.currentRequest.state() !== 'resolved') {
                        _this.currentRequest.abort();
                    }
                    var autoCompleteUrlParameters = _.extend(_this.model.toJSON(), {
                        term: options.term,
                        page: options.page,
                        per_page: _this.perPage
                    });
                    if (options.term !== '' &&
                        !_this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                        _this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                    } else {
                        _this.makeRequest(options, autoCompleteUrlParameters);
                    }
                }
            });
        },

        getRawModelValue: function() {
            return this.model.get(this.valueFieldName);
        },

        parseRawValue: function(value) {
            return value || '';
        },

        getChoiceLabel: function() {
            var label = _.result(this.getSelect2Data(), 'label');
            return label !== void 0 ? label : '';
        },

        getServerUpdateData: function() {
            var data = {};
            data[this.valueFieldName] = this.getValue();
            return data;
        },

        getModelUpdateData: function() {
            var data = this.getServerUpdateData();
            data[this.fieldName] = this.getChoiceLabel();
            return data;
        }
    }, {
        DEFAULT_ACCESSOR_CLASS: 'oroentity/js/tools/entity-select-search-api-accessor',
        processMetadata: AbstractRelationEditorView.processMetadata
    });

    return RelatedIdRelationEditorView;
});
