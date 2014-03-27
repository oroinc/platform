/*jslint nomen: true, vars: true*/
/*global define*/

define(['jquery', 'underscore', 'backbone'], function ($, _, Backbone) {
    'use strict';

    /**
     * @export  orosidebar/widget/sticky-note
     */
    var stickyNote = {};

    stickyNote.ContentView = Backbone.View.extend({
        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var settings = view.model.get('settings') || {};
            var content = _.escape(String(settings.content)).replace(/\r?\n/g, '<br/>');
            view.$el.html(content);
            return view;
        }
    });

    stickyNote.SetupView = Backbone.View.extend({
        template: _.template('<h3>Sticky Note settings</h3><textarea style="width: 400px; height: 150px;"><%= settings.content %></textarea>'),

        initialize: function () {
            this.on('ok', this.onSubmit);
            Backbone.View.prototype.initialize.apply(this, arguments);
        },

        render: function () {
            var view = this;
            view.$el.html(view.template(view.model.toJSON()));
            return view;
        },

        onSubmit: function () {
            var view = this;
            var model = view.model;

            var content = view.$el.find('textarea').val();
            var settings = model.get('settings');

            if (settings.content != content) {
                settings.content = content;
                model.set({ settings: settings }, { silent: true });
                model.trigger('change');
            }

            this.trigger('close');
        }
    });

    return stickyNote;
});
