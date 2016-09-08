define(['underscore', 'backgrid', 'oroui/js/tools/text-util'
    ], function(_, Backgrid, textUtil) {
    'use strict';

    /**
     * Cell formatter with fixed fromRaw method
     *
     * @export  orodatagrid/js/datagrid/formatter/cell-formatter
     * @class   orodatagrid.datagrid.formatter.CellFormatter
     * @extends Backgrid.CellFormatter
     */
    var CellFormatter = function() {};

    CellFormatter.prototype = new Backgrid.CellFormatter();

    _.extend(CellFormatter.prototype, {
        /**
         * @inheritDoc
         */
        fromRaw: function(rawData) {
            if (rawData === null) {
                return '';
            }
            var result = Backgrid.CellFormatter.prototype.fromRaw.apply(this, arguments);
            return textUtil.prepareText(result);
        }
    });

    return CellFormatter;
});
