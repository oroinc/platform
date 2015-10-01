define(function(require) {
    'use strict';

    var RelatedIdRelationEditorView;
    var SelectEditorView = require('./select-editor-view');
    var AutocompleteApiAccessor = require('oroui/js/tools/autocomplete-api-accessor');
    var _ = require('underscore');
    require('jquery.select2');

    RelatedIdRelationEditorView = SelectEditorView.extend({
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

            this.textFieldName = options.text_field_name;
            this.autocompleteApiAccessor = new AutocompleteApiAccessor(
                options.column.get('metadata').inline_editing.autocomplete_api_accessor);

            this.idProperty = options.id_property || this.DEFAULT_ID_PROPERTY;
            this.textProperty = options.text_property || this.DEFAULT_TEXT_PROPERTY;
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
                text: this.model.get(this.column.get('name'))
            };
        },

        addInitialOptionToResultIfNeeded: function(results) {
            var idProperty = this.idProperty;
            return _.uniq([this.getInitialResultItem()].concat(results), function(item) {
                return '' + item[idProperty];
            });
        },

        getSelect2Options: function() {
            var _this = this;
            var currentRequest = null;
            var currentTerm = null;

            var makeRequest = function(options) {
                if (options.term === '' && _.isArray(_this.column.emptyQueryChoices)) {
                    options.callback({
                        results: _this.addInitialOptionToResultIfNeeded(_this.column.emptyQueryChoices),
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
                currentRequest.done(function(response) {
                    if (_this.disposed) {
                        return;
                    }
                    _this.availableChoices = _this.formatChoices(response.results);
                    if (options.term === '') {
                        _this.column.emptyQueryChoices = [].concat(_this.availableChoices);
                        _this.column.emptyQueryMoreChoices = response.more;
                        _this.availableChoices = _this.addInitialOptionToResultIfNeeded(_this.availableChoices);
                    }
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

        formatChoices: function(data) {
            var choices = [];
            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                choices.push({
                    id: item[this.idProperty],
                    text: item[this.textProperty] || ' ' // that symbol is &nbsp;
                });
            }
            return choices;
        },

        getModelValue: function() {
            return this.model.get(this.idFieldName) || '';
        },

        getChoiceLabel: function() {
            return this.$('.select2-choice').data('select2-data').text;
        },

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== ('' + this.getModelValue());
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
    });

    return RelatedIdRelationEditorView;
});
