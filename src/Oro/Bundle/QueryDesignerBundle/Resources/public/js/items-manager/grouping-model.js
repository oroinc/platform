/*global define*/
/*jslint nomen: true*/
define(['backbone'], function (Backbone) {
    'use strict';

    /**
     * @class   oroquerydesigner.itemsManager.GroupingModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name : null
        }
    });
});
