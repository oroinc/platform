define(['jquery', 'jquery-ui', 'backbone', 'oro/constants', 'oro/model/widget', 'oro/view/icon', 'oro/view/widget', 'text!oro/template/sidebar'],
    function ($, _jqueryUI, Backbone, Constants, WidgetModel, IconView, WidgetView, SidebarTemplate) {
    'use strict';

    var SidebarView = Backbone.View.extend({
        template: _.template(SidebarTemplate),

        events: {
            'click .sidebar-add': 'onClickAdd',
            'click .sidebar-toggle': 'onClickToggle'
        },

        initialize: function () {
            this.iconViews = {};
            this.hoverViews = {};
            this.widgetViews = {};

            this.padding = this.model.position === Constants.SIDEBAR_LEFT ? 'padding-left' : 'padding-right';

            this.model.on('change', this.render, this);

            this.model.widgets.on('reset', this.onWidgetsReset, this);
            this.model.widgets.on('reset', this.render, this);

            this.model.widgets.on('add', this.onWidgetAdded, this);
            this.model.widgets.on('add', this.render, this);

            this.model.widgets.on('remove', this.onWidgetRemoved, this);
            this.model.widgets.on('remove', this.render, this);

            Backbone.on('showWidgetHover', this.onShowWidgetHover, this);
            Backbone.on('hideWidgetHover', this.onHideWidgetHover, this);
            Backbone.on('removeWidget', this.onRemoveWidget, this);
            Backbone.on('setupWidget', this.onSetupWidget, this);
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

        renderWidgets: function () {
            var view = this;
            var $content = view.$el.find('.sidebar-content');

            _.each(view.widgetViews, function (widgetView) {
                widgetView.render().delegateEvents();
                $content.append(widgetView.$el);
            });

            return view;
        },

        onClickAdd: function (e) {
            e.stopPropagation();
            e.preventDefault();

            var widget = new WidgetModel({
                title: Date.now().toString(),
                settings: {
                    content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse pulvinar.'
                }
            });

            this.model.widgets.push(widget);
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
                hoverView.remove();
                delete this.hoverViews[cid];
            }
        },

        onRemoveWidget: function (cid) {
            var widget = this.model.widgets.get(cid);
            if (widget) {
                this.model.widgets.remove(widget);
            }
        },

        onSetupWidget: function (cid) {
            var widget = this.model.widgets.get(cid);
            if (!widget) {
                return;
            }

            var settings = widget.get('settings');
            settings.content += ' ' + Date.now();

            widget.set({ settings: settings }, { silent: true });
            widget.trigger('change');
        }
    });

    return SidebarView;
});