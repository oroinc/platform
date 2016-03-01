/*global define*/
define([
    'backbone',
    './model'
], function(Backbone, Model) {
    'use strict';

    /**
     * @class   orodashboard.items.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: Model
    });
});
