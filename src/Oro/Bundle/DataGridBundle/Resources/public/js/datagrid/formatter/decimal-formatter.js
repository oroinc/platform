import Backgrid from 'backgrid';
import numberFormatter from 'orolocale/js/formatter/number';

/**
 * Cell formatter with fixed toRaw method
 *
 * @export  orodatagrid/js/datagrid/formatter/decimal-formatter
 * @class   orodatagrid.datagrid.formatter.DecimalFormatter
 * @extends Backgrid.CellFormatter
 */
const DecimalFormatter = function() {
    Backgrid.CellFormatter.call(this);
};

DecimalFormatter.prototype = Object.create(Backgrid.CellFormatter.prototype);

Object.assign(DecimalFormatter.prototype, {
    /**
     * @inheritdoc
     */
    toRaw: function(rawData) {
        // convert to a numeric value to support correct grid sorting;
        if (!isNaN(rawData)) {
            return numberFormatter.unformat(rawData);
        }

        return rawData;
    }
});

export default DecimalFormatter;
