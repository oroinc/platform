# EntityModel

**EntityModel**:
 - provides approach to work with backend over JSON API
 - with the help of registry, allows to share data over user interface

### Create a new model
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
                registry.retain(taskModel, component);
            });
    }
```
It is the only case, when a model has to be created manually, over `new EntityModel(null, {type: '...'})`. 
Because it doe's not have an id yet and cannot be requested from registry. If an identifier of an entity (id and type)
is known, a model have to be requested from the registry. An instance that going to use this model 
have to be provided to the registry as a applicant argument, to bind life cycle with a model.

### Update a model
```javascript
    var registry = requirejs('oroui/js/app/services/registry');
    
    // ...
    updateModel: function() {
        // the last argument is passed to bind life cycles of the model and applicant
        this.taskModel = registry.getEntity({type: 'tasks', id: '25'}, this);
        this.taskModel.set({
            subject: 'Test update action of EntityModel',
            taskPriority: {type: 'taskpriorities', id: 'normal'}
        });
        this.taskModel.save();
    }
```
Once applicant object gets disposed, registry will dispose all previously requested models and relationshipCollections
automatically, if they don't have any other instances that have requested them.

### Retain and Relieve entityModel with the help registry
If an entityModel has been obtained somehow differently from a direct request to registry
(e.g. received within options):
```javascript
    initialize: function(options) {
        _.extend(this, _.pick(options, 'entityModel'));
        registry.retain(this.entityModel, this);
        // ...
    }
```
The registry has to be notified that the model in use of some instance. Otherwise registry can unexpectedly dispose
the model, once all object-applicants got disposed.

The registry has a method to unbind life cycles of an instance and a model, in case model is not in use any more:

```javascript
    disableView: function() {
        registry.relieve(this.entityModel, this);
        delete this.entityModel;
        // ...
    }
```

# EntityRelationshipCollection
EntityRelationshipCollection instance can be requested straight from registry, using an identifier object:
```javascript
    var registry = requirejs('oroui/js/app/services/registry');
    // ...
    updateModel: function() {
        var relationIdentifier = {
            type: 'accounts', 
            id: '1',
            association: 'contacts'
        };
        this.accountContacts = 
            registry.getEntityRelationshipCollection(relationIdentifier, this);
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
In both cases, applicant has to be specified, to allow registry synchronize life cycles of collection and applicant

### Add and remove models from EntityRelationshipCollection
Here's example how models can be added into collection:
```javascript
    initialize: function(options) {
        this.accountContacts = options.accountModel.getRelationship('contacts', this);
        this.accountContacts.add([
            {data: {type: 'contacts', id: '2'}},
            {data: {type: 'contacts', id: '3'}}
        ]);
        this.accountContacts.save();
    }
```
Similar way some models can be removed from collection
```javascript
    initialize: function(options) {
        this.accountContacts = options.accountModel.getRelationship('contacts', this);
        this.accountContacts.remove([
            {data: {type: 'contacts', id: '2'}},
            {data: {type: 'contacts', id: '3'}}
        ]);
        this.accountContacts.save();
    }
```
Or just reset it with empty array to delete all relations
```javascript
    initialize: function(options) {
        this.accountContacts = options.accountModel.getRelationship('contacts', this);
        this.accountContacts.reset([]);
        this.accountContacts.save();
    }
```
