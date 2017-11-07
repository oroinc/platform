#EntityStructureDataProvider

**EntityStructureDataProvider**:
 - provides accesses over `EntityStructuresCollection` to [EntityStructure API](../entity_structure_data_provider.md).
 - shares same instance of `EntityStructuresCollection` between providers
 - contains pack of helper methods to filters and navigate across entity relations

###Get the EntityStructureDataProvider instance
There's static method `getOwnDataContainer` of `EntityStructureDataProvider` that allows to get provider's instance:
```javascript
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    // ...
    initialize: function(options) {
        EntityStructureDataProvider.getOwnDataContainer(this, {
            rootEntity: 'Oro\\Bundle\\UserBundle\\Entity\\User'
        }).then(function(provider) {
            this.provider = provider;
        }.bind(this));
    }
```
The method works asynchronously, it returns provider instance within promise.
The promise assures that the instance `EntityStructuresCollection` of the provider already contains 
fetched data from the server and the provider is ready to use.

See methods documentation in [entity-structure-data-provider.js](../../public/js/app/services/entity-structure-data-provider.js)
