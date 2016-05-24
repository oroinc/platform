define(['underscore', 'backbone'], function(_, Backbone) {
    'use strict';

    function SimplifiedEventList(columns) {
        this.columns = columns;

        // listener will be removed with columns instance
        // no need to dispose this class
        columns.on('change:renderable add remove reset change:columnEventList', function() {
            delete this.cachedEventList;
            this.trigger('change');
        }, this);
    }

    SimplifiedEventList.prototype = {
        getEventsMap: function() {
            if (!this.cachedEventList) {
                var simplifiedCellEvents = {};
                this.columns.each(function(column) {
                    if (!column.get('renderable')) {
                        return;
                    }
                    var cellCtor = column.get('cell');
                    if (cellCtor.prototype.simplifiedEventBinding) {
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
