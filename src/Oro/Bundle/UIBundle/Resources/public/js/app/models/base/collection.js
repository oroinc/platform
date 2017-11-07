define([
    'chaplin',
    './model'
], function(Chaplin, BaseModel) {
    'use strict';

    var BaseCollection;

    /**
     * @class BaseCollection
     * @extends Chaplin.Collection
     */
    BaseCollection = Chaplin.Collection.extend(/** @lends BaseCollection.prototype */{
        model: BaseModel,

        /**
         * Returns additional parameters to be merged into serialized object
         */
        serializeExtraData: function() {
            return {};
        }
    });

    return BaseCollection;
});
