define(function(require) {
    'use strict';

    const StringCell = require('./string-cell');
    const Backgrid = require('backgrid');

    /**
     * Html column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/html-cell
     * @class   oro.datagrid.cell.HtmlCell
     * @extends oro.datagrid.cell.StringCell
     */
    const HtmlCell = StringCell.extend({
        /**
         * use a default implementation to do not affect html content
         * @property {(Backgrid.CellFormatter)}
         */
        formatter: new Backgrid.CellFormatter(),

        /**
         * @inheritdoc
         */
        constructor: function HtmlCell(options) {
            HtmlCell.__super__.constructor.call(this, options);
        },

        /**
         * Render a text string in a table cell. The text is converted from the
         * model's raw value for this cell's column.
         */
        render: function() {
            const value = this.model.get(this.column.get('name'));
            const formattedValue = this.formatter.fromRaw(value);
            this.$el.html(formattedValue);
            return this;
        }
    });

    return HtmlCell;
});
