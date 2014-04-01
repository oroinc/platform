/*jslint browser: true, nomen: true, vars: true*/
/*global define*/

define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _jqueryUI = require('jquery-ui');
    var _ = require('underscore');
    var Backbone = require('backbone');

    var __ = require('orotranslation/js/translator');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    var constants = require('./constants');

    var IconView = require('./widget-container/icon-view');
    var WidgetContainerView = require('./widget-container/view');
    var WidgetAddView = require('./widget-container/widget-add-view');
    var WidgetSetupView = require('./widget-container/widget-setup-view');

    var sidebarTemplate = require('text!./templates/template.html');

    var WIDGET_SORT_DELAY = 100;

    function stateToClass(position, state) {
        return position.toLowerCase().replace('_', '-') + '-' + state.slice(8).toLowerCase();
    }

    /**
     * @export  orosidebar/js/view
     * @class   orosidebar.View
     * @extends Backbone.View
     */
    var SidebarView = Backbone.View.extend({
        template: _.template(sidebarTemplate),

        events: {
            'click .sidebar-add a': 'onClickAdd',
            'click .sidebar-resize a': 'onClickToggle',
            'click .sidebar-toggle a': 'onClickToggle'
        },

        options: {
            availableWidgets: null,
            widgets: null
        },

        initialize: function () {
            var view = this;
            var model = view.model;
            var widgets = this.getWidgets();

            view.iconViews = {};
            view.hoverViews = {};
            view.widgetViews = {};

            view.listenTo(model, 'change', view.render);

            view.listenTo(widgets, 'reset', view.onWidgetsReset);
            view.listenTo(widgets, 'add', view.onWidgetAdded);
            view.listenTo(widgets, 'remove', view.onWidgetRemoved);
            view.listenTo(widgets, 'reset', view.render);
            view.listenTo(widgets, 'add', view.render);
            view.listenTo(widgets, 'remove', view.render);

            view.listenTo(Backbone, 'showWidgetHover', view.onShowWidgetHover);
            view.listenTo(Backbone, 'refreshWidget', view.onRefreshWidget);
            view.listenTo(Backbone, 'removeWidget', view.onRemoveWidget);
            view.listenTo(Backbone, 'closeWidget', view.onCloseWidget);
            view.listenTo(Backbone, 'setupWidget', view.onSetupWidget);
        },

        getAvailableWidgets: function() {
            return this.options.availableWidgets;
        },

        getWidgets: function() {
            return this.options.widgets;
        },

        getPosition: function() {
            return this.model.get('position');
        },

        render: function () {
            var view = this;
            var model = view.model;
            var $main = view.options.$main;
            var maximized = model.get('state') === constants.SIDEBAR_MAXIMIZED;
            var minimized = model.get('state') === constants.SIDEBAR_MINIMIZED;

            view.$el.html(view.template(model.toJSON()));
            view.$el.toggleClass('sidebar-maximized', maximized);
            $main.toggleClass(stateToClass(model.get('position'), constants.SIDEBAR_MAXIMIZED), maximized);
            $main.toggleClass(stateToClass(model.get('position'), constants.SIDEBAR_MINIMIZED), minimized);

            if (minimized) {
                view.renderIcons();
            } else {
                view.renderWidgets();
            }

            return view;
        },

        renderIcons: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            this.getWidgets().each(function (widget) {
                var iconView = view.iconViews[widget.cid];
                if (!iconView) {
                    return;
                }
                iconView.render().delegateEvents();
                $content.append(iconView.$el);
            });

            $content.sortable({
                revert: true,
                axis: 'y',
                containment: 'parent',
                delay: WIDGET_SORT_DELAY,
                start: function (event, ui) {
                    var cid = ui.item.data('cid');
                    view.onIconDragStart(cid);
                },
                stop: function (event, ui) {
                    var cid = ui.item.data('cid');
                    view.onIconDragStop(cid);

                    view.reorderWidgets();
                }
            });

            return view;
        },

        renderWidgets: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            this.getWidgets().each(function (widget) {
                var widgetView = view.widgetViews[widget.cid];
                if (!widgetView) {
                    return;
                }
                if (widget.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                    widget.set({ state: constants.WIDGET_MAXIMIZED }, { silent: true });
                }
                widgetView.render().delegateEvents();
                $content.append(widgetView.$el);
            });

            $content.sortable({
                revert: true,
                axis: 'y',
                containment: 'parent',
                delay: WIDGET_SORT_DELAY,
                start: function (event, ui) {
                    var cid = ui.item.data('cid');
                    view.onIconDragStart(cid);
                },
                stop: function (event, ui) {
                    var cid = ui.item.data('cid');
                    view.onIconDragStop(cid);

                    view.reorderWidgets();
                }
            });

            return view;
        },

        onIconDragStart: function (cid) {
            var widget = this.getWidgets().get(cid);
            if (widget) {
                widget.isDragged = true;
            }
        },

        onIconDragStop: function (cid) {
            var widget = this.getWidgets().get(cid);
            if (widget) {
                widget.isDragged = false;
            }
        },

        reorderWidgets: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            var ids = $content.sortable('toArray', { attribute: 'data-cid' });
            var widgetOrder = _.object(ids, _.range(ids.length));

            this.getWidgets().each(function (widget) {
                var order = widgetOrder[widget.cid];
                widget.set({ position: order }, { silent: true });
                widget.save();
            });

            this.getWidgets().sort();
        },

        onClickAdd: function (e) {
            var view = this;

            e.stopPropagation();
            e.preventDefault();

            var widgetAddView = new WidgetAddView({
                sidebar: this
            });

            widgetAddView.open();
        },

        onClickToggle: function (e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
        },

        onWidgetsReset: function () {
            var view = this;

            this.getWidgets().each(function (widget) {
                var widgetView = new WidgetContainerView({
                    model: widget
                });

                view.widgetViews[widget.cid] = widgetView;

                var iconView = new IconView({
                    model: widget
                });

                view.iconViews[widget.cid] = iconView;
            });
        },

        onWidgetAdded: function (widget) {
            var widgetView = new WidgetContainerView({
                model: widget
            });

            this.widgetViews[widget.cid] = widgetView;

            var iconView = new IconView({
                model: widget
            });

            this.iconViews[widget.cid] = iconView;
        },

        onWidgetRemoved: function (widget) {
            var cid = widget.cid;

            var widgetView = this.widgetViews[cid];
            if (widgetView) {
                if (widgetView.contentView) {
                    widgetView.contentView.remove();
                }
                widgetView.remove();
                delete this.widgetViews[cid];
            }

            var iconView = this.iconViews[cid];
            if (iconView) {
                iconView.remove();
                delete this.iconViews[cid];
            }

            var hoverView = this.hoverViews[cid];
            if (hoverView) {
                hoverView.contentView.remove();
                hoverView.remove();
                delete this.hoverViews[cid];
            }
        },

        onShowWidgetHover: function (cid, cord) {
            var view = this;

            var widget = this.getWidgets().get(cid);
            if (!widget) {
                return;
            }

            view.hideAllWidgetHovers();

            widget.snapshotState();
            widget.set({ 'state': constants.WIDGET_MAXIMIZED_HOVER });

            var hoverView = new WidgetContainerView({
                model: widget
            });

            view.$el.append(hoverView.render().$el);

            hoverView.setOffset({top: cord.top});
            view.hoverViews[cid] = hoverView;
        },

        onRefreshWidget: function (cid) {
            var widget = this.getWidgets().get(cid);
            if (!widget) {
                return;
            }

            var widgetView, state = widget.get('state');
            if (state == constants.WIDGET_MAXIMIZED) {
                widgetView = this.widgetViews[cid];
            } else if (state == constants.WIDGET_MAXIMIZED_HOVER) {
                widgetView = this.hoverViews[cid];
            }

            if (widgetView) {
                widgetView.contentView.trigger('refresh');
            }
        },

        hideWidgetHover: function (cid) {
            var hoverView = this.hoverViews[cid];
            if (hoverView) {
                hoverView.model.restoreState();
                hoverView.contentView.remove();
                hoverView.remove();
                delete this.hoverViews[cid];
            }
        },

        hideAllWidgetHovers: function () {
            var view = this;

            this.getWidgets().each(function (widget) {
                view.hideWidgetHover(widget.cid);
            });
        },

        onRemoveWidget: function (cid) {
            var widget = this.getWidgets().get(cid);
            if (!widget) {
                return;
            }

            var modal = new DeleteConfirmation({
                content: __('The widget will be removed')
            });

            modal.on('ok', function () {
                widget.destroy();
                modal.off();
            });

            modal.on('cancel', function () {
                modal.off();
            });

            modal.open();
        },

        onCloseWidget: function (cid) {
            var view = this;

            var widget = this.getWidgets().get(cid);
            if (!widget) {
                return;
            }

            view.hideWidgetHover(cid);
        },

        onSetupWidget: function (cid) {
            var widget = this.getWidgets().get(cid);
            if (!widget) {
                return;
            }

            var widgetSnapshot = JSON.stringify(widget);

            var widgetSetupView = new WidgetSetupView({
                model: widget,
                okCloses: false
            });

            widgetSetupView.on('ok', function () {
                widget.save();
            });

            widgetSetupView.on('cancel', function () {
                widget.set(JSON.parse(widgetSnapshot), { silent: true });
                widget.trigger('change');
            });

            widgetSetupView.open();
        }
    });

    return SidebarView;
});
