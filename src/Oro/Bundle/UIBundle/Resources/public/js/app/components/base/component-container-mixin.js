define(function(require) {
    'use strict';

    var componentContainerMixin;
    var ComponentManager = require('oroui/js/app/components/component-manager');

    componentContainerMixin = {
        /**
         * @returns {jQuery}
         */
        getLayoutElement: function() {
            throw Error('"getLayoutElement" method have to be defined in the component container');
        },

        /**
         * Getter for component manager
         *
         * @returns {ComponentManager}
         */
        _getComponentManager: function() {
            if (!this.componentManager) {
                this.componentManager = new ComponentManager(this.getLayoutElement());
            }
            return this.componentManager;
        },

        /**
         * Getter/setter for components
         *
         * @param {string} name
         * @param {BaseComponent=} component to set
         * @param {HTMLElement=} el
         */
        pageComponent: function(name, component, el) {
            if (this.disposed) {
                component.dispose();
                return;
            }

            if (name && component) {
                if (!el) {
                    throw Error('The element related to the component is required');
                }
                return this._getComponentManager().add(name, component, el);
            } else {
                return this._getComponentManager().get(name);
            }
        },

        /**
         * @param {string} name component name to remove
         */
        removePageComponent: function(name) {
            this._getComponentManager().remove(name);
        },

        /**
         * Applies callback function to all component
         *
         * @param {Function} callback
         * @param {Object?} context
         */
        forEachComponent: function(callback, context) {
            this._getComponentManager().forEachComponent(callback, context || this);
        },

        /**
         * Initializes all linked page components
         * @param {Object|null} options
         */
        initPageComponents: function(options) {
            return this._getComponentManager().init(options);
        },

        /**
         * Destroys all linked page components
         */
        disposePageComponents: function() {
            if (this.disposed) {
                return;
            }
            if (this.componentManager) {
                this._getComponentManager().dispose();
                delete this.componentManager;
            }
        }
    };

    return componentContainerMixin;
});
