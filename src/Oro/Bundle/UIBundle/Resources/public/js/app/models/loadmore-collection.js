/*jslint nomen:true*/
/*global define*/
define(['./base/use-route-collection', './base/model'
], function (UseRouteCollection, BaseModel) {
    'use strict';

    var LoadMoreCollection;

    /**
     * Collection with "load more" functionality support
     */
    LoadMoreCollection = UseRouteCollection.extend({
        stateDefaults: {
            /**
             * Initial quantity of items to load
             * @type {number}
             */
            limit: 5,

            /**
             * Quantity of extra items to load on loadMore() call
             * @type {number}
             */
            loadMoreItemsQuantity: 10
        },

        /**
         * Loads additional state.loadMoreItemsQuantity items to this collection
         * @returns {$.Promise} promise
         */
        loadMore: function () {
            var loadDeferred;
            this.state.set({
                limit: this.state.get('limit') + this.state.get('loadMoreItemsQuantity')
            });
            loadDeferred = $.Deferred();
            if (this.isSyncing()) {
                this.once('sync', function () {
                    loadDeferred.resolve(this);
                });
            } else {
                loadDeferred.resolve(this);
            }
            return loadDeferred.promise();
        }
    });
    
    return LoadMoreCollection;
});
