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
            this.model.on('change', this.render, this);
        },

        render: function () {
            var template = null;

            if (this.model.state === Constants.WIDGET_MINIMIZED) {
                template = this.templateMin;
            } else {
                template = this.templateMax;
            }

            this.$el.html(template(this.model.toJSON()));

            return this;
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