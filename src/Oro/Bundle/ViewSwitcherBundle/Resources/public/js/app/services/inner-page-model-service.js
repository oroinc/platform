define(function(require) {
    'use strict';

    var InnerPageModel = require('oroviewswitcher/js/app/models/inner-page-model');
    var instance;

    return {
        getModel: function() {
            if (instance) {
                return instance;
            }

            return instance = new InnerPageModel();
        }
    };
});
