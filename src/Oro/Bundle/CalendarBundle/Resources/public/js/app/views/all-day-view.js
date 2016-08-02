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

        autoRender: true,

        events: {
            'change input[name$="[allDay]"]': 'onAllDayChange'
        },

        startAtTimeElement: null,
        oldStartAtValue: null,

        endAtTimeElement: null,
        oldEndAtValue: null,

        render: function() {
            var self = this;
            var renderDeferred = this.renderDeferred = $.Deferred();
            this.initLayout().done(function() {
                self.handleLayoutInit();
                renderDeferred.resolve();
            });
        },

        handleLayoutInit: function() {
            this.handleAllDayEventFlag(this.$('input[name$="[allDay]"]'), 0);
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
                this.oldStartAtValue = this.startAtTimeElement.timepicker('getTime');
                this.oldEndAtValue = this.endAtTimeElement.timepicker('getTime');

                var resetTimeDelegate = function() {
                    $(this).timepicker('setTime', 0).trigger('change');
                };
                this.startAtTimeElement.hide(animationDuration, resetTimeDelegate);
                this.endAtTimeElement.hide(animationDuration, resetTimeDelegate);
            } else {
                if (this.oldStartAtValue) {
                    this.startAtTimeElement.timepicker('setTime', this.oldStartAtValue);
                }
                if (this.oldEndAtValue) {
                    this.endAtTimeElement.timepicker('setTime', this.oldEndAtValue);
                }

                this.startAtTimeElement.show(animationDuration);
                this.endAtTimeElement.show(animationDuration);
            }
        }
    });

    return CalendarEventAllDayView;
});
