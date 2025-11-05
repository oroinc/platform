import _ from 'underscore';
import BaseModel from 'oroui/js/app/models/base/model';
import EntityStructureDataProvider from 'oroentity/js/app/services/entity-structure-data-provider';

const EntityFieldModel = BaseModel.extend({
    fieldAttribute: 'name',

    /**
     * @type {EntityStructureDataProvider}
     */
    dataProvider: null,

    /**
     * @inheritdoc
     */
    constructor: function EntityFieldModel(...args) {
        EntityFieldModel.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    initialize: function(attributes, options) {
        if (!options || !(options.dataProvider instanceof EntityStructureDataProvider)) {
            throw new TypeError('Option "dataProvider" have to be instance of EntityStructureDataProvider');
        }
        _.extend(this, _.pick(options, 'dataProvider'));
        EntityFieldModel.__super__.initialize.call(this, attributes, options);
    },

    /**
     * @inheritdoc
     */
    validate: function(attrs, options) {
        let error;
        try {
            this.dataProvider.pathToEntityChain(attrs[this.fieldAttribute]);
        } catch (e) {
            error = e.message;
        }
        return error;
    }
});

export default EntityFieldModel;
