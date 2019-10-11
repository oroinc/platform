define(function(require) {
    'use strict';

    var InnerPageModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    InnerPageModel = BaseModel.extend({
        defaults: {
            backToLogin: 'Back to Demo Log-in',
            backToLoginIcon: 'fa-cogs',
            isLoggedIn: false,
            isAdminPanel: false,
            personalDemoUrl: '#',
            styleMode: null, // View-switcher has one more styleMode: 'dark-mode'
            projectName: 'Oro',
            needHelp: 'Need Help?',
            personalizedDemo: 'Want a Personalized Demo?'
        },

        /**
         * @inheritDoc
         */
        constructor: function InnerPageModel(data, options) {
            InnerPageModel.__super__.constructor.call(this, data, options);
        }
    });

    return InnerPageModel;
});
