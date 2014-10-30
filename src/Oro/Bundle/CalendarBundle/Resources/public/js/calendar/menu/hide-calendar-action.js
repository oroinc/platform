/*global define, console*/
define(['module', 'jquery', 'underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection'
], function (module, $, _, Backbone, __, messenger, ConnectionCollection) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/hide-calendar-action
     * @class   orocalendar.calendar.menu.HideCalendarAction
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        selectors: {
            findItemByCalendar: function (calendarId) { return '.connection-item[data-calendar="' + calendarId + '"]'; }
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.collection = this.options.collection || new ConnectionCollection();
            this.options.collection.setCalendar(this.options.calendar);
            this.template = _.template($(this.options.itemTemplateSelector).html());

            // subscribe to connection collection events
            this.listenTo(this.getCollection(), 'destroy', this.onModelDeleted);
        },

        getCollection: function () {
            return this.options.collection;
        },

        onModelDeleted: function (model) {
            this.options.colorManager.removeCalendarColors(model.get('calendar'));
            this.$el.find(this.selectors.findItemByCalendar(model.get('calendar'))).remove();
            this.trigger('connectionRemove', model);
        },

        execute: function (calendarId, options) {
            var model,
                deletingMsg = messenger.notificationMessage('warning', __('Excluding the calendar, please wait ...'));
            try {
                model = this.getCollection().get(calendarId);
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

        getName: function() {
            return 'orocalendar/js/calendar/menu/hide-calendar-action';
        }
    });
});
