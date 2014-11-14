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
            calendarAlias:   'data-calendar-alias',
            color:           'data-color',
            backgroundColor: 'data-bg-color',
            visible:         'data-visible'
        },

        /** @property {Object} */
        selectors: {
            container:     '.calendars',
            itemContainer: '.connection-container',
            item:          '.connection-item',
            lastItem:      '.connection-item:last',
            findItemByCalendar: function (calendarUid) { return '.connection-item[data-calendar-uid="' + calendarUid + '"]'; },
            newCalendarSelector: '#new_calendar',
            contextMenuTemplate: '#template-calendar-menu',
            visibilityButton: '.calendar-color'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collection = this.collection || new ConnectionCollection();
            this.collection.setCalendar(this.options.calendar);
            this.options.connectionsView = this;
            this.template = _.template($(this.options.itemTemplateSelector).html());
            this.contextMenuTemplate = _.template($(this.selectors.contextMenuTemplate).html());

            // render connected calendars
            this.collection.each(_.bind(this.onModelAdded, this));

            // subscribe to connection collection events
            this.listenTo(this.collection, 'add', this.onModelAdded);
            this.listenTo(this.collection, 'change', this.onModelChanged);
            this.listenTo(this.collection, 'destroy', this.onModelDeleted);

            // subscribe to connect new calendar event
            var container = this.$el.closest(this.selectors.container);
            container.find(this.selectors.newCalendarSelector).on('change', _.bind(function (e) {
                this.addModel(e.val, $(e.target).select2('data').fullName);
                // clear autocomplete
                $(e.target).select2('val', '');
            }, this));
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
        },

        getCollection: function () {
            return this.collection;
        },

        findItem: function (model) {
            return this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid')));
        },

        onModelAdded: function (model) {
            var $el,
                viewModel = model.toJSON();
            // init text/background colors
            this.options.colorManager.applyColors(viewModel, _.bind(function () {
                var $last = this.$el.find(this.selectors.lastItem);
                return $last.attr(this.attrs.calendarAlias) === 'user' ? $last.attr(this.attrs.backgroundColor) : null;
            }, this));
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.backgroundColor);
            model.set('color', viewModel.color);
            model.set('backgroundColor', viewModel.backgroundColor);

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
                    this.showContextMenu($currentTarget, model);
                }
            }, this));

            this.$el.find(this.selectors.itemContainer).append($el);

            this._addVisibilityButtonEventListener(this.findItem(model), model);

            if (model.get('visible')) {
                this.trigger('connectionAdd', model);
            }
        },

        onModelChanged: function (model) {
            this.options.colorManager.setCalendarColors(model.get('calendarUid'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
        },

        onModelDeleted: function (model) {
            this.options.colorManager.removeCalendarColors(model.get('calendarUid'));
            this.findItem(model).remove();
            this.trigger('connectionRemove', model);
        },

        showCalendar: function (model) {
            this._showItem(model, true);
        },

        hideCalendar: function (model) {
            this._showItem(model, false);
        },

        toggleCalendar: function (model) {
            if (model.get('visible')) {
                this.hideCalendar(model);
            } else {
                this.showCalendar(model);
            }
        },

        showContextMenu: function ($container, model) {
            var $el = $(this.contextMenuTemplate(model.toJSON())),
                modules = _.uniq($el.find("li[data-module]").map(function () {
                    return $(this).data('module');
                }).get()),
                options = this.options,
                containerHtml = $container.html(),
                showLoadingTimeout;

            if (modules.length > 0 && this._initActionSyncObject()) {
                // show loading message, if loading takes more than 100ms
                showLoadingTimeout = setTimeout(_.bind(function () {
                    $container.html('<span class="loading-indicator"></span>');
                }, this), 100);
                options._actionSyncObject = this._actionSyncObject;
                options.$el = $el;
                options.model = model;
                modules = _.object(modules, modules);
                // load context menu
                tools.loadModules(modules, _.bind(function (modules) {
                    clearTimeout(showLoadingTimeout);
                    $container.html(containerHtml);

                    _.each(modules, _.bind(function (moduleConstructor, moduleName) {
                        var actionModule = new moduleConstructor(options);
                        $el.one('click', "li[data-module='" + moduleName + "'] .action", _.bind(function (e) {
                            if (this._initActionSyncObject()) {
                                $('.context-menu-button').css('display', '');
                                $el.remove();
                                $(document).off('.' + this.cid);
                                actionModule.execute(model);
                            }
                        }, this));
                    }, this));

                    $container.closest(this.selectors.item)
                        .append($el)
                        .find('.context-menu-button').css('display', 'block');

                    $(document).on('click.' + this.cid, _.bind(function (event) {
                        if (!$(event.target).hasClass('context-menu') && !$(event.target).closest('.context-menu').length) {
                            $('.context-menu-button').css('display', '');
                            $el.remove();
                            $(document).off('.' + this.cid);
                        }
                    }, this));

                    this._actionSyncObject.resolve();
                }, this));
            }
        },

        addModel: function (calendarId, calendarName) {
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
                    model.set('calendarName', calendarName);
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

        showUpdateError: function (err) {
            this._showError(__('Sorry, the calendar updating was failed'), err);
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        },

        _showItem: function (model, visible) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...')),
                $connection = this.findItem(model),
                $visibilityButton = $connection.find(this.selectors.visibilityButton);
            this._removeVisibilityButtonEventListener($connection, model);
            this._setItemVisibility($visibilityButton, visible ? model.get('backgroundColor') : '');
            try {
                model.save('visible', visible, {
                    wait: true,
                    success: _.bind(function () {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was updated.'));
                        this.trigger(visible ? 'connectionAdd' : 'connectionRemove', model);
                        this._addVisibilityButtonEventListener($connection, model);
                        if (this._actionSyncObject) {
                            this._actionSyncObject.resolve();
                        }
                    }, this),
                    error: _.bind(function (model, response) {
                        savingMsg.close();
                        this.showUpdateError(response.responseJSON || {});
                        this._addVisibilityButtonEventListener($connection, model);
                        this._setItemVisibility($visibilityButton, visible ? '' : model.get('backgroundColor'));
                        if (this._actionSyncObject) {
                            this._actionSyncObject.reject();
                        }
                    }, this)
                });
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this._addVisibilityButtonEventListener($connection, model);
                this._setItemVisibility($visibilityButton, visible ? '' : model.get('backgroundColor'));
                if (this._actionSyncObject) {
                    this._actionSyncObject.reject();
                }
            }
        },

        _setItemVisibility: function ($visibilityButton, backgroundColor) {
            if (backgroundColor) {
                $visibilityButton.removeClass('un-color');
                $visibilityButton.css({backgroundColor: '#' + backgroundColor, borderColor: '#' + backgroundColor});
            } else {
                $visibilityButton.css({backgroundColor: '', borderColor: ''});
                $visibilityButton.addClass('un-color');
            }
        },

        _addVisibilityButtonEventListener: function ($connection, model) {
            $connection.on('click.' + this.cid, this.selectors.visibilityButton, _.bind(function (e) {
                if (this._initActionSyncObject()) {
                    this.toggleCalendar(model);
                }
            }, this));
        },

        _removeVisibilityButtonEventListener: function ($connection, model) {
            $connection.off('.' + this.cid);
        },

        _initActionSyncObject: function () {
            if (this._actionSyncObject) {
                return false;
            }
            this._actionSyncObject = $.Deferred();
            this._actionSyncObject.then(
                _.bind(function () {
                    delete this._actionSyncObject;
                }, this),
                _.bind(function () {
                    delete this._actionSyncObject;
                }, this)
            );
            return true;
        }
    });
});
