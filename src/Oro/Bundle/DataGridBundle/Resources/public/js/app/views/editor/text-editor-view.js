define(function(require) {
    'use strict';

    var TextEditorView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    TextEditorView = BaseView.extend({
        autoRender: true,
        template: require('tpl!../../../../templates/text-editor.html'),
        className: 'text-editor',
        events: {
            'change input[name=value]': 'onChange',
            'keyup input[name=value]': 'onChange',
            'click [data-action]': 'rethrowAction'
        },

        initialize: function(options) {
            this.options = options;
            this.cell = options.cell;
            this.column = options.column;
            TextEditorView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = {};
            data.data = this.model.toJSON();
            data.column = this.column.toJSON();
            data.value = this.getModelValue();
            return data;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            TextEditorView.__super__.render.call(this);
            this.$el.addClass(_.result(this, 'className'));
            this.validator = this.$el.validate({
                submitHandler: _.bind(this.onSave, this),
                rules: {
                    value: this.getValidationRules()
                }
            });
            this.onChange();
        },

        focus: function() {
            this.$('input[name=value]').focus();
        },

        getValidationRules: function() {
            return this.column.get('validationRules') || {};
        },

        getModelValue: function() {
            return this.model.get(this.column.get('name'));
        },

        getValue: function() {
            var data = {};
            data[this.column.get('name')] = this.$('input[name=value]').val();
            return data;
        },

        onSave: function() {
            this.trigger('saveAction', this.getValue());
        },

        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.trigger($(e.currentTarget).attr('data-action') + 'Action');
        },

        onChange: function() {
            var currentValue = this.$('input[name=value]').val();
            var disableSubmit = currentValue === (this.model.get('value') ? this.model.get('value') : '');
            if (disableSubmit) {
                this.$('[type=submit]').attr('disabled', 'disabled');
            } else {
                this.$('[type=submit]').removeAttr('disabled');
            }
        }
    });

    return TextEditorView;
});
