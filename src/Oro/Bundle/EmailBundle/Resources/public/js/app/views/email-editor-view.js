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
        readyPromise: null,
        domCache: null,

        events: {
            'click #add-signature': 'onAddSignatureButtonClick',
            'change [name$="[template]"]': 'onTemplateChange',
            'change [name$="[type]"]': 'onTypeChange'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            EmailEditorView.__super__.initialize.apply(this, arguments);
            this.templatesProvider = options.templatesProvider;
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
                    {'activeGroup': 'platform', 'activeSubGroup': 'email_configuration'}
                );
                message = this.model.get('isSignatureEditable') ?
                    __('oro.email.thread.no_signature', {url: url}) :
                    __('oro.email.thread.no_signature_no_permission');
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
                    .done(_.bind(this.fillForm, this));
            }, this));
            confirm.open();
        },

        fillForm: function(emailData) {
            if (!this.model.get('parentEmailId') || !this.domCache.subject.val()) {
                this.domCache.subject.val(emailData.subject);
            }

            var body = this.initBody(emailData.body, false);
            this.domCache.body.val(body);
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
            return this.pageComponent('wrap_oro_email_email_body').view.pageComponent('oro_email_email_body').view;
        },

        initFields: function() {
            if (!this.model.get('email').get('bcc').length || !this.model.get('email').get('cc').length) {
                this.$('[data-ftid$="_email_to"]').parents('.controls').find('ul.select2-choices').after(
                    '<div class="cc-bcc-holder"/>'
                );
            }
            if (!this.model.get('email').get('cc').length) {
                this.hideField('Cc');
            }
            if (!this.model.get('email').get('bcc').length) {
                this.hideField('Bcc');
            }
        },

        showField: function(fieldName) {
            var field = fieldName.toLowerCase();
            var $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').show();
            $field.parents('.controls').find('input.select2-input')
                .unbind('focusout')
                .on('focusout', _.bind(function(e) {
                    setTimeout(_.bind(function() {
                        if (!$field.val()) {
                            this.hideField(fieldName);
                        }
                    }, this), 200);
                }, this))
                .focus();

            this.$('[data-ftid$="_email_to"]')
                .parents('.control-group.taggable-field')
                .find('label').html(__('oro.email.to'));
            this.addForgedAsterisk();

        },

        hideField: function(fieldName) {
            var field = fieldName.toLowerCase();
            var $field = this.$('[data-ftid$="_email_' + field + '"]');
            $field.parents('.control-group.taggable-field').hide();

            if (this.$('span.show' + fieldName).length > 0) {
                return;
            }
            this.$('.cc-bcc-holder').append('<span class="show' + fieldName + '">' + fieldName +  '</span>');
            this.$('.show' + fieldName).on('click', _.bind(function(e) {
                e.stopPropagation();
                var target = e.target;
                $(target).remove();
                this.showField(fieldName);
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
                body += this.model.get('bodyFooter');
            }

            return body;
        }
    });

    return EmailEditorView;
});
