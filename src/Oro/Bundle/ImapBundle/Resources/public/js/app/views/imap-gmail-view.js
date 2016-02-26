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
        accessToken: '',
        refreshToken: '',
        expiredAt: '',
        email: '',

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.googleErrorMessage = options.googleErrorMessage;
            this.type = options.type;
        },

        render: function() {
            if (this.html && this.html.length > 0) {
                this.$el.html(this.html);
            }

            this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val(this.accessToken);
            this.$el.find('input[name$="[userEmailOrigin][refreshToken]"]').val(this.refreshToken);
            this.$el.find('input[name$="[userEmailOrigin][user]"]').val(this.email);
            this.$el.find('input[name$="[userEmailOrigin][accessTokenExpiresAt]"]').val(this.expiredAt);

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
         * @returns {{type: string, accessToken: *, clientId: *, user: *, imapPort: *, imapHost: *, imapEncryption: *, smtpPort: *, smtpHost: *, smtpEncryption: *, accessTokenExpiresAt: *, refreshToken: *}}
         */
        getData: function() {
            var accessToken = this.$el.find('input[name$="[userEmailOrigin][accessToken]"]').val();
            var refreshToken = this.$el.find('input[name$="[userEmailOrigin][refreshToken]"]').val();

            if (!accessToken) {
                accessToken = this.accessToken;
            }
            if (!refreshToken) {
                refreshToken = this.refreshToken;
            }

            return {
                type: this.type,
                accessToken: accessToken,
                refreshToken: refreshToken,
                clientId: this.$el.find('input[name$="[userEmailOrigin][clientId]"]').val(),
                user: this.$el.find('input[name$="[userEmailOrigin][user]"]').val(),
                imapPort: this.$el.find('input[name$="[userEmailOrigin][imapPort]"]').val(),
                imapHost: this.$el.find('input[name$="[userEmailOrigin][imapHost]"]').val(),
                imapEncryption: this.$el.find('input[name$="[userEmailOrigin][imapEncryption]"]').val(),
                smtpPort: this.$el.find('input[name$="[userEmailOrigin][smtpPort]"]').val(),
                smtpHost: this.$el.find('input[name$="[userEmailOrigin][smtpHost]"]').val(),
                smtpEncryption: this.$el.find('input[name$="[userEmailOrigin][smtpEncryption]"]').val(),
                accessTokenExpiresAt: this.$el.find('input[name$="[userEmailOrigin][accessTokenExpiresAt]"]').val()
            };
        },

        /**
         * Set access token
         * @param {string} value
         */
        setAccessToken: function(value) {
            this.accessToken = value;
        },

        /**
         * Set refresh token
         * @param {string} value
         */
        setRefreshToken: function(value) {
            this.refreshToken = value;
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
         * Change style for block with error message to show
         */
        showErrorMessage: function() {
            var $errorBlock = this.getErrorBlock();

            if ($errorBlock.length > 0) {
                $errorBlock.html(this.errorMessage);
                $errorBlock.show();
            }
        },

        /**
         * Change style for block with error message to hide
         */
        hideErrorMessage: function() {
            var $errorBlock = this.getErrorBlock();
            if ($errorBlock.length > 0) {
                $errorBlock.hide();
            }
        },

        /**
         * @returns {*}
         */
        getErrorBlock: function() {
            return this.$el.find(this.googleErrorMessage);
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
