/** @lends MultiselectEditorView */
define(function(require) {
    'use strict';

    /**
     * @TODO FIX DOC
     *
     * @augments [RelatedIdRelationEditorView](./related-id-relation-editor-view.md)
     * @exports MultiSelectEditorView
     */
    var MultiSelectEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var select2autosizer = require('../../../utils/select2-autosizer');

    MultiSelectEditorView = SelectEditorView.extend(/** @exports MultiSelectEditorView.prototype */{
        initialize: function(options) {
            options.ignore_value_field_name = true;
            MultiSelectEditorView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'change input[name=value]': 'autoSize'
        },

        listen: {
            'change:visibility': 'autoSize'
        },

        autoSize: function() {
            select2autosizer.applyTo(this.$el);
        },

        getSelect2Options: function() {
            var options = MultiSelectEditorView.__super__.getSelect2Options.apply(this, arguments);
            options.multiple = true;
            return options;
        },

        getFormattedValue: function() {
            return this.getModelValue().join(',');
        },

        getModelValue: function() {
            var value = this.model.get(this.column.get('name'));
            if (_.isString(value)) {
                value = JSON.parse(value);
            }
            if (_.isNull(value) || value === void 0) {
                return [];
            }
            return value;
        },

        getValue: function() {
            var select2Value = this.$('input[name=value]').val();
            var ids;
            if (select2Value !== '') {
                ids = select2Value.split(',').map(function(id) {return parseInt(id);});
            } else {
                ids = [];
            }
            return ids;
        }
    });

    return MultiSelectEditorView;
});
