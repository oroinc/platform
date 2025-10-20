import BaseCollection from 'oroui/js/app/models/base/collection';
import AttributeModel from './attribute-model';

const AttributeCollection = BaseCollection.extend({
    model: AttributeModel,

    /**
     * @inheritdoc
     */
    constructor: function AttributeCollection(...args) {
        AttributeCollection.__super__.constructor.apply(this, args);
    }
});

export default AttributeCollection;
