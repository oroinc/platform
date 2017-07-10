define([
    'underscore',
    'oroui/js/messenger',
    './../base/page-region-view'
], function(_, messenger, PageRegionView) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        /**
         * @type {Array}
         */
        pageItems: ['flashMessages'],

        listen: {
            'page:afterChange mediator': 'onPageAfterChange'
        },

        /**
         * Current route
         *
         * @type {object}
         */
        route: null,

        /**
         * @inheritDoc
         */
        render: function() {
            return this;
        },

        /**
         * @inheritDoc
         */
        onPageUpdate: function(pageData, actionArgs, jqXHR, promises) {
            this.data = _.pick(pageData, this.pageItems);
            this.actionArgs = actionArgs;
            this.route = actionArgs.route;
        },

        /**
         * Shows messages once page is ready to use
         */
        onPageAfterChange: function() {
            if (this.route && this.route.previous) {
                // clear container if it is not the first load of page
                this.$el.empty();
            }

            // process messages stored in queue or storage
            messenger.flushStoredMessages();

            // process messages from page data (if the page is not from cache)
            if (this.data && this.actionArgs && this.actionArgs.options.fromCache !== true) {
                this._addMessages(this.data.flashMessages);
            }

            this.data = null;
            this.actionArgs = null;
            this.route = null;
        },

        /**
         * Add session messages
         *
         * @param {Object} messages
         */
        _addMessages: function(messages) {
            _.each(messages, function(messages, type) {
                _.each(messages, function(message) {
                    messenger.notificationFlashMessage(type, message);
                });
            });
        }
    });

    return PageMainMenuView;
});
