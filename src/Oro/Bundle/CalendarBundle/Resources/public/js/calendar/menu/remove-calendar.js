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
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this.defferedActionEnd = options.defferedActionEnd;
        },

        execute: function (model) {
            var deletingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...')),
                $connection = this.connectionsView.findItem(model);
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
