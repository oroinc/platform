/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'chaplin',
    './component-container-mixin'
], function (_, Backbone, Chaplin, componentContainerMixin) {
    'use strict';

    var BaseComponent, componentOptions;

    componentOptions = ['model', 'collection', 'parent', 'name'];

    /**
     * Base component's constructor
     *
     * @export oroui/js/app/components/base/component
     * @class oroui.app.components.base.Component
     */
    BaseComponent = function (options) {
        var $sourceElement = options._sourceElement;
        this.cid = _.uniqueId('component');
        _.extend(this, _.pick(options, componentOptions));
        if (this.parent) {
            this.parent.pageComponent(this.name || this.cid, this);
        }
        if ($sourceElement) {
            $sourceElement.data('componentInstance', this);
            this.on('dispose', function () {
                $sourceElement.removeData('componentInstance');
            });
        }
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
         * @returns {jQuery.Promise}
         */
        init: function (options) {
            var Component, component, deferredInit;
            Component = this;
            component = new Component(options);
            deferredInit = component.deferredInit || $.Deferred().resolve(component);
            return deferredInit.promise();
        },

        /**
         * Takes from Backbone standard extend method
         * to provide inheritance for Components
         */
        extend: Backbone.Model.extend
    });

    _.extend(
        BaseComponent.prototype,

        // component can hold other components
        componentContainerMixin,

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
            this.disposePageComponents();
            this.trigger('dispose', this);
            this.unsubscribeAllEvents();
            this.stopListening();
            this.off();
            // dispose and remove all own properties
            _.each(this, function (item, name) {
                if (item && name !== 'parent' && typeof item.dispose === 'function') {
                    item.dispose();
                }
                delete this[name];
            }, this);
            this.disposed = true;

            // remove link to parent
            delete this.parent;

            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
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
        },

        delegateListener: function (eventName, target, callback) {
            var prop;
            if (target === 'mediator') {
                this.subscribeEvent(eventName, callback);
            } else if (!target) {
                this.on(eventName, callback, this);
            } else {
                prop = this[target];
                if (prop) {
                    this.listenTo(prop, eventName, callback);
                }
            }
        }
    });

    return BaseComponent;
});
