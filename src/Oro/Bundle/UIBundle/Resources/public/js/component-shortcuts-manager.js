define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');

    let config = require('module-config').default(module.id);
    config = _.extend({
        reservedKeys: ['options']
    }, config);

    const ComponentShortcutsManager = {
        reservedKeys: config.reservedKeys,

        shortcuts: {},

        /**
         * @param {String} key
         * @param {Object} shortcut
         */
        add: function(key, shortcut) {
            if (this.reservedKeys.indexOf(key) !== -1) {
                throw new Error('Component shortcut `' + key + '` is reserved!');
            }

            if (this.shortcuts[key]) {
                throw new Error('Component shortcut `' + key + '` already exists!');
            }

            const capitalizeKey = _.map(key.split('-'), function(item) {
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
         * @param {Object} shortcut
         * @param {Object} elemData
         * @return {Object}
         */
        getComponentData: function(shortcut, elemData) {
            let dataOptions = elemData[shortcut.dataKey];
            const module = shortcut.moduleName || dataOptions;

            if (!_.isObject(dataOptions)) {
                dataOptions = {};
                if (shortcut.scalarOption) {
                    dataOptions[shortcut.scalarOption] = elemData[shortcut.dataKey];
                }
            }

            const options = $.extend(
                true,
                {},
                shortcut.options,
                dataOptions,
                elemData.pageComponentOptions
            );

            return {
                pageComponentModule: module,
                pageComponentOptions: options
            };
        }
    };

    return ComponentShortcutsManager;
});
