<a name="module_RoutingCollection"></a>
## RoutingCollection
RoutingCollection is an abstraction of collection which uses Oro routing system.It keeps itself in actual state when route or state changes.Basic usage:```javascriptvar CommentCollection = RoutingCollection.extend({    routeName: 'oro_api_comment_get_items',    routeQueryParameters: ['page', 'limit'],    stateDefaults: {        page: 1,        limit: 10    }});var commentCollection = new CommentCollection([], {    routeParams: {        // specify required parameters        relationId: 123,        relationClass: 'Some_Class'    }});// load first page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=1)commentCollection.fetch();// load second page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2)commentCollection.state.set({page: 2})```


* [RoutingCollection](#module_RoutingCollection)
  * [.routeName](#module_RoutingCollection#routeName) : <code>string</code>
  * [.routeQueryParameters](#module_RoutingCollection#routeQueryParameters) : <code>Array.&lt;string&gt;</code>
  * [._route](#module_RoutingCollection#_route) : <code>RouteModel</code>
  * [.state](#module_RoutingCollection#state) : <code>BaseModel</code>
  * [.stateDefaults](#module_RoutingCollection#stateDefaults) : <code>object</code>
  * [.routeParams](#module_RoutingCollection#routeParams) : <code>object</code>
  * [.initialize()](#module_RoutingCollection#initialize)
  * [.updateRoute(newParameters, options)](#module_RoutingCollection#updateRoute)
  * [.url()](#module_RoutingCollection#url)
  * [.sync()](#module_RoutingCollection#sync)
  * [.parse()](#module_RoutingCollection#parse)
  * [.checkUrlChange()](#module_RoutingCollection#checkUrlChange)
  * [.serializeExtraData()](#module_RoutingCollection#serializeExtraData)
  * [._onErrorResponse()](#module_RoutingCollection#_onErrorResponse)

<a name="module_RoutingCollection#routeName"></a>
### routingCollection.routeName : <code>string</code>
Route name this collection belongs to

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**See**: RouteModel.routeName  
<a name="module_RoutingCollection#routeQueryParameters"></a>
### routingCollection.routeQueryParameters : <code>Array.&lt;string&gt;</code>
List of query parameters which this route accepts.

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**See**: RouteModel.routeQueryParameters  
<a name="module_RoutingCollection#_route"></a>
### routingCollection._route : <code>RouteModel</code>
Route object which used to generate urls. Collection will reload whenever route is changed.Attributes will be available at the view as <%= route.page %>

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
<a name="module_RoutingCollection#state"></a>
### routingCollection.state : <code>BaseModel</code>
State model. Contains unparsed part of server response. Attributes will be available at theview as `<%= state.count %>`

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#stateDefaults"></a>
### routingCollection.stateDefaults : <code>object</code>
Default state

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#routeParams"></a>
### routingCollection.routeParams : <code>object</code>
Default route parameters

**Kind**: instance property of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#initialize"></a>
### routingCollection.initialize()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#updateRoute"></a>
### routingCollection.updateRoute(newParameters, options)
Clean way to pass new parameters to route

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  

| Param |
| --- |
| newParameters | 
| options | 

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
Fetches collection if url is changed.Callback for state and route changes.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#serializeExtraData"></a>
### routingCollection.serializeExtraData()
**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
<a name="module_RoutingCollection#_onErrorResponse"></a>
### routingCollection._onErrorResponse()
Default error response handler functionIt will show error messages for all HTTP error codes except 400.

**Kind**: instance method of <code>[RoutingCollection](#module_RoutingCollection)</code>  
**Access:** protected  
