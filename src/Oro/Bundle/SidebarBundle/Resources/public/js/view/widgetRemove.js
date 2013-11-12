define(function (require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');

    var WidgetRemoveTemplate = require('text!oro/template/widgetRemove');

    var WidgetRemoveView = Backbone.View.extend({
        className: 'sidebar-widgetremove',
        template: _.template(WidgetRemoveTemplate),

        initialize: function () {
            var view = this;
            view.listenTo(view.model, 'change', view.render);
        },

        render: function () {
            var view = this;
            var model = view.model;
            var widget = view.options.widget;

            view.$el.html(view.template(view.model.toJSON()));

            view.$dialog = view.$el.dialog({
                modal: true,
                resizable: false,
                height: 200,
                buttons: {
                    'Remove': function () {
                        model.widgets.remove(widget);
                        view.close();
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

    return WidgetRemoveView;
});