/*jslint nomen: true, vars: true*/
/*global define, requirejs*/

define(['jquery', 'underscore', 'backbone', 'oro/sidebar/constants', 'text!oro/sidebar/widget-container/widget-min-template',
    'text!oro/sidebar/widget-container/widget-max-template'
    ], function ($, _, Backbone, constants, widgetMinTemplate, widgetMaxTemplate) {
    'use strict';

    /**
     * @export  oro/sidebar/widget-controller/view
     * @class oro.sidebar.widget-controller.View
     * @extends Backbone.View
     */
    var WidgetView = Backbone.View.extend({
        className: 'sidebar-widget',
        templateMin: _.template(widgetMinTemplate),
        templateMax: _.template(widgetMaxTemplate),

        events: {
            'click .sidebar-widget-header-toggle': 'onClickToggle',
            'click .sidebar-widget-settings': 'onClickSettings',
            'click .sidebar-widget-remove': 'onClickRemove',
            'click .sidebar-widget-close': 'onClickClose'
        },

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;
            var template = null;

            if (model.get('state') === constants.WIDGET_MINIMIZED) {
                template = view.templateMin;
            } else {
                template = view.templateMax;
            }

            view.$el.html(template(model.toJSON()));
            view.$el.attr('data-cid', model.cid);

            if (model.get('state') !== constants.WIDGET_MINIMIZED && model.get('module')) {
                requirejs([model.get('module')], function (Widget) {
                    var $widgetContent = view.$el.find('.sidebar-widget-content');
                    if (!view.contentView) {
                        view.contentView = new Widget.ContentView({
                            model: model
                        });
                    }
                    view.contentView.setElement($widgetContent);
                    view.contentView.render();
                });
            }

            return view;
        },

        onClickToggle: function (e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
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

        onClickClose: function (e) {
            e.stopPropagation();
            e.preventDefault();
            Backbone.trigger('closeWidget', this.model.cid);
        }
    });

    return WidgetView;
});
