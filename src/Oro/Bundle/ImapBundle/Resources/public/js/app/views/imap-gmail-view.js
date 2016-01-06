define(function(require) {
    'use strict';

    var ImapGmailView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    ImapGmailView = BaseView.extend({
        events: {
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][check]"]': 'onClickConnect',
            'click button[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][checkFolder]"]': 'onCheckFolder'
        },

        $googleErrorMessage: null,

        errorMessage: '',

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
        initialize: function(options) {
            this.$googleErrorMessage = this.$el.find(options.googleErrorMessage);
        },

        render: function() {
            if (this.html.length > 0) {
                this.$el.html(this.html);
            }

            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessToken]"]').val(this.token);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][user]"]').val(this.email);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessTokenExpiresAt]"]').val(this.expiredAt);
            this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][googleAuthCode]"]').val(this.googleAuthCode);

            if (this.errorMessage.length > 0) {
                this.showErrorMessage();
            } else {
                this.hideErrorMessage();
            }

            this._deferredRender();
            this.initLayout().done(_.bind(this._resolveDeferredRender, this));
        },

        /**
         * Set error message
         * @param {string} message
         */
        setErrorMessage: function (message) {
            this.errorMessage = message;
        },

        /**
         * Clear error message
         */
        resetErrorMessage: function() {
            this.errorMessage = '';
        },

        /**
         * Set html template
         * @param {string} html
         */
        setHtml: function(html) {
            this.html = html;
        },

        /**
         * Handler event of click on the button Connection
         * @param e
         */
        onClickConnect: function(e) {
            this.trigger('checkConnection', this.getData());
        },

        /**
         * Handler event of click on the button Retrieve Folders
         */
        onCheckFolder: function() {
            this.trigger('getFolders', this.getData());
        },

        /**
         * Return values from types of form
         * @returns {{type: string, accessToken: *, clientId: *, user: *, imapPort: *, imapHost: *, imapEncryption: *, smtpPort: *, smtpHost: *, smtpEncryption: *, accessTokenExpiresAt: *, googleAuthCode: *}}
         */
        getData: function() {
            var token = this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][accessToken]"]').val();

            if (!token) {
                token = this.token;
            }

            return {
                type : 'Gmail',
                accessToken : token,
                clientId : this.$el.find('input[name="oro_user_user_form[imapAccountType][imapGmailConfiguration][clientId]"]').val(),
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

        /**
         * Set token
         * @param {string} value
         */
        setToken: function(value) {
            this.token = value;
        },

        /**
         * Set email
         * @param {string} value
         */
        setEmail: function(value) {
            this.email = value;
        },

        /**
         * Set expiredAt
         * @param {string} value
         */
        setExpiredAt: function(value) {
            this.expiredAt = value;
        },

        /**
         * set googleAuthCode
         * @param {string} value
         */
        setGoogleAuthCode: function(value) {
            this.googleAuthCode = value;
        },

        /**
         * Change style for block with error message to show
         */
        showErrorMessage: function() {
            this.$googleErrorMessage.html(this.errorMessage);
            this.$googleErrorMessage.show();
        },

        /**
         * Change style for block with error message to hide
         */
        hideErrorMessage: function() {
            this.$googleErrorMessage.hide();
        }
    });

    return ImapGmailView;
});
