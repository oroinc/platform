/*jslint nomen:true*/
/*global define, console*/
define(['underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger'
    ], function (_, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/remove-calendar
     * @class   orocalendar.calendar.menu.RemoveCalendar
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({
        initialize: function (options) {
            this.connectionsView = options.connectionsView;
        },

        execute: function (model, actionSyncObject) {
            var removingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...')),
                $connection = this.connectionsView.findItem(model);
            try {
                $connection.hide();
                model.destroy({
                    wait: true,
                    success: _.bind(function () {
                        removingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was removed.'), {
                            namespace: 'calendar-ns'
                        });
                        actionSyncObject.resolve();
                    }, this),
                    error: _.bind(function (model, response) {
                        removingMsg.close();
                        this._showError(__('Sorry, the calendar removing was failed'), response.responseJSON || {});
                        $connection.show();
                        actionSyncObject.reject();
                    }, this)
                });
            } catch (err) {
                removingMsg.close();
                this._showError(__('Sorry, unexpected error was occurred'), err);
                $connection.show();
                this.actionSyncObject.reject();
            }
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
