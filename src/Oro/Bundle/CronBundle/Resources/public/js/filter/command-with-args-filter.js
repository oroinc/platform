define([
    'oro/filter/choice-filter'
], function(ChoiceFilter) {
    'use strict';

    var CommandWithArgsFilter;

    CommandWithArgsFilter = ChoiceFilter.extend({
        /**
         * @inheritDoc
         */
        constructor: function CommandWithArgsFilter() {
            CommandWithArgsFilter.__super__.constructor.apply(this, arguments);
        }
    });

    return CommandWithArgsFilter;
});
