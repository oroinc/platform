define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const NumberFormatter = require('orodatagrid/js/datagrid/formatter/number-formatter');

    /**
     * Number column cell.
     *
     * @export  oro/datagrid/cell/number-cell
     * @class   oro.datagrid.cell.NumberCell
     * @extends Backgrid.NumberCell
     */
    const NumberCell = Backgrid.NumberCell.extend({
        /** @property {orodatagrid.datagrid.formatter.NumberFormatter} */
        formatterPrototype: NumberFormatter,

        /** @property {String} */
        style: 'decimal',

        /**
         * @inheritdoc
         */
        constructor: function NumberCell(options) {
            NumberCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            _.extend(this, options);
            NumberCell.__super__.initialize.call(this, options);
            this.formatter = this.createFormatter();
        },

        /**
         * Creates number cell formatter
         *
         * @return {orodatagrid.datagrid.formatter.NumberFormatter}
         */
        createFormatter() {
            return new this.formatterPrototype({style: this.style});
        },

        /**
         * @inheritdoc
         */
        render() {
            const render = NumberCell.__super__.render.call(this);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritdoc
         */
        enterEditMode() {
            if (this.isEditableColumn() && !this.currentEditor) {
                NumberCell.__super__.enterEditMode.call(this);
            }
        },

        /**
         * @inheritdoc
         */
        exitEditMode() {
            if (!this.isEditableColumn()) {
                NumberCell.__super__.exitEditMode.call(this);
            }
        }
    });

    return NumberCell;
});
