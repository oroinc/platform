define([
    'jquery',
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'orocalendar/js/calendar/connection/collection',
    'orocalendar/js/calendar/connection/model',
    'oroui/js/tools'
], function($, _, Backbone, __, messenger, ConnectionCollection, ConnectionModel, tools) {
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
            findItemByCalendar: function(calendarUid) {
                return '.connection-item[data-calendar-uid="' + calendarUid + '"]';
            },
            newCalendarSelector: '#new_calendar',
            contextMenuTemplate: '#template-calendar-menu',
            visibilityButton: '.calendar-color'
        },

        events: {
            'mouseover .connection-item': 'onOverCalendarItem',
            'mouseout .connection-item': 'onOutCalendarItem'
        },

        initialize: function(options) {
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
            container.find(this.selectors.newCalendarSelector).on('change', _.bind(function(e) {
                var itemData = $(e.target).inputWidget('data');
                this.addModel(e.val, itemData.fullName, itemData.userId);
                // clear autocomplete
                $(e.target).inputWidget('val', '');
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off('.' + this.cid);
            Backbone.View.prototype.dispose.call(this);
        },

        getCollection: function() {
            return this.collection;
        },

        findItem: function(model) {
            return this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid')));
        },

        setItemVisibility: function($item, backgroundColor, skipActive) {
            var $visibilityButton = $item.find(this.selectors.visibilityButton);
            var colors;
            if (backgroundColor) {
                if (skipActive !== true) {
                    $item.addClass('active');
                    $item.attr(this.attrs.visible, 'true');
                    $item.attr(this.attrs.backgroundColor, backgroundColor);
                    $item.attr(this.attrs.color, this.options.colorManager.getContrastColor(backgroundColor));
                }
                $visibilityButton.removeClass('un-color');
                $visibilityButton.css({backgroundColor: backgroundColor, borderColor: backgroundColor});
            } else {
                if (skipActive !== true) {
                    $item.removeClass('active');
                    $item.attr(this.attrs.visible, 'false');
                    colors = this.options.colorManager.getCalendarColors($item.attr(this.attrs.calendarUid));
                    $item.attr(this.attrs.backgroundColor, colors.backgroundColor);
                    $item.attr(this.attrs.color, colors.color);
                }
                $visibilityButton.css({backgroundColor: '', borderColor: ''});
                $visibilityButton.addClass('un-color');
            }
        },

        onModelAdded: function(model) {
            var $el;
            var viewModel = model.toJSON();
            // init text/background colors
            this.options.colorManager.applyColors(viewModel, _.bind(function() {
                var $last = this.$el.find(this.selectors.lastItem);
                var calendarAlias = $last.attr(this.attrs.calendarAlias);
                return ['user', 'system', 'public'].indexOf(calendarAlias) !== -1 ?
                    $last.attr(this.attrs.backgroundColor) : null;
            }, this));
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.backgroundColor);
            model.set('backgroundColor', viewModel.backgroundColor);

            $el = $(this.template(viewModel));
            // set 'data-' attributes
            _.each(this.attrs, function(value, key) {
                $el.attr(value, viewModel[key]);
            });
            // subscribe to toggle context menu
            $el.on('click', '.context-menu-button', _.bind(function(e) {
                e.stopPropagation();

                var $currentTarget = $(e.currentTarget);
                var $contextMenu = $currentTarget.closest(this.selectors.item).find('.context-menu');
                if ($contextMenu.length) {
                    $contextMenu.remove();
                } else {
                    if (this._closeContextMenu) {
                        this._closeContextMenu();
                    }

                    this.showContextMenu($currentTarget, model, e.pageX, e.pageY);
                }
            }, this));

            this.$el.find(this.selectors.itemContainer).append($el);

            this._addVisibilityButtonEventListener(this.findItem(model), model);

            if (model.get('visible')) {
                this.trigger('connectionAdd', model);
            }
        },

        onModelChanged: function(model) {
            this.options.colorManager.setCalendarColors(model.get('calendarUid'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
        },

        onModelDeleted: function(model) {
            this.options.colorManager.removeCalendarColors(model.get('calendarUid'));
            this.findItem(model).remove();
            this.trigger('connectionRemove', model);
        },

        onOverCalendarItem: function(e) {
            var $item = $(e.currentTarget);
            if ($item.attr(this.attrs.visible) === 'false') {
                this.setItemVisibility($item, $item.attr(this.attrs.backgroundColor), true);
            }
        },

        onOutCalendarItem: function(e) {
            var $item = $(e.currentTarget);
            if ($item.attr(this.attrs.visible) === 'false') {
                this.setItemVisibility($item, '', true);
            }
        },

        showCalendar: function(model) {
            this._showItem(model, true);
        },

        hideCalendar: function(model) {
            this._showItem(model, false);
        },

        toggleCalendar: function(model) {
            if (model.get('visible')) {
                this.hideCalendar(model);
            } else {
                this.showCalendar(model);
            }
        },

        showContextMenu: function($button, model, posX, posY) {
            var $container = $button.closest(this.selectors.item);
            var $contextMenu = $(this.contextMenuTemplate(model.toJSON()));
            var closeContextMenu = _.bind(function() {
                    $('.context-menu-button').css('display', '');
                    $contextMenu.remove();
                    $(document).off('.' + this.cid);
                    delete this._closeContextMenu;
                }, this);
            var modules = _.uniq($contextMenu.find('li[data-module]').map(function() {
                    return $(this).data('module');
                }).get());
            var buttonHtml = $button.html();
            var showLoadingTimeout;

            this._closeContextMenu = closeContextMenu;

            if (modules.length > 0 && this._initActionSyncObject()) {
                // show loading message, if loading takes more than 100ms
                showLoadingTimeout = setTimeout(_.bind(function() {
                    $button.html('<span class="loading-indicator"></span>');
                }, this), 100);
                // load context menu
                tools.loadModules(_.object(modules, modules), _.bind(function(modules) {
                    clearTimeout(showLoadingTimeout);
                    $button.html(buttonHtml);

                    _.each(modules, _.bind(function(ModuleConstructor, moduleName) {
                        $contextMenu.find('li[data-module="' + moduleName + '"]').each(_.bind(function(index, el) {
                            var action = new ModuleConstructor({
                                    el: el,
                                    model: model,
                                    collection: this.options.collection,
                                    connectionsView: this.options.connectionsView,
                                    colorManager: this.options.colorManager,
                                    closeContextMenu: closeContextMenu
                                });
                            action.$el.one('click', '.action', _.bind(function(e) {
                                if (this._initActionSyncObject()) {
                                    closeContextMenu();
                                    action.execute(model, this._actionSyncObject);
                                }
                            }, this));
                        }, this));
                    }, this));

                    $contextMenu.appendTo($container.find('.connection-menu-container'));
                    $container.find('.context-menu-button').css('display', 'block');

                    $(document).on('click.' + this.cid, _.bind(function(event) {
                        if (!$(event.target).hasClass('context-menu') &&
                            !$(event.target).closest('.context-menu').length) {
                            closeContextMenu();
                        }
                    }, this));

                    this._actionSyncObject.resolve();
                }, this));
            }
        },

        addModel: function(calendarId, calendarName, userId) {
            var savingMsg;
            var model;
            var calendarAlias = 'user';
            var calendarUid = calendarAlias + '_' + calendarId;
            var el = this.$el.find(this.selectors.findItemByCalendar(calendarUid));
            if (el.length > 0) {
                messenger.notificationFlashMessage('warning',
                    __('oro.calendar.flash_message.calendar_already_exists'), {namespace: 'calendar-ns'});
            } else {
                savingMsg = messenger.notificationMessage('warning', __('oro.calendar.flash_message.calendar_adding'));
                try {
                    model = new ConnectionModel({
                        targetCalendar: this.options.calendar,
                        calendarName: calendarName,
                        calendarAlias: calendarAlias,
                        calendar: calendarId,
                        calendarUid: calendarUid,
                        userId: userId
                    });
                    this.collection.create(model, {
                        wait: true,
                        success: _.bind(function() {
                            savingMsg.close();
                            messenger.notificationFlashMessage('success',
                                __('oro.calendar.flash_message.calendar_added'), {namespace: 'calendar-ns'});
                        }, this),
                        error: _.bind(function(collection, response) {
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

        showAddError: function(err) {
            this._showError(__('Sorry, the calendar adding was failed'), err);
        },

        showUpdateError: function(err) {
            this._showError(__('Sorry, the calendar updating was failed'), err);
        },

        showMiscError: function(err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        _showError: function(message, err) {
            messenger.showErrorMessage(message, err);
        },

        _showItem: function(model, visible) {
            var savingMsg = messenger.notificationMessage('warning',
                __('oro.calendar.flash_message.calendar_updating'));
            var $connection = this.findItem(model);
            this._removeVisibilityButtonEventListener($connection, model);
            this.setItemVisibility($connection, visible ? model.get('backgroundColor') : '');
            try {
                model.save('visible', visible, {
                    wait: true,
                    success: _.bind(function() {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success',
                            __('oro.calendar.flash_message.calendar_updated'), {namespace: 'calendar-ns'});
                        this._addVisibilityButtonEventListener($connection, model);
                        if (this._actionSyncObject) {
                            this._actionSyncObject.resolve();
                        }
                    }, this),
                    error: _.bind(function(model, response) {
                        savingMsg.close();
                        this.showUpdateError(response.responseJSON || {});
                        this._addVisibilityButtonEventListener($connection, model);
                        this.setItemVisibility($connection, visible ? '' : model.get('backgroundColor'));
                        if (this._actionSyncObject) {
                            this._actionSyncObject.reject();
                        }
                    }, this)
                });
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this._addVisibilityButtonEventListener($connection, model);
                this.setItemVisibility($connection, visible ? '' : model.get('backgroundColor'));
                if (this._actionSyncObject) {
                    this._actionSyncObject.reject();
                }
            }
        },

        _addVisibilityButtonEventListener: function($connection, model) {
            $connection.on('click.' + this.cid, _.bind(function(e) {
                if (this._initActionSyncObject()) {
                    this.toggleCalendar(model);
                }
            }, this));
        },

        _removeVisibilityButtonEventListener: function($connection, model) {
            $connection.off('.' + this.cid);
        },

        _initActionSyncObject: function() {
            if (this._actionSyncObject) {
                return false;
            }
            this._actionSyncObject = $.Deferred();
            this._actionSyncObject.then(
                _.bind(function() {
                    delete this._actionSyncObject;
                }, this),
                _.bind(function() {
                    delete this._actionSyncObject;
                }, this)
            );
            return true;
        }
    });
});
