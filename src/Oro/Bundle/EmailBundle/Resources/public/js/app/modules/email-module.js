define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('oroemail/js/validator/email-address');
});
