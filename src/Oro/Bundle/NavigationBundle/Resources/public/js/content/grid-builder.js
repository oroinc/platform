/*jslint nomen:true */
/*global define*/
define(['underscore', '../content-manager'], function (_, ContentManager) {
    'use strict';

    var methods = {
        initHandler: function (deferred, $el) {
            this.metadata = $el.data('metadata') || {};
            if (!_.isUndefined(this.metadata.options) && _.isArray(this.metadata.options.contentTags || [])) {
                ContentManager.tagContent(this.metadata.options.contentTags);
            }
            deferred.resolve();
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
         * @param {jQuery.Deferred} deferred
         * @param {jQuery} $el
         * @param {String} gridName
         */
        init: function (deferred, $el, gridName) {
            if (_.indexOf(this.allowedTracking, gridName) !== -1) {
                methods.initHandler(deferred, $el);
            }
        }
    };
});
