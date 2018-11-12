define(function(require) {
    'use strict';

    var NewEmailMessageComponent;
    var _ = require('underscore');
    var module = require('module');
    var sync = require('orosync/js/sync');
    var BaseComponent = require('oroui/js/app/components/base/component');

    NewEmailMessageComponent = BaseComponent.extend({
        onNewEmailDebounced: null,

        /**
         * @inheritDoc
         */
        constructor: function NewEmailMessageComponent() {
            NewEmailMessageComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var channel = module.config().wsChannel;
            this.onNewEmailDebounced = _.debounce(this.onNewEmail.bind(this, options._sourceElement), 6000, true);
            sync.subscribe(channel, this.onMessage.bind(this));
        },

        /**
         * @param {Array} message
         */
        onMessage: function(message) {
            if (_.result(message, 'hasNewEmail') === true) {
                this.onNewEmailDebounced();
            }
        },

        onNewEmail: function($notificationElement) {
            if ($notificationElement.parent().hasClass('show') === false) {
                $notificationElement.show().delay(5000).fadeOut(1000);
            }
        }
    });

    return NewEmailMessageComponent;
});
