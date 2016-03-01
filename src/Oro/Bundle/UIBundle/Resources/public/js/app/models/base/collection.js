define([
    'chaplin',
    './model'
], function(Chaplin, BaseModel) {
    'use strict';

    var BaseCollection;

    BaseCollection = Chaplin.Collection.extend({
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
