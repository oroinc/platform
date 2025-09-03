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
                    fallback: 'input[data-name="field__subject-fallback"]',
                    syncValue: true
                },
                content: {
                    input: 'textarea[data-name="field__content"]',
                    fallback: 'input[data-name="field__content-fallback"]',
                    syncValue: true
                },
                attachments: {
                    input: 'select[data-name="field__file-placeholder"], input[type="file"][data-name="field__file"]',
                    fallback: 'input[data-name="field__attachments-fallback"]',
                    syncValue: false
                }
            }
        },

        fields: null,

        events: {
            'row-collection:updated': 'onContentAdded'
        },

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
            this.fields = {};

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

            this.addFieldsEventHandlers();

            EmailTemplateLocalizationView.__super__.initialize.call(this, options);
        },

        /**
         * Event handler on item is added to a row collection
         */
        onContentAdded() {
            this.removeFieldsEventHandlers();
            this.addFieldsEventHandlers();
        },

        /**
         * Do any operations with tinyMCE editor if it exists
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
                field.$input.prop('disabled', 'disabled');

                this.operateWithEditor(fieldName, editor => {
                    editor.mode.set('readonly');
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                    $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                });

                mediator.trigger(this.eventToParent('localized-template:field-fallback'), fieldName);
            } else {
                field.$input.prop('disabled', false);

                this.operateWithEditor(fieldName, editor => {
                    editor.mode.set('design');
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                });

                this.onChangeInput(fieldName);
            }
            field.$input.inputWidget('refresh');
        },

        onChangeInput: function(fieldName) {
            mediator.trigger(
                this.eventToChildren('localized-template:field-change'),
                fieldName,
                this.fields[fieldName].$input.val()
            );
        },

        onParentFieldChange: function(fieldName, content) {
            if (this.isFieldFallback(fieldName) && this.fields[fieldName].syncValue) {
                this.fields[fieldName].$input.filter((i, el) => {
                    return $(el).attr('type') !== 'file';
                }).val(content);

                this.operateWithEditor(fieldName, editor => {
                    editor.setContent(content);
                });

                this.fields[fieldName].$input.trigger('change');
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
         * Collect fields and start listen to change events
         */
        addFieldsEventHandlers() {
            this.removeFieldsEventHandlers();

            this.fields = {};
            for (const fieldName in this.options.fields) {
                if (this.options.fields.hasOwnProperty(fieldName)) {
                    const field = {
                        $input: this.$el.find(this.options.fields[fieldName].input),
                        $fallback: this.$el.find(this.options.fields[fieldName].fallback),
                        syncValue: this.options.fields[fieldName].syncValue
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
                                editor.on('change', this.onChangeInput.bind(this, fieldName));
                            });
                            this.processFallback(fieldName);
                        });

                        this.operateWithEditor(fieldName, editor => {
                            editor.on('change', this.onChangeInput.bind(this, fieldName));
                        });
                    }

                    this.processFallback(fieldName);
                }
            }
        },

        /**
         * Remove event listeners
         */
        removeFieldsEventHandlers() {
            for (const fieldName in this.fields) {
                if (this.fields.hasOwnProperty(fieldName)) {
                    this.fields[fieldName].$input.off(this.eventNamespace());
                    this.fields[fieldName].$fallback.off(this.eventNamespace());
                }
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.removeFieldsEventHandlers();
            mediator.off(null, null, this);
            delete this.fields;
            EmailTemplateLocalizationView.__super__.dispose.call(this);
        }
    });

    return EmailTemplateLocalizationView;
});
