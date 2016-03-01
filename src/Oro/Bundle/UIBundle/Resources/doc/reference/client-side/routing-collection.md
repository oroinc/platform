<a name="module_RoutingCollection"></a>
## RoutingCollection
RoutingCollection is an abstraction of collection which uses Oro routing system.

It keeps itself in actual state when route or state changes.

Basic usage:
```javascript
var CommentCollection = RoutingCollection.extend({
    routeDefaults: {
        routeName: 'oro_api_comment_get_items',
        routeQueryParameterNames: ['page', 'limit']
    },

    stateDefaults: {
        page: 1,
        limit: 10
    },

    // provide access to route
    setPage: function (pageNo) {
        this._route.set({page: pageNo});
    }
});

var commentCollection = new CommentCollection([], {
    routeParameters: {
        // specify required parameters
        relationId: 123,
        relationClass: 'Some_Class'
    }
});

// load first page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=1)
commentCollection.fetch();

// load second page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2)
commentCollection.setPage(2)
```

**Augment**: BaseCollection  

* [RoutingCollection](#module_RoutingCollection)
  * [._route](#module_RoutingCollection#_route) : <code>RouteModel</code>
  * [._state](#module_RoutingCollection#_state) : <code>BaseModel</code>
  * [.routeDefaults](#module_RoutingCollection#routeDefaults) : <code>Object</code>
  * [.stateDefaults](#module_RoutingCollection#stateDefaults) : <code>Object</code>
  * [.initialize()](#module_RoutingCollection#initialize)
  * [._createState(parameters)](#module_RoutingCollection#_createState)
  * [._createRoute(parameters)](#module_RoutingCollection#_createRoute)
  * [._mergeAllPropertyVersions(attrName)](#module_RoutingCollection#_mergeAllPropertyVersions) ⇒ <code>Object</code>
  * [.getRouteParameters()](#module_RoutingCollection#getRouteParameters) ⇒ <code>Object</code>
  * [.getState()](#module_RoutingCollection#getState) ⇒ <code>Object</code>
  * [.url()](#module_RoutingCollection#url)
  * [.sync()](#module_RoutingCollection#sync)
  * [.parse()](#module_RoutingCollection#parse)
  * [.checkUrlChange()](#module_RoutingCollection#checkUrlChange)
  * [.serializeExtraData()](#module_RoutingCollection#serializeExtraData)
  * [._onErrorResponse()](#module_RoutingCollection#_onErrorResponse)
  * [._onAdd()](#module_RoutingCollection#_onAdd)
  * [._onRemove()](#module_RoutingCollection#_onRemove)
  * [.dispose()](#module_RoutingCollection#dispose)

<a name="module_RoutingCollection#_route"></a>
### routingCollection._route : <code>RouteModel</code>
Route object which used to generate urls. Collection will reload whenever route is changed.
Attributes will be available at the view as <%= route.page %>

Access to route attributes should be realized in descendants. (e.g. `setPage()` or `setPerPage()`)

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#_state"></a>
### routingCollection._state : <code>BaseModel</code>
State of the collection. Must contain both settings and server response parts such as
totalItemsQuantity of items
on server. Attributes will be available at the view as `<%= state.totalItemsQuantity %>`.

The `stateChange` event is fired when state is changed.

Override `parse()` function to add values from server response to the state

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#routeDefaults"></a>
### routingCollection.routeDefaults : <code>Object</code>
Default route attributes

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#stateDefaults"></a>
### routingCollection.stateDefaults : <code>Object</code>
Default state

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#initialize"></a>
### routingCollection.initialize()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#_createState"></a>
### routingCollection._createState(parameters)
Creates state object. Merges attributes from all stateDefaults objects/functions in class hierarchy.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  

| Param | Type |
| --- | --- |
| parameters | <code>Object</code> | 

<a name="module_RoutingCollection#_createRoute"></a>
### routingCollection._createRoute(parameters)
Creates route. Merges attributes from all routeDefaults objects/functions in class hierarchy.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  

| Param | Type |
| --- | --- |
| parameters | <code>Object</code> | 

<a name="module_RoutingCollection#_mergeAllPropertyVersions"></a>
### routingCollection._mergeAllPropertyVersions(attrName) ⇒ <code>Object</code>
Utility function. Extends `Chaplin.utils.getAllPropertyVersions` with merge and `_.result()` like call,
if property is function

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  

| Param | Type | Description |
| --- | --- | --- |
| attrName | <code>string</code> | attribute to merge |

<a name="module_RoutingCollection#getRouteParameters"></a>
### routingCollection.getRouteParameters() ⇒ <code>Object</code>
Returns current route parameters

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#getState"></a>
### routingCollection.getState() ⇒ <code>Object</code>
Returns collection state

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#url"></a>
### routingCollection.url()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#sync"></a>
### routingCollection.sync()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#parse"></a>
### routingCollection.parse()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#checkUrlChange"></a>
### routingCollection.checkUrlChange()
Fetches collection if url is changed.
Callback for state and route changes.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#serializeExtraData"></a>
### routingCollection.serializeExtraData()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#_onErrorResponse"></a>
### routingCollection._onErrorResponse()
Default error response handler function
It will show error messages for all HTTP error codes except 400.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#_onAdd"></a>
### routingCollection._onAdd()
General callback for 'add' event

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#_onRemove"></a>
### routingCollection._onRemove()
General callback for 'remove' event

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#dispose"></a>
### routingCollection.dispose()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
