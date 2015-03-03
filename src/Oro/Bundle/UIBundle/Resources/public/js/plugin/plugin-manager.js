define(function (require) {
    'use strict';

    var BasePlugin = require('./base');

    function PluginManager(main) {
        this.main = main;
        this.pluginList = [];
    }

    PluginManager.prototype = {
        /**
         * Keeps plugin instances
         */
        pluginList: null,

        getInstance: function (Constructor) {
            var item = this.getItem(Constructor);
            if (!item) {
                return null;
            }
            return item.instance;
        },

        getItem: function (Constructor) {
            var i, item;
            for (i = 0; i < this.pluginList.length; i++) {
                item = this.pluginList[i];
                if (item.constructor === Constructor) {
                    return item;
                }
            }
            return null;
        },

        registerInstance: function (Constructor, instance) {
            if (this.getInstance(Constructor) !== null) {
                throw new Error('Plugin is already instantiated');
            }
            this.pluginList.push({
                constructor: Constructor,
                instance: instance
            });
        },

        enable: function (Constructor, options) {
            var instance,
                item = this.getItem(Constructor);
            if (!(Constructor.prototype instanceof BasePlugin)) {
                throw new Error('Constructor must be a child of BasePlugin');
            }
            if (item === null) {
                instance = new Constructor(this.main, options);
                this.registerInstance(Constructor, instance);
                item = this.getItem(Constructor);
            }
            if (!item.enabled) {
                item.instance.enable();
                item.enabled = true;
            }
        },

        disable: function (Constructor) {
            var item = this.getItem(Constructor);
            if (item === null) {
                // nothing to do
                return;
            }
            if (item.enabled) {
                item.instance.disable();
                item.enabled = false;
            }
        },

        disableAll: function () {
            var item, i;
            for (i = 0; i < this.pluginList.length; i++) {
                item = this.pluginList[i];
                if (item.enabled) {
                    item.instance.disable();
                    item.enabled = false;
                }
            }
        },

        dispose: function () {
            var item, i;
            for (i = 0; i < this.pluginList.length; i++) {
                item = this.pluginList[i];
                if (item.enabled) {
                    item.instance.disable();
                    item.enabled = false;
                }
                item.instance.dispose();
            }
            this.pluginList = [];
        }
    };

    return PluginManager;
});
