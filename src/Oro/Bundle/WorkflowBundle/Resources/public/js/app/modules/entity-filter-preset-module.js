require([
    'oroentity/js/app/services/entity-structure-data-provider'
], function(EntityStructureDataProvider) {
    'use strict';

    EntityStructureDataProvider.defineFilterPreset('workflow', {
        optionsFilter: {virtual: false, unidirectional: false, exclude: false}
    });
});
