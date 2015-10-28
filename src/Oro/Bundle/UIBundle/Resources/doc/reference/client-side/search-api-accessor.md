<a name="module_SearchApiAccessor"></a>
## SearchApiAccessor ⇐ <code>[ApiAccessor](./api-accessor.md)</code>
Provides access to the search API for autocompletes.
This class is by design to be initiated from server configuration.

**Extends:** <code>[ApiAccessor](./api-accessor.md)</code>  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>Object</code> | Options container Also check the options for [ApiAccessor](./api-accessor.md) |
| options.search_handler_name | <code>string</code> | Name of the search handler to use |
| options.label_field_name | <code>string</code> | Name of the property that will be used as a label |
| options.value_field_name | <code>string</code> | Optional. Name of the property that will be used as an identifier.                                       By default = `'id'` |


* [SearchApiAccessor](#module_SearchApiAccessor) ⇐ <code>[ApiAccessor](./api-accessor.md)</code>
  * [.prepareUrlParameters()](#module_SearchApiAccessor#prepareUrlParameters)
  * [.formatResult(response)](#module_SearchApiAccessor#formatResult) ⇒ <code>Object</code>

<a name="module_SearchApiAccessor#prepareUrlParameters"></a>
### searchApiAccessor.prepareUrlParameters()
**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  
<a name="module_SearchApiAccessor#formatResult"></a>
### searchApiAccessor.formatResult(response) ⇒ <code>Object</code>
Formats response before it is sent out from this api accessor.
Converts it to form
``` javascipt
{
    results: [{id: '<value>', label: '<label>'}, ...],
    more: '<more>'
}
```

**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  

| Param | Type |
| --- | --- |
| response | <code>Object</code> | 

