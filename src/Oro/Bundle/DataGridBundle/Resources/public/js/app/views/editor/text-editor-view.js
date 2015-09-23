define(function(require) {
    'use strict';

    var TextEditorView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    TextEditorView = BaseView.extend({
        autoRender: true,
        tagName: 'form',
        template: require('tpl!../../../../templates/text-editor.html'),
        className: 'text-editor',
        inputType: 'text',
        events: {
            'change input[name=value]': 'onChange',
            'keyup input[name=value]': 'onChange',
            'click [data-action]': 'rethrowAction',
            'submit': 'onSave'
        },

        TAB_KEY_CODE: 9,
        ENTER_KEY_CODE: 13,
        ESCAPE_KEY_CODE: 27,

        initialize: function(options) {
            this.options = options;
            this.cell = options.cell;
            this.column = options.column;
            $(document).on('keydown' + this.eventNamespace(), _.bind(this.onKeyDown, this));
            TextEditorView.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off(this.eventNamespace());
            TextEditorView.__super__.dispose.call(this);
        },

        getTemplateData: function() {
            var data = {};
            data.inputType = this.inputType;
            data.data = this.model.toJSON();
            data.column = this.column.toJSON();
            data.value = this.getFormattedValue();
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
                errorPlacement: function(error, element) {
                    error.appendTo(element.closest('.inline-editor-wrapper'));
                },
                rules: {
                    value: this.getValidationRules()
                }
            });
            this.onChange();
        },

        focus: function() {
            this.$('input[name=value]').setCursorToEnd().focus();
        },

        getValidationRules: function() {
            return this.column.get('validationRules') || {};
        },

        getFormattedValue: function() {
            return this.getModelValue();
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return raw ? raw : '';
        },

        getValue: function() {
            return this.$('input[name=value]').val();
        },

        onSave: function(e, postfix) {
            var data = {};
            data[this.column.get('name')] = this.getValue();
            this.trigger('save' + (postfix ? postfix : '') + 'Action', data);
        },

        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.trigger($(e.currentTarget).attr('data-action') + 'Action');
        },

        isChanged: function() {
            return this.getValue() !== this.getModelValue();
        },

        onChange: function() {
            if (!this.isChanged()) {
                this.$('[type=submit]').attr('disabled', 'disabled');
            } else {
                this.$('[type=submit]').removeAttr('disabled');
            }
        },

        onKeyDown: function(e) {
            switch (e.keyCode) {
                case this.TAB_KEY_CODE:
                    var postfix = e.shiftKey ? 'AndEditPrev' : 'AndEditNext';
                    if (this.isChanged()) {
                        if (this.validator.form()) {
                            this.onSave(null, postfix);
                        } else {
                            this.focus();
                        }
                    } else {
                        this.trigger('cancel' + postfix + 'Action');
                    }
                    e.preventDefault();
                    break;
                case this.ENTER_KEY_CODE:
                    if (this.isChanged()) {
                        if (this.validator.form()) {
                            this.onSave(null, false);
                        } else {
                            this.focus();
                        }
                    } else {
                        this.trigger('cancelAction');
                    }
                    e.preventDefault();
                    break;
                case this.ESCAPE_KEY_CODE:
                    this.trigger('cancelAction');
                    e.preventDefault();
                    break;
            }
        }
    });

    return TextEditorView;
});
