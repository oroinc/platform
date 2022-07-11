import $ from 'jquery.validate';

// general validation methods
$.validator.loadMethod([
    'oroform/js/validator/count',
    'oroform/js/validator/date',
    'oroform/js/validator/datetime',
    'oroform/js/validator/email',
    'oroform/js/validator/length',
    'oroform/js/validator/notblank',
    'oroform/js/validator/notnull',
    'oroform/js/validator/number',
    'oroform/js/validator/numeric-range',
    'oroform/js/validator/range',
    'oroform/js/validator/open-range',
    'oroform/js/validator/regex',
    'oroform/js/validator/repeated',
    'oroform/js/validator/not-blank-group',
    'oroform/js/validator/time',
    // 'oroform/js/validator/url', /* turned off, due to it is too heavy and not in use on the front */
    'oroform/js/validator/type',
    'oroform/js/validator/callback'
]);
