define(function(require) {
    'use strict';

    var DemoPageModel = require('oroviewswitcher/js/app/models/demo-page-model');
    var instance;

    return {
        getModel: function() {
            if (instance) {
                return instance;
            }

            return instance = new DemoPageModel();
        }
    };
});
