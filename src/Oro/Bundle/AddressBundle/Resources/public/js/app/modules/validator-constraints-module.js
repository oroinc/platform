define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod('oroaddress/js/validator/name-or-organization');
});
