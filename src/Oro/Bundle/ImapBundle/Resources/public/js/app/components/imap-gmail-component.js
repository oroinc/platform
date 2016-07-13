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

        scopes: ['https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.email'],

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.route = _.result(options, 'route') || '';
            this.routeAccessToken = _.result(options, 'routeAccessToken') || '';
            this.routeGetFolders = _.result(options, 'routeGetFolders') || '';
            this.formParentName = _.result(options, 'formParentName') || '';
            this.originId = _.result(options, 'id') || null;

            var viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);

            this.view.setAccessToken(_.result(options, 'accessToken') || '');
            this.view.setExpiredAt(_.result(options, 'accessTokenExpiresAt') || '');
            this.view.setEmail(_.result(options, 'user') || '');

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
            this.view.resetErrorMessage();
            this.requestGoogleAuthCode();
        },

        /**
         * Request to google API to get google auth code
         */
        requestGoogleAuthCode: function(emailAddress) {
            var data = this.view.getData();
            var args = {};

            if (data.clientId.length === 0) {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.emptyClientId'));
                this.view.render();
            } else {
                this._wrapFirstWindowOpen(args);
                args.deferred = gapi.auth.authorize({
                        'client_id': data.clientId,
                        'scope': this.scopes.join(' '),
                        'immediate': false,
                        'login_hint': emailAddress,
                        'access_type': 'offline',
                        'response_type': 'code',
                        'approval_prompt': 'force'
                    }, _.bind(this.handleResponseGoogleAuthCode, this)
                ).then(
                    null,
                    function() {
                        mediator.execute(
                            'showFlashMessage',
                            'error',
                            __('oro.imap.connection.google.oauth.error.closed_auth')
                        );
                    }
                );
            }
        },

        /**
         * Handler response from google API for request to get google auth code
         */
        handleResponseGoogleAuthCode: function(response) {
            if (response.error === 'access_denied') {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.access_deny'));
                this.view.render();
            } else {
                mediator.execute('showLoading');
                this.requestAccessToken(response.code);
            }
        },

        /**
         * Request to google API to get token
         */
        requestAccessToken: function(code) {
            $.ajax({
                url: this.getUrlGetAccessToken(),
                method: 'POST',
                data: {code: code},
                success: _.bind(this.prepareAuthorization, this),
                error:  _.bind(this.requestError, this)
            });
        },

        _wrapFirstWindowOpen: function(args) {
            args = args || {};

            (function(wrapped) {
                window.open = function() {
                    window.open = wrapped;

                    var win = wrapped.apply(this, arguments);
                    if (win) {
                        var i = setInterval(function() {
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
                            __('oro.imap.connection.google.oauth.error.blocked_popup')
                        );
                    }

                    return win;
                };
            })(window.open);
        },

        /**
         * Handler response for request to get token
         */
        prepareAuthorization: function(response) {
            if (response.error !== undefined) {
                this.view.setErrorMessage(response.error);
                this.view.render();
                mediator.execute('hideLoading');
            } else if (response) {
                this.view.setEmail(response.email_address);
                this.view.setAccessToken(response.access_token);
                this.view.setRefreshToken(response.refresh_token);
                this.view.setExpiredAt(response.expires_in);
                this.view.render();
                mediator.trigger('change:systemMailBox:email', {email: response.email_address});

                this.requestFormGetFolder();
            }
        },

        requestError: function(response) {
            this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.request'));
            this.view.render();
        },

        /**
         * Request to server to get template with button Retrieve Folders
         */
        requestFormGetFolder: function() {
            var data = this.view.getData();
            data.formParentName = this.formParentName;
            mediator.execute('showLoading');

            $.ajax({
                url: this.getUrl(),
                method: 'POST',
                data: data,
                success: _.bind(this.renderFormGetFolder, this),
                error:  _.bind(this.requestError, this)
            });
        },

        /**
         * Hendler response from server for request to get template with button Retrieve Folders
         * @param response
         */
        renderFormGetFolder: function(response) {
            if (response.error !== undefined) {
                this.view.setErrorMessage(response.error);
                this.view.render();
                mediator.execute('hideLoading');
            } else if (response.html === undefined) {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.request'));
                this.view.render();
                mediator.execute('hideLoading');
            } else {
                this.view.setHtml(response.html);
                this.view.render();
                this.view.autoRetrieveFolders();
            }
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
                success: _.bind(this.handlerGetFolders, this),
                error: _.bind(this.requestError, this)
            });
        },

        /**
         * Handler response from server to get folders
         * @param response
         */
        handlerGetFolders: function(response) {
            if (response.error !== undefined) {
                this.view.setErrorMessage(response.error);
            } else if (response.html === undefined) {
                this.view.setErrorMessage(__('oro.imap.connection.google.oauth.error.request'));
            } else {
                this.view.setHtml(response.html);
            }
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
                formParentName: this.formParentName,
                id: this.originId
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
         * Generate url for request to get access token
         * @returns {string|*}
         */
        getUrlGetAccessToken: function() {
            return routing.generate(this.routeAccessToken, this._getUrlParams());
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
