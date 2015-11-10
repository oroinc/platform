/** @lends MultiselectEditorView */
define(function(require) {
    'use strict';

    /**
     * @TODO FIX DOC
     *
     * @augments [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)
     * @exports MultiselectEditorView
     */
    var MultiselectEditorView;
    var RelatedIdRelationEditorView = require('./related-id-relation-editor-view');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');

    MultiselectEditorView = RelatedIdRelationEditorView.extend(/** @exports MultiselectEditorView.prototype */{
        initialize: function(options) {
            options.ignore_value_field_name = true;
            MultiselectEditorView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'change input[name=value]': 'autoSize'
        },

        getAvailableOptions: function(options) {
            return [];
        },

        render: function() {
            MultiselectEditorView.__super__.render.call(this);
            this.autoSize();
        },

        autoSize: function() {
            var widthPieces = Math.ceil(Math.pow(this.getValue().data.length, 0.6));
            var widthes = this.$('.select2-search-choice').map(function(i, item) {return item.clientWidth;});
            widthes.sort();
            var percentile90 = widthes[Math.floor(widthes.length * 0.9)];
            this.$('.select2-choices').width((percentile90 + this.SELECTED_ITEMS_H_MARGIN) * widthPieces);
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
            var options = MultiselectEditorView.__super__.getSelect2Options.apply(this, arguments);
            options.multiple = true;
            return options;
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            try {
                return JSON.parse(raw);
            } catch (e) {
                return {
                    count: 0,
                    data: []
                };
            }
        },

        getValue: function() {
            var select2Value = this.$('input[name=value]').val();
            var ids;
            if (select2Value !== '') {
                ids = select2Value.split(',');
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

    return MultiselectEditorView;
});
