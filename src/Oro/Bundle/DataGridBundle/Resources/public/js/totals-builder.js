define(['jquery', 'underscore', 'oroui/js/mediator'
    ], function($, _, mediator) {
    'use strict';

    var initHandler = function(collection) {
        collection.on('beforeReset', function(collection, resp) {
            if (resp.options) {
                collection.state.totals = resp.options.totals;
            }
        });
    };

    return {
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
            options.gridPromise.done(function(grid) {
                initHandler(grid.collection);
                deferred.resolve();
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
