define([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    'oroform/js/app/views/datepair-view'
], function($, mediator, BaseView, DatepairView) {
    'use strict';

    var CalendarEventFormView;
    CalendarEventFormView = BaseView.extend({

        /**
         * Options
         */
        options: {},

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
            this.initLayout().done(function() {
                self.handleLayoutInit();
            });
        },

        handleLayoutInit: function() {
            var opts;
            var datepair;
            opts = this.options;
            datepair = new DatepairView(opts);
            this.subview('datepair', datepair);
        }
    });

    return CalendarEventFormView;
});
