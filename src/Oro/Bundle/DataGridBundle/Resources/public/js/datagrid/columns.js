define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');
    var Column = require('./column/column');
    var SimplifiedEventList = require('./simplified-cell-event-binding/SimplifiedEventList');

    GridColumns = Backgrid.Columns.extend({
        model: Column,
        comparator: 'order',
        getSimplifiedEventList: function() {
            if (!this.simplifiedEventList) {
                this.simplifiedEventList = new SimplifiedEventList(this);
            }
            return this.simplifiedEventList;
        }
    });

    return GridColumns;
});
