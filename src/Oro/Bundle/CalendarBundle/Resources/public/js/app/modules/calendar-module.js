define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init ContentManager's handlers
     */
    BaseController.loadBeforeAction([
        'jquery',
        'jquery.validate'
    ], function($) {
        $.validator.loadMethod('orocalendar/js/validator/dateearlierthan');
    });
});
