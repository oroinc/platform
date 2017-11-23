## ApiAccessor ⇐ [BaseClass](./base-class.md)

<a name="module_ApiAccessor"></a>

Abstraction of api access point. This class is by design to be initiated from server configuration.

#### Sample usage of api_accessor with a full set of options provided(except `route_parameters_rename_map`).
Example of configuration provided on the server:
``` yml
save_api_accessor:
    route: orocrm_opportunity_task_update # for example this route uses following mask
                        # to generate url /api/opportunity/{opportunity_id}/tasks/{id}
    http_method: POST
    headers:
        Api-Secret: ANS2DFN33KASD4F6OEV7M8
    default_route_parameters:
        opportunity_id: 23
    action: patch
    query_parameter_names: [action]
```

Then following code on the client:
``` javascript
var apiAP = new ApiAccessror(serverConfiguration);
apiAP.send({id: 321}, {name: 'new name'}).then(function(result) {
    console.log(result)
})
```
Will raise POST request to `/api/opportunity/23/tasks/321?action=patch` with body == `{name: 'new name'}`
and will put response to console after completion

**Extends:** [BaseClass](./base-class.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container |
| options.route | `string` | Required. Route name |
| options.http_method | `string` | Http method to access this route (e.g. GET/POST/PUT/PATCH...)                          By default `'GET'`. |
| options.form_name | `string` | Optional. Wraps the request body into a form_name, so request will look like                          `{<form_name>:<request_body>}` |
| options.headers | `Object` | Optional. Allows to provide additional http headers |
| options.default_route_parameters | `Object` | Optional. Provides default parameters for route,                                                    this defaults will be merged the `urlParameters` to get url |
| options.route_parameters_rename_map | `Object` | Optional. Allows to rename incoming parameters, which came                                                    into send() function, to proper names.                                                    Please provide here an object with following structure:                                                    `{<old-name>: <new-name>, ...}` |
| options.query_parameter_names | `Array.&lt;string&gt;` | Optional. Array of parameter names to put into query                          string (e.g. `?<parameter-name>=<value>&<parameter-name>=<value>`).                          (The reason of adding this argument is that FOSRestBundle doesn’t provides acceptable                          query parameters for client usage, so it is required to specify list of them) |


* [ApiAccessor](#module_ApiAccessor) ⇐ [BaseClass](./base-class.md)
  * [.initialize(options)](#module_ApiAccessor#initialize)
  * [.isCacheAllowed()](#module_ApiAccessor#isCacheAllowed) ⇒ `boolean`
  * [.clearCache()](#module_ApiAccessor#clearCache)
  * [.validateUrlParameters(urlParameters)](#module_ApiAccessor#validateUrlParameters) ⇒ `boolean`
  * [.send(urlParameters, body, headers, options)](#module_ApiAccessor#send) ⇒ `$.Promise`
  * [._makeAjaxRequest(options)](#module_ApiAccessor#_makeAjaxRequest)
  * [.hashCode(url)](#module_ApiAccessor#hashCode) ⇒ `string`
  * [.isCacheExistsFor(urlParameters)](#module_ApiAccessor#isCacheExistsFor)
  * [.getHeaders(headers)](#module_ApiAccessor#getHeaders) ⇒ `Object`
  * [.prepareUrlParameters(urlParameters)](#module_ApiAccessor#prepareUrlParameters) ⇒ `Object`
  * [.getUrl(urlParameters)](#module_ApiAccessor#getUrl) ⇒ `string`
  * [.formatBody(body)](#module_ApiAccessor#formatBody) ⇒ `Object`
  * [.formatResult(response)](#module_ApiAccessor#formatResult) ⇒ `Object`
  * [.getErrorHandlerMessage(options)](#module_ApiAccessor#getErrorHandlerMessage) ⇒ `boolean`

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize(options)
**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | passed to the constructor |

<a name="module_ApiAccessor#isCacheAllowed"></a>
### apiAccessor.isCacheAllowed() ⇒ `boolean`
Returns true if selected HTTP_METHOD allows caching

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
<a name="module_ApiAccessor#clearCache"></a>
### apiAccessor.clearCache()
Clears response cache

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
<a name="module_ApiAccessor#validateUrlParameters"></a>
### apiAccessor.validateUrlParameters(urlParameters) ⇒ `boolean`
Validates url parameters

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
**Returns**: `boolean` - - true, if parameters are valid and route url could be built  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | `Object` | Url parameters to compose the url |

<a name="module_ApiAccessor#send"></a>
### apiAccessor.send(urlParameters, body, headers, options) ⇒ `$.Promise`
Sends request to the server and returns $.Promise instance with abort() support

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
**Returns**: `$.Promise` - - $.Promise instance with abort() support  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | `Object` | Url parameters to compose the url |
| body | `Object` | Request body |
| headers | `Object` | Headers to send with the request |
| options | `Object` | Additional options |
| options.processingMessage | `string` | Shows notification message while request is going |
| options.preventWindowUnload | `boolean` &#124; `string` | Prevent window from being unloaded without user                          confirmation until request is finished.                          If true provided - page unload will be prevented with default message.                          If string provided - please describe change in it. This string will be added to                              list on changes.                          Default message will be like:                            Server is being updated and the following changes might be lost:                            {messages list, each on new line} |

<a name="module_ApiAccessor#_makeAjaxRequest"></a>
### apiAccessor._makeAjaxRequest(options)
Makes Ajax request or returns result from cache

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
**Access:** protected  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | options to pass to ajax call |

<a name="module_ApiAccessor#hashCode"></a>
### apiAccessor.hashCode(url) ⇒ `string`
Returns hash code of url

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type |
| --- | --- |
| url | `string` | 

<a name="module_ApiAccessor#isCacheExistsFor"></a>
### apiAccessor.isCacheExistsFor(urlParameters)
Returns true if data is cached for concrete urlParameters

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  
**Access:** protected  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | `Object` | url parameters to check |

<a name="module_ApiAccessor#getHeaders"></a>
### apiAccessor.getHeaders(headers) ⇒ `Object`
Prepares headers for the request.

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type | Description |
| --- | --- | --- |
| headers | `Object` | Headers to merge into the default list |

<a name="module_ApiAccessor#prepareUrlParameters"></a>
### apiAccessor.prepareUrlParameters(urlParameters) ⇒ `Object`
Prepares url parameters before the url build

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param |
| --- |
| urlParameters | 

<a name="module_ApiAccessor#getUrl"></a>
### apiAccessor.getUrl(urlParameters) ⇒ `string`
Prepares url for the request.

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | `Object` | Map of url parameters to use |

<a name="module_ApiAccessor#formatBody"></a>
### apiAccessor.formatBody(body) ⇒ `Object`
Prepares the request body.

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type | Description |
| --- | --- | --- |
| body | `Object` | Map of the url parameters to use |

<a name="module_ApiAccessor#formatResult"></a>
### apiAccessor.formatResult(response) ⇒ `Object`
Formats response before it is sent out from this api accessor.

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param | Type |
| --- | --- |
| response | `Object` | 

<a name="module_ApiAccessor#getErrorHandlerMessage"></a>
### apiAccessor.getErrorHandlerMessage(options) ⇒ `boolean`
Returns error handler message attribute from given options

**Kind**: instance method of [ApiAccessor](#module_ApiAccessor)  

| Param |
| --- |
| options | 

