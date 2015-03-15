/*global require*/
require([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function (mediator, BaseController) {
    'use strict';

    /**
     * Init ContentManager's handlers
     */
    BaseController.loadBeforeAction([
        'jquery',
        'jquery.validate'
    ], function ($) {
        $.validator.loadMethod('orocalendar/js/validator/dateearlierthan');
    });
});
