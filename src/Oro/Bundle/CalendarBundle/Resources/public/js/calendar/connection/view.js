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
            contextMenuSpinnerTemplate: '#template-calendar-menu-spinner',
            visibleButton: '.calendar-color'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collection = this.collection || new ConnectionCollection();
            this.collection.setCalendar(this.options.calendar);
            this.options.connectionsView = this;
            this.template = _.template($(this.options.itemTemplateSelector).html());
            this.contextMenuTemplate = _.template($(this.selectors.contextMenuTemplate).html());

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
                this.addModel(e.val, $(e.target).select2('data').fullName);
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
                var $last = this.$el.find(this.selectors.lastItem);
                return $last.attr(this.attrs.calendarAlias) === 'user' ? $last.attr(this.attrs.backgroundColor) : null;
            }, this));
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.color, viewModel.backgroundColor);
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
                    this.contextMenu($currentTarget, model);
                }
            }, this));

            this.$el.find(this.selectors.itemContainer).append($el);

            this.addVisibleEventListener(model);

            if (model.get('visible')) {
                this.trigger('connectionAdd', model);
            }
        },

        addVisibleEventListener: function (model) {
            var $itemConnection = this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid')));
            $itemConnection.one('click.' + this.cid, this.selectors.visibleButton, _.bind(function (e) {
                this.menu = _.template($(this.selectors.contextMenuSpinnerTemplate).html());
                this.options.defferedActionEnd = $.Deferred();
                this.options.defferedActionEnd.then(
                    _.bind(function () {
                        this.menu = _.template($(this.selectors.contextMenuTemplate).html());
                    }, this),
                    _.bind(function () {
                        this.menu = _.template($(this.selectors.contextMenuTemplate).html());
                    }, this)
                );
                this.toggleCalendar($(e.currentTarget), model);
            }, this));
        },

        offVisibleEventListener: function (model) {
            var $itemConnection = this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid')));
            $itemConnection.off('.' + this.cid);
        },

        setDisplayVisibleItem: function (model, $target) {
            $target.removeClass('un-color');
            var style = {
                backgroundColor: "#" + model.get('backgroundColor'),
                borderColor: "#" + model.get('backgroundColor')
            };
            $target.css(style);
        },

        setDisplayNoneItem: function (model, $target) {
            var style = {
                backgroundColor: "",
                borderColor: ""
            };
            $target.css(style);
            $target.addClass('un-color');
        },

        showCalendar: function (model, $target, savingMsg) {
            this.offVisibleEventListener(model);
            this.setDisplayVisibleItem(model, $target);
            try {
                model.save('visible', true, {
                    wait: true,
                    success: _.bind(function () {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was updated.'));
                        this.trigger('connectionAdd', model);
                        this.addVisibleEventListener(model);
                        this.options.defferedActionEnd.resolve();
                    }, this),
                    error: _.bind(function (model, response) {
                        savingMsg.close();
                        this.showUpdateError(response.responseJSON || {});
                        this.addVisibleEventListener(model);
                        this.setDisplayNoneItem(model, $target);
                        this.options.defferedActionEnd.resolve();
                    })
                });
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this.addVisibleEventListener(model);
                this.setDisplayNoneItem(model, $target);
                this.options.defferedActionEnd.resolve();
            }
        },

        hideCalendar: function (model, $target, savingMsg) {
            this.offVisibleEventListener(model);
            this.setDisplayNoneItem(model, $target);
            try {
                model.save('visible', false, {
                    wait: true,
                    success: _.bind(function () {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was updated.'));
                        this.trigger('connectionRemove', model);
                        this.addVisibleEventListener(model);
                        this.options.defferedActionEnd.resolve();
                    }, this),
                    error: _.bind(function (model, response) {
                        savingMsg.close();
                        this.showUpdateError(response.responseJSON || {});
                        this.addVisibleEventListener(model);
                        this.setDisplayVisibleItem(model, $target);
                        this.options.defferedActionEnd.resolve();
                    })
                });
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this.addVisibleEventListener(model);
                this.setDisplayVisibleItem(model, $target);
                this.options.defferedActionEnd.resolve();
            }
        },

        toggleCalendar: function ($target, model) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...'));
            if (model.get('visible')) {
                this.hideCalendar(model, $target, savingMsg);
            } else {
                this.showCalendar(model, $target, savingMsg);
            }
        },

        contextMenu: function ($container, model) {
            var $el = $(this.contextMenuTemplate(model.toJSON())),
                options = this.options,
                modules = _.uniq($el.find("a[data-module]").map(function () {
                    return $(this).data('module');
                }).get());

            if (modules.length > 0) {
                this.options.defferedActionEnd = $.Deferred();
                this.options.defferedActionEnd.then(
                    _.bind(function () {
                        this.menu = _.template($(this.selectors.contextMenuTemplate).html());
                    }, this),
                    _.bind(function () {
                        this.menu = _.template($(this.selectors.contextMenuTemplate).html());
                    }, this)
                );
                modules = _.object(modules, modules);
                tools.loadModules(modules, _.bind(function (modules) {
                    _.each(modules, _.bind(function (moduleConstructor, moduleName) {
                        var  actionModule = new moduleConstructor(options);
                        $el.one('click', "a[data-module='" + moduleName + "']", _.bind(function (e) {
                            this.menu = _.template($(this.selectors.contextMenuSpinnerTemplate).html());
                            var dataOptions = $(e.target).attr('data-options') || {};
                            $('.context-menu-button').css('display', '');
                            $el.remove();
                            $(document).off('.' + this.cid);
                            actionModule.execute(model, dataOptions);
                        }, this));
                    }, this));
                    $container.closest(this.selectors.item)
                        .append($el)
                        .find('.context-menu-button').css('display', 'block');
                    $(document).one('click.' + this.cid, function (event) {
                        if (!$(event.target).hasClass('context-menu')) {
                            $('.context-menu-button').css('display', '');
                            $el.remove();
                        }
                    });
                }, this));
            }
        },

        onModelChanged: function (model) {
            this.options.colorManager.setCalendarColors(
                model.get('calendarUid'),
                model.get('color'),
                model.get('backgroundColor')
            );
            this.trigger('connectionChange', model);
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
