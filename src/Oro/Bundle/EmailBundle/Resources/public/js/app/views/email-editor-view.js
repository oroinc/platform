define(function(require) {
    'use strict';

    var EmailEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var routing = require('routing');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var ApplyTemplateConfirmation = require('oroemail/js/app/apply-template-confirmation');

    EmailEditorView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function EmailEditorView() {
            EmailEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            EmailEditorView.__super__.initialize.apply(this, arguments);
            this.templatesProvider = options.templatesProvider;
            this.editorComponentName = options.editorComponentName;
            this.setupCache();
        },

        render: function() {
            this.domCache.body.val(this.initBody(this.domCache.body.val()));
            this.addForgedAsterisk();
            this.renderPromise = this.initLayout();
            this.renderPromise.done(_.bind(this.initFields, this));
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
            var url;
            var message;
            var signature = this.model.get('signature');
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
            var tinyMCE = this.getBodyEditorView().tinymceInstance;
            var quoteNode = tinyMCE.getBody().querySelector('.quote');
            var signatureNode = tinyMCE.dom.create('p', {}, signature);
            tinyMCE.getBody().insertBefore(signatureNode, quoteNode);
            tinyMCE.selection.setCursorLocation(signatureNode);
            signatureNode.scrollIntoView();
            tinyMCE.execCommand('mceFocus', false);
        },

        addTextSignature: function(signature) {
            var quoteIndex;
            var cursorPosition;
            var value = this.domCache.body.val();
            var EOL = '\r\n';
            var firstQuoteLine = this.getBodyEditorView().getFirstQuoteLine();
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
            var templateId = $(e.target).val();
            if (!templateId) {
                return;
            }

            var confirm = new ApplyTemplateConfirmation({
                content: __('oro.email.emailtemplate.apply_template_confirmation_content')
            });
            confirm.on('ok', _.bind(function() {
                mediator.execute('showLoading');
                this.templatesProvider.create(templateId, this.model.get('email').get('relatedEntityId'))
                    .always(_.bind(mediator.execute, mediator, 'hideLoading'))
                    .fail(_.bind(this.showTemplateErrorMessage, this))
                    .done(_.bind(this.fillForm, this));
            }, this));
            confirm.open();
        },

        showTemplateErrorMessage: function(jqXHR) {
            var reason = jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON.reason : '';
            var $errorContainer = this._getErrorContainer();

            $errorContainer.find('.alert-error').remove();

            mediator.execute(
                'showMessage',
                'error',
                reason ? reason : __('oro.email.emailtemplate.load_failed'),
                {container: $errorContainer}
            );
        },

        fillForm: function(emailData) {
            var editorView = this.getBodyEditorView();
            var $errorContainer = this._getErrorContainer();

            $errorContainer.find('.alert-error').remove();

            if (!this.model.get('parentEmailId') || !this.domCache.subject.val()) {
                this.domCache.subject.val(emailData.subject);
            }

            var body = this.initBody(emailData.body, false);
            this.domCache.body.val(body);

            if (editorView.enabled && editorView.tinymceInstance) {
                editorView.tinymceInstance.setContent(body);
            }

            this.domCache.type.find('input[value=' + emailData.type + ']')
                .prop('checked', true)
                .trigger('change');
        },

        onTypeChange: function(e) {
            this.getBodyEditorView().setEnabled($(e.target).val() === 'html');
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
            var field = fieldName.toLowerCase();
            var $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').show();
            $field.parents('.controls').find('input.select2-input')
                .unbind('focusout')
                .on('focusout', _.bind(function(e) {
                    setTimeout(_.bind(function() {
                        if (!$field.val()) {
                            this.hideField(fieldName, fieldValue);
                        }
                    }, this), 200);
                }, this))
                .focus();

            this.$('[data-ftid$="_email_to"]')
                .parents('.control-group.taggable-field')
                .find('label').html(__('oro.email.to.label'));
            this.addForgedAsterisk();
        },

        hideField: function(fieldName, fieldValue) {
            var field = fieldName.toLowerCase();
            var $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').hide();

            if (this.$('span.show' + fieldName).length > 0) {
                return;
            }
            this.$('.cc-bcc-holder').append('<span class="show' + fieldName + '">' + fieldValue + '</span>');
            this.$('.show' + fieldName).on('click', _.bind(function(e) {
                e.stopPropagation();
                var target = e.target;
                $(target).remove();
                this.showField(fieldName, fieldValue);
            }, this));
        },

        addForgedAsterisk: function() {
            var labelTab = this.$('.forged-required').find('label');
            var emTag = labelTab.find('em');

            if (emTag.length <= 0) {
                labelTab.append('<em>*</em>');
            } else {
                emTag.html('*');
            }
        },

        initBody: function(body, appendSignature) {
            appendSignature = typeof appendSignature !== 'undefined' ? appendSignature : true;
            var signature = this.model.get('signature');
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
