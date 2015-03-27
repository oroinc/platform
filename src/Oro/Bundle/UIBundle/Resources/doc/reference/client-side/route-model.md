<a name="module_RouteModel"></a>
## RouteModel
Abstraction of routeBasic usage:```javascriptvar route = new RouteModel({    // route specification    routeName: 'oro_api_comment_get_items',    routeQueryParameters: ['page', 'limit'],    // required parameters for route path    relationId: 123,    relationClass: 'Some_Class'    // default query parameter    limit: 10});// returns api/rest/latest/relation/Some_Class/123/comment?limit=10route.getUrl();// returns api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2route.getUrl({page: 2})```


* [RouteModel](#module_RouteModel)
  * [._routeParameters](#module_RouteModel#_routeParameters) : <code>Array.&lt;string&gt;</code>
  * [.initialize()](#module_RouteModel#initialize)
  * [._updateRouteParameters()](#module_RouteModel#_updateRouteParameters)
  * [.getUrl(options)](#module_RouteModel#getUrl) ⇒ <code>string</code>

<a name="module_RouteModel#_routeParameters"></a>
### routeModel._routeParameters : <code>Array.&lt;string&gt;</code>
List of all parameters accepted by route, includes "path" and "query" parameter names

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
**Access:** protected  
<a name="module_RouteModel#initialize"></a>
### routeModel.initialize()
**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#_updateRouteParameters"></a>
### routeModel._updateRouteParameters()
Updates list of route arguments accepted by this route

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Access:** protected  
<a name="module_RouteModel#getUrl"></a>
### routeModel.getUrl(options) ⇒ <code>string</code>
Returns url defined by this model

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Returns**: <code>string</code> - route url  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> | parameters to override |

