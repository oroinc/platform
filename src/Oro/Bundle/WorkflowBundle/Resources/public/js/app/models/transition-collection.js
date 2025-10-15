import BaseCollection from 'oroui/js/app/models/base/collection';
import TransitionModel from './transition-model';

const TransitionCollection = BaseCollection.extend({
    model: TransitionModel,

    /**
     * @inheritdoc
     */
    constructor: function TransitionCollection(...args) {
        TransitionCollection.__super__.constructor.apply(this, args);
    }
});

export default TransitionCollection;
