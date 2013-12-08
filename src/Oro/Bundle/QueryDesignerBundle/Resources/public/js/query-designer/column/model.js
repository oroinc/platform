/* global define */
define(['backbone', 'oro/app'],
function(Backbone, app) {
    'use strict';

    /**
     * @export  oro/query-designer/column/model
     * @class   oro.queryDesigner.column.Model
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

        toJSON: function(options) {
            return app.deepClone(this.attributes);
        }
    });
});
