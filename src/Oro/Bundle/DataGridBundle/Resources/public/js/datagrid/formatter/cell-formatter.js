import _ from 'underscore';
import Backgrid from 'backgrid';
import textUtil from 'oroui/js/tools/text-util';

/**
 * Cell formatter with fixed fromRaw method
 *
 * @export  orodatagrid/js/datagrid/formatter/cell-formatter
 * @class   orodatagrid.datagrid.formatter.CellFormatter
 * @extends Backgrid.CellFormatter
 */
const CellFormatter = function() {
    Backgrid.CellFormatter.call(this);
};

CellFormatter.prototype = Object.create(Backgrid.CellFormatter.prototype);

_.extend(CellFormatter.prototype, {
    /**
     * @inheritdoc
     */
    fromRaw: function(rawData) {
        if (rawData === null) {
            return '';
        }
        const result = Backgrid.CellFormatter.prototype.fromRaw.call(this, rawData);
        return textUtil.prepareText(result);
    }
});

export default CellFormatter;
