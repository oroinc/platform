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
        trackGrids: [],

        /**
         * Adds grid to track list
         *
         * @param name
         */
        addGridTrack: function (name) {
            if (_.indexOf(this.trackGrids, name) === -1) {
                this.trackGrids.push(name);
            }
        },

        /**
         * Builder interface implementation
         *
         * @param $el
         * @param {String} gridName
         */
        init: function ($el, gridName) {
            if (_.indexOf(this.trackGrids, gridName) !== -1) {
                methods.initHandler($el);
            }
        }
    };
});
