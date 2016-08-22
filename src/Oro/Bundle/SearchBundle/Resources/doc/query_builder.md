Query builder
====================

To perform search queries, you need to use the query builder =>
\Oro\Bundle\SearchBundle\Query\Query.

Example:
```
$query = (new Query())
        ->select('sku')
        ->from('oro_search_product')
        ->andWhere('all_data', '=', 'Functions', 'text')
        ->orWhere('price', '>', 85, 'decimal');
```
Syntax of Query builder is close to Doctrine 2.

* **select()** - accepts a string or array of strings, that represent field names in the search index. 
The values of those fields will be returned in the *selected_data* key of the response items.
**NOTE**: If you don't want to overwrite this setting, use the *addSelect()* method instead.
* **from()** - takes array or string of entity aliases to search from. If argument was `*`,
then search will be performed for all entities.

* **andWhere()**, **orWhere()** - functions set AND WHERE and OR WHERE functions in search request.
    * First argument - field name to search from. It can be set to `*` for searching by all fields.
    * Second argument - operators `<`, `>`, `=`, `!=`, etc. If first argument is for text field, this parameter will be ignored.
    * Third argument - value to search
    * Fourth argument - field type.

* **setFirstResult()** - set the first result offset

* **setMaxResults()** - set max results of records in result.

As a result of query, `Oro\Bundle\SearchBundle\Query\Result` will be returned
with info about search query and result items.
