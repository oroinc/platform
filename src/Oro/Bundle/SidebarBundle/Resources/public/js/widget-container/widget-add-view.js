
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var widgetAddTemplate = require('text!./templates/widget-add-template.html');
    var WidgetContainerModel = require('./model');

    var Modal = require('oroui/js/modal');
    var constants = require('../constants');

    var __ = require('orotranslation/js/translator');

    /**
     * @export  orosidebar/js/widget-container/widget-add-view
     * @class   orosidebar.widgetContainer.WidgetAddView
     * @extends oro.Modal
     */
    return Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal',

        options: {
            sidebar: null
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            options.content = _.template(widgetAddTemplate)({
                'availableWidgets': options.sidebar.getAvailableWidgets()
            });
            options.title = __('oro.sidebar.widget.add.dialog.title');

            Modal.prototype.initialize.apply(this, arguments);
        },

        open: function() {
            var view = this;
            var position = this.options.sidebar.getPosition();

            Modal.prototype.open.apply(this, arguments);

            var selected = null;

            view.$el.find('ol').selectable({
                selected: function(event, ui) {
                    selected = ui.selected;
                }
            });

            view.once('ok', function() {
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

                var widget = new WidgetContainerModel(_.extend({}, widgetData, {
                    widgetName: widgetName,
                    position: widgets.length,
                    placement: placement
                }));
                widgets.push(widget);

                widget.save();

                view.close();
            });
        }
    });
});
