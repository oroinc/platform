/*global gapi */
define(function(require) {
    'use strict';

    var ImapGmailComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var ImapGmailView = require('oroimap/js/app/views/imap-gmail-view');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');

    ImapGmailComponent = BaseComponent.extend({
        ViewType: ImapGmailView,

        scopes: ['https://mail.google.com/'],

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.route = _.result(options, 'route') || '';
            this.routeGetFolders = _.result(options, 'routeGetFolders') || '';
            this.formParentName = _.result(options, 'formParentName') || '';

            var viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);

            this.listenTo(this.view, 'getFolders', this.onGetFolders);

            require(['//apis.google.com/js/client.js?onload=checkAuth'], _.bind(function() {
                this.listenTo(this.view, 'checkConnection', this.onCheckConnection);
            }, this));
        },

        /**
         * Prepares options for the related view
         *
         * @param {Object} options - component's options
         * @return {Object}
         */
        prepareViewOptions: function(options) {
            return {
                el: options._sourceElement,
                googleErrorMessage: options.googleErrorMessage,
                type: options.type
            };
        },

        /**
         * Handler event checkConnection
         */
        onCheckConnection: function() {
            mediator.execute('showLoading');
            this.view.resetErrorMessage();
            this.requestAccessToken();
        },

        /**
         * Request to google API to get google auth code
         */
        requestGoogleAuthCode: function(emailAddress) {
            var data = this.view.getData();

            if (data.clientId.length === 0) {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.emptyClientId'));
                this.view.render();
            } else {
                gapi.auth.authorize({
                    'client_id': data.clientId,
                    'scope': this.scopes.join(' '),
                    'immediate': false,
                    'login_hint': emailAddress,
                    'access_type': 'offline',
                    'response_type': 'code',
                    'approval_prompt': 'force'
                }, _.bind(this.handleResponseGoogleAuthCode, this));
            }
        },

        /**
         * Handler response from google API  for request to get google auth code
         */
        handleResponseGoogleAuthCode: function(response) {
            if (response.error === 'access_denied') {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.access_deny'));
                this.view.render();
                mediator.execute('hideLoading');
            } else {
                this.view.setGoogleAuthCode(response.code);
                this.view.render();
                this.requestFormGetFolder();
            }
        },

        handleClosedGoogleAuthWindow: function() {
            mediator.execute('hideLoading');
            mediator.execute('showFlashMessage', 'error', __('oro.email.error.google_auth'));
        },

        /**
         * Request to google API to get token
         */
        requestAccessToken: function() {
            //https://github.com/google/google-api-javascript-client/issues/25#issuecomment-76695596
            (function(wrapped) {
                window.open = function() {
                    window.open = wrapped;

                    var win = wrapped.apply(this, arguments);
                    var i = setInterval(function() {
                        if (win.closed) {
                            clearInterval(i);
                            setTimeout(function() {
                                authorizeDeferred.cancel();
                            }, 1500);
                        }
                    }, 100);
                    return win;
                };
            })(window.open);

            var authorizeDeferred = gapi.auth.authorize({
                    'client_id': this.view.getData().clientId,
                    'scope': this.scopes.join(' '),
                    'immediate': false,
                    'authuser': -1
                },
                _.bind(this.checkAuthorization, this)
            ).then(
                null,
                _.bind(this.handleClosedGoogleAuthWindow, this)
            );
        },

        /**
         * Handler response from google API  for request to get token
         */
        checkAuthorization: function(result) {
            this.view.setToken(result.access_token);
            this.view.setExpiredAt(result.expires_in);

            gapi.client.load('gmail', 'v1', _.bind(this.requestProfile, this));
        },

        /**
         * Request to google API to get user profile
         */
        requestProfile: function() {
            var request = gapi.client.gmail.users.getProfile({
                'userId': 'me'
            });

            request.execute(_.bind(this.responseProfile, this));
        },

        /**
         * Handler response from google API  for request to get user profile
         */
        responseProfile: function(response) {
            if (response.code === 403) {
                this.view.setErrorMessage(response.message);
                this.view.render();
            } else if (response) {
                this.view.setEmail(response.emailAddress);
                mediator.trigger('change:systemMailBox:email', {email: response.emailAddress});
                this.requestGoogleAuthCode(response.emailAddress);
            }
        },

        /**
         * Request to server to get template with button Retrieve Folders
         */
        requestFormGetFolder: function() {
            var data = this.view.getData();
            data.formParentName = this.formParentName;

            $.ajax({
                url: this.getUrl(),
                method: 'POST',
                data: data,
                success: _.bind(this.renderFormGetFolder, this)
            });
        },

        /**
         * Hendler response from server for request to get template with button Retrieve Folders
         * @param response
         */
        renderFormGetFolder: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
            this.view.autoRetrieveFolders();
        },

        /**
         * Handler event getFolders
         * @param value
         */
        onGetFolders: function(value) {
            delete value.type;
            var data = this.prepareDataForForm(value);
            mediator.execute('showLoading');

            $.ajax({
                url: this.getUrlGetFolders(),
                method: 'POST',
                data: data,
                success: _.bind(this.handlerGetFolders, this)
            });
        },

        /**
         * Handler response from server to get folders
         * @param response
         */
        handlerGetFolders: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
            mediator.execute('hideLoading');
        },

        /**
         * Wrap data from view in form oro_imap_configuration_gmail for request to server
         * @param values
         * @returns {{oro_imap_configuration_gmail: {}}}
         */
        prepareDataForForm: function(values) {
            var data = {
                oro_imap_configuration_gmail: {},
                formParentName: this.formParentName
            };

            for (var i in values) {
                if (values.hasOwnProperty(i)) {
                    data.oro_imap_configuration_gmail[i] = values[i];
                }
            }

            return data;
        },

        /**
         * Generate url for request to server to get template with button Retrieve Folders
         * @returns {string|*}
         */
        getUrl: function() {
            return routing.generate(this.route, this._getUrlParams());
        },

        /**
         * Generate url for request to get folders
         * @returns {string|*}
         */
        getUrlGetFolders: function() {
            return routing.generate(this.routeGetFolders, this._getUrlParams());
        },

        /**
         * Prepare parameters for routes
         * @returns {{}}
         * @private
         */
        _getUrlParams: function() {
            return {};
        }
    });

    return ImapGmailComponent;
});
