/* global gapi */
/* global google */
import scriptjs from 'scriptjs';
import routing from 'routing';
import BaseComponent from 'oroui/js/app/components/base/component';
import GoogleSyncCheckboxView from 'oroimap/js/app/views/google-sync-checkbox-view';

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

        scriptjs('//accounts.google.com/gsi/client', function() {
            this.listenTo(this.view, 'requestToken', this.requestToken);
        }.bind(this));

        scriptjs('//apis.google.com/js/api.js', function() {
            gapi.load('client', function() {
                gapi.client.init({});
            });
        });
    },

    requestToken: function() {
        const client = google.accounts.oauth2.initTokenClient({
            client_id: this.$clientIdElement.val(),
            scope: this.scopes.join(' '),
            redirect_uri: routing.generate('oro_google_integration_sso_login_google', {}, true),
            callback: ''
        });
        client.callback = async resp => {
            if (resp.error !== undefined) {
                throw (resp);
            }
            this.checkAuthorization(resp.access_token);
        };

        client.requestAccessToken();
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

export default GoogleSyncCheckbox;
