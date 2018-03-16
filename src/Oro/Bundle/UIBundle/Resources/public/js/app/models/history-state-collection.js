define(function(require) {
    'use strict';

    var HistoryStateCollection;
    var HistoryStateModel = require('./history-state-model');
    var BaseCollection = require('./base/collection');

    HistoryStateCollection = BaseCollection.extend({
        model: HistoryStateModel,

        /**
         * @inheritDoc
         */
        constructor: function HistoryStateCollection() {
            HistoryStateCollection.__super__.constructor.apply(this, arguments);
        }
    });

    return HistoryStateCollection;
});
