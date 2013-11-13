define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _jqueryUI = require('jquery-ui');
    var _ = require('underscore');
    var Backbone = require('backbone');

    var Modal = require('oro/modal');

    var Constants = require('oro/constants');
    var WidgetModel = require('oro/model/widget');

    var IconView = require('oro/view/icon');
    var WidgetView = require('oro/view/widget');
    var WidgetAddView = require('oro/view/widgetAdd');
    var WidgetSetupView = require('oro/view/widgetSetup');

    var SidebarTemplate = require('text!oro/template/sidebar');

    var SidebarView = Backbone.View.extend({
        template: _.template(SidebarTemplate),

        events: {
            'click .sidebar-add': 'onClickAdd',
            'click .sidebar-toggle': 'onClickToggle'
        },

        initialize: function () {
            var view = this;

            view.iconViews = {};
            view.hoverViews = {};
            view.widgetViews = {};

            view.padding = view.model.position === Constants.SIDEBAR_LEFT ? 'margin-left' : 'margin-right';

            view.listenTo(view.model, 'change', view.render);

            view.listenTo(view.model.widgets, 'reset', view.onWidgetsReset);
            view.listenTo(view.model.widgets, 'reset', view.render);

            view.listenTo(view.model.widgets, 'add', view.onWidgetAdded);
            view.listenTo(view.model.widgets, 'add', view.render);

            view.listenTo(view.model.widgets, 'remove', view.onWidgetRemoved);
            view.listenTo(view.model.widgets, 'remove', view.render);

            view.listenTo(Backbone, 'showWidgetHover', view.onShowWidgetHover);
            view.listenTo(Backbone, 'hideWidgetHover', view.onHideWidgetHover);
            view.listenTo(Backbone, 'removeWidget', view.onRemoveWidget);
            view.listenTo(Backbone, 'setupWidget', view.onSetupWidget);
        },

        render: function () {
            var view = this;
            var model = view.model;

            view.$el.html(view.template(model.toJSON()));

            if (model.state === Constants.SIDEBAR_MAXIMIZED) {
                view.$el.addClass('sidebar-maximized');
            } else {
                view.$el.removeClass('sidebar-maximized');
            }

            view.options.$main.css(view.padding, view.$el.width() + 'px');

            if (model.state === Constants.SIDEBAR_MINIMIZED) {
                return view.renderIcons();
            } else {
                return view.renderWidgets();
            }

            return view;
        },

        renderIcons: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            _.each(view.iconViews, function (iconView) {
                iconView.render().delegateEvents();

                $content.append(iconView.$el);

                $content.sortable({
                    revert: true,
                    axis: 'y',
                    containment: 'parent',
                    start: function(event, ui) {
                        var cid = ui.item.data('cid');
                        view.onIconDragStart(cid);
                    },
                    stop: function(event, ui) {
                        var cid = ui.item.data('cid');
                        view.onIconDragStop(cid);

                        view.reorderWidgets();
                    }
                });
            });

            return view;
        },

        renderWidgets: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            _.each(view.widgetViews, function (widgetView) {
                widgetView.render().delegateEvents();
                $content.append(widgetView.$el);
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

            console.log('Widget order:', ids);
        },

        onClickAdd: function (e) {
            var view = this;
            var model = view.model;

            e.stopPropagation();
            e.preventDefault();

            var widgetAddView = new WidgetAddView({
                model: model
            });

            widgetAddView.render();
        },

        onClickToggle: function (e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
        },

        onWidgetsReset: function () {
            var view = this;

            this.model.widgets.each(function (widget) {
                var widgetView = new WidgetView({
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
            var widgetView = new WidgetView({
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
                widgetView.contentView.remove();
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
            var widget = this.model.widgets.get(cid);
            if (!widget) {
                return;
            }

            widget.snapshotState();
            widget.state = Constants.WIDGET_MAXIMIZED;

            var hoverView = new WidgetView({
                model: widget
            });


            var widgetWidth = 200;

            this.$el.append(hoverView.render().$el);
            hoverView.$el.css('position', 'fixed');
            hoverView.$el.width(widgetWidth);

            if ((cord.left + widgetWidth) > document.width) {
                cord.left = document.width - widgetWidth;
            }

            hoverView.$el.offset(cord);

            this.hoverViews[cid] = hoverView;
        },

        onHideWidgetHover: function (cid) {
            var hoverView = this.hoverViews[cid];
            if (hoverView) {
                hoverView.model.restoreState();
                hoverView.contentView.remove();
                hoverView.remove();
                delete this.hoverViews[cid];
            }
        },

        onRemoveWidget: function (cid) {
            var view = this;
            var model = view.model;

            var widget = model.widgets.get(cid);
            if (!widget) {
                return;
            }

            var modal = new Modal({
                content: 'The widget will be removed.'
            });

            modal.on('ok', function () {
                model.widgets.remove(widget);
                modal.off();
            });

            modal.on('cancel', function () {
                modal.off();
            });

            modal.open();
        },

        onSetupWidget: function (cid) {
            var widget = this.model.widgets.get(cid);
            if (!widget) {
                return;
            }

            var widgetSetupView = new WidgetSetupView({
                model: widget
            });

            widgetSetupView.render();
        },
    });

    return SidebarView;
});