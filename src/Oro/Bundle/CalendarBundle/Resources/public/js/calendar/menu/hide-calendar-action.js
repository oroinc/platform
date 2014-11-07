/*jslint nomen:true*/
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

        initialize: function (options) {
            this.collection = options.collection || new ConnectionCollection();
            this.collection.setCalendar(options.calendar);
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this.defferedActionEnd = options.defferedActionEnd;
        },

        onModelDeleted: function (model) {
            this.colorManager.removeCalendarColors(model.get('calendarUid'));
            this.$el.find(this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))).remove();
            this.connectionsView.trigger('connectionRemove', model);
        },

        execute: function (calendarUid, options) {
            var model,
                deletingMsg = messenger.notificationMessage('warning', __('Removing the calendar, please wait ...'));
            try {
                model = this.collection.findWhere({calendarUid: calendarUid});
                this.$el.find(this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))).hide();
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
                        this.$el.find(this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))).show();
                    }, this)
                });
            } catch (err) {
                deletingMsg.close();
                this.showMiscError(err);
                this.defferedActionEnd.resolve();
                this.$el.find(this.connectionsView.selectors.findItemByCalendar(model.get('calendarUid'))).show();
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
