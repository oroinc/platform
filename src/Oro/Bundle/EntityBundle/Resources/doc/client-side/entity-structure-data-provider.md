# EntityStructureDataProvider

**EntityStructureDataProvider**:
 - provides access over `EntityStructuresCollection` to [EntityStructure API](../entity_structure_data_provider.md).
 - shares same instance of `EntityStructuresCollection` between providers
 - contains pack of helper methods to filters and navigate across entity relations

### Get the EntityStructureDataProvider instance
There's static method `createDataProvider` of `EntityStructureDataProvider` that allows to get provider's instance:
```javascript
var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    // ...
    initialize: function(options) {
        var providerOptions = {
            rootEntity: 'Oro\\Bundle\\UserBundle\\Entity\\User'
        };
        EntityStructureDataProvider
            .createDataProvider(providerOptions, this)
            .then(function(provider) {
                this.provider = provider;
            }.bind(this));
    }
```
Signature for `createDataProvider` method is following
```js
    /**
     * Creates instance of data provider and returns it with the promise object
     *
     * @param {Object=} options
     * @param {string} [options.rootEntity] class name of root entity
     * @param {string} [options.filterPreset] name of filter preset
     * @param {Object.<string, boolean>} [options.optionsFilter] acceptable entity's and fields' options
     *  example:
     *      {auditable: true, configurable: true, unidirectional: false}
     * @param {[Object|string]} [options.exclude]
     *  examples:
     *      ['relationType'] - will exclude all entries that has 'relationType' key (means relational fields)
     *      [{type: 'date'}] - will exclude all entries that has property "type" equals to "date"
     * @param {[Object|string]} [options.include]
     *  examples:
     *      ['relationType'] - will include all entries that has 'relationType' key (means relational fields)
     *      [{type: 'date'}] - will include all entries that has property "type" equals to "date"
     * @param {fieldsFilterer} [options.fieldsFilterer]
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterWhitelist]
     *  whitelist of fields that has NOT to be filtered out
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be included to results, despite it might not pass the filters
     * @param {Object.<string, Object.<string, boolean>>} [options.fieldsFilterBlacklist]
     *  blacklist of fields that HAS to be filtered out
     *  examples:
     *      {'Oro\\Bundle\\UserBundle\\Entity\\User': {groups: true}} - groups field of User entity
     *          has to be excluded from results, despite it might pass the filters
     * @param {RegistryApplicant} applicant
     * @return {Promise.<EntityStructureDataProvider>}
     */
    createDataProvider: function(options, applicant) { ... }
```
Where is the first argument is options for the provider:
- `rootEntity` class name of entity that's navigation by fields and relations starts from
- `optionsFilter`, `exclude` and `include` rules allow to define constraints for entities and fields that provider returns
- `fieldsFilterer` custom filter function
- `filterPreset` name of preconfigured filter
- `fieldsFilterWhitelist` and `fieldsFilterBlacklist` allows to define filter strategy for specific fields

And the second the applicant, instance that has requested the provider. 
It allows to define life cycles of shared `EntityStructuresCollection` instance in registry (see [registry](../../../../UIBundle/Resources/doc/reference/client-side/registry.md) for details).

The method works asynchronously, it returns provider instance within promise.
The promise assures the instance `EntityStructuresCollection` of the provider already contains 
fetched data from the server and the provider is ready to use.

### Define filter's preset 
It is common situation, when several providers use same filters configuration.
For such cases it is possible to define filter preset:  
```js
    EntityStructureDataProvider.defineFilterPreset('workflow', {
        optionsFilter: {unidirectional: false, configurable: true},
        exclude: [
            {relationType: 'manyToMany'},
            {relationType: 'oneToMany'}
        ]
    });
```
Once preset is defined, its name can be used to configure the provider
```js
    EntityStructureDataProvider
        .createDataProvider({
            filterPreset: 'workflow',
            include: [{type: 'date'}, {type: 'datetime'}]
        }, applicant)
```
Direct definition of `fieldsFilterer`, `optionsFilter`, `exclude` and `include` options have higer priority over defined in used `filterPreset`. That allows customise filter configuration for certain provider.

See other methods documentation in [entity-structure-data-provider.js](../../public/js/app/services/entity-structure-data-provider.js)
