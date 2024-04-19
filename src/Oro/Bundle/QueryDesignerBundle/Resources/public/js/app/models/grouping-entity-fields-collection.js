import EntityFieldsCollection from './entity-fields-collection';

const GroupingEntityFieldsCollection = EntityFieldsCollection.extend({
    groupingDynamicEntityFieldsCollection: null,

    constructor: function GroupingEntityFieldsCollection(...args) {
        GroupingEntityFieldsCollection.__super__.constructor.apply(this, args);
    },

    initialize(models, {groupingDynamicEntityFieldsCollection, ...options}) {
        Object.assign(this, {groupingDynamicEntityFieldsCollection});
        GroupingEntityFieldsCollection.__super__.initialize.call(this, models, options);
    },

    _prepareModel(attrs, options) {
        options.groupingDynamicEntityFieldsCollection = this.groupingDynamicEntityFieldsCollection;
        return GroupingEntityFieldsCollection.__super__._prepareModel.call(this, attrs, options);
    }
});

export default GroupingEntityFieldsCollection;
