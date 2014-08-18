Query builder
====================

To run search queries must be used query builder.

For example:
```
$query = $this->getSearchManager()->select()
        ->from('Oro/Bundle/SearchBundle/Entity:Product')
        ->andWhere('all_data', '=', 'Functions', 'text')
        ->orWhere('price', '>', 85, 'decimal');
```
Syntax of Query builder as close to Doctrine 2.

* **from()** - takes array or string of entities to search from. If argument of function was `*`,
then search will be run for all entities.

* **andWhere()**, **orWhere()** - functions set AND WHERE and OR WHERE functions in search request.
    * First argument - field name to search from. It can be set to `*` for searching by all fields.
    * Second argument - operators `<`, `>`, `=`, `!=`, etc. If first argument is for text field, this parameter will be ignored.
    * Third argument - value to search
    * Fourth argument - field type.

* **setFirstResult()** - set the first result offset

* **setMaxResults()** - set max results of records in result.

As result of query, will be returned `Oro\Bundle\SearchBundle\Query\Result` class
with info about search query and result items.