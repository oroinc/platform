define(function(require) {
    'use strict';

    const InnerPageModel = require('oroviewswitcher/js/app/models/inner-page-model');
    let instance;

    return {
        getModel: function() {
            if (instance) {
                return instance;
            }

            return instance = new InnerPageModel();
        }
    };
});
