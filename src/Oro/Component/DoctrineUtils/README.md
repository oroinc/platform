Oro Doctrine Utils Component
============================

`Oro Doctrine Utils Component` provides some useful classes meant to make using Doctrine components easier.

QueryHintResolver class
-----------------------
**Description:**
The [QueryHintResolver](./ORM/QueryHintResolver.php) can be used to make [Doctrine query hints](https://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html#query-hints) more flexible. An example you can find in ORO Platform.

**Methods:**

- **addTreeWalker** - Maps a query hint to a tree walker.
- **addOutputWalker** - Maps a query hint to an output walker.
- **resolveHints** - Resolves query hints.
- **addHint** - Adds a hint to a query object.
- **addHints** - Adds hints to a query object.

SqlWalker class
---------------
**Description:**
The [SqlWalker](./ORM/SqlWalker.php) is an [Doctrine output walker](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html#modify-the-output-walker-to-generate-vendor-specific-sql) that is used to
 modify queries to be more vendor optimized.
 
 One of the class goals is to align NULLs sorting logic for MySQL and PostgreSQL. By default when ASC sorting is performed 
 NULLs are sorted first in MySQL and last in PostgreSQL. SqlWalker applies NULLS FIRST instruction for nullable columns
 when them appears in ORDER BY clause. For DESC sorting NULLS LAST instruction is applied as well.
 
 This behavior may be turned off by setting special query hint `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS`
 
```php
$query->setHint('HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS', true);
```

PreciseOrderByWalker class
--------------------------
**Description:**
The [PreciseOrderByWalker](./ORM/Walker/PreciseOrderByWalker.php) is an [Doctrine tree walker](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html) that is used to modify ORDER BY clause of a query to make sure that records will be returned in the same order independent from a state of SQL server and from values of OFFSET and LIMIT clauses. This is achieved by adding the primary key column of the first root entity to the end of ORDER BY clause.

Example of usage:

```php
$query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [PreciseOrderByWalker::class]);
```
