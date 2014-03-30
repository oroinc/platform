/*jslint nomen: true, vars: true*/
/*global define, requirejs*/

define(function (require) {
    'use strict';

    var Modal = require('oroui/js/modal');

    /**
     * @export  oro/sidebar/widget-controller/widget-setup-view
     * @class oro.sidebar.widget-controller.WidgetSetupView
     * @extends oro.Modal
     */
    var WidgetSetupView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-setup',

        initialize: function (options) {
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

                view.once('ok', function () {
                    view.setupView.trigger('ok');
                });

                view.once('cancel', function () {
                    view.setupView.trigger('cancel');
                    view.setupView.remove();
                });

                view.setupView.once('close', function () {
                    view.setupView.remove();
                    view.close();
                });
            });

            Modal.prototype.initialize.apply(this, arguments);
        }
    });

    return WidgetSetupView;
});
