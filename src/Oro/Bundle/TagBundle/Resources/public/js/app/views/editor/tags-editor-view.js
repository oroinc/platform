/** @lends TagsEditorView */
define(function(require) {
    'use strict';

    /**
     * Tags-select content editor. Please note that it requires column data format
     * corresponding to [tags-view](../viewer/tags-view.md).
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
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
     *                 permissions:
     *                     oro_tag_create: true
     *                     oro_tag_unassign_global: true
     *           save_api_accessor:
     *               # usual save api configuration
     *               route: 'oro_api_post_taggable'
     *               http_method: 'POST'
     *               default_route_parameters:
     *                   entity: <entity-url-safe-class-name>
     *               route_parameters_rename_map:
     *                   id: entityId
     *           autocomplete_api_accessor:
     *               # usual configuration for tags view
     *               class: 'oroui/js/tools/search-api-accessor'
     *               search_handler_name: 'tags'
     *               label_field_name: 'name'
     *           validation_rules:
     *             NotBlank: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     * inline_editing.editor.permissions      | Permissions
     * inline_editing.editor.permissions.oro_tag_create | Allows user to create new tag
     * inline_editing.editor.permissions.oro_tag_unassign_global | Allows user to edit tags assigned by all users
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.permissions - Permissions object
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments [AbstractRelationEditorView](../../../../FormBundle/Resources/doc/editor/abstract-relation-editor-view.md)
     * @exports TagsEditorView
     */
    var TagsEditorView;
    var AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var select2autosizer = require('oroui/js/tools/select2-autosizer');

    TagsEditorView = AbstractRelationEditorView.extend(/** @exports TagsEditorView.prototype */{
        className: 'tags-select-editor',
        DEFAULT_PER_PAGE: 20,

        events: {
            'change input[name=value]': 'autoSize'
        },

        listen: {
            'change:visibility': 'autoSize'
        },

        initialize: function(options) {
            TagsEditorView.__super__.initialize.apply(this, arguments);
            this.listenTo(this.autocompleteApiAccessor, 'cache:clear', this.onCacheClear);
            this.permissions = options.permissions || {};
        },

        getInitialResultItem: function() {
            var _this = this;
            return this.getModelValue().map(function(item) {
                return _this.applyPermissionsToTag({
                    id: item.id,
                    label: item.name,
                    owner: item.owner
                });
            });
        },

        applyPermissionsToTag: function(tag) {
            var isOwner = tag.owner === void 0 ? true : tag.owner;
            tag.locked = this.permissions.oro_tag_unassign_global ? false : !isOwner;
            return tag;
        },

        autoSize: function() {
            select2autosizer.applyTo(this.$el, this);
        },

        getSelect2Options: function() {
            var _this = this;
            var options = _.omit(TagsEditorView.__super__.getSelect2Options.apply(this, arguments), 'data');
            _this.currentData = null;
            _this.firstPageData = {
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
                    return item.label;
                },
                formatResult: function(item) {
                    return item.label + (item.isNew ?
                            (' <span class="select2__result-entry-info">(' +
                            __('oro.tag.inline_editing.new_tag') + ')</span>') :
                            '');
                },
                formatNoMatches: function() {
                    // no matches appears in following two cases only
                    // we use this message not for its original mission
                    return _this.isLoading ?
                        __('oro.tag.inline_editing.loading') :
                        (_this.isCurrentTagSelected() ?
                                __('oro.tag.inline_editing.existing_tag') :
                                __('oro.tag.inline_editing.no_matches')
                        );
                },
                initSelection: function(element, callback) {
                    callback(_this.getInitialResultItem());
                },
                query: function(options) {
                    _this.currentTerm = options.term;
                    _this.currentPage = options.page;
                    _this.currentCallback = options.callback;
                    _this.isLoading = true;
                    if (options.page === 1) {
                        // immediately show first item
                        _this.showResults();
                    }
                    options.callback = _.bind(_this.commonDataCallback, _this);
                    if (_this.currentRequest && _this.currentRequest.term !== '' &&
                        _this.currentRequest.state() !== 'resolved') {
                        _this.currentRequest.abort();
                    }
                    var autoCompleteUrlParameters = _this.buildAutoCompleteUrlParameters();
                    if (options.term !== '' &&
                        !_this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                        _this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                    } else {
                        _this.makeRequest(options, autoCompleteUrlParameters);
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
                callback: _.bind(this.commonDataCallback, this),
                term: this.currentTerm,
                page: this.currentPage,
                per_page: this.perPage
            }, this.buildAutoCompleteUrlParameters());
        },

        isCurrentTagSelected: function() {
            var select2Data = this.$('.select2-container').inputWidget('valData');
            for (var i = 0; i < select2Data.length; i++) {
                var tag = select2Data[i];
                if (tag.label === this.currentTerm) {
                    return true;
                }
            }
            return false;
        },

        showResults: function() {
            var data;
            if (this.currentPage === 1) {
                data = $.extend({}, this.firstPageData);
                if (this.permissions.oro_tag_create && this.isValidTerm(this.currentTerm)) {
                    if (this.firstPageData.term === this.currentTerm &&
                        -1 === this.indexOfTermInResults(this.currentTerm, data.results)) {
                        data.results.unshift({
                            id: this.currentTerm,
                            label: this.currentTerm,
                            isNew: true,
                            owner: true
                        });
                    }
                } else {
                    if (this.firstPageData.isDummy) {
                        // do not update list until choices will be loaded
                        return;
                    }
                }
                data.results.sort(_.bind(this.tagSortCallback, this));
            } else {
                data = $.extend({}, this.currentData);
                data.results = this.filterTermFromResults(this.currentTerm, data.results);
            }
            this.currentCallback(data);
        },

        indexOfTermInResults: function(term, results) {
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                if (result.label === term) {
                    return i;
                }
            }
            return -1;
        },

        filterTermFromResults: function(term, results) {
            results = _.clone(results);
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                if (result.label === term) {
                    results.splice(i, 1);
                    break;
                }
            }
            return results;
        },

        tagSortCallback: function(a, b) {
            var firstCondition = this.getTermSimilarity(a.label) - this.getTermSimilarity(b.label);
            return firstCondition !== 0 ? firstCondition : a.label.length - b.label.length;
        },

        getTermSimilarity: function(term) {
            var lowerCaseTerm = term.toLowerCase();
            var index = lowerCaseTerm.indexOf(this.currentTerm.toLowerCase());
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
            var stringValue = _.toArray(this.getValue().sort().map(function(item) {
                return item.label;
            })).join('☕');
            var stringModelValue = _.toArray(this.getModelValue().sort().map(function(item) {
                return item.name;
            })).join('☕');
            return stringValue !== stringModelValue;
        },

        getValue: function() {
            return this.$('.select2-container').inputWidget('valData');
        },

        getServerUpdateData: function() {
            var data = {};
            data[this.fieldName] = this.getValue().map(function(item) {
                return {
                    name: item.label
                };
            });
            return data;
        },

        getModelUpdateData: function() {
            var data = {};
            data[this.fieldName] = this.getValue().map(function(item) {
                return {
                    id: item.id,
                    name: item.label,
                    owner: item.owner === void 0 ? true : item.owner
                };
            });
            return data;
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
