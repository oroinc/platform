# Oro Doctrine Utils Component

`Oro Doctrine Utils Component` provides some useful classes meant to make using Doctrine components easier.

## QueryHintResolver class

The [QueryHintResolver](./ORM/QueryHintResolver.php) can be used to make [Doctrine query hints](https://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html#query-hints) more flexible. An example you can find in OroPlatform.

Here are desctiption of the class methods:

- **addTreeWalker** - Maps a query hint to a tree walker.
- **addOutputWalker** - Maps a query hint to an output walker.
- **resolveHints** - Resolves query hints.
- **addHint** - Adds a hint to a query object.
- **addHints** - Adds hints to a query object.

## SqlWalker class

The [SqlWalker](./ORM/SqlWalker.php) is an [Doctrine output walker](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html#modify-the-output-walker-to-generate-vendor-specific-sql) that is used to
 modify queries to be more vendor optimized.
 
 One of the class goals is to align NULLs sorting logic for MySQL and PostgreSQL. By default when ASC sorting is performed
 NULLs are sorted first in MySQL and last in PostgreSQL. SqlWalker applies NULLS FIRST instruction for nullable columns
 when them appears in ORDER BY clause. For DESC sorting NULLS LAST instruction is applied as well.
 
 This behavior may be turned off by setting special query hint `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS`
 
```php
$query->setHint('HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS', true);
```

## PreciseOrderByWalker class

The [PreciseOrderByWalker](./ORM/Walker/PreciseOrderByWalker.php) is an [Doctrine tree walker](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html) that is used to modify ORDER BY clause of a query to make sure that records will be returned in the same order independent from a state of SQL server and from values of OFFSET and LIMIT clauses. This is achieved by adding the primary key column of the first root entity to the end of ORDER BY clause.

Example of usage:

```php
$query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [PreciseOrderByWalker::class]);
```

## TransactionWatcherInterface interface

Sometimes it is required to perform some work only after data are commited to the database. For instance, sending
notifications to users or to external systems. In this case the [TransactionWatcherInterface](./DBAL/TransactionWatcherInterface.php)
can be heplful.

To be able to register DBAL transaction watchers you need to register the
[AddTransactionWatcherCompilerPass](.DependencyInjection/AddTransactionWatcherCompilerPass.php) compiler pass
and class loader for the transaction watcher aware connection proxy in your application, for example:

```php
class AppBundle extends Bundle
{
    public function __construct(KernelInterface $kernel)
    {
        $loader = new ClassLoader(
            AddTransactionWatcherCompilerPass::CONNECTION_PROXY_NAMESPACE . '\\',
            AddTransactionWatcherCompilerPass::getConnectionProxyRootDir($kernel->getCacheDir())
        );
        $loader->register();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddTransactionWatcherCompilerPass('oro.doctrine.connection.transaction_watcher')
        );
    }
}
```

## QueryBuilderUtil class

Constructing DQL queries dynamically may make them vulnerable for injections. To be sure that data, passed ans field name
or table alias is safe `QueryBuilderUtil` contains a set of methods:
 `sprintf` - should be as query safe replacement instead of sprintf
 `checkIdentifier` - when there is a need to safely pass variable into query as part of identifier
 `getField` - is a shortcut for QueryBuilderUtil::sprintf('%s.%s', $alias, $field)
