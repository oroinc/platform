<a name="module_RouteModel"></a>
## RouteModel
Abstraction of routeBasic usage:```javascriptvar route = new RouteModel({    // route specification    routeName: 'oro_api_comment_get_items',    routeQueryParameterNames: ['page', 'limit'],    // required parameters for route path    relationId: 123,    relationClass: 'Some_Class'    // default query parameter    limit: 10});// returns api/rest/latest/relation/Some_Class/123/comment?limit=10route.getUrl();// returns api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2route.getUrl({page: 2})```


* [RouteModel](#module_RouteModel)
  * [.routeName](#module_RouteModel#routeName) : <code>string</code>
  * [.routeQueryParameterNames](#module_RouteModel#routeQueryParameterNames) : <code>Array.&lt;string&gt;</code>
  * [.getAcceptableParameters()](#module_RouteModel#getAcceptableParameters) ⇒ <code>Array.&lt;string&gt;</code>
  * [.getUrl([options])](#module_RouteModel#getUrl) ⇒ <code>string</code>

<a name="module_RouteModel#routeName"></a>
### routeModel.routeName : <code>string</code>
Name of the route

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#routeQueryParameterNames"></a>
### routeModel.routeQueryParameterNames : <code>Array.&lt;string&gt;</code>
List of acceptable query parameter names for this route

**Kind**: instance property of <code>[RouteModel](#module_RouteModel)</code>  
<a name="module_RouteModel#getAcceptableParameters"></a>
### routeModel.getAcceptableParameters() ⇒ <code>Array.&lt;string&gt;</code>
Return list of parameter names accepted by this route

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Access:** protected  
<a name="module_RouteModel#getUrl"></a>
### routeModel.getUrl([options]) ⇒ <code>string</code>
Returns url defined by this model

**Kind**: instance method of <code>[RouteModel](#module_RouteModel)</code>  
**Returns**: <code>string</code> - route url  

| Param | Type | Description |
| --- | --- | --- |
| [options] | <code>Object</code> | parameters to override |

