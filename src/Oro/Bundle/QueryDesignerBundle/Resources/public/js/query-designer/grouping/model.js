/*global define*/
/*jslint nomen: true*/
define(['backbone', 'underscore'], function (Backbone, _) {
    'use strict';

    /**
     * @export  oro/query-designer/grouping/model
     * @class   oro.queryDesigner.grouping.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            id : null,
            name : null,
        },

        getFieldLabel: function (name) {
            var getter = 'get' + name.charAt(0).toUpperCase() + name.slice(1) + 'Label';
            return this[getter] ? _.result(this, getter) : this.get(name);
        },

        getNameLabel: function () {
            var name = this.get('name');
            return name ? this.nameTemplate(this.util.splitFieldId(name)) : '';
        }
    });
});
