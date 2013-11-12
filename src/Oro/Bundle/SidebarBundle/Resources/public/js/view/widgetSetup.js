define(function (require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');

    var WidgetSetupView = Backbone.View.extend({
        className: 'sidebar-widgetsetup',

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

                var modelSnapshot = JSON.stringify(model);

                view.$dialog = view.$el.dialog({
                    modal: true,
                    resizable: false,
                    height: 300,
                    buttons: {
                        'Save': function () {
                            view.close();
                        },
                        Cancel: function () {
                            model.set(JSON.parse(modelSnapshot), { silent: true });
                            model.trigger('change');

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