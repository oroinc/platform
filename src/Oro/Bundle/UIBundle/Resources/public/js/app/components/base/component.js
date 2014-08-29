/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'chaplin'
], function (_, Backbone, Chaplin) {
    'use strict';

    var BaseComponent;

    // base component's constructor
    BaseComponent = function (options) {
        this.cid = _.uniqueId('component');
        this.initialize(options);
    };

    // defines static methods
    _.extend(BaseComponent, {
        /**
         * Creates component,
         * if it has defer property (means it'll be initialized in async way) -- returns promise object
         *
         * @param {Object} options
         * @returns {BaseComponent|promise}
         */
        init: function (options) {
            var Component, result;
            Component = this;
            result = new Component(options);
            if (result.defer) {
                result = result.defer.promise();
            }
            return result;
        },

        /**
         * Takes from Backbone standard extend method
         * to provide inheritance for Components
         */
        extend: Backbone.Model.extend
    });

    // defines prototype properties and  methods
    _.extend(BaseComponent.prototype, Backbone.Events, Chaplin.EventBroker, {
        /**
         * Defer object, helps to notify environment that component is initialized
         * in case it work in asynchronous way
         */
        defer: null,

        /**
         * Flag shows if the component is disposed or not
         */
        disposed: false,

        /**
         * Runs initialization logic
         *
         * @param {Object=} options
         */
        initialize: function (options) {
            // should be defined in descendants
        },

        /**
         * Disposes the component
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.trigger('dispose', this);
            this.unsubscribeAllEvents();
            this.stopListening();
            this.off();
            // disposes registered sub-components
            _.each(this.subComponents || [], function (component) {
                if (component && typeof component.dispose === 'function') {
                    component.dispose();
                }
            });
            // dispose and remove all own properties
            _.each(this, function (item, name) {
                if (item && typeof item.dispose === "function") {
                    item.dispose();
                }
                delete this[name];
            }, this);
            this.disposed = true;
            return typeof Object.freeze === "function" ? Object.freeze(this) : void 0;
        }
    });

    return BaseComponent;
});
