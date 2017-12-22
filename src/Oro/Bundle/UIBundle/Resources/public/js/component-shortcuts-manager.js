define(function(require) {
    'use strict';

    var ComponentShortcutsManager;

    var _ = require('underscore');
    var config = require('module').config();
    config = _.extend({
        reservedKeys: ['options']
    }, config);

    ComponentShortcutsManager = {
        reservedKeys: config.reservedKeys,

        shortcuts: {},

        /**
         * @param {String} key
         * @param {Object} shortcut
         */
        add: function(key, shortcut) {
            var capitalizeKey;

            if (this.reservedKeys.indexOf(key) !== -1) {
                throw new Error('Component shortcut `' + key + '` is reserved!');
            }

            if (this.shortcuts[key]) {
                throw new Error('Component shortcut `' + key + '` already exists!');
            }

            capitalizeKey = _.map(key.split('-'), function(item) {
                return _.capitalize(item);
            }).join('');

            shortcut.dataKey = 'pageComponent' + capitalizeKey;
            shortcut.dataAttr = 'data-page-component-' + key;

            this.shortcuts[key] = shortcut;
        },

        /**
         * @param {string} key
         */
        remove: function(key) {
            delete this.shortcuts[key];
        },

        /**
         * @returns {object}
         */
        getAll: function() {
            return this.shortcuts;
        },

        /**
         * Prepare component data by element attributes and shortcut config
         *
         * @param {object} shortcut
         * @param {object|string} dataOptions
         * @return {object}
         */
        getComponentData: function(shortcut, dataOptions) {
            var module = shortcut.moduleName || dataOptions;

            var options = dataOptions;
            if (!_.isObject(options)) {
                options = {};
                if (shortcut.scalarOption) {
                    options[shortcut.scalarOption] = dataOptions;
                }
            }
            options = _.defaults({}, options, shortcut.options);

            return {
                pageComponentModule: module,
                pageComponentOptions: options
            };
        }
    };

    return ComponentShortcutsManager;
});
