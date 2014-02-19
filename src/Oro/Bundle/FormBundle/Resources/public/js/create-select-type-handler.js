/* global define */
define(['jquery', 'oro/widget-manager', 'routing', 'oro/navigation'],
function ($, widgetManager, routing, Navigation) {
    'use strict';

    /**
     * @export  oro/create-select-type-handler
     * @class   oro.createSelectTypeHandler
     */
    return function (
        btnContainer,
        viewContainer,
        currentModeEl,
        existingEl,
        gridWidgetAlias,
        viewWidgets,
        gridModelId
    ) {
        var entityCreateBlock = viewContainer.find('.entity-create-block');

        var setCurrentMode = function (mode) {
            btnContainer.removeClass('create grid view').addClass(mode);
            viewContainer.removeClass('create grid view').addClass(mode);
            currentModeEl.val(mode);

            if (mode == 'create') {
                entityCreateBlock.removeAttr('data-validation-ignore');
            } else {
                entityCreateBlock.attr('data-validation-ignore', true);
            }
        };

        // Render grid and change current mode to grid
        btnContainer.find('.entity-select-btn').on('click', function() {
            widgetManager.getWidgetInstanceByAlias(gridWidgetAlias, function(widget) {
                if (widget.firstRun) {
                    widget.render();
                }
            });
            setCurrentMode('grid');
        });

        // Render create from and change current mode to create
        btnContainer.find('.entity-create-btn').on('click', function() {
            setCurrentMode('create');
        });

        var loadViewWidgets = function (model) {
            var getRouteParameters = function(map, model) {
                var parameters = {};
                for (var routeParamName in map) if (map.hasOwnProperty(routeParamName)) {
                    parameters[routeParamName] = model.get(map[routeParamName]);
                }
                return parameters;
            };

            for (var i = 0; i < viewWidgets.length; i++) {
                (function (viewWidget) {
                    widgetManager.getWidgetInstanceByAlias(viewWidget['widget_alias'], function(w) {
                        w.setUrl(
                            routing.generate(
                                viewWidget['route_name'],
                                getRouteParameters(viewWidget['grid_row_to_route'], model)
                            )
                        );
                        w.render();
                    });
                })(viewWidgets[i]);
            }
        };

        // On grid row select render widgets and change current mode to view
        widgetManager.getWidgetInstanceByAlias(gridWidgetAlias, function (widget) {
            widget.on('grid-row-select', function(data) {
                existingEl.val(data.model.get(gridModelId));
                loadViewWidgets(data.model);
                setCurrentMode('view');
            });
        });
    }
});