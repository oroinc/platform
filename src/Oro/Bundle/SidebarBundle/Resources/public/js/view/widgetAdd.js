define(function (require) {
    'use strict';

    var $ = require('jquery');

    var WidgetAddTemplate = require('text!oro/template/widgetAdd');
    var WidgetModel = require('oro/model/widget');

    var Modal = require('oro/modal');

    var WidgetAddView = Modal.extend({
        initialize: function(options) {
            var view = this;
            var model = view.model;

            options.content = _.template(WidgetAddTemplate, model.toJSON());

            Modal.prototype.initialize.apply(this, arguments);
        },

        open: function () {
            var view = this;
            var model = view.model;

            Modal.prototype.open.apply(this, arguments);

            var selected = null;

            view.$el.find('ol').selectable({
                selected: function (event, ui) {
                    selected = ui.selected;
                }
            });

            view.once('ok', function () {
                if (!selected) {
                    view.close();
                    return;
                }

                var moduleId = $(selected).closest('li').data('moduleid');

                requirejs([moduleId], function (Widget) {
                    var widget = new WidgetModel({
                        title: Widget.defaults.title,
                        icon: Widget.defaults.icon,
                        module: moduleId,
                        settings: Widget.defaults.settings(),
                        order: model.widgets.length
                    });

                    model.widgets.push(widget);

                    view.close();
                });
            });
        }
    });

    return WidgetAddView;
});