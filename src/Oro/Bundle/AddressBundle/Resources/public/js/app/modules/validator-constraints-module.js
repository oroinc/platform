define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('oroaddress/js/validator/name-or-organization');
});
