/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection', 'orocalendar/js/calendar/connection/model'
    ], function ($, _, Backbone, __, messenger, ConnectionCollection, ConnectionModel) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/view
     * @class   orocalendar.calendar.connection.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        attrs: {
            calendarUid:     'data-calendar-uid',
            color:           'data-color',
            backgroundColor: 'data-bg-color'
        },

        /** @property {Object} */
        selectors: {
            container:     '.calendars',
            itemContainer: '.connection-container',
            item:          '.connection-item',
            lastItem:      '.connection-item:last',
            findItemByCalendar: function (calendarUid) { return '.connection-item[data-calendar-uid="' + calendarUid + '"]'; },
            removeButton:  '.remove-connection-button',
            newCalendarSelector: '#new_calendar'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.collection = this.options.collection || new ConnectionCollection();
            this.options.collection.setCalendar(this.options.calendar);
            this.template = _.template($(this.options.itemTemplateSelector).html());

            // render connected calendars
            this.getCollection().each(_.bind(function (model) {
                this.onModelAdded(model);
            }, this));

            // subscribe to connection collection events
            this.listenTo(this.getCollection(), 'add', this.onModelAdded);
            this.listenTo(this.getCollection(), 'change', this.onModelChanged);
            this.listenTo(this.getCollection(), 'destroy', this.onModelDeleted);

            // subscribe to connect new calendar event
            var container = this.$el.closest(this.selectors.container);
            container.find(this.selectors.newCalendarSelector).on('change', _.bind(function (e) {
                this.addModel(e.val);
                // clear autocomplete
                $(e.target).select2('val', '');
            }, this));
        },

        getCollection: function () {
            return this.options.collection;
        },

        onModelAdded: function (model) {
            var el,
                viewModel = model.toJSON();
            // init text/background colors
            this.options.colorManager.applyColors(viewModel, _.bind(function () {
                return this.$el.find(this.selectors.lastItem).attr(this.attrs.backgroundColor);
            }, this));
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.color, viewModel.backgroundColor);

            el = $(this.template(viewModel));
            // set 'data-' attributes
            _.each(this.attrs, function (value, key) {
                el.attr(value, viewModel[key]);
            });
            // subscribe to disconnect calendar event
            el.on('click', this.selectors.removeButton, _.bind(function (e) {
                this.deleteModel($(e.currentTarget).closest(this.selectors.item).attr(this.attrs.calendarUid));
            }, this));

            this.$el.find(this.selectors.itemContainer).append(el);

            this.trigger('connectionAdd', model);
        },

        onModelChanged: function (model) {
            this.options.colorManager.setCalendarColors(model.get('calendarUid'), model.get('color'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
        },

        onModelDeleted: function (model) {
            this.options.colorManager.removeCalendarColors(model.get('calendarUid'));
            this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid'))).remove();
            this.trigger('connectionRemove', model);
        },

        addModel: function (calendarId) {
            var savingMsg, model,
                calendarAlias = 'user',
                calendarUid = calendarAlias + '_' + calendarId,
                el = this.$el.find(this.selectors.findItemByCalendar(calendarUid));
            if (el.length > 0) {
                messenger.notificationFlashMessage('warning', __('This calendar already exists.'));
            } else {
                savingMsg = messenger.notificationMessage('warning', __('Adding the calendar, please wait ...'));
                try {
                    model = new ConnectionModel();
                    model.set('targetCalendar', this.options.calendar);
                    model.set('calendarAlias', calendarAlias);
                    model.set('calendar', calendarId);
                    model.set('calendarUid', calendarUid);
                    this.getCollection().create(model, {
                        wait: true,
                        success: _.bind(function () {
                            savingMsg.close();
                            messenger.notificationFlashMessage('success', __('The calendar was added.'));
                        }, this),
                        error: _.bind(function (collection, response) {
                            savingMsg.close();
                            this.showAddError(response.responseJSON || {});
                        }, this)
                    });
                } catch (err) {
                    savingMsg.close();
                    this.showMiscError(err);
                }
            }
        },

        deleteModel: function (calendarUid) {
            var model,
                deletingMsg = messenger.notificationMessage('warning', __('Excluding the calendar, please wait ...'));
            try {
                model = this.getCollection().findWhere({calendarUid: calendarUid});
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

        showAddError: function (err) {
            this._showError(__('Sorry, the calendar adding was failed'), err);
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
