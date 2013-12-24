/*jslint nomen: true, vars: true*/
/*global define*/

define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');

    var iconTemplate = require('text!oro/sidebar/widget-container/icon-template');
    var constants = require('oro/sidebar/constants');

    /**
     * @export  oro/sidebar/widget-controller/icon-view
     * @class oro.sidebar.widget-controller.IconView
     * @extends Backbone.View
     */
    var IconView = Backbone.View.extend({
        className: 'sidebar-icon',
        template: _.template(iconTemplate),

        events: {
            'click': 'onClick'
        },

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;

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

    return IconView;
});
