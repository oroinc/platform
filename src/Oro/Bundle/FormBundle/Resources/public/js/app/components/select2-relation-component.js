import Select2Component from 'oro/select2-component';

const Select2RelationComponent = Select2Component.extend({
    /**
     * @inheritdoc
     */
    constructor: function Select2RelationComponent(options) {
        Select2RelationComponent.__super__.constructor.call(this, options);
    },

    makeQuery: function(query, configs) {
        return [query, configs.target_entity, configs.target_field].join(',');
    }
});

export default Select2RelationComponent;
