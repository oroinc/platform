/** @lends AbstractRelationEditorView */
define(function(require) {
    'use strict';

    /**
     * Abstract select editor which requests data from server.
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.input_delay      | Delay before user finished input and request sent to server
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
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../reference/search-apis.md)
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports AbstractRelationEditorView
     */
    var AbstractRelationEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    require('jquery.select2');

    AbstractRelationEditorView = SelectEditorView.extend(/** @exports AbstractRelationEditorView.prototype */{
        input_delay: 250,
        currentTerm: '',
        DEFAULT_PER_PAGE: 20,
        initialize: function(options) {
            AbstractRelationEditorView.__super__.initialize.apply(this, arguments);
            this.autocompleteApiAccessor = options.autocomplete_api_accessor.instance;
            this.perPage = options.per_page || this.DEFAULT_PER_PAGE;
            if (options.input_delay) {
                this.input_delay = options.input_delay;
            }
            this.debouncedMakeRequest = _.debounce(_.bind(this.makeRequest, this), this.input_delay);
        },

        getAvailableOptions: function(options) {
            return [];
        },

        addInitialResultItem: function(results) {
            return _.clone(results);
        },

        filterInitialResultItem: function(results) {
            return _.clone(results);
        },

        makeRequest: function(options, autoCompleteUrlParameters) {
            var _this = this;
            if (this.disposed) {
                return;
            }
            this.currentRequest = this.autocompleteApiAccessor.send(autoCompleteUrlParameters);
            this.currentRequest.done(function(response) {
                if (_this.disposed) {
                    return;
                }
                if (_this.currentTerm === options.term) {
                    if (options.term === '' && options.page === 1) {
                        _this.availableChoices = _this.addInitialResultItem(response.results);
                    } else if (options.term === '' && options.page !== 1) {
                        _this.availableChoices = _this.filterInitialResultItem(response.results);
                    } else {
                        _this.availableChoices = _.clone(response.results);
                    }
                    options.callback({
                        results: _this.availableChoices,
                        page: autoCompleteUrlParameters.page,
                        term: autoCompleteUrlParameters.term,
                        more: response.more
                    });
                }
            });
            this.currentRequest.fail(function(ajax) {
                if (ajax.statusText !== 'abort') {
                    if (!_this.disposed) {
                        options.callback({
                            results: [],
                            page: autoCompleteUrlParameters.page,
                            term: autoCompleteUrlParameters.term,
                            error: true,
                            more: false
                        });
                    }
                    throw Error('Cannot load choices for autocomplete');
                }
            });
        }
    }, {
        DEFAULT_ACCESSOR_CLASS: 'oroentity/js/tools/entity-select-search-api-accessor',
        processMetadata: function(columnMetadata) {
            var apiSpec = columnMetadata.inline_editing.autocomplete_api_accessor;
            if (!_.isObject(apiSpec)) {
                throw new Error('`autocomplete_api_accessor` is required option');
            }
            if (!apiSpec.class) {
                apiSpec.class = AbstractRelationEditorView.DEFAULT_ACCESSOR_CLASS;
            }
            return tools.loadModuleAndReplace(apiSpec, 'class').then(function() {
                if (!apiSpec.clientCache) {
                    apiSpec.clientCache = {
                        enable: true
                    };
                }
                var AutocompleteApiAccessor = apiSpec['class'];
                apiSpec.instance = new AutocompleteApiAccessor(apiSpec);
                if (!columnMetadata.inline_editing.editor.view_options) {
                    columnMetadata.inline_editing.editor.view_options = {};
                }
                columnMetadata.inline_editing.editor.view_options.autocomplete_api_accessor = apiSpec;
            });
        }
    });

    return AbstractRelationEditorView;
});
