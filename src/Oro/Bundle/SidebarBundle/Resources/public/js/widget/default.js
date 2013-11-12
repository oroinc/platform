define(['jquery', 'backbone'], function ($, Backbone) {
    'use strict';

    var DefaultView = Backbone.View.extend({
        template: _.template('<span></span>'),

        initialize: function () {
            this.model.on('change', this.render, this);
        },

        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });

    return DefaultView;
});