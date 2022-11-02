import EntityStructureDataProvider from 'oroentity/js/app/services/entity-structure-data-provider';

EntityStructureDataProvider.defineFilterPreset('workflow', {
    optionsFilter: {unidirectional: false, configurable: true},
    exclude: [
        {relationType: 'manyToMany'},
        {relationType: 'oneToMany'}
    ]
});
