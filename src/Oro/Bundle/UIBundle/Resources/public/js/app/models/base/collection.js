/*global define*/
define([
    'chaplin',
    './model'
], function (Chaplin, BaseModel) {
    'use strict';

    var BaseCollection;

    BaseCollection = Chaplin.Collection.extend({
        model: BaseModel,

        /**
         * @inheritDoc
         */
        serialize: function () {
            return {
                items: this.map(Chaplin.utils.serialize),
                length: this.length
            };
        }
    });

    return BaseCollection;
});
