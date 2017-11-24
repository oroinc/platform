require([
    'oroentity/js/app/services/entity-structure-data-provider'
], function(EntityStructureDataProvider) {
    'use strict';

    EntityStructureDataProvider.defineFilterPreset('dataaudit', {
        optionsFilter: {auditable: true}
    });
});
