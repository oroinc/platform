define(['jquery', 'backbone', 'text!oro/sidebar/widget-container/icon-template'],
    function ($, Backbone, IconTemplate) {
        'use strict';

    var IconView = Backbone.View.extend({
        className: 'sidebar-icon',
        template: _.template(IconTemplate),

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