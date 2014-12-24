/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'chaplin'
], function (_, Backbone, Chaplin) {
    'use strict';

    var BaseComponent, componentOptions;

    componentOptions = ['model', 'collection', '_sourceElement'];

    /**
     * Base component's constructor
     *
     * @export oroui/js/app/components/base/component
     * @class oroui.app.components.base.Component
     */
    BaseComponent = function (options) {
        this.cid = _.uniqueId('component');
        if (options._sourceElement) {
            options._sourceElement.data('component', this);
        }
        _.extend(this, _.pick(options, componentOptions));
        this.initialize(options);
        this.delegateListeners();
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
            if (result.deferredInit) {
                result = result.deferredInit.promise();
            }
            return result;
        },

        /**
         * Takes from Backbone standard extend method
         * to provide inheritance for Components
         */
        extend: Backbone.Model.extend
    });

    _.extend(
        BaseComponent.prototype,

        // extends BaseComponent.prototype with some Backbone's and Chaplin's functionality
        /** @lends {Backbone.Events} */ Backbone.Events,
        /** @lends {Chaplin.EventBroker} */ Chaplin.EventBroker,
        // lends useful methods Chaplin.View
        _.pick(Chaplin.View.prototype, ['delegateListeners', 'delegateListener']), {

        // defines own properties and methods
        /**
         * Defer object, helps to notify environment that component is initialized
         * in case it work in asynchronous way
         */
        deferredInit: null,

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
        },

        /**
         * Create flag of deferred initialization
         *
         * @protected
         */
        _deferredInit: function () {
            this.deferredInit = $.Deferred();
        },

        /**
         * Resolves deferred initialization
         *
         * @protected
         */
        _resolveDeferredInit: function () {
            if (this.deferredInit) {
                this.deferredInit.resolve(this);
            }
        }
    });

    return BaseComponent;
});
