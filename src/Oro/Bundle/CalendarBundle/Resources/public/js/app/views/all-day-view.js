define([
    'jquery',
    'oroui/js/app/views/base/view'
], function($, BaseView) {
    'use strict';

    var CalendarEventAllDayView;
    CalendarEventAllDayView = BaseView.extend({

        /**
         * Options
         */
        options: {},

        events: {
            'change input[name$="[allDay]"]': 'onAllDayChange'
        },

        startAtTimeElement: null,
        endAtTimeElement: null,

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
            this.handleAllDayEventFlag($('input[name$="[allDay]"]'), 0);
        },

        onAllDayChange: function(event) {
            this.handleAllDayEventFlag($(event.target), 200);
        },

        handleAllDayEventFlag: function(allDayEventElement, animationDuration) {
            if (!this.startAtTimeElement) {
                var startAtElements = this.$('input[name$="[start]"]').closest('.control-group-datetime');
                this.startAtTimeElement = startAtElements.find('.timepicker-input');
            }
            if (!this.endAtTimeElement) {
                var endAtElements = this.$('input[name$="[end]"]').closest('.control-group-datetime');
                this.endAtTimeElement = endAtElements.find('.timepicker-input');
            }
            if (allDayEventElement.prop('checked')) {
                this.startAtTimeElement.data('old-time-value', this.startAtTimeElement.timepicker('getTime'));
                this.endAtTimeElement.data('old-time-value', this.endAtTimeElement.timepicker('getTime'));

                var resetTimeDelegate = function() {
                    $(this).timepicker('setTime', 0).trigger('change');
                };
                this.startAtTimeElement.hide(animationDuration, resetTimeDelegate);
                this.endAtTimeElement.hide(animationDuration, resetTimeDelegate);
            } else {
                var oldStartTimeValue = this.startAtTimeElement.data('old-time-value');
                if (oldStartTimeValue) {
                    this.startAtTimeElement.timepicker('setTime', oldStartTimeValue);
                }
                var oldEndTimeValue = this.endAtTimeElement.data('old-time-value');
                if (oldEndTimeValue) {
                    this.endAtTimeElement.timepicker('setTime', oldEndTimeValue);
                }

                this.startAtTimeElement.show(animationDuration);
                this.endAtTimeElement.show(animationDuration);
            }
        }
    });

    return CalendarEventAllDayView;
});
