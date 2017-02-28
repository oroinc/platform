define(function(require) {
    'use strict';

    var ActionManager;
    var _ = require('underscore');
    var error = require('oroui/js/error');

    /**
     * Actions manager, can stored action views, and share for all jstree
     *
     * @type {Object}
     */
    ActionManager = {
        /**
         * Actions stack
         *
         * @property {Object}
         */
        actions: {},

        /**
         * Register actions
         *
         * @param {String} name
         * @param {Object} action
         */
        addAction: function(name, action) {
            if (!_.isObject(action) || !action.hasOwnProperty('view')) {
                return error.showErrorInConsole('"view" and "name" property is required, please define');
            }

            if (!_.isFunction(action.view)) {
                return error.showErrorInConsole('"view" should be constructor function');
            }

            _.defaults(action, {
                name: name,
                isAvailable: function() {
                    return true;
                }
            });

            this.actions[action.name] = action;
        },

        /**
         *
         * Get actions for current jstree
         *
         * @param {Object} options
         * @return {Array}
         */
        getActions: function(options) {
            return _.filter(this.actions, function(action) {
                return action.isAvailable(options);
            }, this);
        }
    };

    return ActionManager;
});
