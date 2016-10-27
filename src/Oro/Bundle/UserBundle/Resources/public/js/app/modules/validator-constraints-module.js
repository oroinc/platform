/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init ContentManager's handlers
     */
    BaseController.loadBeforeAction([
        'jquery', 'jquery.validate'
    ], function($) {
        var constraints = [
            'orouser/js/validator/password-complexity'
        ];

        $.validator.loadMethod(constraints);
    });
});
