/**
 * The mediator provides listening and triggering event through all browser windows/tabs
 * on the same URL domain.
 *
 * Based on `storage` event that fires on window when some property of localStorage was changed.
 */
define(function(require) {
    'use strict';

    var InterWindowMediator;
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');

    InterWindowMediator = BaseClass.extend({
        /**
         * @inheritDoc
         */
        constructor: function InterWindowMediator() {
            this.onStorageChange = this.onStorageChange.bind(this);

            InterWindowMediator.__super__.constructor.call(this);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            InterWindowMediator.__super__.initialize.call(this, options);

            this.id = (Math.random().toString() + Date.now()).substr(2);
            window.addEventListener('storage', this.onStorageChange);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            window.removeEventListener('storage', this.onStorageChange);

            InterWindowMediator.__super__.dispose.call(this);
        },

        /**
         * @param {string} eventName
         * @param {...(Object|Array|number|string|boolean|null}} - optional data that will be passed to a handler as arguments
         */
        trigger: function(eventName) {
            var eventData = {targetId: this.id, args: _.rest(arguments)};
            localStorage.setItem(eventName, JSON.stringify(eventData));
            localStorage.removeItem(eventName);
        },

        onStorageChange: function(e) {
            if (e.newValue !== null && e.newValue !== '') {
                var eventData = JSON.parse(e.newValue);

                // Since IE11 triggers `storage` event on current window lets check and skip it
                if (eventData.targetId !== this.id) {
                    var triggerArguments = [e.key];
                    triggerArguments.push.apply(triggerArguments, eventData.args);

                    InterWindowMediator.__super__.trigger.apply(this, triggerArguments);
                }
            }
        }
    });

    return InterWindowMediator;
});
