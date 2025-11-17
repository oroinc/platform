import ChoiceFilter from 'oro/filter/choice-filter';

const CommandWithArgsFilter = ChoiceFilter.extend({
    /**
     * @inheritdoc
     */
    constructor: function CommandWithArgsFilter(options) {
        CommandWithArgsFilter.__super__.constructor.call(this, options);
    }
});

export default CommandWithArgsFilter;
