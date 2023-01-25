define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tinyMCE = require('tinymce/tinymce');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

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
                this.onParentFieldChange.bind(this),
                this
            );

            mediator.on(
                this.eventToChildren('localized-template:field-fallback'),
                this.onChangeInput.bind(this),
                this
            );

            this.fields = {};
            for (const fieldName in this.options.fields) {
                if (this.options.fields.hasOwnProperty(fieldName)) {
                    const field = {
                        $input: this.$el.find(this.options.fields[fieldName].input),
                        $fallback: this.$el.find(this.options.fields[fieldName].fallback)
                    };

                    this.fields[fieldName] = field;

                    if (field.$fallback.length) {
                        field.$fallback.on(
                            'change' + this.eventNamespace(),
                            this.processFallback.bind(this, fieldName)
                        );
                    }

                    if (field.$input.length) {
                        field.$input.on(
                            'change' + this.eventNamespace(),
                            this.onChangeInput.bind(this, fieldName)
                        );
                        // Re-rendering process when enable or disable tinyMCE
                        field.$input.on('rendered', () => {
                            this.operateWithEditor(fieldName, editor => {
                                editor.on('Change', this.onChangeInput.bind(this, fieldName));
                            });
                            this.processFallback(fieldName);
                        });

                        this.operateWithEditor(fieldName, editor => {
                            editor.on('Change', this.onChangeInput.bind(this, fieldName));
                        });
                    }

                    this.processFallback(fieldName);
                }
            }

            EmailTemplateLocalizationView.__super__.initialize.call(this, options);
        },

        /**
         * Do any operations with tinyMCE editor if it is exists
         * @param fieldName
         * @param callback
         * @return {Object|null}
         */
        operateWithEditor(fieldName, callback) {
            const editor = tinyMCE.get(this.fields[fieldName].$input.attr('id'));

            if (editor && typeof callback === 'function') {
                callback(editor);
            }

            return editor;
        },

        processFallback: function(fieldName) {
            const field = this.fields[fieldName];

            if (this.isFieldFallback(fieldName)) {
                field.$input.attr('disabled', 'disabled');

                this.operateWithEditor(fieldName, editor => {
                    editor.mode.set('readonly');
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                    $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                });

                mediator.trigger(this.eventToParent('localized-template:field-fallback'), fieldName);
            } else {
                field.$input.removeAttr('disabled');

                this.operateWithEditor(fieldName, editor => {
                    editor.mode.set('design');
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                });

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

                this.operateWithEditor(fieldName, editor => {
                    editor.setContent(content);
                });

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
