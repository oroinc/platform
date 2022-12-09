define(function(require) {
    'use strict';

    const Backbone = require('backbone');

    function CellEventList(columns) {
        this.columns = columns;

        // listener will be removed with columns instance
        // no need to dispose this class
        columns.on('change:renderable add remove reset change:columnEventList', () => {
            delete this.cachedEventList;
            this.trigger('change');
        });
    }

    CellEventList.prototype = {
        getEventsMap: function() {
            if (!this.cachedEventList) {
                const cellEventsList = {};
                this.columns.each(function(column) {
                    if (!column.get('renderable')) {
                        return;
                    }
                    const Cell = column.get('cell');
                    if (Cell.prototype.delegatedEventBinding && typeof Cell.prototype.events !== 'function') {
                        const events = Cell.prototype.events;
                        // prevent CS error 'cause we must completely repeat Backbone behaviour
                        // eslint-disable-next-line guard-for-in
                        for (const eventName in events) {
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

    Object.assign(CellEventList.prototype, Backbone.Events);

    return CellEventList;
});
