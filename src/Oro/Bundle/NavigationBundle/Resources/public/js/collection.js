/*global define*/
define(['backbone', './model'
    ], function (Backbone, NavigationModel) {
    'use strict';

    /**
     * @export  oronavigation/js/collection
     * @class   oronavigation.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: NavigationModel
    });
});
