define(['jquery', 'underscore', 'oroui/js/widget-manager', 'routing'
], function($, _, widgetManager, routing) {
    'use strict';

    /**
     * @export  oroform/js/create-select-type-handler
     * @class   oroform.createSelectTypeHandler
     */
    return function(
        btnContainer,
        viewContainer,
        currentModeEl,
        existingEl,
        routeParametersEl,
        gridWidgetAlias,
        viewWidgets,
        gridModelId,
        templateMode,
        templateRouteParameters
    ) {
        var setAltLabel = function(el, mode) {
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

        var setCurrentMode = function(mode) {
            var $btnContainer = $(btnContainer);
            setAltLabel($btnContainer.find('.entity-select-btn'), mode);
            setAltLabel($btnContainer.find('.entity-create-btn'), mode);
            setAltLabel($btnContainer.find('.entity-cancel-btn'), mode);

            var $viewContainer = $(viewContainer);
            $viewContainer.removeClass('create grid view').addClass(mode);
            $(currentModeEl).val(mode);

            var entityCreateBlock = $viewContainer.find('.entity-create-block');
            if (mode === 'create') {
                entityCreateBlock.removeAttr('data-validation-ignore');
            } else {
                entityCreateBlock.attr('data-validation-ignore', true);
            }
        };

        var getCurrentMode = function() {
            return $(currentModeEl).val();
        };

        // Render grid and change current mode to grid
        var $btnContainer = $(btnContainer);
        var $selectBtn = $btnContainer.find('.entity-select-btn');
        var $createBtn = $btnContainer.find('.entity-create-btn');
        var $cancelBtn = $btnContainer.find('.entity-cancel-btn');

        var drawGrid = function() {
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
        };

        $selectBtn.on('click', function() {
            drawGrid();
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

        var drawViewWidget = function(viewWidget, routeParameters) {
            widgetManager.getWidgetInstanceByAlias(viewWidget.widget_alias, function(w) {
                w.setUrl(routing.generate(viewWidget.route_name, routeParameters));
                w.render();
            });
        };

        var loadViewWidgets = function(model) {
            var getRouteParameters = function(map, model) {
                var parameters = {};
                for (var routeParamName in map) {
                    if (map.hasOwnProperty(routeParamName)) {
                        parameters[routeParamName] = model.get(map[routeParamName]);
                    }
                }
                return parameters;
            };

            var allRouteParameters = {};
            for (var i = 0; i < viewWidgets.length; i++) {
                var routeParameters = getRouteParameters(viewWidgets[i].grid_row_to_route, model);
                var widgetAlias = viewWidgets[i].widget_alias;
                allRouteParameters[widgetAlias] = routeParameters;
                drawViewWidget(viewWidgets[i], routeParameters);
            }
            $(routeParametersEl).val(JSON.stringify(allRouteParameters));
        };

        // On grid row select render widgets and change current mode to view
        widgetManager.getWidgetInstanceByAlias(gridWidgetAlias, function(widget) {
            widget.on('grid-row-select', function(data) {
                var selectedId = data.model.get(gridModelId);
                if (selectedId !== $(existingEl).val()) {
                    $(existingEl).val(selectedId);
                    loadViewWidgets(data.model);
                }
                setCurrentMode('view');
            });
        });

        var getCurrentRouteParameters = function() {
            return JSON.parse($(routeParametersEl).val());
        };

        var setMode = function(mode) {
            setCurrentMode(mode);
            switch (mode) {
                case 'view':
                    var allRouteParameters = getCurrentRouteParameters();
                    for (var i = 0; i < viewWidgets.length; i++) {
                        var widgetAlias = viewWidgets[i].widget_alias;
                        if (allRouteParameters[widgetAlias]) {
                            drawViewWidget(viewWidgets[i], allRouteParameters[widgetAlias]);
                        }
                    }
                    break;
                case 'grid':
                    drawGrid();
                    break;
            }
        };

        // update mode
        var currentMode = getCurrentMode();
        var currentRouteParameters = getCurrentRouteParameters();
        if (templateMode !== currentMode ||
            currentMode === 'view' && !_.isEqual(templateRouteParameters, currentRouteParameters)
        ) {
            setMode(currentMode);
        }
    };
});
