/*jslint nomen:true */
/*global define, require*/
define(['jquery', 'underscore', 'oroui/js/mediator'
    ], function ($, _, mediator) {
    'use strict';

    var initHandler = function (collection) {
        collection.on('beforeReset', function (collection, resp) {
            collection.state.totals = resp.options.totals;
        });
        this.deferred.resolve();
    };

    return {
        /**
         * Builder interface implementation
         *
         * @param {jQuery.Deferred} deferred
         * @param {jQuery} $el
         * @param {String} gridName
         */
        init: function (deferred, $el, gridName) {
            var self, onCollectionSet;
            self = {
                deferred: deferred,
                $el: $el,
                gridName: gridName
            };
            onCollectionSet = _.bind(initHandler, self);
            mediator.once('datagrid_collection_set_after', onCollectionSet);
            mediator.once('hash_navigation_request:start', function () {
                mediator.off('datagrid_collection_set_after', onCollectionSet);
            });
        }
    };
});
