define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

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

        initialize: function(options) {
            this.startDashboardrField = $(options.startDashboardrField);
            this.updateClientView();
        },

        updateClientView: function() {
            const selectedType = this.$el.find(':selected').val();
            if ('widgets' === selectedType) {
                this.startDashboardrField.removeClass('hide');
            } else {
                this.startDashboardrField.addClass('hide');
            }
        }
    });

    return DashboardTypeWatcherView;
});
