define(function() {
    'use strict';

    var SingletonService;

    /**
     * Helper service for emulating singleton behaviour.
     */
    SingletonService = {
        /**
         * @property {Object}
         */
        instances: {},

        /**
         *
         * @param {string} key
         * @param {function} Class
         * @param {mixed} options
         * @return {Object}
         */
        getInstance: function(key, Class, options) {
            if (!this.instances[key]) {
                this.instances[key] = new Class(options);
            }

            return this.instances[key];
        }
    };

    return SingletonService;
});
