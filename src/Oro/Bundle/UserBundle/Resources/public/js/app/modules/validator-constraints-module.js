define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod('orouser/js/validator/password-complexity');
});
