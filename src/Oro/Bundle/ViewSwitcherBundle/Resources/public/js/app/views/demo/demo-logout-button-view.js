define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!oroviewswitcher/templates/demo-logout-button.html');

    const DemoLogoutButtonView = BaseView.extend({
        keepElement: true,
        autoRender: true,
        template: template,

        listen: {
            'change:isLoggedIn model': '_debouncedRender',
            'change:backToLogin model': '_debouncedRender',
            'change:backToLoginIcon model': '_debouncedRender'
        },

        /**
         * @inheritdoc
         */
        constructor: function DemoLogoutButtonView(options) {
            this._debouncedRender = _.debounce(this.render.bind(this), 0);
            DemoLogoutButtonView.__super__.constructor.call(this, options);
        }
    });

    return DemoLogoutButtonView;
});
