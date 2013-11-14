define(function (require) {
    'use strict';

    var Modal = require('oro/modal');

    var WidgetSetupView = Modal.extend({
        initialize: function(options) {
            var view = this;
            var model = view.model;

            options.content = '<div class="sidebar-widgetsetup"></div>';

            var moduleId = model.get('module');

            requirejs([moduleId], function (Widget) {
                view.setupView = new Widget.SetupView({
                    model: model
                });
                view.setupView.render();
                view.$el.find('.sidebar-widgetsetup').append(view.setupView.$el);
            });

            Modal.prototype.initialize.apply(this, arguments);

            view.once('ok cancel', function () {
                view.setupView.remove();
            });
        },
    });

    return WidgetSetupView;
});