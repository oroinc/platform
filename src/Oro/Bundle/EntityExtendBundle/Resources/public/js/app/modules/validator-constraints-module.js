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
            'oroentityextend/js/validator/field-name-length'
        ];

        $.validator.loadMethod(constraints);
    });
});
