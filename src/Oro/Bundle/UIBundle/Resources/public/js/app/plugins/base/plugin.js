define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');

    const delegateEventSplitter = /^(\S+)\s*(.*)$/;

    function BasePlugin(main, manager, options) {
        this.cid = _.uniqueId(this.cidPrefix);
        this.main = main;
        this.manager = manager;
        this.options = options;
        this.initialize(main, options);
    }

    _.extend(BasePlugin.prototype, Backbone.Events, {
        cidPrefix: 'plugin',

        /**
         * Constructor
         *
         * @param main {Object} object this plugin attached to
         * @param options {object=}
         */
        initialize: function(main, options) {},

        /**
         * Delegated event handlers to the element of the main. The same way as Backbone.View does
         * @see https://backbonejs.org/#View-delegateEvents
         * @return {BasePlugin}
         */
        delegateEvents() {
            const events = _.result(this, 'events', {});
            this.undelegateEvents();
            for (let [key, method] of Object.entries(events)) {
                if (typeof method === 'string') {
                    method = this[method];
                }
                if (!method) {
                    continue;
                }
                const [, event, selector] = key.match(delegateEventSplitter);
                this.main.$el.on(`${event}${this.eventNamespace()}`, selector, method.bind(this));
            }
            return this;
        },

        /**
         * Removes event handlers for the element of the main. The same way as Backbone.View does
         * @see https://backbonejs.org/#View-undelegateEvents
         * @return {BasePlugin}
         */
        undelegateEvents() {
            this.main.$el.off(this.ownEventNamespace());
            return this;
        },

        eventNamespace: function() {
            return this.main.eventNamespace() + this.ownEventNamespace();
        },

        ownEventNamespace: function() {
            return this.main.eventNamespace.call(this);
        },

        /**
         * Enables plugin
         */
        enable: function() {
            this.enabled = true;
            this.trigger('enabled');
        },

        /**
         * Disables plugin
         */
        disable: function() {
            this.enabled = false;
            this.stopListening();
            this.trigger('disabled');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.trigger('disposed');
            this.off();
            this.stopListening();
            for (const prop in this) {
                if (this.hasOwnProperty(prop)) {
                    delete this[prop];
                }
            }
            this.disposed = true;
            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        }
    });

    BasePlugin.extend = Backbone.Model.extend;

    return BasePlugin;
});
