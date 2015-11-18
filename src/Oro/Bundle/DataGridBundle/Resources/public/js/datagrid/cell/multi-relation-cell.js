define([
    'backgrid',
    'underscore'
], function(Backgrid, _) {
    'use strict';

    var MultiRelationCell;

    /**
     * Cell able to display many to one relation.
     *
     * Requires income data format:
     * ```javascript
     * var cellValue = {
     *     data: [
     *         {
     *             id: <id>,
     *             label: <label>
     *         }
     *     ],
     *     count: <actual-data-count>
     * }
     * ```
     *
     * @export  oro/datagrid/cell/multi-relation-cell
     * @class   oro.datagrid.cell.MultiRelationCell
     * @extends oro.datagrid.cell.StringCell
     */
    MultiRelationCell = Backgrid.StringCell.extend({
        /**
         * @property {string}
         */
        type: 'multi-relation',

        /**
         * @property {string}
         */
        className: 'multi-relation-cell',

        /**
         * @inheritDoc
         */
        render: function() {
            var value = this.model.get(this.column.get('name'));

            if (_.isString(value)) {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    this.$el.html('<span style="color:red">Unexpected format</span>');
                    return this;
                }
            }
            if (value === null || value === void 0) {
                // assume empty
                value = {
                    count: 0,
                    data: []
                };
            }

            try {
                this.$el.html(value.count > 0 ? (
                    '<span class="multiselect-value-wrapper"><span class="value-item">' +
                    value.data
                        .map(function(item) {return item.label;})
                        .join('</span><span class="value-item">') +
                    '</span></span>'
                ) : '');
            } catch (e) {
                this.$el.html('<span style="color:red">Unexpected format</span>');
            }

            return this;
        }
    });

    return MultiRelationCell;
});
