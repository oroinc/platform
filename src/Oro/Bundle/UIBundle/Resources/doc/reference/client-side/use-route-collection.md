<a name="module_UseRouteCollection"></a>
## UseRouteCollection
UseRouteCollection is an abstraction of collection which uses Oro routing system.It keeps itself in actual state when route or state changes.Basic usage:```javascriptvar CommentCollection = UseRouteCollection.extend({    routeName: 'oro_api_comment_get_items',    routeQueryParameters: ['page', 'limit'],    stateDefaults: {        page: 1,        limit: 10    }});var commentCollection = new CommentCollection([], {    routeParams: {        // specify required parameters        relationId: 123,        relationClass: 'Some_Class'    }});// load first page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=1)commentCollection.fetch();// load second page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2)commentCollection.state.set({page: 2})```


* [UseRouteCollection](#module_UseRouteCollection)
  * [.routeName](#module_UseRouteCollection#routeName) : <code>string</code>
  * [.routeQueryParameters](#module_UseRouteCollection#routeQueryParameters) : <code>Array.&lt;string&gt;</code>
  * [._route](#module_UseRouteCollection#_route) : <code>RouteModel</code>
  * [.state](#module_UseRouteCollection#state) : <code>BaseModel</code>
  * [.stateDefaults](#module_UseRouteCollection#stateDefaults) : <code>object</code>
  * [.routeParams](#module_UseRouteCollection#routeParams) : <code>object</code>
  * [.initialize()](#module_UseRouteCollection#initialize)
  * [.url()](#module_UseRouteCollection#url)
  * [.sync()](#module_UseRouteCollection#sync)
  * [.parse()](#module_UseRouteCollection#parse)
  * [.checkUrlChange()](#module_UseRouteCollection#checkUrlChange)
  * [.serializeExtraData()](#module_UseRouteCollection#serializeExtraData)
  * [._onErrorResponse()](#module_UseRouteCollection#_onErrorResponse)

<a name="module_UseRouteCollection#routeName"></a>
### useRouteCollection.routeName : <code>string</code>
Route name this collection belongs to

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**See**: RouteModel.routeName  
<a name="module_UseRouteCollection#routeQueryParameters"></a>
### useRouteCollection.routeQueryParameters : <code>Array.&lt;string&gt;</code>
List of query parameters which this route accepts.

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**See**: RouteModel.routeQueryParameters  
<a name="module_UseRouteCollection#_route"></a>
### useRouteCollection._route : <code>RouteModel</code>
Route object which used to generate urls. Collection will reload whenever route is changed

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**Access:** protected  
<a name="module_UseRouteCollection#state"></a>
### useRouteCollection.state : <code>BaseModel</code>
State model. Collection will reload whenever state is changed

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#stateDefaults"></a>
### useRouteCollection.stateDefaults : <code>object</code>
Default state

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#routeParams"></a>
### useRouteCollection.routeParams : <code>object</code>
Default route parameters

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#initialize"></a>
### useRouteCollection.initialize()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#url"></a>
### useRouteCollection.url()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#sync"></a>
### useRouteCollection.sync()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#parse"></a>
### useRouteCollection.parse()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#checkUrlChange"></a>
### useRouteCollection.checkUrlChange()
Fetches collection if url is changed.Callback for state and route changes.

**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#serializeExtraData"></a>
### useRouteCollection.serializeExtraData()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#_onErrorResponse"></a>
### useRouteCollection._onErrorResponse()
Default error response handler functionIt will show error messages for all HTTP error codes except 400.

**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**Access:** protected  
