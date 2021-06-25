define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const routing = require('routing');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const ApplyTemplateConfirmation = require('oroemail/js/app/apply-template-confirmation');

    const EmailEditorView = BaseView.extend({
        templatesProvider: null,
        editorComponentName: null,
        readyPromise: null,
        domCache: null,

        events: {
            'click #add-signature': 'onAddSignatureButtonClick',
            'change [name$="[template]"]': 'onTemplateChange',
            'change [name$="[type]"]': 'onTypeChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailEditorView(options) {
            EmailEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            EmailEditorView.__super__.initialize.call(this, options);
            this.templatesProvider = options.templatesProvider;
            this.editorComponentName = options.editorComponentName;
            this.setupCache();
        },

        render: function() {
            this.domCache.body.val(this.initBody(this.domCache.body.val()));
            this.addForgedAsterisk();
            this.renderPromise = this.initLayout();
            this.renderPromise.done(this.initFields.bind(this));
            return this;
        },

        setupCache: function() {
            this.domCache = {
                subject: this.$('[name$="[subject]"]'),
                body: this.$('[name$="[body]"]'),
                type: this.$('[name$="[type]"]'),
                template: this.$('[name$="[template]"]')
            };
        },

        onAddSignatureButtonClick: function() {
            let url;
            let message;
            const signature = this.model.get('signature');
            if (signature) {
                if (this.getBodyEditorView().tinymceInstance) {
                    this.addHTMLSignature(signature);
                } else {
                    this.addTextSignature(signature);
                }
            } else {
                url = routing.generate(
                    'oro_user_profile_configuration',
                    {activeGroup: 'platform', activeSubGroup: 'user_email_configuration'}
                );
                message = this.model.get('isSignatureEditable')
                    ? __('oro.email.thread.no_signature', {url: url})
                    : __('oro.email.thread.no_signature_no_permission');
                mediator.execute('showFlashMessage', 'info', message);
            }
        },

        addHTMLSignature: function(signature) {
            const tinyMCE = this.getBodyEditorView().tinymceInstance;
            const quoteNode = tinyMCE.getBody().querySelector('.quote');
            const signatureNode = tinyMCE.dom.create('p', {}, signature);
            tinyMCE.getBody().insertBefore(signatureNode, quoteNode);
            tinyMCE.selection.setCursorLocation(signatureNode);
            signatureNode.scrollIntoView();
            tinyMCE.execCommand('mceFocus', false);
        },

        addTextSignature: function(signature) {
            let quoteIndex;
            let cursorPosition;
            let value = this.domCache.body.val();
            const EOL = '\r\n';
            const firstQuoteLine = this.getBodyEditorView().getFirstQuoteLine();
            signature = signature.replace(/(<([^>]+)>)/ig, '');
            if (firstQuoteLine) {
                quoteIndex = value.indexOf(firstQuoteLine);
                if (quoteIndex !== -1) {
                    value = value.substr(0, quoteIndex) + signature + EOL + value.substr(quoteIndex);
                    cursorPosition = quoteIndex + signature.length;
                }
            }
            if (_.isUndefined(cursorPosition)) {
                value += EOL + signature;
                cursorPosition = value.length;
            }
            this.domCache.body.val(value)
                .setCursorPosition(cursorPosition)
                .focus();
        },

        onTemplateChange: function(e) {
            const templateId = $(e.target).val();
            if (!templateId) {
                return;
            }

            const confirm = new ApplyTemplateConfirmation({
                content: __('oro.email.emailtemplate.apply_template_confirmation_content')
            });
            confirm.on('ok', () => {
                mediator.execute('showLoading');
                this.templatesProvider.create(templateId, this.model.get('email').get('relatedEntityId'))
                    .always(mediator.execute.bind(mediator, 'hideLoading'))
                    .fail(this.showTemplateErrorMessage.bind(this))
                    .done(this.fillForm.bind(this));
            });
            confirm.open();
        },

        showTemplateErrorMessage: function(jqXHR) {
            const reason = jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON.reason : '';
            const $errorContainer = this._getErrorContainer();

            $errorContainer.find('.alert-error').remove();

            mediator.execute(
                'showMessage',
                'error',
                reason ? reason : __('oro.email.emailtemplate.load_failed'),
                {container: $errorContainer}
            );
        },

        fillForm: function(emailData) {
            const editorView = this.getBodyEditorView();
            const $errorContainer = this._getErrorContainer();

            $errorContainer.find('.alert-error').remove();

            if (!this.model.get('parentEmailId') || !this.domCache.subject.val()) {
                this.domCache.subject.val(emailData.subject);
            }

            const body = this.initBody(emailData.body, false);
            this.domCache.body.val(body);

            if (editorView.enabled && editorView.tinymceInstance) {
                editorView.tinymceInstance.setContent(body);
            }

            this.domCache.type.find('input[value=' + emailData.type + ']')
                .prop('checked', true)
                .trigger('change');
        },

        onTypeChange: function(e) {
            this.getBodyEditorView().setIsHtml($(e.target).val() === 'html');
        },

        /**
         * Returns wysiwyg editor view
         */
        getBodyEditorView: function() {
            return this.pageComponent('wrap_' + this.editorComponentName).view
                .pageComponent(this.editorComponentName).view;
        },

        initFields: function() {
            if (!this.model.get('email').get('bcc').length || !this.model.get('email').get('cc').length) {
                this.$('[data-ftid$="_email_to"]').parents('.controls').find('ul.select2-choices').after(
                    '<div class="cc-bcc-holder"/>'
                );
            }
            if (!this.model.get('email').get('cc').length) {
                this.hideField('Cc', __('oro.email.cc.label'));
            }
            if (!this.model.get('email').get('bcc').length) {
                this.hideField('Bcc', __('oro.email.bcc.label'));
            }
        },

        showField: function(fieldName, fieldValue) {
            const field = fieldName.toLowerCase();
            const $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').show();
            $field.parents('.controls').find('input.select2-input')
                .unbind('focusout')
                .on('focusout', e => {
                    setTimeout(() => {
                        if (!$field.val()) {
                            this.hideField(fieldName, fieldValue);
                        }
                    }, 200);
                })
                .focus();

            this.$('[data-ftid$="_email_to"]')
                .parents('.control-group.taggable-field')
                .find('label').html(__('oro.email.to.label'));
            this.addForgedAsterisk();
        },

        hideField: function(fieldName, fieldValue) {
            const field = fieldName.toLowerCase();
            const $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').hide();

            if (this.$('span.show' + fieldName).length > 0) {
                return;
            }
            this.$('.cc-bcc-holder').append('<span class="show' + fieldName + '">' + fieldValue + '</span>');
            this.$('.show' + fieldName).on('click', e => {
                e.stopPropagation();
                const target = e.target;
                $(target).remove();
                this.showField(fieldName, fieldValue);
            });
        },

        addForgedAsterisk: function() {
            const labelTab = this.$('.forged-required').find('label');
            const emTag = labelTab.find('em');

            if (emTag.length <= 0) {
                labelTab.append('<em>*</em>');
            } else {
                emTag.html('*');
            }
        },

        initBody: function(body, appendSignature) {
            appendSignature = typeof appendSignature !== 'undefined' ? appendSignature : true;
            const signature = this.model.get('signature');
            if (this.model.get('appendSignature') && appendSignature) {
                if (signature && body.indexOf(signature) < 0) {
                    body += '<br/><br/>' + this.model.get('signature');
                }
            }
            if (this.model.get('bodyFooter')) {
                body = '<body>' + body;
                body += this.model.get('bodyFooter') + '</body>';
            }
            return body;
        },

        _getErrorContainer: function() {
            return this.$('[name$="[template]"]').parent();
        }
    });

    return EmailEditorView;
});
