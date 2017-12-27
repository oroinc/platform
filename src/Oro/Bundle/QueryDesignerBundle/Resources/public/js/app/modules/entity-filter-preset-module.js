define(function(require) {
    'use strict';

    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    EntityStructureDataProvider.defineFilterPreset('querydesigner', {
        optionsFilter: {exclude: false}
    });
});
