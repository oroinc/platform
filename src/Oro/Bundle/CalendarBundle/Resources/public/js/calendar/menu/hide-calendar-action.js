/*jslint nomen:true*/
/*global define, console*/
define(['underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger'
    ], function (_, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/hide-calendar-action
     * @class   orocalendar.calendar.menu.HideCalendarAction
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({
        /** @property {Object} */
        listen: {
            'destroy collection': 'onModelDeleted'
        },

        initialize: function (options) {
            this.collection.setCalendar(options.calendar);
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
        },

        onModelDeleted: function (model) {
            var calendarUid = model.get('calendarUid');
            this.colorManager.removeCalendarColors(calendarUid);
            this.$el.find(this.connectionsView.selectors.findItemByCalendar(calendarUid)).remove();
            this.connectionsView.trigger('connectionRemove', model);
        },

        execute: function (model, options) {
            var deletingMsg = messenger.notificationMessage('warning', __('Excluding the calendar, please wait ...'));
            try {
                model.destroy({
                    wait: true,
                    success: _.bind(function () {
                        deletingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was excluded.'));
                    }, this),
                    error: _.bind(function (model, response) {
                        deletingMsg.close();
                        this.showDeleteError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                deletingMsg.close();
                this.showMiscError(err);
            }
        },

        showDeleteError: function (err) {
            this._showError(__('Sorry, the calendar excluding was failed'), err);
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
