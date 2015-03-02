define(function (require) {
    'use strict';
    var mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools');

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
            var i, record;
            for (i = 0; i < this.pluginList.length; i++) {
                record = this.pluginList[i];
                if (record.constructor === Constructor) {
                    return record.instance;
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
            var instance = this.getInstance(Constructor);
            if (instance === null) {
                instance = new Constructor(this.main, options);
                this.registerInstance(Constructor, instance);
            }
            if (!instance.enabled) {
                instance.enable();
                instance.enabled = true;
            }
        },

        disable: function (Constructor) {
            var instance = this.getInstance(Constructor);
            if (instance === null) {
                // nothing to do
                return;
            }
            if (instance.enabled) {
                instance.disable();
                instance.enabled = false;
            }
        },

        disableAll: function () {
            var instance;
            for (var i = 0; i < this.pluginList.length; i++) {
                instance = this.pluginList[i].instance;
                if (instance.enabled) {
                    instance.disable();
                }
            }
        }
    };

    return PluginManager;
});
