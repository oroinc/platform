import BaseCollection from 'oroui/js/app/models/base/collection';
import TransitionDefinitionModel from './transition-definition-model';

const TransitionDefinitionCollection = BaseCollection.extend({
    model: TransitionDefinitionModel,

    /**
     * @inheritdoc
     */
    constructor: function TransitionDefinitionCollection(...args) {
        TransitionDefinitionCollection.__super__.constructor.apply(this, args);
    }
});

export default TransitionDefinitionCollection;
