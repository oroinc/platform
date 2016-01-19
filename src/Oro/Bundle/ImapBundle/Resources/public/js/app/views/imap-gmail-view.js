define(function(require) {
    'use strict';

    var ImapGmailView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    ImapGmailView = BaseView.extend({
        events: {
            'click button[name$="[userEmailOrigin][check]"]': 'onClickConnect',
            'click button[name$="[userEmailOrigin][checkFolder]"]': 'onCheckFolder',
            'click button.removeRow': 'onResetEmail'
        },

        $googleErrorMessage: null,

        errorMessage: '',

        type: '',

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
            this.type = options.type;
        },

        render: function() {
            if (this.html && this.html.length > 0) {
                this.$el.html(this.html);
            }

            this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val(this.token);
            this.$el.find('input[name$="[userEmailOrigin][user]"]').val(this.email);
            this.$el.find('input[name$="[userEmailOrigin][accessTokenExpiresAt]"]').val(this.expiredAt);
            this.$el.find('input[name$="[userEmailOrigin][googleAuthCode]"]').val(this.googleAuthCode);

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
        setErrorMessage: function(message) {
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
         */
        onClickConnect: function() {
            this.trigger('checkConnection', this.getData());
        },

        /**
         * Handler event of click on the button Retrieve Folders
         */
        onCheckFolder: function() {
            this.trigger('getFolders', this.getData());
        },

        /**
         * Handler event of click 'x' button
         */
        onResetEmail: function() {
            $('select[name$="[accountType]"]').val('').trigger('change');
        },

        /**
         * Return values from types of form
         * @returns {{type: string, accessToken: *, clientId: *, user: *, imapPort: *, imapHost: *, imapEncryption: *, smtpPort: *, smtpHost: *, smtpEncryption: *, accessTokenExpiresAt: *, googleAuthCode: *}}
         */
        getData: function() {
            var token = this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val();

            if (!token) {
                token = this.token;
            }

            return {
                type: this.type,
                accessToken: token,
                clientId: this.$el.find('input[name$="[userEmailOrigin][clientId]"]').val(),
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
        },

        /**
         * Try to load folders tree if it is not loaded
         */
        autoRetrieveFolders: function() {
            if (!this.$el.find('input.folder-tree').length) {
                this.trigger('getFolders', this.getData());
            }
        }
    });

    return ImapGmailView;
});
