/*global define*/
define([
    'backbone',
    './model'
], function(Backbone, GridViewsModel) {
    'use strict';

    var GridViewsCollection;

    GridViewsCollection = Backbone.Collection.extend({
        /** @property */
        model: GridViewsModel
    });

    return GridViewsCollection;
});
