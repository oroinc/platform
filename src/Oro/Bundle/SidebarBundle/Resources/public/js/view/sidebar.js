define(['jquery', 'backbone', 'oro/constants', 'oro/model/widget', 'oro/view/icon', 'oro/view/widget', 'text!oro/template/sidebar'],
    function ($, Backbone, Constants, WidgetModel, IconView, WidgetView, SidebarTemplate) {
    'use strict';

    var SidebarView = Backbone.View.extend({
        template: _.template(SidebarTemplate),

        events: {
            'click .sidebar-add': 'onAddClick',
            'click .sidebar-toggle': 'onToggleClick'
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

        onAddClick: function () {
            var widget = new WidgetModel({
                title: Date.now().toString(),
                settings: { content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse pulvinar.' }
            });

            this.model.widgets.push(widget);
        },

        onToggleClick: function () {
            this.model.toggleState();
        },

        render: function () {
            var view = this;

            this.$el.html(this.template(this.model.toJSON()));

            if (this.model.state === Constants.SIDEBAR_MAXIMIZED) {
                this.$el.addClass('sidebar-maximized');
            } else {
                this.$el.removeClass('sidebar-maximized');
            }

            this.options.$main.css(this.padding, this.$el.width() + 'px');

            var $content = this.$el.find('.sidebar-content');

            if (this.model.state === Constants.SIDEBAR_MINIMIZED) {
                _.each(this.iconViews, function (iconView) {
                    iconView.render().delegateEvents();
                    $content.append(iconView.$el);
                });
            } else {
                _.each(this.widgetViews, function (widgetView) {
                    widgetView.render().delegateEvents();
                    $content.append(widgetView.$el);
                });
            }

            return this;
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