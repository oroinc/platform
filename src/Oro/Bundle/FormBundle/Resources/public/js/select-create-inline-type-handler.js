define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');
    require('jquery.select2');

    /**
     * @export  oroform/js/select-create-inline-type-handler
     * @class   oroform.selectCreateInlineTypeHandler
     */
    return function(container,
        selectorEl,
        label,
        urlParts,
        existingEntityGridId,
        createEnabled
    ) {
        var handleGridSelect = function(e) {
            e.preventDefault();

            var routeName = (urlParts.grid.gridWidgetView) ? urlParts.grid.gridWidgetView : urlParts.grid.route;
            var routeParams = urlParts.grid.parameters;

            var additionalRequestParams = selectorEl.data('select2_query_additional_params');
            if (additionalRequestParams) {
                routeParams = $.extend({}, routeParams, additionalRequestParams);
            }

            var entitySelectDialog = new DialogWidget({
                title: __('Select {{ entity }}', {'entity': label}),
                url: routing.generate(routeName, routeParams),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650,
                    close: function() {
                        selectorEl.off('.' + entitySelectDialog._wid);
                    }
                }
            });

            entitySelectDialog.on('grid-row-select', function(data) {
                entitySelectDialog._showLoading();
                var loadingStarted = false;
                var onSelect = function() {
                    entitySelectDialog.remove();
                    selectorEl.select2('focus');
                    selectorEl.off('select2-data-loaded.' + entitySelectDialog._wid, onSelect);
                };
                var onDataRequest = function() {
                    loadingStarted = true;
                    selectorEl.off('select2-data-request.' + entitySelectDialog._wid, onDataRequest);
                    selectorEl.on('select2-data-loaded.' + entitySelectDialog._wid, onSelect);
                };
                // set value
                selectorEl.on('select2-data-request.' + entitySelectDialog._wid, onDataRequest);
                selectorEl.inputWidget('val', data.model.get(existingEntityGridId), true);
                // if there was no data request sent to server
                if (!loadingStarted) {
                    onSelect();
                    // cleanup
                    selectorEl.off('select2-data-request.' + entitySelectDialog._wid, onDataRequest);
                }
            });
            entitySelectDialog.render();
        };

        var handleCreate = function(e) {
            e.preventDefault();

            var routeName = urlParts.create.route;
            var routeParams = urlParts.create.parameters;

            var additionalRequestParams = selectorEl.data('select2_query_additional_params');
            if (additionalRequestParams) {
                routeParams = $.extend({}, routeParams, additionalRequestParams);
            }

            var entityCreateDialog = new DialogWidget({
                title: __('Create {{ entity }}', {'entity': label}),
                url: routing.generate(routeName, routeParams),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650
                }
            });

            var processSelectedEntities = function(id) {
                selectorEl.inputWidget('val', id, true);
                entityCreateDialog.remove();
                selectorEl.select2('focus');
            };

            entityCreateDialog.on('formSave', _.bind(processSelectedEntities, this));
            entityCreateDialog.render();
        };

        container.find('.entity-select-btn').on('click', handleGridSelect);
        if (createEnabled) {
            container.find('.entity-create-btn').on('click', handleCreate);
        }

        return {
            getUrlParts: function() {
                return urlParts;
            },
            setUrlParts: function(newParts) {
                urlParts = newParts;
            },
            setSelection: function(value) {
                selectorEl.inputWidget('val', value);
            },
            getSelection: function() {
                return selectorEl.inputWidget('val');
            }
        };
    };
});
