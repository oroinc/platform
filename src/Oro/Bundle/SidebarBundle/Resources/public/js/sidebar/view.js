/*jslint browser: true, nomen: true, vars: true*/
/*global define*/

define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _jqueryUI = require('jquery-ui');
    var _ = require('underscore');
    var Backbone = require('backbone');

    var __ = require('oro/translator');
    var DeleteConfirmation = require('oro/delete-confirmation');

    var constants = require('oro/sidebar/constants');

    var IconView = require('oro/sidebar/widget-container/icon-view');
    var WidgetContainerView = require('oro/sidebar/widget-container/view');
    var WidgetAddView = require('oro/sidebar/widget-container/widget-add-view');
    var WidgetSetupView = require('oro/sidebar/widget-container/widget-setup-view');

    var sidebarTemplate = require('text!oro/sidebar/sidebar/template');

    /**
     * @export  oro/sidebar/sidebar/view
     * @class oro.sidebar.sidebar.View
     * @extends Backbone.View
     */
    var SidebarView = Backbone.View.extend({
        template: _.template(sidebarTemplate),

        events: {
            'click .sidebar-add a': 'onClickAdd',
            'click .sidebar-resize a': 'onClickToggle',
            'click .sidebar-toggle a': 'onClickToggle'
        },

        initialize: function () {
            var view = this;
            var model = view.model;

            view.iconViews = {};
            view.hoverViews = {};
            view.widgetViews = {};

            view.padding = model.position === constants.SIDEBAR_LEFT ? 'margin-left' : 'margin-right';

            view.listenTo(model, 'change', view.render);

            view.listenTo(model.widgets, 'reset', view.onWidgetsReset);
            view.listenTo(model.widgets, 'reset', view.render);

            view.listenTo(model.widgets, 'add', view.onWidgetAdded);
            view.listenTo(model.widgets, 'add', view.render);

            view.listenTo(model.widgets, 'remove', view.onWidgetRemoved);
            view.listenTo(model.widgets, 'remove', view.render);

            view.listenTo(Backbone, 'showWidgetHover', view.onShowWidgetHover);
            view.listenTo(Backbone, 'removeWidget', view.onRemoveWidget);
            view.listenTo(Backbone, 'closeWidget', view.onCloseWidget);
            view.listenTo(Backbone, 'setupWidget', view.onSetupWidget);
        },

        render: function () {
            var view = this;
            var model = view.model;

            view.$el.html(view.template(model.toJSON()));

            if (model.get('state') === constants.SIDEBAR_MAXIMIZED) {
                view.$el.addClass('sidebar-maximized');
            } else {
                view.$el.removeClass('sidebar-maximized');
            }

            view.options.$main.css(view.padding, view.$el.width() + 'px');

            if (model.get('state') === constants.SIDEBAR_MINIMIZED) {
                view.renderIcons();
            } else {
                view.renderWidgets();
            }

            return view;
        },

        renderIcons: function () {
            var view = this;
            var model = view.model;
            var $content = view.$el.find('.sidebar-content');

            model.widgets.each(function (widget) {
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
            var model = view.model;
            var $content = view.$el.find('.sidebar-content');

            model.widgets.each(function (widget) {
                var widgetView = view.widgetViews[widget.cid];
                if (!widgetView) {
                    return;
                }
                if (widget.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                    widget.set('state', constants.WIDGET_MAXIMIZED);
                }
                widgetView.render().delegateEvents();
                $content.append(widgetView.$el);
            });

            $content.sortable({
                revert: true,
                axis: 'y',
                containment: 'parent',
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
            var widget = this.model.widgets.get(cid);
            if (widget) {
                widget.isDragged = true;
            }
        },

        onIconDragStop: function (cid) {
            var widget = this.model.widgets.get(cid);
            if (widget) {
                widget.isDragged = false;
            }
        },

        reorderWidgets: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            var ids = $content.sortable('toArray', { attribute: 'data-cid' });
            var widgetOrder = _.object(ids, _.range(ids.length));

            view.model.widgets.each(function (widget) {
                var order = widgetOrder[widget.cid];
                widget.set({ position: order }, { silent: true });
                widget.save();
            });

            view.model.widgets.sort();
        },

        onClickAdd: function (e) {
            var view = this;
            var model = view.model;

            e.stopPropagation();
            e.preventDefault();

            var widgetAddView = new WidgetAddView({
                model: model
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

            this.model.widgets.each(function (widget) {
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

            var widget = view.model.widgets.get(cid);
            if (!widget) {
                return;
            }

            view.hideAllWidgetHovers();

            widget.snapshotState();
            widget.set('state', constants.WIDGET_MAXIMIZED_HOVER);

            var hoverView = new WidgetContainerView({
                model: widget
            });

            var widgetWidth = 200;
            view.$el.append(hoverView.render().$el);
            hoverView.$el.css('position', 'fixed');
            hoverView.$el.width(widgetWidth);

            var windowWidth = $(window).width();

            if ((cord.left + widgetWidth) > windowWidth) {
                cord.left = windowWidth - widgetWidth;
            }

            var sidebarWidth = view.$el.width();
            if (view.model.position === constants.SIDEBAR_LEFT) {
                cord.left += sidebarWidth;
            } else {
                cord.left -= sidebarWidth;
            }

            hoverView.$el.offset(cord);

            view.hoverViews[cid] = hoverView;
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
            var model = view.model;

            model.widgets.each(function (widget) {
                view.hideWidgetHover(widget.cid);
            });
        },

        onRemoveWidget: function (cid) {
            var view = this;
            var model = view.model;

            var widget = model.widgets.get(cid);
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
            var model = view.model;

            var widget = model.widgets.get(cid);
            if (!widget) {
                return;
            }

            view.hideWidgetHover(cid);
        },

        onSetupWidget: function (cid) {
            var widget = this.model.widgets.get(cid);
            if (!widget) {
                return;
            }

            var widgetSnapshot = JSON.stringify(widget);

            var widgetSetupView = new WidgetSetupView({
                model: widget
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
