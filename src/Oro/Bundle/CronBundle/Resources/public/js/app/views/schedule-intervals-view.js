define(function(require) {
    'use strict';

    var ScheduleIntervalsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    ScheduleIntervalsView = BaseView.extend({
        options: {
            selectors: {
                row: '[data-role="schedule-interval-row"]',
                rowError: '[data-role="schedule-interval-row-error"]'
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function ScheduleIntervalsView() {
            ScheduleIntervalsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
