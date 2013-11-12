define(['jquery', 'backbone', 'oro/constants', 'text!oro/template/icon'],
    function ($, Backbone, Constants, IconTemplate) {
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
            this.$el.html(this.template(this.model.toJSON()));
            this.$el.attr('data-cid', this.model.cid);

            return this;
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