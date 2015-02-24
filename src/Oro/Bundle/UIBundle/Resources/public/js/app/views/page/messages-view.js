/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/messenger',
    './../base/page-region-view'
], function (_, messenger, PageRegionView) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        pageItems: ['flashMessages'],

        /**
         * Prevents rendering a view without page data
         *
         * @override
         */
        render: function () {
            if (!this.actionArgs || !this.data) {
                return this;
            }

            this.$el.empty();

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
        _addMessages: function (messages) {
            _.each(messages, function (messages, type) {
                _.each(messages, function (message) {
                    messenger.notificationFlashMessage(type, message);
                });
            });
        }
    });

    return PageMainMenuView;
});
