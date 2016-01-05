define(function(require) {
    'use strict';

    var IMapGmailComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ImapGmailView = require('oroimap/js/app/views/imap-gmail-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    IMapGmailComponent = BaseComponent.extend({
        ViewType: ImapGmailView,

        scopes: ['https://mail.google.com/'],

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.url = _.result(options, 'url') || '';
            this.urlGetFolders = _.result(options, 'urlGetFolders') || '';

            var viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);

            this.listenTo(this.view, 'imapGmailConnectionSetToken', this.requestToken);
            this.listenTo(this.view, 'imapGmailConnectionGetFolders', this.onGetFolders);

            require(['//apis.google.com/js/client.js?onload=checkAuth'], _.bind(function() {
                this.listenTo(this.view, 'requestToken', this.requestToken);
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
                el: options._sourceElement
            };
        },

        requestToken: function() {
            var data = this.view.getData();
            gapi.auth.authorize(
                {
                    'client_id': data.clientId,
                    'scope': this.scopes.join(' '),
                    'immediate': false
                }, _.bind(this.checkAuthorization, this));

            //this.checkAuthorization({access_token:"11111", expires_at: "1451920751"});
        },

        checkAuthorization: function(result) {
            this.view.setToken(result.access_token);
            this.view.setExpiredAt(result.expires_at);

            this.requestGoogleAuthCode();
        },

        requestGoogleAuthCode: function() {
            var data = this.view.getData();

            gapi.auth.authorize(
                {
                    'client_id': data.clientId,
                    'scope': this.scopes.join(' '),
                    'immediate': false,
                    'access_type': 'offline',
                    'response_type': 'code',
                    'approval_prompt': 'force'
                }, _.bind(this.handleResponseGoogleAuthCode, this));

            //this.handleResponseGoogleAuthCode({code: '1111'});
        },

        handleResponseGoogleAuthCode: function(response) {
            this.view.setGoogleAuthCode(response.code);
            gapi.client.load('gmail', 'v1', _.bind(this.requestProfile, this));

            //this.requestProfile();
        },

        requestProfile: function() {
            var request = gapi.client.gmail.users.getProfile({
                'userId': 'me'
            });

            request.execute(_.bind(this.responseProfile, this));

            //this.responseProfile({emailAddress: "test@test.com"});
            //this.responseProfile({emailAddress: "test1@test.com"});
        },

        responseProfile: function(request) {
            if (request) {
                this.view.setEmail(request.emailAddress);
            }
            this.view.render();
            this.requestFormGetFolder();
        },

        requestFormGetFolder: function() {
            $.ajax({
                url : this.url,
                method: "GET",
                data: this.view.getData(),
                success: _.bind(this.renderFormGetFolder, this)
            });
        },

        renderFormGetFolder: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
        },

        onGetFolders: function(value) {
            delete value.type;
            var data = this.prepareDataForForm(value);
            $.ajax({
                url : this.urlGetFolders,
                method: "POST",
                data: data,
                success: _.bind(this.handlerGetFolders, this)
            });
        },

        prepareDataForForm: function(values) {
            var data = {
                oro_imap_configuration_gmail : {}
            };

            for (var i in values) {
                data.oro_imap_configuration_gmail[i] = values[i];
            }

            return data;
        },

        handlerGetFolders: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
        }
    });

    return IMapGmailComponent;
});
