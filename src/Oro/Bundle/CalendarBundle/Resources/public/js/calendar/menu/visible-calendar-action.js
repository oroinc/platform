/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection'
], function ($, _, BaseView, __, messenger, ConnectionCollection) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/visible-calendar-action
     * @class   orocalendar.calendar.menu.VisibleCalendarAction
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({

        initialize: function (options) {
            this.connectionsView = options.connectionsView;
        },

        execute: function (model, options) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...')),
                connectionSelector = this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid')),
                $connection = this.$el.find(connectionSelector),
                $visibleButton = $connection.find(this.connectionsView.selectors.visibleButton);
            try {
                if (model.get('visible')) {
                    this.connectionsView.hideCalendar(model, $visibleButton, savingMsg);
                } else {
                    this.connectionsView.showCalendar(model, $visibleButton, savingMsg);
                }
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                $connection.show();
                options.defferedActionEnd.resolve();
            }
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
