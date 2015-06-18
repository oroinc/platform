/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('jquery'),
        routing = require('routing'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        ApplyTemplateConfirmation = require('oroemail/js/app/apply-template-confirmation');
    require('jquery.select2');

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
        initialize: function (options) {
            EmailEditorView.__super__.initialize.apply(this, options);
            this.templatesProvider = options.templatesProvider;
            this.setupCache();
        },

        render: function () {
            this.domCache.body.val(this.initBody(this.domCache.body.val()));
            this.addForgedAsterisk();
            this.initFields();
            this.renderPromise = this.initLayout();
            return this;
        },

        setupCache: function () {
            this.domCache = {
                subject: this.$('[name$="[subject]"]'),
                body: this.$('[name$="[body]"]'),
                type: this.$('[name$="[type]"]'),
                template: this.$('[name$="[template]"]')
            };
        },

        onAddSignatureButtonClick: function() {
            if (this.model.get('signature')) {
                if (this.pageComponent('bodyEditor').view.tinymceConnected) {
                    var tinyMCE = this.pageComponent('bodyEditor').view.tinymceInstance;
                    tinyMCE.execCommand('mceInsertContent', false, this.model.get('signature'));
                } else {
                    this.domCache.body.focus();
                    var caretPos = this.domCache.body.getCursorPosition();
                    var body = this.domCache.body.val();
                    this.domCache.body.val(body.substring(0, caretPos) +
                        this.model.get('signature').replace(/(<([^>]+)>)/ig, '') + body.substring(caretPos));
                }
            } else {
                var url = routing.generate('oro_user_profile_update'),
                    message = this.model.get('isSignatureEditable') ?
                        __('oro.email.thread.no_signature', {url: url}) :
                        __('oro.email.thread.no_signature_no_permission');
                mediator.execute('showFlashMessage', 'info', message);
            }
        },

        onTemplateChange: function (e) {
            var templateId = $(e.target).val();
            if (!templateId) {
                return;
            }

            var confirm = new ApplyTemplateConfirmation({
                content: __('oro.email.emailtemplate.apply_template_confirmation_content')
            });
            confirm.on('ok', _.bind(function () {
                mediator.execute('showLoading');
                this.templatesProvider.create(templateId, this.model.get('email').get('relatedEntityId'))
                    .always(_.bind(mediator.execute, mediator, 'hideLoading'))
                    .done(_.bind(this.fillForm, this));
            }, this));
            confirm.open();
        },

        fillForm: function (emailData) {
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
            this.pageComponent('bodyEditor').view.setEnabled($(e.target).val() === 'html');
        },

        initFields: function() {
            var select2Config = {
                containerCssClass: 'taggable-email',
                separator: ';',
                tags: [],
                tokenSeparators: [';', ',']
            };
            this.$('input.taggable-field').each(function(key, elem) {
                if ($(elem).hasClass('from')) {
                    select2Config.maximumSelectionSize = 1;
                }
                $(elem).select2(_.extend({}, select2Config));
            });
            if (!this.model.get('email').get('bcc').length || !this.model.get('email').get('cc').length) {
                this.$('[id^=oro_email_email_to]').parents('.controls').find('ul.select2-choices').after(
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

        showField: function (fieldName) {
            var field = fieldName.toLowerCase(),
                $field = this.$('[data-ftid=oro_email_email_' + field + ']');
            $field.parents('.control-group.taggable-field').show();
            $field.parents('.controls').find('input.select2-input')
                .unbind('focusout')
                .on('focusout', _.bind(function(e) {
                    setTimeout(_.bind(function(){
                        if (!$field.val()) {
                            this.hideField(fieldName);
                        }
                    },this), 200);
                }, this))
                .focus();

            this.$('[data-ftid=oro_email_email_to]')
                .parents('.control-group.taggable-field')
                .find('label').html(__('oro.email.to'));
            this.addForgedAsterisk();

        },

        hideField: function (fieldName) {
            var field = fieldName.toLowerCase(),
                $field = this.$('[data-ftid=oro_email_email_' + field + ']');
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

        addForgedAsterisk: function () {
            var labelTab = this.$('.forged-required').find('label'),
                emTag = labelTab.find('em');

            if (emTag.length <= 0) {
                labelTab.append('<em>*</em>')
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
