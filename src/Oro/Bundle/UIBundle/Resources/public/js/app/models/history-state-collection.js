/* global define */
define(function(require) {
    'use strict';

    var HistoryStateCollection,
        HistoryStateModel = require('./history-state-model'),
        BaseCollection = require('./base/collection');

    HistoryStateCollection = BaseCollection.extend({
        model: HistoryStateModel
    });

    return HistoryStateCollection;
});
