define(function(require) {
    'use strict';

    var ImapGmailView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    ImapGmailView =  BaseView.extend({
        events: {
            'click button[name$="[userEmailOrigin][check]"]': 'onClickConnect',
            'click button[name$="[userEmailOrigin][checkFolder]"]': 'onCheckFolder'
        },

        type: 'Gmail',

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
            this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val(this.token);
            this.$el.find('input[name$="[userEmailOrigin][user]"]').val(this.email);
            this.$el.find('input[name$="[userEmailOrigin][accessTokenExpiresAt]"]').val(this.expiredAt);
            this.$el.find('input[name$="[userEmailOrigin][googleAuthCode]"]').val(this.googleAuthCode);
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
            var token = this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val();

            if (!token) {
                token = this.token;
            }

            return {
                type : this.type,
                accessToken : token,
                clientId : this.$el.find('input[name$="[userEmailOrigin][clientId]"]').val(),
                mailboxName: this.$el.find('input[name$="[userEmailOrigin][mailboxName]"]').val(),
                user: this.$el.find('input[name$="[userEmailOrigin][user]"]').val(),
                imapPort: this.$el.find('input[name$="[userEmailOrigin][imapPort]"]').val(),
                imapHost: this.$el.find('input[name$="[userEmailOrigin][imapHost]"]').val(),
                imapEncryption: this.$el.find('input[name$="[userEmailOrigin][imapEncryption]"]').val(),
                smtpPort: this.$el.find('input[name$="[userEmailOrigin][smtpPort]"]').val(),
                smtpHost: this.$el.find('input[name$="[userEmailOrigin][smtpHost]"]').val(),
                smtpEncryption: this.$el.find('input[name$="[userEmailOrigin][smtpEncryption]"]').val(),
                accessTokenExpiresAt: this.$el.find('input[name$="[userEmailOrigin][accessTokenExpiresAt]"]').val(),
                googleAuthCode: this.$el.find('input[name$="[userEmailOrigin][googleAuthCode]"]').val()
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
