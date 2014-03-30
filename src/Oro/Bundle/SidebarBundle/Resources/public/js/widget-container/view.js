/*jslint nomen: true, vars: true*/
/*global define, requirejs*/

define(['jquery', 'underscore', 'backbone', '../constants',
    'text!./templates/widget-min-template.html',
    'text!./templates/widget-max-template.html'
    ], function ($, _, Backbone, constants, widgetMinTemplate, widgetMaxTemplate) {
    'use strict';

    /**
     * @export  orosidebar/js/widget-container/view
     * @class   orosidebar.widgetContainer.View
     * @extends Backbone.View
     */
    var WidgetView = Backbone.View.extend({
        templateMin: _.template(widgetMinTemplate),
        templateMax: _.template(widgetMaxTemplate),

        events: {
            'click .sidebar-widget-header-toggle': 'onClickToggle',
            'click .sidebar-widget-refresh': 'onClickRefresh',
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


            if (view.model.get('cssClass')) {
                view.$el.attr('class', view.model.get('cssClass'));
            }

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

            if (model.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                view.$el.addClass('sidebar-widget-popup');
            } else {
                view.$el.removeClass('sidebar-widget-popup');
            }

            return view;
        },

        setOffset: function (cord) {
            var view = this;
            view.$el.offset(cord);
            view.$el.find('.sidebar-widget-content').css('max-height', view.$el.height());
        },

        onClickToggle: function (e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
        },

        onClickRefresh: function (e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('refreshWidget', this.model.cid);
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
