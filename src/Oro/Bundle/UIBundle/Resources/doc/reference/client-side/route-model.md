## RouteModel

<a name="module_RouteModel"></a>

Abstraction of route

Basic usage:
```javascript
var route = new RouteModel({
    // route specification
    routeName: 'oro_api_comment_get_items',
    routeQueryParameterNames: ['page', 'limit'],

    // required parameters for route path
    relationId: 123,
    relationClass: 'Some_Class',

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
  * [._cachedRouteName](#module_RouteModel#_cachedRouteName) : `String`
  * [._requiredParametersCache](#module_RouteModel#_requiredParametersCache) : `Array.<string>;`
  * [.defaults](#module_RouteModel#defaults) : `Object`
  * [.routeName](#module_RouteModel#routeName) : `string`
  * [.routeQueryParameterNames](#module_RouteModel#routeQueryParameterNames) : `Array.<string>`
  * [.getRequiredParameters()](#module_RouteModel#getRequiredParameters) ⇒ `Array.<string>`
  * [.getAcceptableParameters()](#module_RouteModel#getAcceptableParameters) ⇒ `Array.<string>`
  * [.getUrl([parameters])](#module_RouteModel#getUrl) ⇒ `string`
  * [.validateParameters([parameters])](#module_RouteModel#validateParameters) ⇒ `boolean`

<a name="module_RouteModel#_cachedRouteName"></a>
### routeModel._cachedRouteName : `String`
Route name cache prepared for

**Kind**: instance property of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#_requiredParametersCache"></a>
### routeModel._requiredParametersCache : `Array.<String>`
Cached required parameters

**Kind**: instance property of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#defaults"></a>
### routeModel.defaults : `Object`
**Kind**: instance property of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#routeName"></a>
### routeModel.routeName : `string`
Name of the route

**Kind**: instance property of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#routeQueryParameterNames"></a>
### routeModel.routeQueryParameterNames : `Array.<string>`
List of acceptable query parameter names for this route

**Kind**: instance property of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#getRequiredParameters"></a>
### routeModel.getRequiredParameters() ⇒ `Array.<string>`
Return list of parameter names required by this route (Route parameters are required to build valid url, all
query parameters assumed as filters and are not required)

E.g. for route `api/rest/latest/<relationClass>/<relationId/comments`
This function will return `['relationClass', 'relationId']`

**Kind**: instance method of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#getAcceptableParameters"></a>
### routeModel.getAcceptableParameters() ⇒ `Array.<string>`
Return list of parameter names accepted by this route.
Includes both query and route parameters

E.g. for route `api/rest/latest/<relationClass>/<relationId/comments?page=<page>&limit=<limit>`
this function will return `['relationClass', 'relationId', 'page', 'limit']`

**Kind**: instance method of [RouteModel](#module_RouteModel)  
<a name="module_RouteModel#getUrl"></a>
### routeModel.getUrl([parameters]) ⇒ `string`
Returns url defined by this model

**Kind**: instance method of [RouteModel](#module_RouteModel)  
**Returns**: `string` - route url  

| Param | Type | Description |
| --- | --- | --- |
| [parameters] | `Object` | parameters to override |

<a name="module_RouteModel#validateParameters"></a>
### routeModel.validateParameters([parameters]) ⇒ `boolean`
Validates parameters list

**Kind**: instance method of [RouteModel](#module_RouteModel)  
**Returns**: `boolean` - true, if parameters are valid  

| Param | Type | Description |
| --- | --- | --- |
| [parameters] | `Object` | parameters to build url |

