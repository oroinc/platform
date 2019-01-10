define(function(require) {
    'use strict';

    var DateTimeValidationMessageHandlerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    DateTimeValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        /**
         * @inheritDoc
         */
        constructor: function DateTimeValidationMessageHandlerView() {
            DateTimeValidationMessageHandlerView.__super__.constructor.apply(this, arguments);
        },

        delegateEvents: function() {
            DateTimeValidationMessageHandlerView.__super__.delegateEvents.call(this);

            var datepickerEvents = _.map(['datepicker:dialogHide', 'datepicker:dialogReposition'], function(eventName) {
                return eventName + this.eventNamespace();
            }, this);
            this.$el.parent().find('.datepicker-input').on(datepickerEvents.join(' '),
                this.onDatepickerDialogReposition.bind(this));

            var timepickerEvents = _.map(['showTimepicker', 'hideTimepicker'], function(eventName) {
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
            var $input = $(this.el).parent().find('.datepicker-input');

            return $input.data('datepicker').dpDiv.is(':visible') && $input.is($.datepicker._lastInput) &&
                $input.hasClass('ui-datepicker-dialog-is-below');
        },

        isTimepickerActive: function() {
            var $list = $(this.el).parent().find('.timepicker-input').data('timepicker-list');

            return $list && $list.is(':visible') && !$list.hasClass('ui-timepicker-positioned-top');
        },

        onDatepickerDialogReposition: function(e, position) {
            this.active = position === 'below';
            this.update();
        },

        onTimepickerDialogToggle: function(e) {
            var $list = this.$el.parent().find('.timepicker-input').data('timepicker-list');

            this.active = $list && $list.is(':visible') && !$list.hasClass('ui-timepicker-positioned-top');
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
