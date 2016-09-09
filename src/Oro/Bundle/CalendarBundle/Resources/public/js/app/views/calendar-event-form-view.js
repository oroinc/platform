define([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    'oroform/js/app/views/datepair-view',
    'orocalendar/js/app/views/all-day-view'
], function($, mediator, BaseView, DatepairView, AllDayView) {
    'use strict';

    var CalendarEventFormView;
    CalendarEventFormView = BaseView.extend({

        /**
         * Options
         */
        options: {},

        events: {
            'change input[name$="[contexts]"]': 'onContextChange',
            'select2-data-loaded input[name$="[contexts]"]': 'onContextChange'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = options || {};
            this.render();
        },

        render: function() {
            var self = this;
            var renderDeferred = this.renderDeferred = $.Deferred();
            this.initLayout().done(function() {
                self.handleLayoutInit();
                renderDeferred.resolve();
            });
        },

        handleLayoutInit: function() {
            var opts;
            var datepair;
            opts = this.options;
            datepair = new DatepairView(opts);
            this.subview('datepair', datepair);
            this.subview('all_day', new AllDayView(opts));
        },

        onContextChange: function() {
            this.$el.trigger('content:changed');
        }
    });

    return CalendarEventFormView;
});
