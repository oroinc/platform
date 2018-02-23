define(function(require) {
    'use strict';

    var DashboardChangeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');

    DashboardChangeView = BaseView.extend({
        events: {
            change: 'onChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function DashboardChangeView() {
            DashboardChangeView.__super__.constructor.apply(this, arguments);
        },

        onChange: function(e) {
            var url = routing.generate('oro_dashboard_view', {id: $(e.currentTarget).val(), change_dashboard: true});
            mediator.execute('redirectTo', {url: url}, {redirect: true});
        }
    });

    return DashboardChangeView;
});
