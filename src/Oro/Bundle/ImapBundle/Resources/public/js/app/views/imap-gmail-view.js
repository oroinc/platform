define(function(require) {
    'use strict';

    var ImapGmailView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    ImapGmailView =  BaseView.extend({
        events: {
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][check]"]': 'onClickConnect',
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][checkFolder]"]': 'onCheckFolder'
        },

        html: '',

        token: '',

        expiredAt: '',

        email: '',

        googleAuthCode: '',

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {},

        render: function() {
            this.$el.html(this.html);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessToken]"]').val(this.token);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][user]"]').val(this.email);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessTokenExpiresAt]"]').val(this.expiredAt);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][googleAuthCode]"]').val(this.googleAuthCode);
            this._deferredRender();
            this.initLayout().done(_.bind(this._resolveDeferredRender, this));
        },

        setHtml: function(html) {
            this.html = html;
        },

        onClickConnect: function(e) {
            this.trigger('imapGmailConnectionSetToken', this.getData());
        },

        onCheckFolder: function() {
            this.trigger('imapGmailConnectionGetFolders', this.getData());
        },

        getData: function() {
            var token = this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessToken]"]').val();

            if (!token) {
                token = this.token;
            }

            return {
                type : 'Gmail',
                accessToken : token,
                clientId : this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][clientId]"]').val(),
                mailboxName: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][mailboxName]"]').val(),
                user: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][user]"]').val(),
                imapPort: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][imapPort]"]').val(),
                imapHost: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][imapHost]"]').val(),
                imapEncryption: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][imapEncryption]"]').val(),
                smtpPort: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][smtpPort]"]').val(),
                smtpHost: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][smtpHost]"]').val(),
                smtpEncryption: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][smtpEncryption]"]').val(),
                accessTokenExpiresAt: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessTokenExpiresAt]"]').val(),
                googleAuthCode: this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][googleAuthCode]"]').val()
            };
        },

        setToken: function(value) {
            this.token = value;
        },

        setEmail: function(value) {
            this.email = value;
        },

        setExpiredAt: function(value) {
            this.expiredAt = value;
        },

        setGoogleAuthCode: function(value) {
            this.googleAuthCode = value;
        }
    });

    return ImapGmailView;
});
