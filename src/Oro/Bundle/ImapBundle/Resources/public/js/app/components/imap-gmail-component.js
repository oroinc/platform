define(function(require) {
    'use strict';

    var IMapGmailComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var accountTypeView = require('oroimap/js/app/views/imap-gmail-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    IMapGmailComponent = BaseComponent.extend({
        ViewType: accountTypeView,

        scopes: ['https://mail.google.com/'],

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var config = options.configs || {};
            this.url = _.result(options, 'url') || '';
            this.urlGetFolders = _.result(options, 'urlGetFolders') || '';

            var viewConfig = this.prepareViewOptions(options, config);
            this.view = new this.ViewType(viewConfig);

            this.listenTo(this.view, 'imapGmailConnectionSetToken', this.onSetToken);
            this.listenTo(this.view, 'imapGmailConnectionGetFolders', this.onGetFolders);

            require(['//apis.google.com/js/client.js?onload=checkAuth'], _.bind(function() {
                this.listenTo(this.view, 'requestToken', this.requestToken);
            }, this));
        },

        /**
         * Prepares options for the related view
         *
         * @param {Object} options - component's options
         * @param {Object} config - select2's options
         * @return {Object}
         */
        prepareViewOptions: function(options, config) {
            return {
                el: options._sourceElement
            };
        },

        onSetToken: function() {
            this.requestToken();
        },

        requestToken: function() {
            var data = this.view.getData();
            gapi.auth.authorize(
                {
                    'client_id': data.client_id,
                    'scope': this.scopes.join(' '),
                    'immediate': false
                    'response_type': 'code'
                }, _.bind(this.checkAuthorization, this));

            //this.checkAuthorization('1111');
        },

        checkAuthorization: function(result) {
            console.log('checkAuthorization', result);
            this.view.setToken(result.access_token);

            gapi.client.load('gmail', 'v1', _.bind(this.listLabels, this));
        },

        listLabels: function() {
            var request = gapi.client.gmail.users.getProfile({
                'userId': 'me'
            });

            request.execute(_.bind(this.responseEmail, this));
        },

        responseEmail:function(request) {
            console.log('request', request);
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
                success: _.bind(this.templateLoaded, this)
            });
        },

        onGetFolders: function(value) {
            $.ajax({
                url : this.urlGetFolders,
                method: "POST",
                data: value,
                success: _.bind(this.handlerGetFolders, this)
            });
        },

        templateLoaded: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
        },

        handlerGetFolders: function(response) {
            this.view.setHtml(response.html);
            this.view.render();
        }

    });

    return IMapGmailComponent;
});
