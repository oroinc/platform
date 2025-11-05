import EntityFieldModel from 'oroquerydesigner/js/app/models/entity-field-model';

const ColumnModel = EntityFieldModel.extend({
    fieldAttribute: 'name',

    defaults: {
        name: null,
        label: null,
        func: {},
        sorting: null
    },

    /**
     * @inheritdoc
     */
    constructor: function ColumnModel(...args) {
        ColumnModel.__super__.constructor.apply(this, args);
    }
});

export default ColumnModel;
