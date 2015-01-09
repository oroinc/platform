define(function (require) {
    'use strict';

    var componentContainerMixin,
        _ = require('underscore');

    componentContainerMixin = {
        /**
         * Getter/setter for components
         *
         * @param {string} name
         * @param {BaseComponent=} component to set
         */
        pageComponent: function (name, component) {
            if (!this.pageComponents) {
                this.pageComponents = {};
            }
            if (name && component) {
                this.removePageComponent(name);
                this.pageComponents[name] = component;
                component.once('dispose', _.bind(function () {
                    delete this.pageComponents[name];
                }, this));
                return component;
            } else {
                return this.pageComponents[name];
            }
        },

        /**
         * @param {string} name component name to remove
         */
        removePageComponent: function (name) {
            if (!this.pageComponents) {
                this.pageComponents = {};
            }
            var component = this.pageComponents[name];
            if (component) {
                component.dispose();
            }
        },

        /**
         * Destroys all linked page components
         */
        disposePageComponents: function () {
            if (!this.pageComponents) {
                return;
            }
            _.each(this.pageComponents, function (component) {
                component.dispose();
            });
            delete this.pageComponents;
        }
    };

    return componentContainerMixin;
});
