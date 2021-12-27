define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const accountTypeView = require('oroimap/js/app/views/account-type-view');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');

    const AccountTypeComponent = BaseComponent.extend({
        ViewType: accountTypeView,

        /**
         * @inheritdoc
         */
        constructor: function AccountTypeComponent(options) {
            AccountTypeComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.route = _.result(options, 'route') || '';
            this.formParentName = _.result(options, 'formParentName') || '';
            this.originId = _.result(options, 'id') || null;

            const viewConfig = this.prepareViewOptions(options);
            this.view = new this.ViewType(viewConfig);
            this.view.render();

            this.listenTo(this.view, 'imapConnectionChangeType', this.onChangeAccountType);
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
                url: this.getUrl(),
                method: 'POST',
                data: {
                    type: value,
                    formParentName: this.formParentName,
                    id: this.originId
                },
                success: this.templateLoaded.bind(this)
            });
        },

        onIMapGotToken: function(value) {
            mediator.execute('showLoading');
            $.ajax({
                url: this.getUrl(),
                method: 'POST',
                data: {
                    type: value.type,
                    token: value.token
                },
                success: this.templateLoaded.bind(this)
            });
        },

        /**
         * Handler response
         * @param {Object} response - contain`s html of new form
         */
        templateLoaded: function(response) {
            mediator.execute('hideLoading');
            this.view.setHtml(response.html).render();
        },

        /**
         * Generate url for requests
         * @returns {string|*}
         */
        getUrl: function() {
            return routing.generate(this.route, this._getUrlParams());
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

    return AccountTypeComponent;
});
