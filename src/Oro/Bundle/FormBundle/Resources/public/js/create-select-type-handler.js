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
        const setAltLabel = function(el, mode) {
            const $labelHolder = el.find('span');
            const altLabel = $labelHolder.data('alt-label-' + mode);
            const regularLabel = $labelHolder.data('label');
            if (altLabel) {
                if (!regularLabel) {
                    $labelHolder.data('label', $labelHolder.html());
                }
                $labelHolder.html(altLabel);
            } else if (regularLabel) {
                $labelHolder.html(regularLabel);
            }
        };

        const setCurrentMode = function(mode) {
            const $btnContainer = $(btnContainer);
            setAltLabel($btnContainer.find('.entity-select-btn'), mode);
            setAltLabel($btnContainer.find('.entity-create-btn'), mode);
            setAltLabel($btnContainer.find('.entity-cancel-btn'), mode);

            const $viewContainer = $(viewContainer);
            $viewContainer.removeClass('create grid view').addClass(mode);
            $(currentModeEl).val(mode);

            const entityCreateBlock = $viewContainer.find('.entity-create-block');
            if (mode === 'create') {
                entityCreateBlock.removeAttr('data-validation-ignore');
            } else {
                entityCreateBlock.attr('data-validation-ignore', true);
            }
        };

        const getCurrentMode = function() {
            return $(currentModeEl).val();
        };

        // Render grid and change current mode to grid
        const $btnContainer = $(btnContainer);
        const $selectBtn = $btnContainer.find('.entity-select-btn');
        const $createBtn = $btnContainer.find('.entity-create-btn');
        const $cancelBtn = $btnContainer.find('.entity-cancel-btn');

        const drawGrid = function() {
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

        const drawViewWidget = function(viewWidget, routeParameters) {
            widgetManager.getWidgetInstanceByAlias(viewWidget.widget_alias, function(w) {
                w.setUrl(routing.generate(viewWidget.route_name, routeParameters));
                w.render();
            });
        };

        const loadViewWidgets = function(model) {
            const getRouteParameters = function(map, model) {
                const parameters = {};
                for (const routeParamName in map) {
                    if (map.hasOwnProperty(routeParamName)) {
                        parameters[routeParamName] = model.get(map[routeParamName]);
                    }
                }
                return parameters;
            };

            const allRouteParameters = {};
            for (let i = 0; i < viewWidgets.length; i++) {
                const routeParameters = getRouteParameters(viewWidgets[i].grid_row_to_route, model);
                const widgetAlias = viewWidgets[i].widget_alias;
                allRouteParameters[widgetAlias] = routeParameters;
                drawViewWidget(viewWidgets[i], routeParameters);
            }
            $(routeParametersEl).val(JSON.stringify(allRouteParameters));
        };

        // On grid row select render widgets and change current mode to view
        widgetManager.getWidgetInstanceByAlias(gridWidgetAlias, function(widget) {
            widget.on('grid-row-select', function(data) {
                const selectedId = data.model.get(gridModelId);
                if (selectedId !== $(existingEl).val()) {
                    $(existingEl).val(selectedId);
                    loadViewWidgets(data.model);
                }
                setCurrentMode('view');
            });
        });

        const getCurrentRouteParameters = function() {
            return JSON.parse($(routeParametersEl).val());
        };

        const setMode = function(mode) {
            setCurrentMode(mode);
            switch (mode) {
                case 'view':
                    const allRouteParameters = getCurrentRouteParameters();
                    for (let i = 0; i < viewWidgets.length; i++) {
                        const widgetAlias = viewWidgets[i].widget_alias;
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
        const currentMode = getCurrentMode();
        const currentRouteParameters = getCurrentRouteParameters();
        if (templateMode !== currentMode ||
            currentMode === 'view' && !_.isEqual(templateRouteParameters, currentRouteParameters)
        ) {
            setMode(currentMode);
        }
    };
});
