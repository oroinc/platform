define(['jquery', 'backbone'], function ($, Backbone) {
    'use strict';

    var helloWorld = {};

    helloWorld.defaults = {
        title: 'Hello world',
        icon: 'http://i214.photobucket.com/albums/cc237/xFlyer/gmail-pencil16.png',
        settings: function () {
            return {
                content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse pulvinar.'
            };
        }
    };

    helloWorld.ContentView = Backbone.View.extend({
        template: _.template('<span><%= settings.content %></span>'),

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            view.$el.html(view.template(view.model.toJSON()));
            return view;
        }
    });

    helloWorld.SetupView = Backbone.View.extend({
        template: _.template('<textarea style="width: 250px; height: 150px;"><%= settings.content %></textarea>'),

        events: {
            'keyup textarea': 'onKeyup'
        },

        render: function () {
            var view = this;
            view.$el.html(view.template(view.model.toJSON()));
            return view;
        },

        onKeyup: function (e) {
            var view = this;
            var model = view.model;

            var content = view.$el.find('textarea').val();

            var settings = model.get('settings');
            settings.content = content;

            model.set({ settings: settings }, { silent: true });
            model.trigger('change');
        },
    });

    return helloWorld;
});