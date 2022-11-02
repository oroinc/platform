import EntityStructureDataProvider from 'oroentity/js/app/services/entity-structure-data-provider';

EntityStructureDataProvider.defineFilterPreset('dataaudit', {
    optionsFilter: {auditable: true, exclude: false}
});
