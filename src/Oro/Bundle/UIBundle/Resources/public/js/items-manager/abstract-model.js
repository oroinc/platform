/*global define*/
/*jslint nomen: true*/
define(['backbone', 'underscore'], function (Backbone, _) {
    'use strict';

    /**
     * @class   oroui.itemsManager.AbstractModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            /* define list of attributes */
        },

        getFieldLabel: function (name) {
            var getter = 'get' + name.charAt(0).toUpperCase() + name.slice(1) + 'Label';
            return this[getter] ? _.result(this, getter) : this.get(name);
        }
    });
});
