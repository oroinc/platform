/*global define*/
/*jslint nomen: true*/
define(['backbone'], function (Backbone) {
    'use strict';

    /**
     * @class   oroquerydesigner.itemsManager.ColumnModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name : null,
            label: null,
            func: null,
            sorting: null
        }
    });
});
