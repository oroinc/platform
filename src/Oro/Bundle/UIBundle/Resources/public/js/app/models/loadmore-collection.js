/*jslint nomen:true*/
/*global define*/
define(['./base/collection', './base/model'
], function (BaseCollection, BaseModel) {
    'use strict';

    var LoadMoreCollection;

    /**
     * Pageable collection
     */
    LoadMoreCollection = BaseCollection.extend({
        /**
         * Basic model to store row data
         *
         * @property {Function}
         */
        model: BaseModel,

        loadMore: function (quantity) {
            var preparedUrl = '/test';
            Backbone.sync('read', this, {
                url: preparedUrl
            })
        }
    });
    
    return LoadMoreCollection;
});
