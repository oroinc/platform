define(function(require) {
    'use strict';

    var DemoPageModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    DemoPageModel = BaseModel.extend({
        defaults: {
            backToLogin: 'Back to Demo Log-in',
            backToLoginIcon: 'fa-cogs',
            isLoggedIn: false,
            isAdminPanel: false,
            personalDemoUrl: '#',
            projectName: 'Oro',
            needHelp: 'Need Help?',
            personalizedDemo: 'Want a Personalized Demo?'
        },

        /**
         * @inheritDoc
         */
        constructor: function DemoPageModel(data, options) {
            DemoPageModel.__super__.constructor.call(this, data, options);
        }
    });

    return DemoPageModel;
});
