/*global define*/
define(['jquery', 'underscore', 'oro/dialog-widget', 'oroui/js/widget-manager', 'oroui/js/mediator'],
    function ($, _, DialogWidget, WidgetManager, mediator) {
        'use strict';

        /**
         * @export oroui/js/widget-control-initializer
         * @class oroui.widgetControlInitializer
         */
        return {
            init: function (container) {
                var self = this;
                container.find('[data-widget-type]').each(
                    function (index, controlElement) {
                        self.initWidgetControlElement(controlElement);
                    }
                );
            },
            initWidgetControlElement: function (controlElement) {
                controlElement = $(controlElement);
                if (controlElement.data('widget-initialized')) {
                    return;
                } else {
                    controlElement.data('widget-initialized', true);
                }

                var widgetType        = controlElement.data('widget-type');
                var widgetEvent       = controlElement.data('widget-event') || 'click';

                require(['oro/' + widgetType + '-widget'],
                    function (Widget) {
                        $(controlElement).on(
                            widgetEvent,
                            function (event) {
                                var widgetOptions     = $.extend(true, {}, controlElement.data('widget-options'));
                                var allowMultiple     = controlElement.data('widget-multiple');
                                var reloadGridName    = controlElement.data('widget-reload-grid-name');
                                var reloadWidgetAlias = controlElement.data('widget-reload-widget-alias');

                                if (!widgetOptions.url && controlElement.data('url')) {
                                    widgetOptions.url = controlElement.data('url') || controlElement.attr('href');
                                }

                                if (!allowMultiple) {
                                    // Only one instance of widget is allowed
                                    if (controlElement.data('widget-opened')) {
                                        return;
                                    } else {
                                        controlElement.data('widget-opened', true);
                                    }
                                }

                                // Create and open widget
                                var widget = new Widget(widgetOptions);

                                var reloadEvent       = controlElement.data('widget-reload-event')
                                    || 'widget_success:' + (widget.getAlias() || widget.getWid());

                                mediator.on(reloadEvent, function () {
                                    WidgetManager.getWidgetInstanceByAlias(reloadWidgetAlias, function (widget) {
                                        widget.loadContent();
                                    });
                                    if (reloadGridName) {
                                        mediator.trigger('datagrid:doRefresh:' + reloadGridName);
                                    }
                                });

                                if (!allowMultiple) {
                                    widget.on('widgetRemove', function () {
                                        controlElement.data('widget-opened', false);
                                    });
                                }

                                widget.render();

                                event.preventDefault();
                            }
                        );
                    }
                );
            }
        };
    }
);
