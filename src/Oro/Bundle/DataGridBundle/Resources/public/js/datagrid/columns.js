import Backgrid from 'backgrid';
import CellEventList from 'orodatagrid/js/datagrid/cell-event-list';

const GridColumns = Backgrid.Columns.extend({
    comparator: 'order',

    /**
     * @inheritdoc
     */
    constructor: function GridColumns(...args) {
        GridColumns.__super__.constructor.apply(this, args);
    },

    getCellEventList: function() {
        if (!this.cellEventList) {
            this.cellEventList = new CellEventList(this);
        }
        return this.cellEventList;
    }
});

export default GridColumns;
