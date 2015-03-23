/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'moment',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    'oroui/lib/jquery.datepair-0.4.4/jquery.datepair.min'
], function ($, _, moment, mediator, BaseView, Datepair) {
    'use strict';

    var _ONE_DAY = 86400000;
    var CalendarEventFormView;
    CalendarEventFormView = BaseView.extend({

        /**
         * Use native pickers of proper HTML-inputs
         */
        nativeMode: false,

        /**
         * Format of date that native date input accepts
         */
        nativeDateFormat: 'YYYY-MM-DD',

        /**
         * Default options
         */
        options: {
            startClass: 'start',
            endClass: 'end',
            timeClass: 'ui-timepicker-input',
            dateClass: 'hasDatepicker',
            defaultDateDelta: 0,
            defaultTimeDelta: 3600000
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function (options) {
            $.extend(this, _.pick(options, ['nativeMode']));

            if (!this.nativeMode) {
                this.render();
            }
        },

        render: function () {
            var self = this;
            mediator.execute('layout:init', this.$el, this).done(function () {
                var $form = $(self.$el.parents('form'));

                $form.datepair({
                    startClass: self.options.startClass,
                    endClass: self.options.endClass,
                    timeClass: self.options.timeClass,
                    dateClass: self.options.dateClass,
                    updateDate: function (input, dateObj) {
                        // calls 'setDate' method instead of native 'update'
                        $(input).datepicker('setDate', dateObj);
                        // triggers event to update backend field
                        $(input).trigger('change');
                    }
                });

                $form.on('rangeError', function (e) {
                    // resets 'start' and 'end' fields to default values on range error
                    var startDateInput = $form.find('.' + self.options.startClass + '.' + self.options.dateClass),
                        endDateInput = $form.find('.' + self.options.endClass + '.' + self.options.dateClass),
                        startTimeInput = $form.find('.' + self.options.startClass + '.' + self.options.timeClass),
                        endTimeInput = $form.find('.' + self.options.endClass + '.' + self.options.timeClass),
                        startDate = $(startDateInput).datepicker('getDate'),
                        startTime = $(startTimeInput).timepicker('getTime');
                    if (startDateInput && $(startDateInput).val() && startDate && endDateInput) {
                        var newDate = new Date(startDate.getTime() + self.options.defaultDateDelta * _ONE_DAY);
                        $(endDateInput).datepicker('setDate', newDate);
                        $(endDateInput).trigger('change');
                    }
                    if (startTimeInput && $(startTimeInput).val() && startTime && endTimeInput) {
                        var newTime = new Date(startTime.getTime() + self.options.defaultTimeDelta);
                        $(endTimeInput).timepicker('setTime', newTime);
                    }
                });
            });
        }
    });

    return CalendarEventFormView;
});
