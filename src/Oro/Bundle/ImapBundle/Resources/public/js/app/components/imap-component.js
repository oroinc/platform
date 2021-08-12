define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const scriptjs = require('scriptjs');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');

    const ImapComponent = BaseComponent.extend({

        /** @property {Array|String[]} */
        scopes: [],

        /** @property {String} */
        type: '',

        /** @property {String|Null} */
        scriptPath: null,

        /** @property {Object} */
        errorMessages: {},

        /**
         * @inheritdoc
         */
        constructor: function ImapComponent(options) {
            ImapComponent.__super__.constructor.call(this, options);
        },

        /**
         * Returns message with certain namespace for proper type
         *
         * @param {String} key
         */
        getErrorMessage: function(key) {
            return __(this.errorsMessages[key] || key);
        },

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

            const viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);

            this.view.setAccessToken(_.result(options, 'accessToken') || '');
            this.view.setExpiredAt(_.result(options, 'accessTokenExpiresAt') || '');
            this.view.setEmail(_.result(options, 'user') || '');

            this.listenTo(this.view, 'getFolders', this.onGetFolders);

            if (this.scriptPath) {
                scriptjs(this.scriptPath, function() {
                    this.listenTo(this.view, 'checkConnection', this.onCheckConnection);
                }.bind(this));
            } else {
                this.listenTo(this.view, 'checkConnection', this.onCheckConnection);
            }

            ImapComponent.__super__.initialize.call(this, options);
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
                vendorErrorMessage: options.vendorErrorMessage,
                type: this.type
            };
        },

        /**
         * Handler event checkConnection
         */
        onCheckConnection: function() {
            this.view.resetErrorMessage();
            this.requestAuthCode();
        },

        /**
         * Request to API to get auth code
         *
         * @param {String} emailAddress
         */
        requestAuthCode: function(emailAddress) {
            throw new Error('Component for type `' + this.type + '`has no auth code provider method implemented!');
        },

        /**
         * Handler response from google API for request to get google auth code
         */
        handleResponseAuthCode: function(response) {
            if (response.error === 'access_denied') {
                this.view.setErrorMessage(this.getErrorMessage('access_deny'));
                this.view.render();
            } else {
                mediator.execute('showLoading');
                this.requestAccessToken(response.code);
            }
        },

        /**
         * Request to ORO backend to get token
         *
         * @param {String|null|Boolean} code
         *
         */
        requestAccessToken: function(code) {
            $.ajax({
                url: this.getUrlGetAccessToken(),
                method: 'POST',
                data: (code && String(code).length) ? {code: code} : {},
                success: this.prepareAuthorization.bind(this),
                errorHandlerMessage: false,
                error: this.requestError.bind(this)
            });
        },

        /**
         * Handler response for request to get token
         */
        prepareAuthorization: function(response) {
            if (response.error !== undefined) {
                this.view.setErrorMessage(response.error);
                this.view.render();
                mediator.execute('hideLoading');
            } else if (response.code !== undefined && response.code >= 400) {
                this.view.setErrorMessage(response.message || this.getErrorMessage('request'));
                this.view.render();
                mediator.execute('hideLoading');
                if (response.code === 401) {
                    mediator.execute(
                        'showFlashMessage',
                        'error',
                        this.getErrorMessage('closed_auth')
                    );
                }
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
            this.view.setErrorMessage(this.getErrorMessage('request'));
            this.view.render();
        },

        /**
         * Request to server to get template with button Retrieve Folders
         */
        requestFormGetFolder: function() {
            const data = this.view.getData();
            data.formParentName = this.formParentName;
            mediator.execute('showLoading');

            $.ajax({
                url: this.getUrl(),
                method: 'POST',
                data: data,
                success: this.renderFormGetFolder.bind(this),
                errorHandlerMessage: false,
                error: this.requestError.bind(this)
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
                this.view.setErrorMessage(this.getErrorMessage('request'));
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
            const data = this.prepareDataForForm(value);
            mediator.execute('showLoading');

            $.ajax({
                url: this.getUrlGetFolders(),
                method: 'POST',
                data: data,
                success: this.handlerGetFolders.bind(this),
                errorHandlerMessage: false,
                error: this.requestError.bind(this)
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
                this.view.setErrorMessage(this.getErrorMessage('request'));
            } else {
                this.view.setHtml(response.html);
            }
            this.view.render();
            mediator.execute('hideLoading');
        },

        /**
         * Wrap data from view in form for request to server
         * @param values
         * @returns {Object}
         */
        prepareDataForForm: function(values) {
            throw new Error('Component for type `' + this.type + '`has to have this method implemented!');
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

    return ImapComponent;
});
