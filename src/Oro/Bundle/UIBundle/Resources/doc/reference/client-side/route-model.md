<a name="module_RouteModel"></a>
## RouteModel
Abstraction of route

Basic usage:
```javascript
var route = new RouteModel({
    // route specification
    routeName: 'oro_api_comment_get_items',
    routeQueryParameterNames: ['page', 'limit'],

    // required parameters for route path
    relationId: 123,
    relationClass: 'Some_Class'

    // default query parameter
    limit: 10
});

// returns api/rest/latest/relation/Some_Class/123/comment?limit=10
route.getUrl();

// returns api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2
route.getUrl({page: 2})
```

**Augment**: BaseModel  

* [RouteModel](#module_RouteModel)
  * [._cachedRouteName](#module_RouteModel#_cachedRouteName) : <code>String</code>
  * [._requiredParametersCache](#module_RouteModel#_requiredParametersCache) : <code>Array.&lt;String&gt;</code>
  * [.defaults](#module_RouteModel#defaults) : <code>Object</code>
  * [.routeName](#module_RouteModel#routeName) : <code>string</code>
  * [.routeQueryParameterNames](#module_RouteModel#routeQueryParameterNames) : <code>Array.&lt;string&gt;</code>
  * [.getRequiredParameters()](#module_RouteModel#getRequiredParameters) ⇒ <code>Array.&lt;string&gt;</code>
  * [.getAcceptableParameters()](#module_RouteModel#getAcceptableParameters) ⇒ <code>Array.&lt;string&gt;</code>
  * [.getUrl([parameters])](#module_RouteModel#getUrl) ⇒ <code>string</code>
  * [.validateParameters([parameters])](#module_RouteModel#validateParameters) ⇒ <code>boolean</code>

<a name="module_RouteModel#_cachedRouteName"></a>
### routeModel._cachedRouteName : <code>String</code>
Route name cache prepared for

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#_requiredParametersCache"></a>
### routeModel._requiredParametersCache : <code>Array.&lt;String&gt;</code>
Cached required parameters

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#defaults"></a>
### routeModel.defaults : <code>Object</code>
**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#routeName"></a>
### routeModel.routeName : <code>string</code>
Name of the route

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#routeQueryParameterNames"></a>
### routeModel.routeQueryParameterNames : <code>Array.&lt;string&gt;</code>
List of acceptable query parameter names for this route

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#getRequiredParameters"></a>
### routeModel.getRequiredParameters() ⇒ <code>Array.&lt;string&gt;</code>
Return list of parameter names required by this route (Route parameters are required to build valid url, all
query parameters assumed as filters and are not required)

E.g. for route `api/rest/latest/<relationClass>/<relationId/comments`
This function will return `['relationClass', 'relationId']`

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#getAcceptableParameters"></a>
### routeModel.getAcceptableParameters() ⇒ <code>Array.&lt;string&gt;</code>
Return list of parameter names accepted by this route.
Includes both query and route parameters

E.g. for route `api/rest/latest/<relationClass>/<relationId/comments?page=<page>&limit=<limit>`
this function will return `['relationClass', 'relationId', 'page', 'limit']`

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#getUrl"></a>
### routeModel.getUrl([parameters]) ⇒ <code>string</code>
Returns url defined by this model

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Returns**: <code>string</code> - route url  

| Param | Type | Description |
| --- | --- | --- |
| [parameters] | <code>Object</code> | parameters to override |

<a name="module_RouteModel#validateParameters"></a>
### routeModel.validateParameters([parameters]) ⇒ <code>boolean</code>
Validates parameters list

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Returns**: <code>boolean</code> - true, if parameters are valid  

| Param | Type | Description |
| --- | --- | --- |
| [parameters] | <code>Object</code> | parameters to build url |

