
define(function(require) {
    'use strict';

    var Modal = require('oroui/js/modal');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    /**
     * @export  oro/sidebar/widget-controller/widget-setup-view
     * @class   oro.sidebar.widgetController.WidgetSetupView
     * @extends oro.Modal
     */
    return Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-setup',

        initialize: function(options) {
            var view = this;
            var model = view.model;

            options.content = '<div class="sidebar-widget-setup"></div>';
            options.title = _.result(options.content, 'widgetTitle') || __('Widget setup');
            options.snapshot = options.snapshot || {};

            var moduleId = model.get('module');

            requirejs([moduleId], function(Widget) {
                view.setupView = new Widget.SetupView({
                    model: model
                });
                view.setupView.render();
                view.$el.find('.sidebar-widget-setup').append(view.setupView.$el);

                view.once('ok', function() {
                    view.setupView.trigger('ok');
                });

                view.on('ok', function() {
                    model.save();
                });

                view.once('cancel', function() {
                    view.setupView.trigger('cancel');
                    view.setupView.remove();
                });

                view.on('cancel', function() {
                    model.set(JSON.parse(options.snapshot), {silent: true});
                    model.trigger('change');
                });

                view.setupView.once('close', function() {
                    view.setupView.remove();
                    view.close();
                });
            });

            Modal.prototype.initialize.apply(this, arguments);
        }
    });
});
