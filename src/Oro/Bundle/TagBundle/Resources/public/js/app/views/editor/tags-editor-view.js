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
     * @augments [AbstractRelationEditorView](./abstract-relation-editor-view.md)
     * @exports TagsEditorView
     */
    var TagsEditorView;
    var AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    var _ = require('underscore');

    TagsEditorView = AbstractRelationEditorView.extend(/** @exports TagsEditorView.prototype */{
        className: 'tags-select-editor',
        DEFAULT_PER_PAGE: 20,
        initialize: function(options) {
            TagsEditorView.__super__.initialize.apply(this, arguments);
        },

        getInitialResultItem: function() {
            return this.getModelValue().map(function(item) {
                return {
                    id: item.id,
                    label: item.name
                };
            });
        },

        getSelect2Options: function() {
            var _this = this;
            return {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                openOnEnter: false,
                selectOnBlur: false,
                multiple: true,
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
                    _this.currentData = null;
                    _this.currentCallback = options.callback;
                    options.callback = function(data) {
                        _this.currentData = data;
                        _this.showResults();
                    };
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
            };
        },

        showResults: function() {
            this.currentCallback(this.currentData);
        },

        getModelValue: function() {
            return this.model.get(this.fieldName) || [];
        },

        getFormattedValue: function() {
            return this.getModelValue().map(function(item) {
                return item.id;
            }).join(',');
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
        processMetadata: AbstractRelationEditorView.processMetadata
    });

    return TagsEditorView;
});
