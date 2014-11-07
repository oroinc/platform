/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection', 'orocalendar/js/calendar/connection/model', 'oroui/js/tools'
    ], function ($, _, Backbone, __, messenger, ConnectionCollection, ConnectionModel, tools) {
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
            newCalendarSelector: '#new_calendar',
            contextMenuTemplate: '#template-calendar-menu'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collection = this.collection || new ConnectionCollection();
            this.collection.setCalendar(this.options.calendar);
            this.options.connectionsView = this;
            this.template = _.template($(this.options.itemTemplateSelector).html());
            this.contextMenuTemplate = _.template($(this.selectors.contextMenuTemplate).html());
            this.cid = 'outsideEvent';

            // render connected calendars
            this.collection.each(_.bind(function (model) {
                this.onModelAdded(model);
            }, this));

            // subscribe to connection collection events
            this.listenTo(this.collection, 'add', this.onModelAdded);
            this.listenTo(this.collection, 'change', this.onModelChanged);

            // subscribe to connect new calendar event
            var container = this.$el.closest(this.selectors.container);
            container.find(this.selectors.newCalendarSelector).on('change', _.bind(function (e) {
                this.addModel(e.val);
                // clear autocomplete
                $(e.target).select2('val', '');
            }, this));
        },

        getCollection: function () {
            return this.collection;
        },

        onModelAdded: function (model) {
            var $el,
                viewModel = model.toJSON();
            // init text/background colors
            this.options.colorManager.applyColors(viewModel, _.bind(function () {
                return this.$el.find(this.selectors.lastItem).attr(this.attrs.backgroundColor);
            }, this));
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.color, viewModel.backgroundColor);

            $el = $(this.template(viewModel));
            // set 'data-' attributes
            _.each(this.attrs, function (value, key) {
                $el.attr(value, viewModel[key]);
            });
            // subscribe to toggle context menu
            $el.on('click', '.context-menu-button', _.bind(function (e) {
                var $currentTarget = $(e.currentTarget),
                    $contextMenu = $currentTarget.closest(this.selectors.item).find('.context-menu');
                if ($contextMenu.length) {
                    $contextMenu.remove();
                } else {
                    this.contextMenu($currentTarget, model);
                }
            }, this));

            this.$el.find(this.selectors.itemContainer).append($el);

            this.trigger('connectionAdd', model);
        },

        contextMenu: function ($container, model) {
            var $el = $(this.contextMenuTemplate(model.toJSON())),
                options = this.options,
                modules = _.uniq($el.find("a[data-module]").map(function () {
                    return $(this).data('module');
                }).get());

            if (modules.length > 0) {
                modules = _.object(modules, modules);
                tools.loadModules(modules, function (modules) {
                    _.each(modules, function (moduleConstructor, moduleName) {
                        var actionModule = new moduleConstructor(options);
                        $el.one('click', "a[data-module='" + moduleName + "']", _.bind(function (e) {
                            $el.remove();
                            $(document).off('.' + options.connectionsView.cid);
                            actionModule.execute(model, $(this).data('options') || {});
                        }, this));
                    });
                    $container.closest(options.connectionsView.selectors.item)
                        .append($el)
                        .find('.context-menu-button').css('display', 'block');
                    $(document).on('click.' + options.connectionsView.cid, function (event) {
                        if (!$(event.target).hasClass('context-menu')) {
                            $('.context-menu-button').css('display', '');
                            $el.remove();
                            $(document).off('.' + options.connectionsView.cid);
                        }
                    });
                });
            }
        },

        onModelChanged: function (model) {
            this.options.colorManager.setCalendarColors(model.get('calendarUid'), model.get('color'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
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
                    this.collection.create(model, {
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
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            $(document).off('.' + this.cid);
            Backbone.View.prototype.dispose.call(this);
        }
    });
});
