define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var GoogleSyncCheckboxView = require('oroimap/js/app/views/google-sync-checkbox-view');
    var GoogleSyncCheckbox;

    GoogleSyncCheckbox = BaseComponent.extend({
        clientId: null,

        $clientIdElement: null,

        scopes: ['https://www.googleapis.com/auth/gmail.readonly'],

        initialize: function(options) {
            this.$clientIdElement = options._sourceElement
                .closest('form[name="google_settings"]')
                .find('input[id*="client_id"]');

            this.view = new GoogleSyncCheckboxView({
                el: options._sourceElement,
                errorMessage: options.errorMessage,
                successMessage: options.successMessage
            });

            require(['//apis.google.com/js/client.js?onload=checkAuth'], _.bind(function() {
                this.listenTo(this.view, 'requestToken', this.requestToken);
            }, this));
        },

        requestToken: function() {
            gapi.auth.authorize(
                {
                    'client_id': this.$clientIdElement.val(),
                    'scope': this.scopes.join(' '),
                    'immediate': false
                }, _.bind(this.checkAuthorization, this));
        },

        checkAuthorization: function(result) {
            this.view.setToken(result);
            this.view.render();
        }
    });

    return GoogleSyncCheckbox;
});
