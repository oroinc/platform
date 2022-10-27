define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    const TimeValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            showTimepicker: 'onTimepickerDialogToggle',
            hideTimepicker: 'onTimepickerDialogToggle'
        },

        /**
         * @inheritdoc
         */
        constructor: function TimeValidationMessageHandlerView(options) {
            TimeValidationMessageHandlerView.__super__.constructor.call(this, options);
        },

        isActive: function() {
            const {list} = this.$el[0].timepickerObj || {};

            return list && list.is(':visible') && !list.hasClass('ui-timepicker-positioned-top');
        },

        getPopperReferenceElement: function() {
            return this.$el;
        },

        onTimepickerDialogToggle: function(e) {
            this.active = this.isActive();
            this.update();
        }
    }, {
        test: function(element) {
            return $(element).hasClass('timepicker-input');
        }
    });

    return TimeValidationMessageHandlerView;
});
