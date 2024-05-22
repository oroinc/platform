import Select2AutocompleteComponent from 'oro/select2-autocomplete-component';

const Select2AutocompleteOpenapiSpecificationEntitiesComponent = Select2AutocompleteComponent.extend({
    /**
     * @property {jQuery}
     */
    $viewSelector: null,

    /**
     * @inheritdoc
     */
    constructor: function Select2AutocompleteOpenapiSpecificationEntitiesComponent(options) {
        Select2AutocompleteOpenapiSpecificationEntitiesComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        Select2AutocompleteOpenapiSpecificationEntitiesComponent.__super__.initialize.call(this, options);
        this.$viewSelector = options._sourceElement.closest('form').find(options.viewSelector);
    },

    /**
     * @inheritdoc
     */
    makeQuery: function(query) {
        return query + ';' + this.$viewSelector.val();
    }
});

export default Select2AutocompleteOpenapiSpecificationEntitiesComponent;
