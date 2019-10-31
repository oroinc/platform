define(function(require) {
    'use strict';

    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    EntityStructureDataProvider.defineFilterPreset('querydesigner', {
        optionsFilter: {exclude: false}
    });
});
