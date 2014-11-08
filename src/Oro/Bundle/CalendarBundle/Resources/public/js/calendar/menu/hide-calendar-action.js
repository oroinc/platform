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
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this.defferedActionEnd = options.defferedActionEnd;
        },

        onModelDeleted: function (model) {
            var calendarUid = model.get('calendarUid');
            this.colorManager.removeCalendarColors(calendarUid);
            this.$el.find(this.connectionsView.selectors.findItemByCalendar(calendarUid)).remove();
            this.connectionsView.trigger('connectionRemove', model);
        },

        execute: function (model, options) {
            var deletingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...')),
                connectionSelector = this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid')),
                $connection = this.$el.find(connectionSelector);
            try {
                $connection.hide();
                model.destroy({
                    wait: true,
                    success: _.bind(function () {
                        deletingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was removed.'));
                        this.defferedActionEnd.resolve();
                    }, this),
                    error: _.bind(function (model, response) {
                        deletingMsg.close();
                        this.showDeleteError(response.responseJSON || {});
                        this.defferedActionEnd.resolve();
                        $connection.show();
                    }, this)
                });
            } catch (err) {
                deletingMsg.close();
                this.showMiscError(err);
                this.defferedActionEnd.resolve();
                $connection.show();
            }
        },

        showDeleteError: function (err) {
            this._showError(__('Sorry, the calendar removing was failed'), err);
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
