define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');
    var SimplifiedEventList = require('./simplified-event-list');

    GridColumns = Backgrid.Columns.extend({
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
