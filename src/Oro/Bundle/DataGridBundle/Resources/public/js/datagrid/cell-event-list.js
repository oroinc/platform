define(['underscore', 'backbone'], function(_, Backbone) {
    'use strict';

    function CellEventList(columns) {
        this.columns = columns;

        // listener will be removed with columns instance
        // no need to dispose this class
        columns.on('change:renderable add remove reset change:columnEventList', function() {
            delete this.cachedEventList;
            this.trigger('change');
        }, this);
    }

    CellEventList.prototype = {
        getEventsMap: function() {
            if (!this.cachedEventList) {
                var cellEventsList = {};
                this.columns.each(function(column) {
                    if (!column.get('renderable')) {
                        return;
                    }
                    var cellCtor = column.get('cell');
                    if (!_.isFunction(cellCtor.prototype.events)) {
                        for (var eventName in cellCtor.prototype.events) { // jshint ignore:line
                            if (!cellEventsList.hasOwnProperty(eventName)) {
                                cellEventsList[eventName] = true;
                            }
                        }
                    }
                });
                this.cachedEventList = cellEventsList;
            }
            return this.cachedEventList;
        }
    };

    _.extend(CellEventList.prototype, Backbone.Events);

    return CellEventList;
});
