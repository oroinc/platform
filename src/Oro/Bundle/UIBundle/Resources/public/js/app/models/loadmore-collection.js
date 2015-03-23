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
            start: 0,
            end: 5,
            loadMoreItemsQuantity: 10
        },

        loadMore: function () {
            this.state.set({
                end: this.state.get('end') + this.state.get('loadMoreItemsQuantity')
            });
        }
    });
    
    return LoadMoreCollection;
});
