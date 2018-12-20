define(function(require) {
    'use strict';

    var $ = require('jquery.validate');

    $.validator.loadMethod('oroemail/js/validator/email-address');
});
