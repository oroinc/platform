define([
    'underscore', 'oroform/js/validator/range'
], function(_, range) {
    'use strict';

    var constraint = _.clone(range);

    constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\NumericRange';

    return constraint;
});
