define(['jquery', 'backbone', 'oro/constants', 'text!oro/template/widgetMin', 'text!oro/template/widgetMax'],
    function ($, Backbone, Constants, WidgetMinTemplate, WidgetMaxTemplate) {
    'use strict';

    var WidgetView = Backbone.View.extend({
        className: 'sidebar-widget',
        templateMin: _.template(WidgetMinTemplate),
        templateMax: _.template(WidgetMaxTemplate),

        events: {
            'click .sidebar-widget-header-toggle': 'onClickToggle',
            'click .sidebar-widget-settings': 'onClickSettings',
            'click .sidebar-widget-remove': 'onClickRemove',
            'mouseleave': 'onHoverOut'
        },

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;
            var template = null;

            if (model.state === Constants.WIDGET_MINIMIZED) {
                template = view.templateMin;
            } else {
                template = view.templateMax;
            }

            view.$el.html(template(model.toJSON()));

            if (model.state === Constants.WIDGET_MAXIMIZED) {
                requirejs([model.get('module')], function (Widget) {
                    var $widgetContent = view.$el.find('.sidebar-widget-content');
                    var widgetView = new Widget.ContentView({
                        model: model
                    });
                    widgetView.setElement($widgetContent);
                    widgetView.render();
                });
            }

            return view;
        },

        onClickToggle: function (e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
        },

        onClickSettings: function (e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('setupWidget', this.model.cid);
        },

        onClickRemove: function (e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('removeWidget', this.model.cid);
        },

        onHoverOut: function () {
            Backbone.trigger('hideWidgetHover', this.model.cid);
        }
    });

    return WidgetView;
});