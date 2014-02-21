/* global define */
define(['backbone', 'oroquerydesigner/js/query-designer/column/model'],
function(Backbone, ColumnModel) {
    'use strict';

    /**
     * @export  oroquerydesigner/js/query-designer/column/collection
     * @class   oro.queryDesigner.column.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: ColumnModel
    });
});
