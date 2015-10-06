<a name="module_ApiAccessor"></a>
## ApiAccessor
Abstraction of api access point. This class is designed to create from server configuration.

**Augment**: StdClass  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> |  |
| options.route | <code>string</code> | Route name |
| options.http_method | <code>string</code> | Http method to access this route. (GET|POST|PUT|PATCH...) |
| options.form_name | <code>string</code> | Wraps request body into form_name, so request will look like                            `{<form_name>:{<field_name>: <new_value>}}` |
| options.headers | <code>object</code> | Allows to provide additional http headers |
| options.default_route_parameters | <code>object</code> | provides default parameters values for route creation,                            this defaults will be merged with row model data to get url |
| options.query_parameter_names | <code>Array.&lt;string&gt;</code> | array of parameter names to put into query string                         (e.g. ?<parameter-name>=<value>&<parameter-name>=<value>).                         (The reason is that FOSRestBundle doesn’t provides them for client usage, \                         so it is required to specify list of available query parameters) |

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize(options)
**Kind**: instance method of <code>[ApiAccessor](#module_ApiAccessor)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> |  |
| options.route | <code>string</code> | Route name |
| options.http_method | <code>string</code> | Http method to access this route. (GET|POST|PUT|PATCH...) |
| options.form_name | <code>string</code> | Wraps request body into form_name, so request will look like                            `{<form_name>:{<field_name>: <new_value>}}` |
| options.headers | <code>object</code> | Allows to provide additional http headers |
| options.default_route_parameters | <code>object</code> | provides default parameters values for route creation,                            this defaults will be merged with row model data to get url |
| options.query_parameter_names | <code>Array.&lt;string&gt;</code> | array of parameter names to put into query string                         (e.g. ?<parameter-name>=<value>&<parameter-name>=<value>).                         (The reason is that FOSRestBundle doesn’t provides them for client usage, \                         so it is required to specify list of available query parameters) |

