/** @lends RelatedIdRelationEditorView */
define(function(require) {
    'use strict';

    /**
     * Text cell content editor
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - current row model
     * @param {Backgrid.Cell} options.cell - current datagrid cell
     * @param {Backgrid.Column} options.column - current datagrid column
     * @param {string} options.placeholder - placeholder for empty element
     * @param {Object} options.validationRules - validation rules in form applicable to jQuery.validate
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
            if (options.id_field_name) {
                this.idFieldName = options.id_field_name;
            } else {
                throw new Error('`id_field_name` option is required');
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

        getSelect2Options: function() {
            var _this = this;
            var currentRequest = null;
            var currentTerm = null;

            var makeRequest = function(options) {
                var column;
                if (options.term === '' && options.page === 1 && _.isArray(_this.column.emptyQueryChoices)) {
                    options.callback({
                        results: _this.column.emptyQueryChoices,
                        more: _this.column.emptyQueryMoreChoices
                    });
                    return;
                }
                currentRequest = _this.autocompleteApiAccessor.send(_.extend(_this.model.toJSON(), {
                    term: options.term,
                    page: options.page,
                    per_page: _this.perPage
                }));
                currentRequest.term = options.term;
                if (options.term === '') {
                    column = _this.column;
                }
                currentRequest.done(function(response) {
                    // save empty query results even if view is disposed
                    if (options.term === '') {
                        column.emptyQueryChoices = response.results;
                        column.emptyQueryMoreChoices = response.more;
                    }
                    if (_this.disposed) {
                        return;
                    }
                    _this.availableChoices = response.results;
                    if (currentTerm === options.term) {
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
            return this.model.get(this.idFieldName) || '';
        },

        getChoiceLabel: function() {
            return this.$('.select2-choice').data('select2-data').label;
        },

        getServerUpdateData: function() {
            var data = {};
            data[this.idFieldName] = this.getValue();
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
