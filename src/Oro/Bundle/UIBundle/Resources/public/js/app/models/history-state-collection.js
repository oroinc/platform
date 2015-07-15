define(function(require) {
    'use strict';

    var HistoryStateCollection;
    var HistoryStateModel = require('./history-state-model');
    var BaseCollection = require('./base/collection');

    HistoryStateCollection = BaseCollection.extend({
        model: HistoryStateModel
    });

    return HistoryStateCollection;
});
