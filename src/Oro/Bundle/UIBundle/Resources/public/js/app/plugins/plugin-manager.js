define(function (require) {
    'use strict';

    var BasePlugin = require('./base/plugin');

    function PluginManager(main) {
        this.main = main;
        this._pluginList = [];
    }

    PluginManager.prototype = {
        /**
         * Contains plugin instances
         */
        _pluginList: null,

        /**
         * Returns internal plugin representation
         *
         * @param {Function} Constructor Plugin constructor
         * @returns {Object}
         */
        getInstance: function (Constructor) {
            var i, instance;
            for (i = 0; i < this._pluginList.length; i++) {
                instance = this._pluginList[i];
                if (instance instanceof Constructor) {
                    return instance;
                }
            }
            return null;
        },

        /**
         * Creates plugin, also it is a way to update options
         *
         * @param {Function} Constructor Plugin constructor
         * @param {Object=} options
         * @param {boolean=} update
         */
        create: function (Constructor, options, update) {
            if (!(Constructor.prototype instanceof BasePlugin)) {
                throw new Error('Constructor must be a child of BasePlugin');
            }
            var reenable = false,
                instance = this.getInstance(Constructor);
            if (instance !== null) {
                if (!update) {
                    throw new Error('Plugin is already instantiated');
                }
                reenable = instance.enabled;
                this.remove(Constructor);
            }
            instance = new Constructor(this.main, this, options);
            if (reenable) {
                instance.enable();
            }
            this._pluginList.push(instance);
            return instance;
        },

        /**
         * Update options for plugin
         *
         * @param {Function} Constructor Plugin constructor
         * @param {Object} options
         */
        updateOptions: function (Constructor, options) {
            var instance = this.getInstance(Constructor);
            if (!instance) {
                throw new Error('Plugin is not instantiated yet');
            }
            this.create(Constructor, options, true);
        },

        /**
         * Removes plugin
         *
         * @param {Function} Constructor Plugin constructor
         */
        remove: function (Constructor) {
            var instance = this.getInstance(Constructor);
            if (instance === null) {
                throw new Error('Plugin is not instantiated yet');
            }
            if (instance.enabled) {
                instance.disable();
            }
            instance.dispose();
            this._pluginList.splice(this._pluginList.indexOf(instance), 1);
        },

        /**
         * Creates and enables plugin
         *
         * @param {Function|Array} Constructor Plugin constructor or array of constructors
         */
        enable: function (Constructor) {
            if (_.isArray(Constructor)) {
                _.each(Constructor, _.bind(this.enable, this));
                return;
            }
            if (!(Constructor.prototype instanceof BasePlugin)) {
                throw new Error('Constructor must be a child of BasePlugin');
            }
            var instance = this.getInstance(Constructor);
            if (instance === null) {
                instance = this.create(Constructor);
            }
            if (!instance.enabled) {
                instance.enable();
            }
        },

        /**
         * Disables plugin
         *
         * @param {Function|Array} Constructor Plugin constructor or array of constructors
         */
        disable: function (Constructor) {
            if (_.isArray(Constructor)) {
                _.each(Constructor, _.bind(this.disable, this));
                return;
            }
            var instance = this.getInstance(Constructor);
            if (instance === null) {
                // nothing to do
                return;
            }
            if (instance.enabled) {
                instance.disable();
            }
        },

        /**
         * Disables all connected plugins
         */
        disableAll: function () {
            var instance, i;
            for (i = 0; i < this._pluginList.length; i++) {
                instance = this._pluginList[i];
                if (instance.enabled) {
                    instance.disable();
                }
            }
        },

        dispose: function () {
            var instance, i;
            for (i = 0; i < this._pluginList.length; i++) {
                instance = this._pluginList[i];
                if (instance.enabled) {
                    instance.disable();
                }
                instance.dispose();
            }
            this._pluginList = [];
        }
    };

    return PluginManager;
});
