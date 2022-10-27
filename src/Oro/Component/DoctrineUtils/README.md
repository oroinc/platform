# Oro Doctrine Utils Component

`Oro Doctrine Utils Component` provides some useful classes meant to make using Doctrine components easier.

## QueryHintResolver class

The [QueryHintResolver](./ORM/QueryHintResolver.php) can be used to make [Doctrine query hints](https://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html#query-hints) more flexible. 

 Descriptions of the class methods are below:

- **addTreeWalker** - Maps a query hint to a tree walker.
- **addOutputWalker** - Maps a query hint to an output walker.
- **resolveHints** - Resolves query hints.
- **addHint** - Adds a hint to a query object.
- **addHints** - Adds hints to a query object.

## SqlWalker and TranslatableSqlWalker Output SQL Walkers

[SqlWalker](./ORM/Walker/SqlWalker.php) and [TranslatableSqlWalker](./ORM/Walker/TranslatableSqlWalker.php) are [Doctrine output walkers](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html#modify-the-output-walker-to-generate-vendor-specific-sql) used to modify queries to be more vendor-optimized. TranslatableSqlWalker should be used instead of the Gedmo TranslationWalker.

Both walkers utilize [DecoratedSqlWalkerTrait](./ORM/Walker/DecoratedSqlWalkerTrait.php) which is responsible for decoration of all
SQL output walker calls with calls to [OutputAstWalkerInterface](./ORM/Walker/OutputAstWalkerInterface.php) before result building, 
and [OutputResultModifierInterface](./ORM/Walker/OutputResultModifierInterface.php) after the result is ready.
OutputAstWalkerInterface and OutputResultModifierInterface are added to the query with two hints `HINT_AST_WALKERS` and `HINT_RESULT_MODIFIERS`.
These hints are automatically filled with an array of classes during container building. 
To add your own AST walker or Output Result Modifier to the decoration, use the `oro_entity.sql_walker` DI tag.

OutputAstWalkerInterface should be used to modify AST tree, but not to generate SQL.
To change the resulting SQL, use OutputResultModifierInterface (it has access to AST but should not modify it). 

```yaml
oro_entity.sql_walker.union:
    class: Oro\Component\DoctrineUtils\ORM\Walker\UnionOutputResultModifier
    abstract: true
    tags:
        - { name: oro_entity.sql_walker }
```

Out-of-the-box, there are several Output Result modifiers registered:

### PostgreSqlOrderByNullsOutputResultModifier - NULLs Sorting
 One of the class goals is to align NULLs sorting logic for MySQL and PostgreSQL. By default when ASC sorting is performed
 NULLs are sorted first in MySQL and last in PostgreSQL. SqlWalker applies NULLS FIRST instruction for nullable columns
 when them appears in ORDER BY clause. For DESC sorting NULLS LAST instruction is applied as well.
 
 This behavior may be turned off by setting special query hint `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS`

```php
$query->setHint('HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS', true);
```
 
### MySqlUseIndexOutputResultModifier - force to [use index](https://dev.mysql.com/doc/refman/5.7/en/index-hints.html) for MySQL
 MySqlUseIndexOutputResultModifier provides functionality of specifying concrete indexes that should be used by MySQL.
 Indexes are fetched from query hint `HINT_USE_INDEX`

```php
$query->setHint('HINT_USE_INDEX', 'my_custom_idx');
```

### UnionOutputResultModifier - Union SELECT to a given query
 UnionOutputResultModifier provides union functionality. Union is added to a placeholder which is saved under `HINT_UNION_KEY`. 
 The value of UNION query is fetched from the `HINT_UNION_VALUE`
 
 
## PreciseOrderByWalker class

The [PreciseOrderByWalker](./ORM/Walker/PreciseOrderByWalker.php) is an [Doctrine tree walker](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-custom-walkers.html) that is used to modify ORDER BY clause of a query to make sure that records will be returned in the same order independent from a state of SQL server and from values of OFFSET and LIMIT clauses. This is achieved by adding the primary key column of the first root entity to the end of ORDER BY clause.

Example of usage:

```php
$query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [PreciseOrderByWalker::class]);
```

## TransactionWatcherInterface interface

Sometimes it is required to perform some work only after data are committed to the database. For instance, sending
notifications to users or to external systems. In this case the [TransactionWatcherInterface](./DBAL/TransactionWatcherInterface.php)
can be helpful.

To be able to register DBAL transaction watchers you need to register the
[AddTransactionWatcherCompilerPass](.DependencyInjection/AddTransactionWatcherCompilerPass.php) compiler pass
and class loader for the transaction watcher aware connection proxy in your application, for example:

```php
class AppBundle extends Bundle
{
    public function __construct(KernelInterface $kernel)
    {
        TransactionWatcherConfigurator::registerConnectionProxies($kernel->getCacheDir());
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

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
