ORM datasource
===============

# Table of contents

- [Overview](#overview)
- [Important notes](#important-notes)
- [How to](#how-to)
    - [Modify a query configuration from PHP code](#modify-a-query-configuration-from-php-code)
    - [Add Query hints](#add-query-hints)

# Overview

This datasource provides an adapter to allow accessing data from doctrine ORM using doctrine query builder.
You can configure query using `query` param under source tree. This query will be converted via [YamlConverter](../../../../Datasource/Orm/QueryConverter/YamlConverter.php) to doctrine `QueryBuilder` object.

## Example

```yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - email.id
                    - email.subject
                from:
                    - { table: Oro\Bundle\EmailBundle\Entity\Email, alias: email }
```

# Important notes

By default all datagrids that use ORM datasource are marked by the [HINT_PRECISE_ORDER_BY](../../../../../../Component/DoctrineUtils/README.md#preciseorderbywalker-class) query hint. This guarantees that rows are sorted the same way independently of the state of SQL server and the values of OFFSET and LIMIT clauses. More details are available in [PostgreSQL documentation](https://www.postgresql.org/docs/8.1/static/queries-limit.html).

If you need to disable this behaviour for your datagrid the following configuration can be used:

```yaml

datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                ...
            hints:
                - { name: HINT_PRECISE_ORDER_BY, value: false }
```

# How to

## Modify a query configuration from PHP code

Sometimes it is required to modify a query configuration from PHP code, for example from datagrid [extensions](../extensions.md) or [listeners](../datagrid.md#extendability). This can be done using [OrmQueryConfiguration](../../../../Datasource/Orm/OrmQueryConfiguration.php) class. To get an instance of this class use `getOrmQuery` method of [DatagridConfiguration](../../../../Datagrid/Common/DatagridConfiguration.php). For example:

```php
$query = $config->getOrmQuery();
$rootAlias = $query->getRootAlias();
$query->addSelect($rootAlias . '.myField');
```

In additional to a query modification methods, the [OrmQueryConfiguration](../../../../Datasource/Orm/OrmQueryConfiguration.php) contains several useful methods like:

- `getRootAlias()` - Returns the FIRST root alias of the query.
- `getRootEntity($entityClassResolver = null, $lookAtExtendedEntityClassName = false)` - Returns the FIRST root entity of the query.
- `findRootAlias($entityClass, $entityClassResolver = null)` - Tries to find the root alias for the given entity.
- `getJoinAlias($join, $conditionType = null, $condition = null)` - Returns an alias for the given join. If the query does not contain the specified join, its alias will be generated automatically. This might be helpful if you need to get an alias to extended association that will be joined later.
- `convertAssociationJoinToSubquery($joinAlias, $columnAlias, $joinEntityClass)` - Converts an association based join to a subquery. This can be helpful in case of performance issues with a datagrid.
- `convertEntityJoinToSubquery($joinAlias, $columnAlias)` - Converts an entity based join to a subquery. This can be helpful in case of performance issues with a datagrid.

Example of `convertAssociationJoinToSubquery` usage in a datagrid listener:

```
public function onPreBuild(PreBuild $event)
{
    $config = $event->getConfig();
    $parameters = $event->getParameters();

    $filters = $parameters->get(OrmFilterExtension::FILTER_ROOT_PARAM, []);
    $sorters = $parameters->get(OrmSorterExtension::SORTERS_ROOT_PARAM, []);
    if (empty($filters['channelName']) && empty($sorters['channelName'])) {
        $config->getOrmQuery()->convertAssociationJoinToSubquery(
            'g',
            'groupName',
            'Acme\Bundle\AppBundle\Entity\UserGroup'
        );
    }
}
```

The original query:

```yaml
query:
    select:
        - g.name as groupName
    from:
        - { table: Acme\Bundle\AppBundle\Entity\User, alias: u }
    join:
        left:
            - { join: u.group, alias: g }
```

The converted query:

```yaml
query:
    select:
        - (SELECT g.name FROM Acme\Bundle\AppBundle\Entity\UserGroup AS g WHERE g = u.group) as groupName
    from:
        - { table: Acme\Bundle\AppBundle\Entity\User, alias: u }
```

Please investigate this class to find out all other features.

### Add Query hints

The following example shows how [query hints](https://doctrine-orm.readthedocs.org/en/latest/reference/dql-doctrine-query-language.html#query-hints) can be set:

``` yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - partial g.{id, label}
                from:
                    - { table: OroContactBundle:Group, alias: g }
            hints:
                - HINT_FORCE_PARTIAL_LOAD
```

If you need to set hint's value you can use the following syntax:

``` yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: orm
            query:
                select:
                    - c
                from:
                    - { table: %oro_contact.entity.class%, alias: c }
                join:
                    left:
                        - { join: c.addresses, alias: address, conditionType: WITH, condition: 'address.primary = true' }
                        - { join: address.country, alias: country }
                        - { join: address.region, alias: region }
            hints:
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: %oro_translation.translation_walker.class% }
```

Please pay attention that ORM datasource uses [Query Hint Resolver](./../../../../../EntityBundle/Resources/doc/query_hint_resolver.md) service to handle hints. If you create own query walker and wish to use it in a grid, just register it in the Query Hint Resolver. For example the hint `HINT_TRANSLATABLE` is registered as an alias for the translation walker and as result the following configurations are equal:

``` yaml
            hints:
                - { name: HINT_CUSTOM_OUTPUT_WALKER, value: %oro_translation.translation_walker.class% }

            hints:
                - HINT_TRANSLATABLE
```
