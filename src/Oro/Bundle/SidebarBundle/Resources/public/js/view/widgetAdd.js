define(function (require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');

    var Constants = require('oro/constants');
    var WidgetAddTemplate = require('text!oro/template/widgetAdd');
    var WidgetModel = require('oro/model/widget');

    var WidgetAddView = Backbone.View.extend({
        className: 'sidebar-widgetadd',
        template: _.template(WidgetAddTemplate),

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;

            var selected = null;

            view.$el.html(view.template(view.model.toJSON()));
            view.$el.find('ol').selectable({
                selected: function (event, ui) {
                    selected = ui.selected;
                }
            });

            view.$dialog = view.$el.dialog({
                modal: true,
                resizable: false,
                height: 300,
                buttons: {
                    'Add': function () {
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
                                settings: Widget.defaults.settings()
                            });

                            model.widgets.push(widget);

                            view.close();
                        });
                    },
                    Cancel: function () {
                        view.close();
                    }
                }
            });

            return view;
        },

        close: function () {
            var view = this;
            view.$dialog.dialog('close');
            view.remove();
        }
    });

    return WidgetAddView;
});