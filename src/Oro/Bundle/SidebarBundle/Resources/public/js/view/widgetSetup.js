define(function (require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');

    var WidgetSetupView = Backbone.View.extend({
        className: 'sidebar-widgetsetup',

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;

            var moduleId = model.get('module');

            requirejs([moduleId], function (Widget) {
                view.setupView = new Widget.SetupView({
                    model: model
                });
                view.setupView.render();
                view.$el.append(view.setupView.$el);

                view.$dialog = view.$el.dialog({
                    modal: true,
                    resizable: false,
                    height: 300,
                    buttons: {
                        'Save': function () {
                            var settings = model.get('settings');
                            settings.content += ' ' + Date.now();

                            model.set({ settings: settings }, { silent: true });
                            model.trigger('change');

                            view.close();
                        },
                        Cancel: function () {
                            view.close();
                        }
                    }
                });
            });

            return view;
        },

        close: function () {
            var view = this;
            view.$dialog.dialog('close');
            view.setupView.remove();
            view.remove();
        }
    });

    return WidgetSetupView;
});