define([
    'backgrid',
    'underscore',
    'orotranslation/js/translator'
], function(Backgrid, _, __) {
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
         * @property {string}
         */
        ERROR_HTML: '<span style="color:red">' + __('Unexpected format') + '</span>',

        /**
         * @inheritDoc
         */
        render: function() {
            var value = this.model.get(this.column.get('name'));

            if (_.isString(value)) {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    this.$el.html(this.ERROR_HTML);
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

            var html;
            try {
                html = value.count > 0 ? (
                    '<span class="multiselect-value-wrapper"><span class="value-item">' +
                    value.data
                        .map(function(item) {return item.label;})
                        .join('</span><span class="value-item">') +
                    '</span></span>'
                ) : '';
            } catch (e) {
                html = this.ERROR_HTML;
            }
            this.$el.html(html);

            return this;
        }
    });

    return MultiRelationCell;
});
