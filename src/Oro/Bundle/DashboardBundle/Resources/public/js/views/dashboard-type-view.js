import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const DashboardTypeWatcherView = BaseView.extend({
    events: {
        change: 'updateClientView'
    },

    options: {
        cloneableDashboardTypes: []
    },

    /**
     * @inheritdoc
     */
    constructor: function DashboardTypeWatcherView(options) {
        DashboardTypeWatcherView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.options = $.extend({}, this.options, options);
        this.startDashboardField = $(options.startDashboardField);
        this.updateClientView();
    },

    updateClientView() {
        const selectedType = this.$el.find(':selected').val();
        // Check if selected dashboard type is in the list of cloneable types
        if (this.options.cloneableDashboardTypes.includes(selectedType)) {
            this.startDashboardField.removeClass('hide');
        } else {
            this.startDashboardField.addClass('hide');
        }
    }
});

export default DashboardTypeWatcherView;
