# EntityModel

**EntityModel**:
 - Provides an approach to work with backend over JSON API
 - With the help of registry, allows to share data over the user interface

### Create a new model

During the initialization, a model has to be created manually, with the `new EntityModel(null, {type: '...'})`.  

```javascript
    var registry = requirejs('oroui/js/app/services/registry');
    var EntityModel = requirejs('oroentity/js/app/models/entity-model');
    
    // ...
    initModel: function() {
        // It is supposed, that code is executed inside a component or some other instance 
        // that has a life cycle and triggers 'dispose' event at the end
        // (such as a view, model or collection)
        var taskModel = this.taskModel = new EntityModel(null, {type: 'tasks'});
        this.taskModel.set({
            subject: 'Test create action of EntityModel',
            taskPriority: {type: 'taskpriorities', id: 'high'},
            status: {type: 'taskstatuses', id: 'open'}
        });
        
        var component = this;
        this.taskModel.save()
            .then(function() {
                // Once the model is saved (and obtained its id) it can be published into the registry.
                // A component, creator of the model, is passed into registry 
                // to bind its life cycle with the model
                registry.put(taskModel, component);
            });
    }
```

Once the model is saved, it gets the id and the entity type assigned, and can now be requested from registry.

If an identifier of an entity model (id and type) is known, the model have to be requested from the registry. An instance that is going to use this model 
have to be provided to the registry as an `applicant` argument, to bind life cycle with a model.

### Update a model

```javascript
    var EntityModel = requirejs('oroentity/js/app/models/entity-model');
    
    // ...
    updateModel: function() {
        // the last argument is passed to bind life cycles of the model and applicant
        this.taskModel = EntityModel.getEntityModel({type: 'tasks', id: '25'}, this);
        this.taskModel.set({
            subject: 'Test update action of EntityModel',
            taskPriority: {type: 'taskpriorities', id: 'normal'}
        });
        this.taskModel.save();
    }
```

Once applicant object gets disposed, registry will dispose all previously requested models and relationshipCollections
automatically, if they were not requested by any other instances.

### Retain and Relieve entityModel with the help registry

Sometimes, an entityModel may be obtained without calling the `EntityModel.getEntityModel()`
(e.g. received within options):

```javascript
    initialize: function(options) {
        _.extend(this, _.pick(options, 'entityModel'));
        registry.retain(this.entityModel, this);
        // ...
    }
```

In this case, the registry has to be notified that the model is in use by some instance. Otherwise registry can unexpectedly dispose
the model as soon as all object-applicants got disposed.

The registry has a method to unbind life cycles of an instance and a model, in case model is not in use any more:

```javascript
    disableView: function() {
        registry.relieve(this.entityModel, this);
        delete this.entityModel;
        // ...
    }
```

# EntityRelationshipCollection

EntityRelationshipCollection instance can either be requested with the help of `getEntityRelationshipCollection` static method using an identifier object:

```javascript
    var EntityRelationshipCollection = requirejs('oroentity/js/app/models/entity-relationship-collection');
    // ...
    updateModel: function() {
        var relationIdentifier = {
            type: 'accounts', 
            id: '1',
            association: 'contacts'
        };
        this.accountContacts = 
            EntityRelationshipCollection.getEntityRelationshipCollection(relationIdentifier, this);
        this.accountContacts.fetch();
    }
```

Or taken from parent model:

```javascript
    initialize: function(options) {
        this.accountContacts = options.accountModel.getRelationship('contacts', this);
        this.accountContacts.fetch();
    }
```

In both cases, applicant has to be specified, to allow registry synchronize life cycles of the collection and the applicant.

### Add and remove models from EntityRelationshipCollection

Here is an example of how models can be added into collection:

```javascript
    addContacts: function(accountModel) {
        this.accountContacts = accountModel.getRelationship('contacts', this);
        this.accountContacts.add([
            {data: {type: 'contacts', id: '2'}},
            {data: {type: 'contacts', id: '3'}}
        ]);
        this.accountContacts.save();
    }
```
Similar way some models can be removed from collection
```javascript
    removeContacts: function(accountModel) {
        this.accountContacts = accountModel.getRelationship('contacts', this);
        this.accountContacts.remove([
            {data: {type: 'contacts', id: '2'}},
            {data: {type: 'contacts', id: '3'}}
        ]);
        this.accountContacts.save();
    }
```
Or just reset it with empty array to delete all relations
```javascript
    resetContacts: function(accountModel) {
        this.accountContacts = accountModel.getRelationship('contacts', this);
        this.accountContacts.reset([]);
        this.accountContacts.save();
    }
```
