define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');

    const ScheduleIntervalsView = BaseView.extend({
        options: {
            selectors: {
                row: '[data-role="schedule-interval-row"]',
                rowError: '[data-role="schedule-interval-row-error"]'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function ScheduleIntervalsView(options) {
            ScheduleIntervalsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.delegate('change content:remove', this._handleErrors);
        },

        _handleErrors: function() {
            this.$(this.options.selectors.rowError).remove();
            this.$(this.options.selectors.row).removeClass('has-row-error');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.undelegate('change content:remove', this._handleErrors);
        }
    });

    return ScheduleIntervalsView;
});
