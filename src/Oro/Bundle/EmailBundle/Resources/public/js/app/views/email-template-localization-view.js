define([
    'jquery',
    'underscore',
    'tinymce/tinymce',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view'
], function($, _, tinyMCE, mediator, BaseView) {
    'use strict';

    const EmailTemplateLocalizationView = BaseView.extend({
        options: {
            localization: {
                id: null,
                parentId: null
            },

            fields: {
                subject: {
                    input: 'input[data-name="field__subject"]',
                    fallback: 'input[data-name="field__subject-fallback"]'
                },
                content: {
                    input: 'textarea[data-name="field__content"]',
                    fallback: 'input[data-name="field__content-fallback"]'
                }
            }
        },

        fields: null,

        /**
         * @inheritdoc
         */
        constructor: function EmailTemplateLocalizationView(options) {
            EmailTemplateLocalizationView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on(
                this.eventToParent('localized-template:field-change'),
                _.bind(this.onParentFieldChange, this),
                this
            );

            mediator.on(
                this.eventToChildren('localized-template:field-fallback'),
                _.bind(this.onChangeInput, this),
                this
            );

            this.fields = {};
            for (const fieldName in this.options.fields) {
                if (this.options.fields.hasOwnProperty(fieldName)) {
                    const field = {
                        $input: this.$el.find(this.options.fields[fieldName].input),
                        $fallback: this.$el.find(this.options.fields[fieldName].fallback)
                    };

                    if (field.$fallback.length) {
                        field.$fallback.on(
                            'change' + this.eventNamespace(),
                            _.bind(this.processFallback, this, fieldName)
                        );
                    }

                    if (field.$input.length) {
                        field.$input.on(
                            'change' + this.eventNamespace(),
                            _.bind(this.onChangeInput, this, fieldName)
                        );

                        field.editor = tinyMCE.get(field.$input.attr('id'));

                        if (field.editor) {
                            field.editor.on(
                                'Change',
                                _.bind(this.onChangeInput, this, fieldName)
                            );
                        }
                    }

                    this.fields[fieldName] = field;

                    this.processFallback(fieldName);
                }
            }

            EmailTemplateLocalizationView.__super__.initialize.call(this, options);
        },

        processFallback: function(fieldName) {
            const field = this.fields[fieldName];

            if (this.isFieldFallback(fieldName)) {
                field.$input.attr('disabled', 'disabled');

                if (field.editor) {
                    field.editor.setMode('readonly');
                    $(field.editor.editorContainer).addClass('disabled');
                    $(field.editor.editorContainer).append('<div class="disabled-overlay"></div>');
                }

                mediator.trigger(this.eventToParent('localized-template:field-fallback'), fieldName);
            } else {
                field.$input.removeAttr('disabled');

                if (field.editor) {
                    field.editor.setMode('design');
                    $(field.editor.editorContainer).removeClass('disabled');
                    $(field.editor.editorContainer).children('.disabled-overlay').remove();
                }

                this.onChangeInput(fieldName);
            }
        },

        onChangeInput: function(fieldName) {
            mediator.trigger(
                this.eventToChildren('localized-template:field-change'),
                fieldName,
                this.fields[fieldName].$input.val()
            );
        },

        onParentFieldChange: function(fieldName, content) {
            if (this.isFieldFallback(fieldName)) {
                this.fields[fieldName].$input.val(content);

                if (this.fields[fieldName].editor) {
                    this.fields[fieldName].editor.setContent(content);
                }

                this.fields[fieldName].$input.change();
            }
        },

        isFieldFallback: function(fieldName) {
            return this.fields[fieldName].$fallback.length && !!this.fields[fieldName].$fallback.is(':checked');
        },

        eventToParent: function(eventName) {
            return eventName + ':' + (this.options.localization.parentId || 0);
        },

        eventToChildren: function(eventName) {
            return eventName + ':' + (this.options.localization.id || 0);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            for (const fieldName in this.fields) {
                if (this.fields.hasOwnProperty(fieldName)) {
                    this.fields[fieldName].$input.off(this.eventNamespace());
                    this.fields[fieldName].$fallback.off(this.eventNamespace());
                }
            }

            mediator.off(null, null, this);
            EmailTemplateLocalizationView.__super__.dispose.call(this);
        }
    });

    return EmailTemplateLocalizationView;
});
