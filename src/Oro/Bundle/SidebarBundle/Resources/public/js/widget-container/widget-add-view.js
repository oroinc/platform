/*jslint nomen: true, vars: true*/
/*global define, requirejs*/

define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var IconView = require('./icon-view');
    var widgetAddTemplate = require('text!./templates/widget-add-template.html');
    var WidgetContainerModel = require('./model');

    var Modal = require('oroui/js/modal');
    var DialogWidget = require('oro/dialog-widget');
    var constants = require('../constants');

    /**
     * @export  orosidebar/js/widget-container/widget-add-view
     * @class   orosidebar.widgetContainer.WidgetAddView
     * @extends oro.Modal
     */
    var WidgetAddView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal',

        options: {
            sidebar: null
        },

        initialize: function (options) {
            var availableWidgets = options.sidebar.getAvailableWidgets(),
                widgets          = options.sidebar.getWidgets();

            widgets.each(function (widget) {
                var iconView   = new IconView({model: widget}),
                    widgetName = widget.get('widgetName');
                availableWidgets[widgetName].iconView = iconView.render().$el.html();
            });

            options.content = _.template(widgetAddTemplate, {
                'availableWidgets': availableWidgets
            });

            options.title = 'Select widget to add';

            Modal.prototype.initialize.apply(this, arguments);
        },

        open: function () {
            var view = this;
            var position = this.options.sidebar.getPosition();

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
                var availableWidgets = this.options.sidebar.getAvailableWidgets();
                var widgets = this.options.sidebar.getWidgets();

                var widgetName = $(selected).closest('li').data('widget-name');
                var widgetData = availableWidgets[widgetName];

                var placement = null;
                if (position === constants.SIDEBAR_LEFT) {
                    placement = 'left';
                } else if (position === constants.SIDEBAR_RIGHT) {
                    placement = 'right';
                }

                var widget = new WidgetContainerModel({
                    widgetName: widgetName,
                    position: widgets.length,
                    placement: placement
                });
                widget.update(widgetData);
                widget.set('settings', widgetData.settings);

                widgets.push(widget);

                widget.save();

                view.close();
            });
        }
    });

    return WidgetAddView;
});
