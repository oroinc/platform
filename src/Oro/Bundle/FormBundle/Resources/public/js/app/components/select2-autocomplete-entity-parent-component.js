import _ from 'underscore';
import Select2AutocompleteComponent from 'oro/select2-autocomplete-component';

const Select2AutocompleteEntityParentComponent = Select2AutocompleteComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        delimiter: ';'
    },

    /**
     * @inheritdoc
     */
    constructor: function Select2AutocompleteEntityParentComponent(options) {
        Select2AutocompleteEntityParentComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        Select2AutocompleteEntityParentComponent.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    makeQuery: function(query) {
        return [query, this.options.configs.entityId].join(this.options.delimiter);
    }
});

export default Select2AutocompleteEntityParentComponent;
