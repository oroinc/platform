/*jslint nomen: true, vars: true*/
/*global define*/

define(['jquery', 'underscore', 'backbone'], function ($, _, Backbone) {
    'use strict';

    /**
     * @export  oro/sidebar/widget/hello-world
     */
    var helloWorld = {};

    helloWorld.defaults = {
        widget_name: 'hello_world',
        title: 'Hello world',
        icon: '/bundles/orosidebar/sidebar_widgets/hello_world/img/icon.ico',
        settings: function () {
            return {
                content: 'Welcome to OroCRM!<br/>'
                + 'OroCRM is an easy-to-use, open source CRM with built-in marketing tools for your ecommerce business. learn more at <a href="http://orocrm.com">orocrm.com</a>'
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
        template: _.template('<h3>Hello world settings</h3><textarea style="width: 400px; height: 150px;"><%= settings.content %></textarea>'),

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
        }
    });

    return helloWorld;
});
