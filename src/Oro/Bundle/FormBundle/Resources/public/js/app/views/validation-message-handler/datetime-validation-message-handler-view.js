define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    const DateTimeValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        /**
         * @inheritdoc
         */
        constructor: function DateTimeValidationMessageHandlerView(options) {
            DateTimeValidationMessageHandlerView.__super__.constructor.call(this, options);
        },

        delegateEvents: function() {
            DateTimeValidationMessageHandlerView.__super__.delegateEvents.call(this);
            const datepickerEvents = ['datepicker:dialogHide', 'datepicker:dialogReposition'].map(eventName => {
                return eventName + this.eventNamespace();
            }, this);
            this.$el.parent().find('.datepicker-input').on(datepickerEvents.join(' '),
                this.onDatepickerDialogReposition.bind(this));
            const timepickerEvents = ['showTimepicker', 'hideTimepicker'].map(eventName => {
                return eventName + this.eventNamespace();
            }, this);

            this.$el.parent().find('.timepicker-input').on(timepickerEvents.join(' '),
                this.onTimepickerDialogToggle.bind(this));
        },

        undelegateEvents: function() {
            $(this.el).parent().find('.datepicker-input').off(this.eventNamespace());
            $(this.el).parent().find('.timepicker-input').off(this.eventNamespace());

            DateTimeValidationMessageHandlerView.__super__.undelegateEvents.call(this);
        },

        isActive: function() {
            return this.isDatepickerActive() || this.isTimepickerActive();
        },

        isDatepickerActive: function() {
            const $input = $(this.el).parent().find('.datepicker-input');

            return $input.data('datepicker').dpDiv.is(':visible') && $input.is($.datepicker._lastInput) &&
                $input.hasClass('ui-datepicker-dialog-is-below');
        },

        isTimepickerActive: function() {
            const {list} = this.$el.parent().find('.timepicker-input')[0].timepickerObj || {};

            return list && list.is(':visible') && !list.hasClass('ui-timepicker-positioned-top');
        },

        onDatepickerDialogReposition: function(e, position) {
            this.active = position === 'below';
            this.update();
        },

        onTimepickerDialogToggle: function(e) {
            this.active = this.isTimepickerActive();
            this.update();
        },

        getPopperReferenceElement: function() {
            return this.$el.parent();
        }
    }, {
        test: function(element) {
            return $(element).data('type') === 'datetime';
        }
    });

    return DateTimeValidationMessageHandlerView;
});
