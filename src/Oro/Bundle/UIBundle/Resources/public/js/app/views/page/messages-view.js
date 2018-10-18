define(function(require) {
    'use strict';

    var PageMessagesView;
    var _ = require('underscore');
    var $ = require('jquery');
    var messenger = require('oroui/js/messenger');
    var PageRegionView = require('oroui/js/app/views/base/page-region-view');
    var config = require('module').config();

    config = _.extend({
        template: null // default template is defined in messenger module
    }, config);

    PageMessagesView = PageRegionView.extend({
        optionNames: PageRegionView.prototype.optionNames.concat(['messages', 'initializeMessenger']),

        initializeMessenger: false,

        /**
         * @type {Array}
         */
        pageItems: ['flashMessages'],

        pageIsGoingToReload: false,

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
        constructor: function PageMessagesView(options) {
            PageMessagesView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.initializeMessenger) {
                this._initializeMessenger();
            }

            return PageMessagesView.__super__.initialize.call(this, options);
        },

        /**
         * Initialize messenger
         */
        _initializeMessenger: function() {
            var options = {
                container: this.$el
            };

            if (config.temlpate) {
                options.temlpate = config.template;
            }

            messenger.setup(options);
        },

        delegateEvents: function() {
            PageMessagesView.__super__.delegateEvents.call(this);
            $(window).on('beforeunload' + this.eventNamespace(), this.onBeforePageReload.bind(this));
        },

        undelegateEvents: function() {
            PageMessagesView.__super__.undelegateEvents.call(this);
            $(window).off('beforeunload' + this.eventNamespace());
        },

        /**
         * @inheritDoc
         */
        render: function() {
            _.each(this.messages, function(message) {
                messenger.notificationFlashMessage(message.type, message.message, message.options);
            });

            this.messages = [];

            return PageMessagesView.__super__.render.call(this);
        },

        onBeforePageReload: function() {
            this.pageIsGoingToReload = true;
        },

        /**
         * @inheritDoc
         */
        onPageUpdate: function(pageData, actionArgs, jqXHR, promises) {
            if (this.disposed) {
                return;
            }
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
            var options;
            if (this.pageIsGoingToReload) {
                options = {afterReload: true};
            }
            _.each(messages, function(messages, type) {
                _.each(messages, function(message) {
                    messenger.notificationFlashMessage(type, message, options);
                });
            });
        }
    });

    return PageMessagesView;
});
