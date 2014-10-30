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
            calendar:        'data-calendar',
            owner:           'data-owner',
            color:           'data-color',
            backgroundColor: 'data-bg-color'
        },

        /** @property {Object} */
        selectors: {
            container:     '.calendars',
            itemContainer: '.connection-container',
            item:          '.connection-item',
            lastItem:      '.connection-item:last',
            findItemByCalendar: function (calendarId) { return '.connection-item[data-calendar="' + calendarId + '"]'; },
            findItemByOwner: function (ownerId) { return '.connection-item[data-owner="' + ownerId + '"]'; },
            removeButton:  '.remove-connection-button',
            newOwnerSelector: '#new_calendar_owner',
            contextMenuTemplate: '#template-calendar-menu'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.collection = this.options.collection || new ConnectionCollection();
            this.options.collection.setCalendar(this.options.calendar);
            this.template = _.template($(this.options.itemTemplateSelector).html());
            this.menu = _.template($(this.selectors.contextMenuTemplate).html());

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
            container.find(this.selectors.newOwnerSelector).on('change', _.bind(function (e) {
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
            this.options.colorManager.setCalendarColors(viewModel.calendar, viewModel.color, viewModel.backgroundColor);

            el = $(this.template(viewModel));
            // set 'data-' attributes
            _.each(this.attrs, function (value, key) {
                el.attr(value, viewModel[key]);
            });
            // subscribe to disconnect calendar event
            //TODO: Add new button and update selector
            el.on('click', this.selectors.removeButton, _.bind(function (e) {
                this.contextMenu($(e.currentTarget), model.get('calendar'));
            }, this));

            this.$el.find(this.selectors.itemContainer).append(el);

            this.trigger('connectionAdd', model);
        },

        contextMenu: function (parent, calendarId) {
            var el = $(this.menu()),
                i,
                modules = [],
                aModules = el.find("a[data-module]");
            for (i=0; i < aModules.length; i++) {
                modules.push($(aModules[i]).attr('data-module'));
            }
            var options = this.options,
                itemSelector = this.selectors.item;
            require(modules, function () {
                for (i=0; i < arguments.length; i++) {
                    var moduleConstructor = arguments[0],
                        actionModule = new moduleConstructor(options);
                    el.on('click', "a[data-module='" + actionModule.getName() + "']", _.bind(function (e) {
                        actionModule.execute(calendarId);
                    }, this));
                }
                parent.closest(itemSelector).append(el);
            });
        },

        onModelChanged: function (model) {
            this.options.colorManager.setCalendarColors(model.get('calendar'), model.get('color'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
        },

        addModel: function (ownerId) {
            var savingMsg, model,
                el = this.$el.find(this.selectors.findItemByOwner(ownerId));
            if (el.length > 0) {
                messenger.notificationFlashMessage('warning', __('This calendar already exists.'));
            } else {
                savingMsg = messenger.notificationMessage('warning', __('Adding the calendar, please wait ...'));
                try {
                    model = new ConnectionModel();
                    model.set('owner', ownerId);
                    model.urlRoot = this.getCollection().url;
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

        showAddError: function (err) {
            this._showError(__('Sorry, the calendar adding was failed'), err);
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
