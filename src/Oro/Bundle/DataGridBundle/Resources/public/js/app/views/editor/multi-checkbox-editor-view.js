/** @lends MultiselectEditorView */
define(function(require) {
    'use strict';

    var MultiCheckboxEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    require('jquery.multiselect');
    require('jquery.multiselect.filter');

    MultiCheckboxEditorView = SelectEditorView.extend(/** @exports MultiSelectEditorView.prototype */{
        className: 'multi-checkbox-editor',
        contextSearch: true,
        template: require('tpl!orodatagrid/templates/multi-checkbox-editor.html'),
        inputSelector: 'select',
        multiselect: null,
        containerSelector: '.inline-editor-wrapper',

        events: {
            'change select': 'onChange',
            'click [data-action]': 'rethrowAction',
            'updatePosition': 'onUpdatePosition'
        },

        listen: {
            'change:visibility': 'onShow'
        },

        onShow: function() {
            this.multiselect = this.$(this.inputSelector).multiselect({
                autoOpen: true,
                classes: this.className,
                header: '',
                height: 'auto',
                position: {
                    my: 'left top-36',
                    at: 'left bottom',
                    of: this.$el
                },
                beforeclose: function() {
                    return false;
                }
            }).multiselectfilter({
                label: '',
                placeholder: '',
                autoReset: true
            });
        },

        onUpdatePosition: function() {
            if (this.multiselect) {
                this.$(this.inputSelector).multiselect('updatePos');
            }
        },

        getModelValue: function() {
            var value = this.model.get(this.column.get('name'));
            if (_.isString(value)) {
                value = JSON.parse(value);
            } else if (_.isArray(value)) {
                value = _.filter(value, function(item) {
                    return item !== '';
                });
            } else if (_.isNull(value) || value === void 0) {
                return [];
            }
            return value;
        },

        getValue: function() {
            var value = this.$('select').val();
            return _.isArray(value) ? value : [];
        },

        getTemplateData: function() {
            var data = MultiCheckboxEditorView.__super__.getTemplateData.call(this);
            _.extend(data, {
                options: this.availableChoices,
                selected: this.getValue()
            });
            return data;
        },

        isChanged: function() {
            var val = this.getValue();
            var old = this.getModelValue();
            if (!_.isArray(old)) {
                old = old === 0 || old ? [old] : [];
            }
            return val.length !== old.length || _.difference(val, old).length > 0;
        },

        dispose: function() {
            this.$(this.inputSelector).multiselect('destroy');
            MultiCheckboxEditorView.__super__.dispose.call(this);
        }
    });

    return MultiCheckboxEditorView;
});
