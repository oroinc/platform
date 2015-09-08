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
