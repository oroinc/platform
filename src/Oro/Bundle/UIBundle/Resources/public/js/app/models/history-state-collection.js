import HistoryStateModel from './history-state-model';
import BaseCollection from './base/collection';

const HistoryStateCollection = BaseCollection.extend({
    model: HistoryStateModel,

    /**
     * @inheritdoc
     */
    constructor: function HistoryStateCollection(...args) {
        HistoryStateCollection.__super__.constructor.apply(this, args);
    }
});

export default HistoryStateCollection;
