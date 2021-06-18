define(function(require) {
    'use strict';

    const HistoryStateModel = require('./history-state-model');
    const BaseCollection = require('./base/collection');

    const HistoryStateCollection = BaseCollection.extend({
        model: HistoryStateModel,

        /**
         * @inheritdoc
         */
        constructor: function HistoryStateCollection(...args) {
            HistoryStateCollection.__super__.constructor.apply(this, args);
        }
    });

    return HistoryStateCollection;
});
