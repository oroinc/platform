/*jslint nomen:true*/
/*global define*/
define(['./base/routing-collection'
], function (UseRouteCollection) {
    'use strict';

    var LoadMoreCollection;

    /**
     * Collection with "load more" functionality support
     */
    LoadMoreCollection = UseRouteCollection.extend({
        routeParams: {
            /**
             * Initial quantity of items to load
             * @type {number}
             */
            limit: 5
        },

        stateDefaults: {
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
            this.route.set({
                limit: this.route.get('limit') + this.state.get('loadMoreItemsQuantity')
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
        },

        /**
         * Getter for totalQuantity
         * @returns {number}
         */
        getTotalQuantity: function () {
            return this.state.get('count');
        },

        /**
         * Setter for totalQuantity
         *
         * @param value {number}
         */
        setTotalQuantity: function (value) {
            this.state.set('count', value);
        }
    });
    
    return LoadMoreCollection;
});
