/* global define */
define(['backbone', 'oro/query-designer/grouping/model'],
function(Backbone, GroupingModel) {
    'use strict';

    /**
     * @export  oro/query-designer/grouping/collection
     * @class   oro.queryDesigner.grouping.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: GroupingModel
    });
});
