define(['underscore', '../content-manager'], function(_, ContentManager) {
    'use strict';

    var methods = {
        initHandler: function(deferred, metadata) {
            if (metadata.options && _.isArray(metadata.options.contentTags)) {
                ContentManager.tagContent(metadata.options.contentTags);
            }
        }
    };

    return {
        /** @property [] */
        allowedTracking: [],

        /**
         * Adds grid to allowed list
         *
         * @param name
         */
        allowTracking: function(name) {
            if (_.indexOf(this.allowedTracking, name) === -1) {
                this.allowedTracking.push(name);
            }
        },

        /**
         * Builder interface implementation
         *
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         * @param {jQuery} [options.$el] container for the grid
         * @param {string} [options.gridName] grid name
         * @param {Object} [options.gridPromise] grid builder's promise
         * @param {Object} [options.data] data for grid's collection
         * @param {Object} [options.metadata] configuration for the grid
         */
        init: function(deferred, options) {
            if (_.indexOf(this.allowedTracking, options.gridName) !== -1) {
                methods.initHandler(deferred, options.metadata);
            }
            deferred.resolve();
        }
    };
});
