## SearchApiAccessor

**SearchApiAccessor** ⇐ [ApiAccessor](./api-accessor.md)
Provides access to the search API for autocompletes.
This class is by design to be initiated from server configuration.

**Extends:** [ApiAccessor](./api-accessor.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | `Object` | Options container Also check the options for [ApiAccessor](./api-accessor.md) |
| options.search_handler_name | `string` | Name of the search handler to use |
| options.label_field_name | `string` | Name of the property that will be used as a label |
| options.value_field_name | `string` | Optional. Name of the property that will be used as an identifier.                                       By default = `'id'` |


* [SearchApiAccessor](#module_SearchApiAccessor) ⇐ [ApiAccessor](./api-accessor.md)
  * [.prepareUrlParameters()](#module_SearchApiAccessor#prepareUrlParameters)
  * [.formatResult(response)](#module_SearchApiAccessor#formatResult) ⇒ `Object`


### searchApiAccessor.prepareUrlParameters()

**Kind**: instance method of [SearchApiAccessor](#module_SearchApiAccessor)  


### searchApiAccessor.formatResult(response) ⇒ `Object`
Formats response before it is sent out from this api accessor.
Converts it to form

``` javascript
{
    results: [{id: '<value>', label: '<label>'}, ...],
    more: '<more>'
}
```

**Kind**: instance method of [SearchApiAccessor](#module_SearchApiAccessor)  

| Param | Type |
| --- | --- |
| response | `Object` | 

