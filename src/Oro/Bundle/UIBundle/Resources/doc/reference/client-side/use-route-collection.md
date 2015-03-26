<a name="module_UseRouteCollection"></a>
## UseRouteCollection
UseRouteCollection is an abstraction of collection which uses Oro routing system.

It keeps itself in actual state when route or state changes.

Basic usage:
```
var CommentCollection = UseRouteCollection.extend({
    routeName: 'oro_api_comment_get_items',
    routeAccepts: ['page', 'limit'],
    stateDefaults: {
        page: 1,
        limit: 10
    }
});

var commentCollection = new CommentCollection([], {
    routeParams: {
        // specify required parameters
        relationId: 1,
        relationClass: 'Some/Class'
    }
});

// load first page
commentCollection.fetch()

// load second page
commentCollection.state.set({page: 2})
```


* [UseRouteCollection](#module_UseRouteCollection)
  * [.routeName](#module_UseRouteCollection#routeName) : <code>string</code>
  * [.routeAccepts](#module_UseRouteCollection#routeAccepts) : <code>Array.&lt;string&gt;</code>
  * [.route](#module_UseRouteCollection#route) : <code>RouteModel</code>
  * [.state](#module_UseRouteCollection#state) : <code>BaseModel</code>
  * [.stateDefaults](#module_UseRouteCollection#stateDefaults) : <code>object</code>
  * [.routeParams](#module_UseRouteCollection#routeParams) : <code>object</code>
  * [.initialize()](#module_UseRouteCollection#initialize)
  * [.url()](#module_UseRouteCollection#url)
  * [.sync()](#module_UseRouteCollection#sync)
  * [.parse()](#module_UseRouteCollection#parse)
  * [.checkUrlChange()](#module_UseRouteCollection#checkUrlChange)
  * [.serialize()](#module_UseRouteCollection#serialize)
  * [._onErrorResponse()](#module_UseRouteCollection#_onErrorResponse)

<a name="module_UseRouteCollection#routeName"></a>
### useRouteCollection.routeName : <code>string</code>
Route name this collection belongs to

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**See**: RouteModel.routeName  
<a name="module_UseRouteCollection#routeAccepts"></a>
### useRouteCollection.routeAccepts : <code>Array.&lt;string&gt;</code>
List of query parameters which this route accepts.
There is no need to specify here arguments which is required to build route path.

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**See**: RouteModel.routeAccepts  
<a name="module_UseRouteCollection#route"></a>
### useRouteCollection.route : <code>RouteModel</code>
Route object which used to generate urls. Collection will reload whenever route is changed

**Kind**: instance property of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
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
Fetches collection if url is changed.
Callback for state and route changes.

**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#serialize"></a>
### useRouteCollection.serialize()
**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
<a name="module_UseRouteCollection#_onErrorResponse"></a>
### useRouteCollection._onErrorResponse()
Default error response handler function
It will show error messages for all HTTP error codes except 400.

**Kind**: instance method of <code>[UseRouteCollection](#module_UseRouteCollection)</code>  
**Access:** protected  
