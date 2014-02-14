/* global define */
define(['backbone', 'oro/app'],
function(Backbone, app) {
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
            label: null,
            func: null,
            sorting: null
        },

        getFieldLabel: function (name, value) {
            return (typeof value === 'object') ? JSON.stringify(value) : value;
        }
    });
});
