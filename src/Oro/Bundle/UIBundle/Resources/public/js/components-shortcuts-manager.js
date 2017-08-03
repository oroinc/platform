define(function() {
    'use strict';

    var ComponentsShortcutsManager;

    ComponentsShortcutsManager = {
        shortcuts: {},

        /**
         * @param {String} key
         * @param {Object} shortcut
         */
        add: function(key, shortcut) {
            if (this.shortcuts[key]) {
                throw new Error('Shortcut `' + key + '` already exists!');
            }

            this.shortcuts[key] = shortcut;
        },

        /**
         * @param {string} key
         */
        remove: function(key) {
            delete this.shortcuts[key];
        },

        /**
         * @returns {shortcuts|{}}
         */
        getAll: function() {
            return this.shortcuts;
        }
    };

    return ComponentsShortcutsManager;
});
