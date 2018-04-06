# OroBatchBundle

OroBatchBundle adds [AkeneoBatchBundle](https://github.com/akeneo/BatchBundle) to Oro applications and enables the batch architecture usage for data processing.

## Components

**BufferedIdentityQueryResultIterator**

Iterates results of Query.

- Allows to iterate large query results without risk of getting out of memory error.
- Allows to iterate through changing dataset
- Ensures initial query result will be constant during pagination, this allows to modify or delete rows included in dataset.

Query dataset is fixed by preloading unique row identifiers.
Iterator uses [IdentityIterationStrategyInterface](./ORM/Query/ResultIterator/IdentityIterationStrategyInterface.php) 
to modify original Query.

First it detects unique result row identifiers and stores them. Than it paginates through array of stored identifiers 
by batches and limits origin Query with current batch set of IDs.
      
By default [SelectIdentifierWalker](./ORM/Query/ResultIterator/SelectIdentifierWalker.php) sql walker is used to detect Entity Single Identity field. 
All these identifiers fetched and hydrated by [IdentifierHydrator](./ORM/Query/ResultIterator/IdentifierHydrator.php) to an array if integers in a memory.
**1,5 million** rows in a query will approximately require **80mb** of memory to store keys.

[LimitIdentifierWalker](./ORM/Query/ResultIterator/LimitIdentifierWalker.php) sql walker is used to add 'Where In (identifiers)' clause to a Query and fetching a batch of a Query result.
 
In case of OneToMany/ManyToMany joins Iterator will iterate Root Entities.
Count method will return number of Root Entities (unique IDs)
In this case actual number of rows fetched in one batch could vary depending on a number of joined rows corresponding given ID.
If Order By used on a joined field, actual joined filed order could be different while Root Entities order stay consistent.

Hydrating Query result to an Entity Object with joined ToMany relations will level above problems 

Works well for Queries with Entity with single identifier.
 
In complex cases you can implement [IdentityIterationStrategyInterface](./ORM/Query/ResultIterator/IdentityIterationStrategyInterface.php)
Interface with custom Iteration Strategy.
Custom Iteration Strategy can be set to iterator with *setIterationStrategy()* method before Query executed (iteration started). 
