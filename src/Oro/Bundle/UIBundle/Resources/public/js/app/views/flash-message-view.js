define(function(require) {
    'use strict';

    var FlashMessageView;
    var BaseView = require('oroui/js/app/views/base/view');
    var messenger = require('oroui/js/messenger');
    var _ = require('underscore');
    var config = require('module').config();

    config = _.extend({
        template: require('tpl!oroui/templates/message-item.html') // default admin template
    }, config);

    FlashMessageView = BaseView.extend({
        autoRender: true,

        template: config.temlpate,

        optionNames: BaseView.prototype.optionNames.concat(['container', 'messages', 'initializeMessenger']),

        initializeMessenger: false,

        /**
         * @inheritDoc
         */
        constructor: function FlashMessageView() {
            FlashMessageView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            if (this.initializeMessenger) {
                this._initializeMessenger();
            }

            return FlashMessageView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Initialize messenger
         */
        _initializeMessenger: function() {
            var options = {
                container: this.container
            };

            if (this.temlpate) {
                options.temlpate = this.template;
            }

            messenger.setup(options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            _.each(this.messages, function(message) {
                messenger.notificationFlashMessage(message.type, message.message, message.options);
            });

            this.messages = [];

            return FlashMessageView.__super__.render.apply(this, arguments);
        }
    });

    return FlashMessageView;
});
