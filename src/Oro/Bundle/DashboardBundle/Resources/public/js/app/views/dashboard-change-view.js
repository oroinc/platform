define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');

    const DashboardChangeView = BaseView.extend({
        events: {
            change: 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function DashboardChangeView(options) {
            DashboardChangeView.__super__.constructor.call(this, options);
        },

        onChange: function(e) {
            const url = routing.generate('oro_dashboard_view', {id: $(e.currentTarget).val(), change_dashboard: true});
            mediator.execute('redirectTo', {url: url}, {redirect: true});
        }
    });

    return DashboardChangeView;
});
