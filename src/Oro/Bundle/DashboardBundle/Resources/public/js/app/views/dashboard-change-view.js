import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';
import routing from 'routing';
import mediator from 'oroui/js/mediator';

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

export default DashboardChangeView;
