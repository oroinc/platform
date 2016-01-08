define(function(require) {
    'use strict';

    var AccountTypeComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var accountTypeView = require('oroimap/js/app/views/account-type-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AccountTypeComponent = BaseComponent.extend({
        ViewType: accountTypeView,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.url = _.result(options, 'url') || '';
            this.formParentName = _.result(options, 'formParentName') || '';

            var viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);

            this.listenTo(this.view, 'imapConnectionChangeType', this.onChangeAccountType);
            this.listenTo(this.view, 'imapConnectionDisconnect', this.onDisconnect);
            this.listenTo(mediator, 'imapGmailConnectionSetToken', this.onIMapGotToken);
        },

        /**
         * Prepares options for the related view
         *
         * @param {Object} options - component's options
         *
         * @return {Object}
         */
        prepareViewOptions: function(options) {
            return {
                el: options._sourceElement
            };
        },

        /**
         * Makes the request to get a form template if account type is changed
         * @param value - values of the form IMAP connection
         */
        onChangeAccountType: function(value) {
            mediator.trigger('change:systemMailBox:email');
            mediator.execute('showLoading');
            $.ajax({
                url: this.url,
                method: 'POST',
                data: {
                    'type': value,
                    'formParentName': this.formParentName
                },
                success: _.bind(this.templateLoaded, this)
            });
        },

        /**
         * Makes the request to get a form template if user clicks to button "Disconnect"
         */
        onDisconnect: function() {
            mediator.execute('showLoading');
            $.ajax({
                url: this.url,
                method: 'POST',
                data: {
                    'formParentName': this.formParentName
                },
                success: _.bind(this.templateLoaded, this)
            });
        },

        onIMapGotToken: function(value) {
            mediator.execute('showLoading');
            $.ajax({
                url: this.url,
                method: 'POST',
                data: {
                    'type': value.type,
                    'token': value.token
                },
                success: _.bind(this.templateLoaded, this)
            });
        },

        /**
         * Handler response
         * @param {Object} response - contain`s html of new form
         */
        templateLoaded: function(response) {
            mediator.execute('hideLoading');
            this.view.setHtml(response.html).render();
        }
    });

    return AccountTypeComponent;
});
