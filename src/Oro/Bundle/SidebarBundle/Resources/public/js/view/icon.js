define(['jquery', 'backbone', 'oro/constants', 'text!oro/template/icon'],
    function ($, Backbone, Constants, IconTemplate) {
        'use strict';

    var IconView = Backbone.View.extend({
        className: 'sidebar-icon',
        template: _.template(IconTemplate),

        events: {
            'mouseenter': 'onHoverIn'
        },

        initialize: function () {
            this.model.on('change', this.render, this);
        },

        render: function () {
            this.$el.html(this.template(this.model.toJSON()));

            return this;
        },

        onHoverIn: function (e) {
            var cord = this.$el.offset();

            Backbone.trigger('showWidgetHover', this.model.cid, cord);
        }
    });

    return IconView;
});