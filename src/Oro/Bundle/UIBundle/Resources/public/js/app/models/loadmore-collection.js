/*jslint nomen:true*/
/*global define*/
define(['./base/use-route-collection', './base/model'
], function (UseRouteCollection, BaseModel) {
    'use strict';

    var LoadMoreCollection;

    /**
     * Pageable collection
     */
    LoadMoreCollection = UseRouteCollection.extend({
        /**
         * Basic model to store row data
         *
         * @property {Function}
         */
        model: BaseModel,

        stateDefaults: {
            limit: 5,
            loadMoreItemsQuantity: 10
        },

        loadMore: function () {
            this.state.set({
                limit: this.state.get('limit') + this.state.get('loadMoreItemsQuantity')
            });
            var loadDeferred = $.Deferred();
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
