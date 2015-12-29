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

        onSetToken: function(value) {
            $.ajax({
                url : this.url,
                method: "GET",
                data: {
                    'type': 'Gmail',
                    'accessToken': value.token
                },
                success: _.bind(this.templateLoaded, this)
            });
        },

        onGetFolders: function(value) {
            $.ajax({
                url : this.urlGetFolders,
                method: "POST",
                data: {
                    'accessToken': value.token
                },
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
