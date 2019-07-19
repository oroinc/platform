Query builder
====================

To build search queries, you need to use Oro\Bundle\SearchBundle\Query\Query
and Oro\Bundle\SearchBundle\Query\Criteria\Criteria classes.

Example:

```
$query = new Query();
$query
    ->select('sku')
    ->from('oro_search_product');
$query->getCriteria()
    ->andWhere(Criteria::expr()->eq('text.all_data', 'Functions'))
    ->orWhere(Criteria::expr()->gt('decimal.price', 85));
```

Syntax of Query builder is close to Doctrine 2.

* **select()** - accepts a string or array of strings, that represent field names in the search index. 
The values of those fields will be returned in the *selected_data* key of the response items.
The select() parser will also accept SQL fieldname aliasing syntax, for example:

```
$query = new Query();
$query->select('fieldvalue as name');
```

**NOTE**: If you don't want to overwrite existing fields, use the *addSelect()* method.

* **from()** - takes array or string of entity aliases to search from. If argument was `*`,
then search will be performed for all entities.

* **addAggregate()** - add aggregating function for field.
    * First argument - aggregate result name. It will be present as separated `key` with aggregating value in the response.
    * Second argument - field name that will be used in aggregating function.
    * Third argument - name of aggregating function. I can be `count`, `sum`, `min`, `max` and `avg`.

The values of aggregated functions will be returned in the *aggregated_data* key of the response.

As a result of query, instance of `Oro\Bundle\SearchBundle\Query\Result` will be returned with info about search query,
result items and aggregated data.
For ORM search index will be returned instance of `Oro\Bundle\SearchBundle\Query\Result`. It is the wrapper under
`Oro\Bundle\SearchBundle\Query\Result` to prevent excess queries to search index when only result items should be fetched.

**NOTE**: To get aggregated data you need to call separated function `getAggregatedData()` of result object. It will make
additional query to search index to get aggregated data.
