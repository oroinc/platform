define([
    'jquery',
    'underscore',
    'backbone',
    'chaplin'
], function($, _, Backbone, Chaplin) {
    'use strict';

    var BaseComponent;
    var componentOptions = ['model', 'collection', 'name'];

    /**
     * Base component's constructor
     *
     * @export oroui/js/app/components/base/component
     * @class oroui.app.components.base.Component
     */
    BaseComponent = function(options) {
        this.cid = _.uniqueId('component');
        _.extend(this, _.pick(options, componentOptions));
        this.initialize(options);
        this.delegateListeners();
    };

    // defines static methods
    _.extend(BaseComponent, {
        /**
         * Takes from Backbone standard extend method
         * to provide inheritance for Components
         */
        extend: Backbone.Model.extend
    });

    // lends methods from Backbone and Chaplin
    _.extend(
        BaseComponent.prototype,

        // extends BaseComponent.prototype with some Backbone's and Chaplin's functionality
        /** @lends {Backbone.Events} */ Backbone.Events,
        /** @lends {Chaplin.EventBroker} */ Chaplin.EventBroker,

        // lends useful methods Chaplin.View
        _.pick(Chaplin.View.prototype, ['delegateListeners', 'delegateListener'])
    );

    // defines own properties and methods
    _.extend(BaseComponent.prototype, {
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
        initialize: function(options) {
            // should be defined in descendants
        },

        /**
         * Disposes the component
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.trigger('dispose', this);
            this.unsubscribeAllEvents();
            this.stopListening();
            this.off();
            // dispose and remove all own properties
            _.each(this, function(item, name) {
                if (item && typeof item.dispose === 'function' && !item.disposed) {
                    item.dispose();
                }
                if (['cid'].indexOf(name) === -1) {
                    delete this[name];
                }
            }, this);
            this.disposed = true;

            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        },

        /**
         * Create flag of deferred initialization
         *
         * @protected
         */
        _deferredInit: function() {
            this.deferredInit = $.Deferred();
        },

        /**
         * Resolves deferred initialization
         *
         * @protected
         */
        _resolveDeferredInit: function() {
            if (this.deferredInit) {
                this.deferredInit.resolve(this);
            }
        }
    });

    return BaseComponent;
});
