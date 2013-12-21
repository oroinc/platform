/*jslint nomen: true, vars: true*/
/*global define, requirejs*/

define(function (require) {
    'use strict';

    var Modal = require('oro/modal');

    /**
     * @export  oro/sidebar/widget-controller/widget-setup-view
     * @class oro.sidebar.widget-controller.WidgetSetupView
     * @extends oro.Modal
     */
    var WidgetSetupView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal',

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
            });

            Modal.prototype.initialize.apply(this, arguments);

            view.once('ok cancel', function () {
                view.setupView.remove();
            });
        }
    });

    return WidgetSetupView;
});
