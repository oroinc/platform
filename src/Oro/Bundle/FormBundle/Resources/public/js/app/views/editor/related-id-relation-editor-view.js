define(function(require) {
    'use strict';

    const AbstractRelationEditorView = require('./abstract-relation-editor-view');
    const _ = require('underscore');
    require('jquery.select2');

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
     * datagrids:
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
     *           autocomplete_api_accessor:
     *             # class: oroentity/js/tools/entity-select-search-api-accessor
     *             # entity_select is default search api
     *             # following options are specific only for entity-select-search-api-accessor
     *             # please place here an options corresponding to specified class
     *             entity_name: {corresponding-entity}
     *             field_name: {corresponding-entity-field-name}
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
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
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.autocomplete_api_accessor     | Required. Specifies available choices
     * inline_editing.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Object} options.input_delay - Delay before user finished input and request sent to server
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.className - CSS class name for editor element
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {Object} options.value_field_name - Related value field name
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../reference/search-apis.md)
     *
     * @augments [AbstractRelationEditorView](./abstract-relation-editor-view.md)
     * @exports RelatedIdRelationEditorView
     */
    const RelatedIdRelationEditorView = AbstractRelationEditorView.extend(/** @lends RelatedIdRelationEditorView.prototype */{
        DEFAULT_ID_PROPERTY: 'id',
        DEFAULT_TEXT_PROPERTY: 'text',

        /**
         * @inheritdoc
         */
        constructor: function RelatedIdRelationEditorView(options) {
            RelatedIdRelationEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            RelatedIdRelationEditorView.__super__.initialize.call(this, options);
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
            const id = String(this.getModelValue());
            for (let i = 0; i < choices.length; i++) {
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
            const options = _.omit(RelatedIdRelationEditorView.__super__.getSelect2Options.call(this), 'data');

            return _.extend(options, {
                allowClear: true,
                noFocus: true,
                formatSelection: function(item) {
                    return item.label;
                },
                formatResult: function(item) {
                    return item.label;
                },
                initSelection: (element, callback) => {
                    callback(this.getInitialResultItem());
                },
                query: options => {
                    this.currentTerm = options.term;
                    if (this.currentRequest && this.currentRequest.term !== '' &&
                        this.currentRequest.state() !== 'resolved') {
                        this.currentRequest.abort();
                    }
                    const autoCompleteUrlParameters = _.extend(this.model.toJSON(), {
                        term: options.term,
                        page: options.page,
                        per_page: this.perPage
                    });
                    if (options.term !== '' &&
                        !this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                        this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                    } else {
                        this.makeRequest(options, autoCompleteUrlParameters);
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
            const label = _.result(this.getSelect2Data(), 'label');
            return label !== void 0 ? label : '';
        },

        getServerUpdateData: function() {
            const data = {};
            data[this.valueFieldName] = this.getValue();
            return data;
        },

        getModelUpdateData: function() {
            const data = this.getServerUpdateData();
            data[this.fieldName] = this.getChoiceLabel();
            return data;
        }
    }, {
        DEFAULT_ACCESSOR_CLASS: 'oroentity/js/tools/entity-select-search-api-accessor',
        processMetadata: AbstractRelationEditorView.processMetadata
    });

    return RelatedIdRelationEditorView;
});
