import Select2AclUserAutocompleteComponent from 'oro/select2-acl-user-autocomplete-component';

const Select2AclUserMultiselectComponent = Select2AclUserAutocompleteComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function Select2AclUserMultiselectComponent(options) {
        Select2AclUserMultiselectComponent.__super__.constructor.call(this, options);
    },

    preConfig: function(config) {
        Select2AclUserMultiselectComponent.__super__.preConfig.call(this, config);
        config.multiple = true;
        return config;
    }
});
export default Select2AclUserMultiselectComponent;
