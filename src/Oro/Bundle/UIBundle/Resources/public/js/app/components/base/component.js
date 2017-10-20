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
        _.extend(this, options[BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME]);
        this.initialize(options);
        this.delegateListeners();
    };

    // defines static methods
    _.extend(BaseComponent, {
        /**
         * The component may have a dependency on other components of the same componentManager (siblingComponents)
         * Dependencies can be declared in the components's prototype as `relatedSiblingComponents` property
         *      relatedSiblingComponents: {
         *          builder: 'condition-builder',
         *          grid: 'account-grid'
         *      },
         * With the object where:
         *  - keys are properties where related components instances will be assigned
         *  - values are names of components in the componentManager
         *
         * Names can be changed over components options
         *      new MyComponent({
         *          relatedSiblingComponents: {
         *              grid: 'my-account-grid'
         *          }
         *      });
         */
        RELATED_SIBLING_COMPONENTS_PROPERTY_NAME: 'relatedSiblingComponents',

        /**
         * Takes from Backbone standard extend method
         * to provide inheritance for Components
         */
        extend: Backbone.Model.extend,

        /**
         * Collects dependency definition from component's prototype chain
         *
         * @param {Function} Component constructor of a component
         * @return {Object.<string, string>} where key is internal name for component's instance,
         *                                  value is component's name in componentManager
         * @static
         */
        getRelatedSiblingComponentNames: function(Component) {
            var PROP = BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME;
            var dependencies = Chaplin.utils.getAllPropertyVersions(Component.prototype, PROP);
            dependencies.push(_.result(Component.prototype, PROP));
            dependencies = _.extend.apply(null, [{}].concat(dependencies));

            // remove dependencies without componentName
            // (the name was falsified in descendant component definition, means is doesn't require it anymore)
            _.each(dependencies, function(componentName, dependencyName) {
                if (!componentName) {
                    delete dependencies[dependencyName];
                }
            });

            return dependencies;
        }
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
        AUXILIARY_OPTIONS: ['_sourceElement', '_subPromises', 'name'],

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
            var siblingComponents = _.keys(BaseComponent.getRelatedSiblingComponentNames(this.constructor));

            // dispose and remove all own properties
            _.each(this, function(item, name) {
                if (componentOptions.indexOf(name) !== -1 || siblingComponents.indexOf(name) !== -1) {
                    /**
                     * Do not dispose auto-assigned props, that were passed over options or sibling components.
                     * Just delete a reference.
                     * Parent view or component have to take care of them, to dispose them properly.
                     */
                    delete this[name];
                    return;
                }
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
        },

        /**
         * Reject deferred initialization
         *
         * @protected
         */
        _rejectDeferredInit: function(error) {
            if (this.deferredInit) {
                if (error) {
                    this.deferredInit.reject(error);
                } else {
                    this.deferredInit.reject();
                }
            }
        }
    });

    return BaseComponent;
});
