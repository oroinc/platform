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
                    var Cell = column.get('cell');
                    if (Cell.prototype.delegatedEventBinding && !_.isFunction(Cell.prototype.events)) {
                        var events = Cell.prototype.events;
                        // prevent CS error 'cause we must completely repeat Backbone behaviour
                        for (var eventName in events) { // jshint forin:false
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
