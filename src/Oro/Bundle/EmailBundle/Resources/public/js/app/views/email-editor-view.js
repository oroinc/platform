/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('jquery'),
        select2 = require('jquery.select2'),
        routing = require('routing'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        messenger = require('oroui/js/messenger'),
        ApplyTemplateConfirmation = require('oroemail/js/app/apply-template-confirmation');

    EmailEditorView = BaseView.extend({
        readyPromise: null,
        $cache: null,

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
            this.templateGenerator = options.templateGenerator;
            this.initElCache();
            this.init();
            this.readyPromise = mediator.execute('layout:init', this.$el, this);
        },

        render: function () {
            throw new Error("EmailEditorView should not be rendered");
        },

        initElCache: function () {
            this.$cache = {
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
                    this.$cache.body.focus();
                    var caretPos = this.$cache.body.getCursorPosition();
                    var body = this.$cache.body.val();
                    this.$cache.body.val(body.substring(0, caretPos) + this.model.get('signature').replace(/(<([^>]+)>)/ig, "") + body.substring(caretPos));
                }
            } else {
                var url = routing.generate('oro_user_profile_update');
                if (this.model.get('isSignatureEditable')) {
                    mediator.execute('showFlashMessage', 'info', __('oro.email.thread.no_signature', {url: url}));
                } else {
                    mediator.execute('showFlashMessage', 'info', __('oro.email.thread.no_signature_no_permission'));
                }
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
                this.templateGenerator.generate(templateId, this.model.get('email').get('relatedEntityId'))
                    .always(function () {
                        mediator.execute('hideLoading');
                    })
                    .done(_.bind(function (res) {
                        if (!this.model.get('parentEmailId') || !this.$cache.subject.val()) {
                            this.$cache.subject.val(res.subject);
                        }

                        var body = this.initBody(res.body, false);
                        this.$cache.body.val(body);
                        this.$cache.type.find('input[value=' + res.type + ']')
                            .prop('checked', true)
                            .trigger('change');
                    }, this));
            }, this));
            confirm.open();
        },

        onTypeChange: function(e) {
            this.pageComponent('bodyEditor').view.setEnabled($(e.target).val() === 'html');
        },

        init: function () {
            this.$cache.body.val(this.initBody(this.$cache.body.val()));
            this.addForgedAsterisk();
            this.initFields();
        },

        initFields: function() {
            var select2Config = {
                containerCssClass: 'taggable-email',
                separator: ";",
                tags: [],
                tokenSeparators: [";", ","]
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
            $field.parents('.control-group.taggable-field').css('display', 'block');
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
            $field.parents('.control-group.taggable-field').css('display', 'none');

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
            var label_tab = this.$('.forged-required').find('label'),
                em_tag = label_tab.find('em');

            if (em_tag.length <= 0) {
                label_tab.append('<em>*</em>')
            } else {
                em_tag.html('*');
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
