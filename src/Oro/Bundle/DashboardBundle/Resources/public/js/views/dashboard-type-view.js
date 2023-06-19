import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const DashboardTypeWatcherView = BaseView.extend({
    events: {
        change: 'updateClientView'
    },

    /**
     * @inheritdoc
     */
    constructor: function DashboardTypeWatcherView(options) {
        DashboardTypeWatcherView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.startDashboardrField = $(options.startDashboardrField);
        this.updateClientView();
    },

    updateClientView() {
        const selectedType = this.$el.find(':selected').val();
        if ('widgets' === selectedType) {
            this.startDashboardrField.removeClass('hide');
        } else {
            this.startDashboardrField.addClass('hide');
        }
    }
});

export default DashboardTypeWatcherView;
