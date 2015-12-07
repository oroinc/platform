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

        /**
         * Current route
         *
         * @type {object}
         */
        route: null,

        /**
         * @inheritDoc
         */
        onPageUpdate: function(pageData, actionArgs, jqXHR, promises) {
            PageMainMenuView.__super__.onPageUpdate.apply(this, arguments);
            this.route = actionArgs.route;
        },

        /**
         * Prevents rendering a view without page data
         *
         * @override
         */
        render: function() {
            if (!this.actionArgs || !this.data) {
                return this;
            }

            if (this.route && this.route.previous) {
                // clear container if it is not the first load of page
                this.$el.empty();
            }

            // does not show messages from cache
            if (this.actionArgs.options.fromCache !== true) {
                this._addMessages(this.data.flashMessages);
            }

            return this;
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
