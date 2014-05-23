/*jslint nomen: true, vars: true*/
/*global define*/
define(function (require) {
    'use strict';

    var _ = require('underscore'),
        Backbone = require('backbone'),

        iconTemplate = require('text!./templates/icon-template.html'),
        constants    = require('../constants');

    /**
     * @export  orosidebar/js/widget-container/icon-view
     * @class   orosidebar.widgetContainer.IconView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        className: 'sidebar-icon',

        events: {
            'click': 'onClick'
        },

        initialize: function () {
            var view = this;
            view.template = _.template(iconTemplate);
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view  = this,
                model = view.model;

            view.$el.html(view.template(model.toJSON()));
            view.$el.attr('data-cid', model.cid);

            if (model.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                view.$el.addClass('sidebar-icon-active');
            } else {
                view.$el.removeClass('sidebar-icon-active');
            }

            return view;
        },

        onClick: function (e) {
            e.stopPropagation();
            e.preventDefault();

            if (this.model.isDragged) {
                return;
            }

            var cord = this.$el.offset();

            Backbone.trigger('showWidgetHover', this.model.cid, cord);
        }
    });
});
