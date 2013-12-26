/* global define */
define(['underscore', 'oro/content-manager'], function (_, ContentManager) {
    'use strict';

    var initialized = false,
        methods = {
            initHandler: function ($el) {
                this.metadata = $el.data('metadata') || {};

                if (!_.isUndefined(this.metadata.options) && _.isArray(this.metadata.options.contentTags || [])) {
                    ContentManager.tagContent(this.metadata.options.contentTags);
                }

                initialized = true;
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
        allowTracking: function (name) {
            if (_.indexOf(this.allowedTracking, name) === -1) {
                this.allowedTracking.push(name);
            }
        },

        /**
         * Builder interface implementation
         *
         * @param $el
         * @param {String} gridName
         */
        init: function ($el, gridName) {
            if (_.indexOf(this.allowedTracking, gridName) !== -1) {
                methods.initHandler($el);
            }
        }
    };
});
