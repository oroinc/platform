define([
    'backgrid'
], function(Backgrid) {
    'use strict';

    var MultiSelectCell;

    /**
     * String column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/multiselect-cell
     * @class   oro.datagrid.cell.MultiselectCell
     * @extends Backgrid.MultiselectCell
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
            try {
                var desc = JSON.parse(this.model.get(this.column.get('name')));
                this.$el.html(desc.count > 0 ? (
                    '<span class="multiselect-value-wrapper"><span class="multiselect-value">' +
                    desc.data
                        .map(function(item) {return item.label;})
                        .join('</span><span class="multiselect-value">') +
                    '</span></span>'
                ) : '');
            }catch (e) {
                this.$el.html('');
            }
            return this;
        }
    });

    return MultiSelectCell;
});
