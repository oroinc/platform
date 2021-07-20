/* global gapi */
define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const ImapGmailView = require('oroimap/js/app/views/imap-gmail-view');
    const BaseImapComponent = require('oroimap/js/app/components/imap-component');

    const ImapGmailComponent = BaseImapComponent.extend({

        ViewType: ImapGmailView,

        scopes: ['https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.email'],

        /** @property {String} */
        type: 'gmail',

        /** @property {String|Null} */
        scriptPath: '//apis.google.com/js/client.js?onload=checkAuth',

        /** @property {Object} */
        errorsMessages: {
            access_deny: 'oro.imap.connection.microsoft.oauth.error.access_deny',
            request: 'oro.imap.connection.microsoft.oauth.error.request',
            closed_auth: 'oro.imap.connection.microsoft.oauth.error.closed_auth',
            blocked_popup: 'oro.imap.connection.microsoft.oauth.error.blocked_popup'
        },

        /**
         * @inheritdoc
         */
        constructor: function ImapGmailComponent(options) {
            ImapGmailComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ImapGmailComponent.__super__.initialize.call(this, options);
        },

        /**
         * Handler event checkConnection
         */
        onCheckConnection: function() {
            this.view.resetErrorMessage();
            this.requestAuthCode();
        },

        /**
         * Request to google API to get google auth code
         */
        requestAuthCode: function(emailAddress) {
            const data = this.view.getData();
            const args = {};

            this._wrapFirstWindowOpen(args);
            args.deferred = gapi.auth.authorize(
                {
                    client_id: data.clientId,
                    scope: this.scopes.join(' '),
                    immediate: false,
                    login_hint: emailAddress,
                    access_type: 'offline',
                    response_type: 'code',
                    approval_prompt: 'force'
                },
                this.handleResponseAuthCode.bind(this)
            ).then(
                null,
                function(reason) {
                    if (null === reason) {
                        // do not show the flash message if there is not rejection reason
                        // usually this happens when all goes ok and the callback function is called,
                        // so, any problems are handled by this callback (see handleResponseAuthCode)
                        // e.g. we do not need the flash message if an user clicks "Deny" button
                        return;
                    }
                    mediator.execute(
                        'showFlashMessage',
                        'error',
                        this.getErrorMessage('closed_auth')
                    );
                }.bind(this)
            );
        },

        /**
         * Wraps the default window.open method to control the
         * deferred API call
         *
         * @param {Object|Array} args
         * @private
         */
        _wrapFirstWindowOpen: function(args) {
            args = args || {};

            (function(wrapped) {
                window.open = function(...openArgs) {
                    window.open = wrapped;

                    const win = wrapped.apply(this, openArgs);
                    if (win) {
                        const i = setInterval(function() {
                            if (win.closed) {
                                clearInterval(i);
                                setTimeout(function() {
                                    if (typeof args.deferred !== 'undefined') {
                                        args.deferred.cancel();
                                    }
                                }, 1500);
                            }
                        }, 100);
                    } else {
                        mediator.execute(
                            'showFlashMessage',
                            'error',
                            this.getErrorMessage('blocked_popup')
                        );
                    }

                    return win;
                };
            })(window.open);
        },

        /**
         * @inheritdoc
         */
        prepareDataForForm: function(values) {
            const data = {
                oro_imap_configuration_gmail: {},
                formParentName: this.formParentName,
                id: this.originId,
                type: 'gmail'
            };

            for (const i in values) {
                if (values.hasOwnProperty(i)) {
                    data.oro_imap_configuration_gmail[i] = values[i];
                }
            }

            return data;
        }
    });

    return ImapGmailComponent;
});
