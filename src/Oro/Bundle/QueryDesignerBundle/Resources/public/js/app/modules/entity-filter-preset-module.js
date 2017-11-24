require([
    'oroentity/js/app/services/entity-structure-data-provider'
], function(EntityStructureDataProvider) {
    'use strict';

    EntityStructureDataProvider.defineFilterPreset('querydesigner', {
        optionsFilter: {exclude: false}
    });
});
