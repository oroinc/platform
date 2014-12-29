/*global define*/
define([
    'chaplin',
    './model'
], function (Chaplin, BaseModel) {
    'use strict';

    var BaseCollection;

    BaseCollection = Chaplin.Collection.extend({
        model: BaseModel
    });

    return BaseCollection;
});
