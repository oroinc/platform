/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function($) {
        $.validator.loadMethod('oroaddress/js/validator/name-or-organization');
    });
});
