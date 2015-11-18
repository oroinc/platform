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
     *             view: orodatagrid/js/app/views/editor/related-id-select-editor-view
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
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.input_delay      | Delay before user finished input and request sent to server
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     * inline_editing.editor.autocomplete_api_accessor     | Required. Specifies available choices
     * inline_editing.editor.autocomplete_api_accessor.class | One of the [list of search APIs](../search-apis.md)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Object} options.input_delay - Delay before user finished input and request sent to server
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     * @param {Object} options.value_field_name - Related value field name
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../search-apis.md)
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports RelatedIdRelationEditorView
     */
    var RelatedIdRelationEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    require('jquery.select2');

    RelatedIdRelationEditorView = SelectEditorView.extend(/** @exports RelatedIdRelationEditorView.prototype */{
        DEFAULT_ID_PROPERTY: 'id',
        DEFAULT_TEXT_PROPERTY: 'text',
        DEFAULT_PER_PAGE: 20,
        input_delay: 250,
        initialize: function(options) {
            if (options.value_field_name || options.ignore_value_field_name) {
                this.valueFieldName = options.value_field_name;
            } else {
                throw new Error('`value_field_name` option is required');
            }

            var apiSpec = options.column.get('metadata').inline_editing.autocomplete_api_accessor;
            var AutocompleteApiAccessor = apiSpec['class'];
            this.autocompleteApiAccessor = new AutocompleteApiAccessor(apiSpec);
            this.perPage = options.per_page || this.DEFAULT_PER_PAGE;
            if (options.input_delay) {
                this.input_delay = options.input_delay;
            }

            RelatedIdRelationEditorView.__super__.initialize.apply(this, arguments);
        },

        getAvailableOptions: function(options) {
            return [];
        },

        getInitialResultItem: function() {
            return {
                id: this.getModelValue(),
                label: this.model.get(this.column.get('name'))
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
            var currentRequest = null;
            var currentTerm = null;

            var makeRequest = function(options) {
                if (_this.disposed) {
                    return;
                }
                if (options.term === '' && options.page === 1 && _this.column.emptyQueryRequest) {
                    currentRequest = _this.column.emptyQueryRequest;
                } else {
                    currentRequest = _this.autocompleteApiAccessor.send(_.extend(_this.model.toJSON(), {
                        term: options.term,
                        page: options.page,
                        per_page: _this.perPage
                    }));
                    currentRequest.term = options.term;
                    if (options.term === '' && options.page === 1) {
                        _this.column.emptyQueryRequest = currentRequest;
                    }
                }
                currentRequest.done(function(response) {
                    if (_this.disposed) {
                        return;
                    }
                    if (currentTerm === options.term) {
                        if (options.term === '' && options.page === 1) {
                            _this.availableChoices = _this.addInitialResultItem(response.results);
                        } else if (options.term === '' && options.page !== 1) {
                            _this.availableChoices = _this.filterInitialResultItem(response.results);
                        } else {
                            _this.availableChoices = _.clone(response.results);
                        }
                        options.callback({
                            results: _this.availableChoices,
                            more: response.more
                        });
                    }
                });
                currentRequest.fail(function(ajax) {
                    if (ajax.statusText !== 'abort') {
                        if (!_this.disposed) {
                            options.callback({
                                results: [],
                                more: false
                            });
                        }
                        throw Error('Cannot load choices for autocomplete');
                    }
                });
            };

            var debouncedMakeRequest = _.debounce(makeRequest, this.input_delay);
            return {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                openOnEnter: false,
                selectOnBlur: false,
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
                ajax: {
                    quietMillis: 250
                },
                query: function(options) {
                    if (currentRequest && currentRequest.term !== '') {
                        currentRequest.abort();
                    }
                    currentTerm = options.term;
                    if (options.term !== '') {
                        debouncedMakeRequest(options);
                    } else {
                        makeRequest(options);
                    }
                }
            };
        },

        getModelValue: function() {
            return this.model.get(this.valueFieldName) || '';
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
            data[this.column.get('name')] = this.getChoiceLabel();
            return data;
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

    return RelatedIdRelationEditorView;
});
