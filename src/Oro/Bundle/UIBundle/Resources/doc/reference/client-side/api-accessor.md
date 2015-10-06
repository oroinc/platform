<a name="module_ApiAccessor"></a>
## ApiAccessor
Abstraction of api access point. This class is designed to create from server configuration.

Options:
Name | Description
-----|------------
route | Route name
http_method | Http method to access this route. (GET|POST|PUT|PATCH...)
form_name | Wraps request body into form_name, so request will look like
            `{<form_name>:{<field_name>: <new_value>}}`
headers | Allows to provide additional http headers
default_route_parameters | provides default parameters values for route creation,
                           this defaults will be merged with row model data to get url
query_parameter_names | array of parameter names to put into query string
                        (e.g. ?<parameter-name>=<value>&<parameter-name>=<value>).
                        (The reason is that FOSRestBundle doesn’t provides them for client usage,
                        so it is required to specify list of available query parameters)

**Augment**: StdClass  

* [ApiAccessor](#module_ApiAccessor)
  * [.initialize](#module_ApiAccessor#initialize)
    * [new initialize(options)](#new_module_ApiAccessor#initialize_new)

<a name="module_ApiAccessor#initialize"></a>
### apiAccessor.initialize
**Kind**: instance class of <code>[ApiAccessor](#module_ApiAccessor)</code>  
<a name="new_module_ApiAccessor#initialize_new"></a>
#### new initialize(options)

| Param |
| --- |
| options | 

