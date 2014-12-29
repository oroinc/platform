/* global define */
define(['routing', 'oro/dialog-widget', 'oroui/js/widget-manager', 'orotranslation/js/translator', 'jquery.select2'],
function (routing, DialogWidget, widgetManager, __) {
    'use strict';

    /**
     * @export  oroform/js/select-create-inline-type-handler
     * @class   oroform.selectCreateInlineTypeHandler
     */
    return function (container,
        selectorEl,
        label,
        urlParts,
        existingEntityGridId,
        createEnabled
    ) {
        var handleGridSelect = function (e) {
            e.preventDefault();

            var entitySelectDialog = new DialogWidget({
                title: __('Select {{ entity }}', {'entity': label}),
                url: routing.generate(urlParts.grid.route, urlParts.grid.parameters),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650,
                    close: function () {
                        selectorEl.off('.' + entitySelectDialog._wid);
                    }
                }
            });

            entitySelectDialog.on('grid-row-select', function (data) {
                entitySelectDialog._showLoading();
                selectorEl.select2('val', data.model.get(existingEntityGridId), true);
                selectorEl.on('change.' + entitySelectDialog._wid, function(){
                    entitySelectDialog.remove();
                    selectorEl.select2('focus');
                });
            });
            entitySelectDialog.render();
        };

        var handleCreate = function (e) {
            e.preventDefault();

            var entityCreateDialog = new DialogWidget({
                title: __('Create {{ entity }}', {'entity': label}),
                url: routing.generate(urlParts.create.route, urlParts.create.parameters),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650
                }
            });

            var processSelectedEntities = function (id) {
                selectorEl.select2('val', id, true);
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
            getUrlParts: function () {
                return urlParts;
            },
            setUrlParts: function (newParts) {
                urlParts = newParts
            },
            setSelection: function (value) {
                selectorEl.select2('val', value);
            },
            getSelection: function () {
                return selectorEl.select2('val');
            }
        };
    };
});
