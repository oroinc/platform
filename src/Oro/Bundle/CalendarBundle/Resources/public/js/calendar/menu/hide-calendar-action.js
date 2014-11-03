/*global define, console*/
define(['jquery', 'underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection'
], function ($, _, BaseView, __, messenger, ConnectionCollection) {
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

        /** @property {Object} */
        selectors: {
            findItemByCalendar: function (calendarId) { return '.connection-item[data-calendar="' + calendarId + '"]'; }
        },

        initialize: function (options) {
            this.collection = options.collection || new ConnectionCollection();
            this.collection.setCalendar(options.calendar);
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
        },

        onModelDeleted: function (model) {
            this.colorManager.removeCalendarColors(model.get('calendar'));
            this.$el.find(this.selectors.findItemByCalendar(model.get('calendar'))).remove();
            this.connectionsView.trigger('connectionRemove', model);
        },

        execute: function (calendarId, options) {
            var model,
                deletingMsg = messenger.notificationMessage('warning', __('Excluding the calendar, please wait ...'));
            try {
                model = this.collection.get(calendarId);
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
        },
    });
});
