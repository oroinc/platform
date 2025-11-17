import Backgrid from 'backgrid';
import CellFormatter from 'orodatagrid/js/datagrid/formatter/cell-formatter';

/**
 * String column cell. Added missing behaviour.
 *
 * @export  oro/datagrid/cell/string-cell
 * @class   oro.datagrid.cell.StringCell
 * @extends Backgrid.StringCell
 */
const RelationCell = Backgrid.StringCell.extend({
    /**
     @property {(Backgrid.CellFormatter|Object|string)}
        */
    formatter: new CellFormatter(),

    /**
     * @inheritdoc
     */
    constructor: function RelationCell(options) {
        RelationCell.__super__.constructor.call(this, options);
    }
});

export default RelationCell;
