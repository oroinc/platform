/* global define */
define(['jquery', 'underscore', 'backbone', 'backgrid'],
function ($, _, Backbone, Backgrid) {
    "use strict";

    /**
     * FooterRow is a controller for a row of footer cells.
     *
     * @class FooterRow
     * @extends Backgrid.Row
     */
    return Backgrid.FooterRow = Backgrid.Row.extend({

        requiredOptions: ["columns", "collection", "footerCell"],

        initialize: function () {
            Backgrid.Row.prototype.initialize.apply(this, arguments);
        },

        makeCell: function (column, options) {
            var footerCell = column.get("footerCell") || options.footerCell || Backgrid.FooterCell;
            return new footerCell({
                column: column,
                collection: this.collection
            });
        }
    })
});
