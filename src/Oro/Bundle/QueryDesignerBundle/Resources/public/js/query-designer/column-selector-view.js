/* global define */
define(['underscore', 'backbone', 'oro/entity-field-view'],
function(_, Backbone, EntityFieldView) {
    'use strict';

    /**
     * @export  oro/query-designer/column-selector-view
     * @class   oro.queryDesigner.ColumnSelectorView
     * @extends oro.EntityFieldView
     */
    return EntityFieldView.extend({
        /** @property {Object} */
        options: {
            columnChainTemplate: null
        },

        getLabel: function (value) {
            return this.options.columnChainTemplate(this.splitFieldId(value));
        }
    });
});
