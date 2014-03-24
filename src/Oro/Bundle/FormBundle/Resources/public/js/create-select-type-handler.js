/* global define */
define(['jquery', 'oroui/js/widget-manager', 'routing', 'oronavigation/js/navigation'],
function ($, widgetManager, routing) {
    'use strict';

    /**
     * @export  oroform/js/create-select-type-handler
     * @class   oroform.createSelectTypeHandler
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
        var setAltLabel = function (el, mode) {
            var $labelHolder = el.find('span');
            var altLabel = $labelHolder.data('alt-label-' + mode);
            var regularLabel = $labelHolder.data('label');
            if (altLabel) {
                if (!regularLabel) {
                    $labelHolder.data('label', $labelHolder.html());
                }
                $labelHolder.html(altLabel);
            } else if (regularLabel) {
                $labelHolder.html(regularLabel);
            }
        };

        var setCurrentMode = function (mode) {
            var $btnContainer = $(btnContainer);
            setAltLabel($btnContainer.find('.entity-select-btn'), mode);
            setAltLabel($btnContainer.find('.entity-create-btn'), mode);
            setAltLabel($btnContainer.find('.entity-cancel-btn'), mode);

            var $viewContainer = $(viewContainer);
            $viewContainer.removeClass('create grid view').addClass(mode);
            $(currentModeEl).val(mode);

            var entityCreateBlock = $viewContainer.find('.entity-create-block');
            if (mode == 'create') {
                entityCreateBlock.removeAttr('data-validation-ignore');
            } else {
                entityCreateBlock.attr('data-validation-ignore', true);
            }
        };

        // Render grid and change current mode to grid
        var $btnContainer = $(btnContainer);
        var $selectBtn = $btnContainer.find('.entity-select-btn');
        var $createBtn = $btnContainer.find('.entity-create-btn');
        var $cancelBtn = $btnContainer.find('.entity-cancel-btn');

        $selectBtn.on('click', function() {
            widgetManager.getWidgetInstanceByAlias(gridWidgetAlias, function(widget) {
                if (widget.firstRun) {
                    widget.render();
                    widget.on('renderComplete', function() {
                        setCurrentMode('grid');
                    });
                } else {
                    setCurrentMode('grid');
                }
            });

        });

        // Render create from and change current mode to create
        $createBtn.on('click', function() {
            setCurrentMode('create');
        });

        $cancelBtn.on('click', function() {
            if ($(existingEl).val()) {
                setCurrentMode('view');
            } else {
                setCurrentMode('create');
            }
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
                var selectedId = data.model.get(gridModelId);
                if (selectedId != $(existingEl).val()) {
                    $(existingEl).val(selectedId);
                    loadViewWidgets(data.model);
                }
                setCurrentMode('view');
            });
        });
    }
});
