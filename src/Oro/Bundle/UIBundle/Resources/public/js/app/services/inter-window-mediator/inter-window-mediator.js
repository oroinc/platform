/**
 * The mediator provides listening and triggering event through all browser windows/tabs
 * on the same URL domain.
 *
 * Based on `storage` event that fires on window when some property of localStorage was changed.
 */
define(function(require) {
    'use strict';

    const BaseClass = require('oroui/js/base-class');

    const InterWindowMediator = BaseClass.extend({
        /**
         * @inheritdoc
         */
        constructor: function InterWindowMediator() {
            this.onStorageChange = this.onStorageChange.bind(this);

            InterWindowMediator.__super__.constructor.call(this);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            InterWindowMediator.__super__.initialize.call(this, options);

            this.id = (Math.random().toString() + Date.now()).substr(2);
            window.addEventListener('storage', this.onStorageChange);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            window.removeEventListener('storage', this.onStorageChange);

            InterWindowMediator.__super__.dispose.call(this);
        },

        /**
         * Triggers the event in rest of browser tabs
         * tabs communication is implemented over storage event
         *
         * @param {string} eventName
         * @param {...(Object|Array|number|string|boolean|null}} - optional data that will be passed to a handler as arguments
         */
        trigger: function(eventName, args) {
            const eventData = {targetId: this.id, args};
            const storageKey = InterWindowMediator.NS + eventName;
            localStorage.setItem(storageKey, JSON.stringify(eventData));
            localStorage.removeItem(storageKey);
        },

        /**
         * Handles storage and triggers local event
         *
         * @param e
         */
        onStorageChange: function(e) {
            if (
                e.key.substring(0, InterWindowMediator.NS.length) === InterWindowMediator.NS &&
                e.newValue !== null && e.newValue !== ''
            ) {
                const eventName = e.key.substring(InterWindowMediator.NS.length);
                const eventData = JSON.parse(e.newValue);

                // Since IE11 triggers `storage` event on current window lets check and skip it
                if (eventData.targetId !== this.id) {
                    // triggers the event over original Backbone.Event.trigger method to execute all inner handlers
                    InterWindowMediator.__super__.trigger.apply(this, [eventName].concat(eventData.args));
                }
            }
        }
    }, {
        NS: 'inter-window-mediator:'
    });

    return InterWindowMediator;
});
