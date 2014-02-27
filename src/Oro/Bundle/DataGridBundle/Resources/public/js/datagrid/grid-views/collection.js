/*global define*/
define(['backbone', './model'
    ], function (Backbone, GridViewsModel) {
    'use strict';

    return Backbone.Collection.extend({
        /** @property */
        model: GridViewsModel
    });
});
