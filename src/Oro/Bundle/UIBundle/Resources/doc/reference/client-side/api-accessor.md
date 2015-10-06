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

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize(Options)
**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| Options | <code>object</code> | passed to constructor |

