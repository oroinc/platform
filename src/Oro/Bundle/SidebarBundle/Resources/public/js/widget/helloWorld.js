define(['jquery', 'backbone'], function ($, Backbone) {
    'use strict';

    var HelloWorldView = Backbone.View.extend({
        template: _.template('<span><%= settings.content %></span>'),

        initialize: function () {
            this.model.on('change', this.render, this);
        },

        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });

    return HelloWorldView;
});