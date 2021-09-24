define(function(require, exports, module) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const messenger = require('oroui/js/messenger');
    const _ = require('underscore');
    let config = require('module-config').default(module.id);

    config = _.extend({
        template: require('tpl-loader!oroui/templates/message-item.html') // default admin template
    }, config);

    const FlashMessageView = BaseView.extend({
        autoRender: true,

        template: config.temlpate,

        optionNames: BaseView.prototype.optionNames.concat(['container', 'messages', 'initializeMessenger']),

        initializeMessenger: false,

        /**
         * @inheritdoc
         */
        constructor: function FlashMessageView(options) {
            FlashMessageView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (this.initializeMessenger) {
                this._initializeMessenger();
            }

            return FlashMessageView.__super__.initialize.call(this, options);
        },

        /**
         * Initialize messenger
         */
        _initializeMessenger: function() {
            const options = {
                container: this.container
            };

            if (this.temlpate) {
                options.temlpate = this.template;
            }

            messenger.setup(options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            _.each(this.messages, function(message) {
                messenger.notificationFlashMessage(message.type, message.message, message.options);
            });

            this.messages = [];

            return FlashMessageView.__super__.render.call(this);
        }
    });

    return FlashMessageView;
});
