<a name="module_LoadMoreCollection"></a>
## LoadMoreCollection

* [LoadMoreCollection](#module_LoadMoreCollection)
  * [LoadMoreCollection](#exp_module_LoadMoreCollection--LoadMoreCollection) ⏏
    * [.parse()](#module_LoadMoreCollection--LoadMoreCollection#parse)
    * [.loadMore()](#module_LoadMoreCollection--LoadMoreCollection#loadMore) ⇒ <code>$.Promise</code>
    * [._onAdd()](#module_LoadMoreCollection--LoadMoreCollection#_onAdd)
    * [._onRemove()](#module_LoadMoreCollection--LoadMoreCollection#_onRemove)

<a name="exp_module_LoadMoreCollection--LoadMoreCollection"></a>
### LoadMoreCollection ⏏
Collection with "load more" functionality support. Any add/remove actions will be considered like already doneon the server and collection will update `state.totalItemsQuantity` and `route.limit`Requires API route which accepts `limit` query parameter

**Kind**: Exported member  
<a name="module_LoadMoreCollection--LoadMoreCollection#parse"></a>
#### loadMoreCollection.parse()
**Kind**: instance method of <code>[LoadMoreCollection](#exp_module_LoadMoreCollection--LoadMoreCollection)</code>  
<a name="module_LoadMoreCollection--LoadMoreCollection#loadMore"></a>
#### loadMoreCollection.loadMore() ⇒ <code>$.Promise</code>
Loads additional state.loadMoreItemsQuantity items to this collection

**Kind**: instance method of <code>[LoadMoreCollection](#exp_module_LoadMoreCollection--LoadMoreCollection)</code>  
**Returns**: <code>$.Promise</code> - promise  
<a name="module_LoadMoreCollection--LoadMoreCollection#_onAdd"></a>
#### loadMoreCollection._onAdd()
**Kind**: instance method of <code>[LoadMoreCollection](#exp_module_LoadMoreCollection--LoadMoreCollection)</code>  
<a name="module_LoadMoreCollection--LoadMoreCollection#_onRemove"></a>
#### loadMoreCollection._onRemove()
**Kind**: instance method of <code>[LoadMoreCollection](#exp_module_LoadMoreCollection--LoadMoreCollection)</code>  
