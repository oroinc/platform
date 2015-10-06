<a name="module_SearchApiAccessor"></a>
## SearchApiAccessor
Provides access to search API for autocompletes.
This class is designed to create from server configuration.

**Augment**: [ApiAccessor](./api-accessor.md)  

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> | Options container |
| options.search_handler_name | <code>string</code> | Name of search handler to use |
| options.label_field_name | <code>string</code> | Name of the property that will be used as label |
| options.id_field_name | <code>string</code> | Optional. Name of the property that will be used as identifier.                                       By default = `'id'` |


* [SearchApiAccessor](#module_SearchApiAccessor)
  * [.prepareUrlParameters()](#module_SearchApiAccessor#prepareUrlParameters)
  * [.formatResult(response)](#module_SearchApiAccessor#formatResult) ⇒ <code>object</code>

<a name="module_SearchApiAccessor#prepareUrlParameters"></a>
### searchApiAccessor.prepareUrlParameters()
**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  
<a name="module_SearchApiAccessor#formatResult"></a>
### searchApiAccessor.formatResult(response) ⇒ <code>object</code>
Formats response before it will be sent out from this api accessor.
Converts it to form
{
    results: [{id: '<id>', label: '<label>'}, ...],
    more: '<more>'
}

**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  

| Param | Type |
| --- | --- |
| response | <code>object</code> | 

