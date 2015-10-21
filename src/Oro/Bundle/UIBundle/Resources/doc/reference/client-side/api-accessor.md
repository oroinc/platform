<a name="module_ApiAccessor"></a>
## ApiAccessor ⇐ <code>[BaseClass](./base-class.md)</code>
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

**Extends:** <code>[BaseClass](./base-class.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container |
| options.route | <code>string</code> | Required. Route name |
| options.http_method | <code>string</code> | Http method to access this route (e.g. GET/POST/PUT/PATCH...)                          By default `'GET'`. |
| options.form_name | <code>string</code> | Optional. Wraps the request body into a form_name, so request will look like                          `{<form_name>:<request_body>}` |
| options.headers | <code>Object</code> | Optional. Allows to provide additional http headers |
| options.default_route_parameters | <code>Object</code> | Optional. Provides default parameters for route,                                                    this defaults will be merged the `urlParameters` to get url |
| options.route_parameters_rename_map | <code>Object</code> | Optional. Allows to rename incoming parameters, which came                                                    into send() function, to proper names.                                                    Please provide here an object with following structure:                                                    `{<old-name>: <new-name>, ...}` |
| options.query_parameter_names | <code>Array.&lt;string&gt;</code> | Optional. Array of parameter names to put into query                          string (e.g. `?<parameter-name>=<value>&<parameter-name>=<value>`).                          (The reason of adding this argument is that FOSRestBundle doesn’t provides acceptable                          query parameters for client usage, so it is required to specify list of them) |


* [ApiAccessor](#module_ApiAccessor) ⇐ <code>[BaseClass](./base-class.md)</code>
  * [.initialize(Options)](#module_ApiAccessor#initialize)
  * [.validateUrlParameters(urlParameters)](#module_ApiAccessor#validateUrlParameters) ⇒ <code>boolean</code>
  * [.send(urlParameters, body, headers, options)](#module_ApiAccessor#send) ⇒ <code>$.Promise</code>
  * [.getHeaders(headers)](#module_ApiAccessor#getHeaders) ⇒ <code>Object</code>
  * [.prepareUrlParameters(urlParameters)](#module_ApiAccessor#prepareUrlParameters) ⇒ <code>Object</code>
  * [.getUrl(urlParameters)](#module_ApiAccessor#getUrl) ⇒ <code>string</code>
  * [.formatBody(body)](#module_ApiAccessor#formatBody) ⇒ <code>Object</code>
  * [.formatResult(response)](#module_ApiAccessor#formatResult) ⇒ <code>Object</code>

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize(Options)
**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| Options | <code>Object</code> | passed to the constructor |

<a name="module_ApiAccessor#validateUrlParameters"></a>
### apiAccessor.validateUrlParameters(urlParameters) ⇒ <code>boolean</code>
Validates url parameters

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  
**Returns**: <code>boolean</code> - - true, if parameters are valid and route url could be built  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | <code>Object</code> | Url parameters to compose the url |

<a name="module_ApiAccessor#send"></a>
### apiAccessor.send(urlParameters, body, headers, options) ⇒ <code>$.Promise</code>
Sends request to the server and returns $.Promise instance with abort() support

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  
**Returns**: <code>$.Promise</code> - - $.Promise instance with abort() support  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | <code>Object</code> | Url parameters to compose the url |
| body | <code>Object</code> | Request body |
| headers | <code>Object</code> | Headers to send with the request |
| options | <code>Object</code> | Additional options |
| options.processingMessage | <code>string</code> | Shows notification message while request is going |
| options.preventWindowUnload | <code>boolean</code> &#124; <code>string</code> | Prevent window from being unloaded without user                          confirmation until request is finished.                          If true provided - page unload will be prevented with default message.                          If string provided - please describe change in it. This string will be added to                              list on changes.                          Default message will be like:                            Server is being updated and the following changes might be lost:                            {messages list, each on new line} |

<a name="module_ApiAccessor#getHeaders"></a>
### apiAccessor.getHeaders(headers) ⇒ <code>Object</code>
Prepares headers for the request.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| headers | <code>Object</code> | Headers to merge into the default list |

<a name="module_ApiAccessor#prepareUrlParameters"></a>
### apiAccessor.prepareUrlParameters(urlParameters) ⇒ <code>Object</code>
Prepares url parameters before the url build

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param |
| --- |
| urlParameters | 

<a name="module_ApiAccessor#getUrl"></a>
### apiAccessor.getUrl(urlParameters) ⇒ <code>string</code>
Prepares url for the request.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | <code>Object</code> | Map of url parameters to use |

<a name="module_ApiAccessor#formatBody"></a>
### apiAccessor.formatBody(body) ⇒ <code>Object</code>
Prepares the request body.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| body | <code>Object</code> | Map of the url parameters to use |

<a name="module_ApiAccessor#formatResult"></a>
### apiAccessor.formatResult(response) ⇒ <code>Object</code>
Formats response before it is sent out from this api accessor.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type |
| --- | --- |
| response | <code>Object</code> | 

