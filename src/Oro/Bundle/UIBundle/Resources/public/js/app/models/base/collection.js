/*global define*/
define([
    'chaplin',
    './model'
], function (Chaplin, BaseModel) {
    'use strict';

    var BaseCollection = Chaplin.Collection.extend({
        model: BaseModel
    });

    return BaseCollection;
});
