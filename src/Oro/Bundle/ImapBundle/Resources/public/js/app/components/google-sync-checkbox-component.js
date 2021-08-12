/* global gapi */
define(function(require) {
    'use strict';

    const scriptjs = require('scriptjs');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const GoogleSyncCheckboxView = require('oroimap/js/app/views/google-sync-checkbox-view');

    const GoogleSyncCheckbox = BaseComponent.extend({
        clientId: null,

        $clientIdElement: null,

        scopes: ['https://mail.google.com/'],

        /**
         * @inheritdoc
         */
        constructor: function GoogleSyncCheckbox(options) {
            GoogleSyncCheckbox.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$clientIdElement = options._sourceElement
                .closest('form[name="google_settings"]')
                .find('input[id*="client_id"]');

            this.view = new GoogleSyncCheckboxView({
                el: options._sourceElement,
                errorMessage: options.errorMessage,
                successMessage: options.successMessage,
                googleErrorMessage: options.googleErrorMessage,
                googleWarningMessage: options.googleWarningMessage
            });

            scriptjs('//apis.google.com/js/client.js?onload=checkAuth', function() {
                this.listenTo(this.view, 'requestToken', this.requestToken);
            }.bind(this));
        },

        requestToken: function() {
            gapi.auth.authorize(
                {
                    client_id: this.$clientIdElement.val(),
                    scope: this.scopes.join(' '),
                    immediate: false
                }, this.checkAuthorization.bind(this));
        },

        checkAuthorization: function(result) {
            this.view.setToken(result);
            gapi.client.load('gmail', 'v1', this.requestProfile.bind(this));
        },

        requestProfile: function() {
            const request = gapi.client.gmail.users.getProfile({
                userId: 'me'
            });

            request.execute(this.responseProfile.bind(this));
        },

        responseProfile: function(response) {
            if (response.code === 403) {
                this.view.setGoogleErrorMessage(response.message);
            }

            this.view.render();
        }
    });

    return GoogleSyncCheckbox;
});
