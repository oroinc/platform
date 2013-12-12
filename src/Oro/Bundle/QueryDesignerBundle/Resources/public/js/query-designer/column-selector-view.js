/* global define */
define(['underscore', 'backbone', 'oro/entity-field-choice-view'],
function(_, Backbone, EntityFieldChoiceView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/query-designer/column-selector-view
     * @class   oro.queryDesigner.ColumnSelectorView
     * @extends oro.EntityFieldChoiceView
     */
    return EntityFieldChoiceView.extend({
        /** @property {Object} */
        options: {
            columnChainTemplate: null
        },

        getLabel: function (value) {
            return this.options.columnChainTemplate(this.splitValue(value));
        }
    });
});
