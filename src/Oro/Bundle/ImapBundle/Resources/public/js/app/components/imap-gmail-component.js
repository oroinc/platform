/* global google */
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import ImapGmailView from 'oroimap/js/app/views/imap-gmail-view';
import BaseImapComponent from 'oroimap/js/app/components/imap-component';

const ImapGmailComponent = BaseImapComponent.extend({

    ViewType: ImapGmailView,

    scopes: ['https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.email'],

    /** @property {String} */
    type: 'gmail',

    /** @property {String|Null} */
    scriptPath: '//accounts.google.com/gsi/client',

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

        const client = google.accounts.oauth2.initCodeClient({
            client_id: data.clientId,
            scope: this.scopes.join(' '),
            redirect_uri: routing.generate('oro_google_integration_sso_login_google', {}, true),
            ux_mode: 'popup',
            immediate: false,
            login_hint: emailAddress,
            access_type: 'offline',
            approval_prompt: 'force',
            callback: ''
        });

        client.callback = async resp => {
            if (resp.error !== undefined && null !== resp.reason) {
                // do not show the flash message if there is no rejection reason
                // usually this happens when all goes ok and the callback function is called,
                // so, any problems are handled by this callback (see handleResponseAuthCode)
                // e.g. we do not need the flash message if a user clicks "Deny" button
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    this.getErrorMessage('closed_auth')
                );
            }
            this.handleResponseAuthCode(resp);
        };

        args.deferred = client.requestCode();
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

export default ImapGmailComponent;
