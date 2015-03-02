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
            var record = this.getRecord(Constructor);
            if (!record) {
                return null;
            }
            return record.instance;
        },

        getRecord: function (Constructor) {
            var i, record;
            for (i = 0; i < this.pluginList.length; i++) {
                record = this.pluginList[i];
                if (record.constructor === Constructor) {
                    return record;
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
                record = this.getRecord(Constructor);
            if (record === null) {
                instance = new Constructor(this.main, options);
                if (!(instance instanceof BasePlugin)) {
                    throw new Error('Constructor must be a child of BasePlugin');
                }
                this.registerInstance(Constructor, instance);
                record = this.getRecord(Constructor);
            }
            if (!record.enabled) {
                record.instance.enable();
                record.enabled = true;
            }
        },

        disable: function (Constructor) {
            var record = this.getRecord(Constructor);
            if (record === null) {
                // nothing to do
                return;
            }
            if (record.enabled) {
                record.instance.disable();
                record.enabled = false;
            }
        },

        disableAll: function () {
            var record, i;
            for (i = 0; i < this.pluginList.length; i++) {
                record = this.pluginList[i];
                if (record.enabled) {
                    record.instance.disable();
                    record.enabled = false;
                }
            }
        },

        dispose: function () {
            var record, i;
            for (i = 0; i < this.pluginList.length; i++) {
                record = this.pluginList[i];
                if (record.enabled) {
                    record.instance.disable();
                    record.enabled = false;
                }
                record.dispose();
            }
            this.pluginList = [];
        }
    };

    return PluginManager;
});
