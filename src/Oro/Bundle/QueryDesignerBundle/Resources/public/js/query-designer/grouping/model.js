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

        initialize: function() {
            if (!this.get('id')) {
                this.set('id', this.cid);
            }
        },

        toJSON: function(options) {
            return app.deepClone(this.attributes);
        }
    });
});
