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
            this.collection = options.collection || new ConnectionCollection();
            this.collection.setCalendar(options.calendar);
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
        },

        execute: function (calendarUid, options) {
            var model,
                savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...'));
            try {
                model = this.collection.findWhere({calendarUid: calendarUid});
                var $target = this.connectionsView.$el.find(
                    this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))
                ).find(this.connectionsView.selectors.visibleButton);
                if (model.get('visible')) {
                    this.connectionsView.hideCalendar(model, $target, savingMsg);
                } else {
                    this.connectionsView.showCalendar(model, $target, savingMsg);
                }
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this.$el.find(this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))).show();
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
