define(function(require) {
    'use strict';

    const range = require('oroform/js/validator/range');

    // clone of range constraint
    const numericRange = range.slice();

    numericRange[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\NumericRange';

    return numericRange;
});
