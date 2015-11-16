define([
    'backgrid',
    'underscore'
], function(Backgrid, _) {
    'use strict';

    var MultiSelectCell;

    /**
     * Cell able to display multiselect values.
     *
     * Requires income data format:
     * ```javascript
     * var cellValue = [<id-1>, <id-2>, ...];
     * ```
     *
     * Also please prepare and pass choices through cell configuration
     *
     * @export  oro/datagrid/cell/multi-select-cell
     * @class   oro.datagrid.cell.MultiSelectCell
     * @extends oro.datagrid.cell.StringCell
     */
    MultiSelectCell = Backgrid.StringCell.extend({
        /**
         * @property {string}
         */
        type: 'multiselect',

        /**
         * @property {string}
         */
        className: 'multiselect-cell',

        /**
         * @inheritDoc
         */
        render: function() {
            var value = this.model.get(this.column.get('name'));
            var choices = this.choices;

            if (_.isString(value)) {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    this.$el.html('');
                    return;
                }
            }

            if (value === null || value === void 0) {
                // assume empty
                value = [];
            }

            this.$el.html(value.length > 0 ? (
                '<span class="multiselect-value-wrapper"><span class="value-item">' +
                value
                    .map(function(item) {return choices[item];})
                    .join('</span><span class="value-item">') +
                '</span></span>'
            ) : '');
            return this;
        }
    });

    return MultiSelectCell;
});
