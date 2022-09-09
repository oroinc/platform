define(function(require) {
    'use strict';

    const AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const select2autosizer = require('oroui/js/tools/select2-autosizer');

    /**
     * Tags-select content editor. Please note that it requires column data format
     * corresponding to [tags-view](../viewer/tags-view.md).
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Sample configuration
     *       {column-name-1}:
     *         frontend_type: tags
     *         inline_editing:
     *           editor:
     *             # view: orotag/js/app/views/editor/tags-editor-view
     *             view_options:
     *               permissions:
     *                 oro_tag_create: true
     *           save_api_accessor:
     *             # usual save api configuration
     *             route: 'oro_api_post_taggable'
     *             http_method: 'POST'
     *             default_route_parameters:
     *               entity: <entity-url-safe-class-name>
     *             route_parameters_rename_map:
     *               id: entityId
     *           autocomplete_api_accessor:
     *             # usual configuration for tags view
     *             class: 'oroui/js/tools/search-api-accessor'
     *             search_handler_name: 'tags'
     *             label_field_name: 'name'
     *           validation_rules:
     *             NotBlank: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.editor.view_options.permissions      | Permissions
     * inline_editing.editor.view_options.permissions.oro_tag_create | Allows user to create new tag
     * inline_editing.autocomplete_api_accessor            | Required. Specifies available choices
     * inline_editing.autocomplete_api_accessor.class | One of the [list of search APIs](../reference/search-apis.md)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.permissions - Permissions object
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../../../../FormBundle/Resources/doc/reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {Object} options.autocomplete_api_accessor - Autocomplete API specification.
     *                                      Please see [list of search API's](../reference/search-apis.md)
     *
     * @augments [AbstractRelationEditorView](../../../../FormBundle/Resources/doc/editor/abstract-relation-editor-view.md)
     * @exports TagsEditorView
     */
    const TagsEditorView = AbstractRelationEditorView.extend(/** @exports TagsEditorView.prototype */{
        className: 'tags-select-editor',
        DEFAULT_PER_PAGE: 20,

        events: {
            'change input[name=value]': 'autoSize'
        },

        listen: {
            'change:visibility': 'autoSize'
        },

        /**
         * @inheritdoc
         */
        constructor: function TagsEditorView(options) {
            TagsEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            TagsEditorView.__super__.initialize.call(this, options);
            this.listenTo(this.autocompleteApiAccessor, 'cache:clear', this.onCacheClear);
            this.permissions = options.permissions || {};
            this.cell = options.cell;
        },

        getInitialResultItem: function() {
            return this.getModelValue().map(function(item) {
                return {
                    id: item.id,
                    label: item.name,
                    owner: item.owner
                };
            });
        },

        autoSize: function() {
            select2autosizer.applyTo(this.$el, this);
            if (this.cell) {
                this.cell.$el.css('height', this.$el.height());
            }
        },

        getSelect2Options: function() {
            const options = _.omit(TagsEditorView.__super__.getSelect2Options.call(this), 'data');
            this.currentData = null;
            this.firstPageData = {
                results: [],
                more: false,
                isDummy: true
            };
            this.isSelect2Initialized = true;

            return _.extend(options, {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                multiple: true,
                id: 'label',
                formatSelection: function(item) {
                    return _.escape(item.label);
                },
                formatResult: function(item) {
                    return _.escape(item.label) + (item.isNew
                        ? (' <span class="select2__result-entry-info">(' +
                        __('oro.tag.inline_editing.new_tag') + ')</span>')
                        : '');
                },
                formatNoMatches: () => {
                    // no matches appears in following two cases only
                    // we use this message not for its original mission
                    return this.isLoading
                        ? __('oro.tag.inline_editing.loading')
                        : (this.isCurrentTagSelected()
                            ? __('oro.tag.inline_editing.existing_tag')
                            : __('oro.tag.inline_editing.no_matches')
                        );
                },
                initSelection: (element, callback) => {
                    callback(this.getInitialResultItem());
                },
                query: options => {
                    this.currentTerm = options.term;
                    this.currentPage = options.page;
                    this.currentCallback = options.callback;
                    this.isLoading = true;
                    if (options.page === 1) {
                        // immediately show first item
                        this.showResults();
                    }
                    options.callback = this.commonDataCallback.bind(this);
                    if (this.currentRequest && this.currentRequest.term !== '' &&
                        this.currentRequest.state() !== 'resolved') {
                        this.currentRequest.abort();
                    }
                    const autoCompleteUrlParameters = this.buildAutoCompleteUrlParameters();
                    if (options.term !== '' &&
                        !this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                        this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                    } else {
                        this.makeRequest(options, autoCompleteUrlParameters);
                    }
                }
            });
        },

        buildAutoCompleteUrlParameters: function() {
            return _.extend(this.model.toJSON(), {
                term: this.currentTerm,
                page: this.currentPage,
                per_page: this.perPage
            });
        },

        commonDataCallback: function(data) {
            this.currentData = data;
            if (data.page === 1) {
                this.firstPageData = data;
            }
            this.isLoading = false;
            this.showResults();
        },

        onCacheClear: function() {
            this.makeRequest({
                callback: this.commonDataCallback.bind(this),
                term: this.currentTerm,
                page: this.currentPage,
                per_page: this.perPage
            }, this.buildAutoCompleteUrlParameters());
        },

        isCurrentTagSelected: function() {
            const select2Data = this.$('.select2-container').inputWidget('data');
            if (!select2Data) {
                return false;
            }
            for (let i = 0; i < select2Data.length; i++) {
                const tag = select2Data[i];
                if (tag.label === this.currentTerm) {
                    return true;
                }
            }
            return false;
        },

        showResults: function() {
            let data;
            if (this.currentPage === 1) {
                data = $.extend({}, this.firstPageData);
                if (
                    this.permissions.oro_tag_create &&
                    this.isValidTerm(this.currentTerm) &&
                    this.firstPageData.term === this.currentTerm &&
                    -1 === this.indexOfTermInResults(this.currentTerm, data.results)
                ) {
                    data.results.unshift({
                        id: this.currentTerm,
                        label: this.currentTerm,
                        isNew: true,
                        owner: true
                    });
                } else if (this.firstPageData.isDummy) {
                    // do not update list until choices will be loaded
                    return;
                }
                data.results.sort(this.tagSortCallback.bind(this));
            } else {
                data = $.extend({}, this.currentData);
                data.results = this.filterTermFromResults(this.currentTerm, data.results);
            }
            this.currentCallback(data);
        },

        indexOfTermInResults: function(term, results) {
            for (let i = 0; i < results.length; i++) {
                const result = results[i];
                if (result.label === term) {
                    return i;
                }
            }
            return -1;
        },

        filterTermFromResults: function(term, results) {
            results = _.clone(results);
            for (let i = 0; i < results.length; i++) {
                const result = results[i];
                if (result.label === term) {
                    results.splice(i, 1);
                    break;
                }
            }
            return results;
        },

        tagSortCallback: function(a, b) {
            const firstCondition = this.getTermSimilarity(a.label) - this.getTermSimilarity(b.label);
            return firstCondition !== 0 ? firstCondition : a.label.length - b.label.length;
        },

        getTermSimilarity: function(term) {
            const lowerCaseTerm = term.toLowerCase();
            const index = lowerCaseTerm.indexOf(this.currentTerm.toLowerCase());
            if (index === -1) {
                return 1000;
            }
            return index;
        },

        isValidTerm: function(term) {
            return _.isString(term) && term.length > 0;
        },

        parseRawValue: function(value) {
            return value || [];
        },

        formatRawValue: function(value) {
            return this.parseRawValue(value).map(function(item) {
                return item.id;
            }).join(',');
        },

        isChanged: function() {
            if (!this.isSelect2Initialized) {
                return false;
            }
            const stringValue = _.toArray(this.getValue().sort().map(function(item) {
                return item.label;
            })).join('☕');
            const stringModelValue = _.toArray(this.getModelValue().sort().map(function(item) {
                return item.name;
            })).join('☕');
            return stringValue !== stringModelValue;
        },

        getValue: function() {
            return this.$('.select2-container').inputWidget('data');
        },

        getServerUpdateData: function() {
            const data = {};
            data[this.fieldName] = this.getValue().map(function(item) {
                return {
                    name: item.label
                };
            });
            return data;
        },

        getModelUpdateData: function() {
            const data = {};
            data[this.fieldName] = this.getValue().map(function(item) {
                return {
                    id: item.id,
                    name: item.label,
                    owner: item.owner === void 0 ? true : item.owner
                };
            });
            return data;
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            if (this.cell) {
                this.cell.$el.css('height', '');
            }

            TagsEditorView.__super__.dispose.call(this);
        }
    }, {
        DEFAULT_ACCESSOR_CLASS: 'oroentity/js/tools/entity-select-search-api-accessor',
        processMetadata: AbstractRelationEditorView.processMetadata,
        processSavePromise: function(promise, metadata) {
            promise.done(function() {
                metadata.inline_editing.autocomplete_api_accessor.instance.clearCache();
            });
            return promise;
        }
    });

    return TagsEditorView;
});
