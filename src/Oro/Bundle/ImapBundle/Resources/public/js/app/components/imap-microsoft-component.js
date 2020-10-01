define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const ImapMicrosoftView = require('oroimap/js/app/views/imap-microsoft-view');
    const BaseImapComponent = require('oroimap/js/app/components/imap-component');
    const routing = require('routing');
    const popup = require('oroimap/js/app/components/popup');
    const $ = require('jquery');

    const ImapMicrosoftComponent = BaseImapComponent.extend({

        ViewType: ImapMicrosoftView,

        /** @property {String|null} */
        origin: null,

        /** @property {String[]|Array} scopes */
        scopes: [
            'openid',
            'offline_access',
            'User.Read',
            'profile',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/POP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send'
        ],

        /** @property {String} */
        type: 'microsoft',

        /** @property {Object} */
        popup: {
            width: 500,
            height: 600
        },

        /** @property {Object} */
        errorsMessages: {
            emptyClientId: 'oro.imap.connection.microsoft.oauth.error.emptyClientId',
            access_deny: 'oro.imap.connection.microsoft.oauth.error.access_deny',
            request: 'oro.imap.connection.microsoft.oauth.error.request',
            closed_auth: 'oro.imap.connection.microsoft.oauth.error.closed_auth',
            blocked_popup: 'oro.imap.connection.microsoft.oauth.error.blocked_popup'
        },

        /**
         * @inheritDoc
         */
        constructor: function ImapMicrosoftComponent(options) {
            ImapMicrosoftComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.popup = options.popup || this.popup;
            ImapMicrosoftComponent.__super__.initialize.call(this, options);
        },

        /**
         * Handler event checkConnection
         */
        onCheckConnection: function() {
            this.view.resetErrorMessage();
            this.requestAuthCode();
        },

        /**
         * @param {Object} data
         * @return {string}
         */
        buildLoginUrl: function(data) {
            const url = 'https://login.microsoftonline.com/' + data.tenant + '/oauth2/v2.0/authorize';
            const urlParts = {
                client_id: data.clientId,
                response_type: 'code',
                redirect_uri: routing.generate('oro_imap_microsoft_access_token', {}, true),
                response_mode: 'query',
                scope: this.scopes.join(' '),
                state: Math.floor(10000 + Math.random() * 90000)
            };

            return [url, $.param(urlParts)].join('?');
        },

        /**
         * Request to API to get auth code
         */
        requestAuthCode: function(emailAddress) {
            const data = this.view.getData();
            popup.whenAuthorized(this.buildLoginUrl(data), this.popup)
                .done(function() {
                    this.requestAccessToken();
                }.bind(this))
                .fail(function() {
                    mediator.execute(
                        'showFlashMessage',
                        'error',
                        this.getErrorMessage('blocked_popup')
                    );
                }.bind(this));
        },

        /**
         * @inheritDoc
         */
        prepareDataForForm: function(values) {
            const data = {
                oro_imap_configuration_microsoft: {},
                formParentName: this.formParentName,
                id: this.originId,
                type: 'microsoft'
            };

            for (const i in values) {
                if (values.hasOwnProperty(i)) {
                    data.oro_imap_configuration_microsoft[i] = values[i];
                }
            }

            return data;
        }
    });

    return ImapMicrosoftComponent;
});
