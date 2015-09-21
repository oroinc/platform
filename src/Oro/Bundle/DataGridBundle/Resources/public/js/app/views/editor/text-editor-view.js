define(function(require) {
    'use strict';

    var TextEditorView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    TextEditorView = BaseView.extend({
        autoRender: true,
        template: require('tpl!../../../../templates/text-editor.html'),
        events: {
            'change input[name=value]': 'onChange',
            'keyup input[name=value]': 'onChange',
            'click [data-action]': 'rethrowAction'
        },

        /**
         * @inheritDoc
         */
        render: function() {
            TextEditorView.__super__.render.call(this);
            this.validator = this.$el.validate({
                submitHandler: _.bind(this.onSave, this),
                rules: {
                    value: this.model.get('validationRules') || {}
                }
            });
            this.onChange();
        },

        focus: function() {
            this.$('input[name=value]').focus();
        },

        updateModel: function() {
            this.model.set({
                value: this.$('input[name=value]').val(),
                valid: this.$('input[name=value]').valid()
            });
        },

        onSave: function() {
            this.updateModel();
            this.trigger('saveAction');
        },

        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.updateModel();
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
