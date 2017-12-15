#Registry

**Registry**:
 - is singleton service that allows to share some instances with unique identifiers (with property `globalId`)
 - takes care of life circle for shared instances.
 Collects information, who has requested the instance and removes it once the list of applicants gets empty. 

###Registry API
The method `put` is used to add the objects with unique property `globalId` to the registry.
The second argument `applicant` is the object, instance of `PageComponent`, `Model`, `Collection`, `View` or any other instance that has life cycle and triggers `dispose` event at the end.
It is used in registry to preserver only objects that still in use by any applicant. If all applicants for the object are disposed -- registry disposes the object as well. 
```js
    /**
     * Puts instance into registry
     *
     * @param {{globalId: string}} instance
     * @param {RegistryApplicant} applicant
     * @throws {Error} invalid instance or instance already exists in registry
     */
    put: function(instance, applicant) { ... }
```

`globalId` is also used to get the object from registry
```js
    /**
     * Fetches instance from registry by globalId
     *
     * @param {string} globalId
     * @param {RegistryApplicant} applicant
     * @return {Object|null}
     */
    fetch: function(globalId, applicant) { ... }
```
It is pretty common case when these two methods are used together to fetch existing instance or create a new one and return it.
```js    
    var registry = require('oroui/js/app/services/registry');
    var BaseClass = require('oroui/js/base-class');
    
    var Unique = BaseClass.extend({
        globalId: null,
        constructor: function(globalIds) {
            this.globalId = globalId;
        }
    }, {
        getObject: function(globalId, applicant) {
            var obj = registry.fetch(globalId, applicant); 
            if (!obj) {
                obj = new Unique(globalId);
                registry.put(obj, applicant);
            }
            return obj;
        }
    });
    
    // and after, inside PageComponent
    
    initialize: function(options) {
        this.obj = Unique.getObject('uniqueKey', this);
    }
    
```
There's tow other methods that allows to maintain up to date the list of actual applicants
```js
    /**
     * Adds applicant relation to registry for instance
     *
     * @param {{globalId: string}} instance
     * @param {RegistryApplicant} applicant
     */
    retain: function(instance, applicant) { ... }

    /**
     * Removes applicant relation from registry for instance
     *
     * @param {{globalId: string}} instance
     * @param {RegistryApplicant} applicant
     */
    relieve: function(instance, applicant) { ... }
```
If the instance came from options and you need to preserve it in property for future use -- 
registry needs to be notified that new applicant holds the instance.
```js
        initialize: function(options) {
            this.instance = options.instance;
            registry.retain(this.instance, this); 
        }
```
Or in case the applicant does not need any more shared instance, it can notify the registry as well
```js
        disable: function() {
            registry.relieve(this.instance, this);
            delete this.instance;
        }
```

See other methods documentation in [registry.js](../../../public/js/app/services/registry/registry.js)
