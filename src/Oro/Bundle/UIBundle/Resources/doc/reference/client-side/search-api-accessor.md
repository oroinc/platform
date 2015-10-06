<a name="module_SearchApiAccessor"></a>
## SearchApiAccessor
Provides access to search API for autocompletes.
This class is designed to create from server configuration.

**Augment**: [ApiAccessor](../api-accessor.md)  

* [SearchApiAccessor](#module_SearchApiAccessor)
  * [.initialize](#module_SearchApiAccessor#initialize)
    * [new initialize(options)](#new_module_SearchApiAccessor#initialize_new)
  * [.prepareUrlParameters(urlParameters)](#module_SearchApiAccessor#prepareUrlParameters) ⇒ <code>object</code>
  * [.getUrl()](#module_SearchApiAccessor#getUrl)
  * [.send()](#module_SearchApiAccessor#send)
  * [.formatResult(response)](#module_SearchApiAccessor#formatResult) ⇒ <code>object</code>

<a name="module_SearchApiAccessor#initialize"></a>
### searchApiAccessor.initialize
**Kind**: instance class of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  
<a name="new_module_SearchApiAccessor#initialize_new"></a>
#### new initialize(options)

| Param | Type | Description |
| --- | --- | --- |
| options | <code>object</code> |  |
| options.search_handler_name | <code>string</code> | NAme of search handler to use |
| options.label_field_name | <code>string</code> | Name of the property that will be used as label |
| options.id_field_name | <code>string</code> | Optional. Name of the property that will be used as identifier.                                       By default = 'id' |

<a name="module_SearchApiAccessor#prepareUrlParameters"></a>
### searchApiAccessor.prepareUrlParameters(urlParameters) ⇒ <code>object</code>
Prepares url parameters before build url

**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  

| Param |
| --- |
| urlParameters | 

<a name="module_SearchApiAccessor#getUrl"></a>
### searchApiAccessor.getUrl()
**Kind**: instance method of <code>[SearchApiAccessor](#module_SearchApiAccessor)</code>  
<a name="module_SearchApiAccessor#send"></a>
### searchApiAccessor.send()
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

