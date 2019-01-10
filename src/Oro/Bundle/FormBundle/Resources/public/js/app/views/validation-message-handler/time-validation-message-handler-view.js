define(function(require) {
    'use strict';

    var TimeValidationMessageHandlerView;
    var $ = require('jquery');
    var AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    TimeValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            showTimepicker: 'onTimepickerDialogToggle',
            hideTimepicker: 'onTimepickerDialogToggle'
        },

        /**
         * @inheritDoc
         */
        constructor: function TimeValidationMessageHandlerView() {
            TimeValidationMessageHandlerView.__super__.constructor.apply(this, arguments);
        },

        isActive: function() {
            var $list = this.$el.data('timepicker-list');

            return $list && $list.is(':visible') && !$list.hasClass('ui-timepicker-positioned-top');
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
