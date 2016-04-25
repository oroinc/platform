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
        },

        onContextChange: function() {
            this.$el.trigger('content:changed');
        }
    });

    return CalendarEventFormView;
});
