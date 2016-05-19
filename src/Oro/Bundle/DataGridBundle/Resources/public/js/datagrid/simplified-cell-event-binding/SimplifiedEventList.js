define(['underscore', 'backbone'], function(_, Backbone) {
    'use strict';

    function SimplifiedEventList(columns) {
        this.columns = columns;
    }

    SimplifiedEventList.prototype = {
        getEventsMap: function() {
            if (!this.cachedEventList) {
                var simplifiedCellEvents = {};
                this.columns.each(function(column) {
                    var cellCtor = column.get('cell');
                    if (cellCtor.simplifiedEventBinding) {
                        for (var eventName in cellCtor.prototype.events) { // jshint ignore:line
                            if (!simplifiedCellEvents.hasOwnProperty(eventName)) {
                                simplifiedCellEvents[eventName] = true;
                            }
                        }
                    }
                });
                this.cachedEventList = simplifiedCellEvents;
            }
            return this.cachedEventList;
        }
    };

    _.extend(SimplifiedEventList.prototype, Backbone.Events);

    return SimplifiedEventList;
});
