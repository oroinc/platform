<a name="module_ApiAccessor"></a>
## ApiAccessor
Abstraction of api access point. This class is designed to create from server configuration.

**Augment**: StdClass  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> |  |
| options.route | <code>string</code> | Route name |
| options.http_method | <code>string</code> | Http method to access this route (e.g. GET/POST/PUT/PATCH...)                                     By default `'GET'`. |
| options.form_name | <code>string</code> | Wraps request body into form_name, so request will look like                            `{<form_name>:<request_body>}` |
| options.headers | <code>object</code> | Allows to provide additional http headers |
| options.default_route_parameters | <code>object</code> | Provides default parameters values for route creation,                            this defaults will be merged with row model data to get url |
| options.query_parameter_names | <code>Array.&lt;string&gt;</code> | Array of parameter names to put into query string                         (e.g. `?<parameter-name>=<value>&<parameter-name>=<value>`).                         (The reason of adding this argument is that FOSRestBundle doesn’t provides acceptable                         query parameters for client usage, so it is required to specify list of them) |


* [ApiAccessor](#module_ApiAccessor)
  * [.initialize(Options)](#module_ApiAccessor#initialize)
  * [.send(urlParameters, body, headers)](#module_ApiAccessor#send) ⇒ <code>$.Promise</code>
  * [.getHeaders(headers)](#module_ApiAccessor#getHeaders) ⇒ <code>object</code>
  * [.getUrl(urlParameters)](#module_ApiAccessor#getUrl) ⇒ <code>string</code>
  * [.formatBody(body)](#module_ApiAccessor#formatBody) ⇒ <code>object</code>

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize(Options)
**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| Options | <code>object</code> | passed to constructor |

<a name="module_ApiAccessor#send"></a>
### apiAccessor.send(urlParameters, body, headers) ⇒ <code>$.Promise</code>
Prepares request body.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  
**Returns**: <code>$.Promise</code> - - Promise with abort() support  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | <code>object</code> | Url parameters to combine url |
| body | <code>object</code> | Request body |
| headers | <code>object</code> | Headers to send with request |

<a name="module_ApiAccessor#getHeaders"></a>
### apiAccessor.getHeaders(headers) ⇒ <code>object</code>
Prepares headers for request.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| headers | <code>object</code> | Headers to merge into default list |

<a name="module_ApiAccessor#getUrl"></a>
### apiAccessor.getUrl(urlParameters) ⇒ <code>string</code>
Prepares url for request.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| urlParameters | <code>object</code> | Map of url parameters to use |

<a name="module_ApiAccessor#formatBody"></a>
### apiAccessor.formatBody(body) ⇒ <code>object</code>
Prepares request body.

**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| body | <code>object</code> | Map of url parameters to use |

