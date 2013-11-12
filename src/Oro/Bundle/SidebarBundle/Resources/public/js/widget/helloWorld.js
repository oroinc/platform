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

    HelloWorldView.defaults = {
        title: 'Hello world',
        icon: 'http://i214.photobucket.com/albums/cc237/xFlyer/gmail-pencil16.png',
        settings: function () {
            return {
                content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse pulvinar.'
            };
        }
    };

    return HelloWorldView;
});